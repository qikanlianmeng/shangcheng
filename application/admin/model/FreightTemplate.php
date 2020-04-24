<?php

namespace app\admin\model;
use think\Model;
use think\Db;
use think\Cache;

class FreightTemplate extends Model
{
    
    //自定义初始化
    protected function initialize()
    {
        //需要调用Model的initialize方法
        parent::initialize();
        //TODO:自定义的初始化
    }
	//运费模板列表
	function get_list(){
		$list = Db::name("freight_template")->select();
		foreach($list as $k=>$v){
			switch($v['charge_type']){
				case 1:
					$list[$k]['type_name'] = '按件数';
					break;
				case 2:
					$list[$k]['type_name'] = '按重量';
					break;
				case 3:
					$list[$k]['type_name'] = '按体积';
					break;
			}
		}
		return $list;
	}
	//获得重组后的区域数组
	function get_region(){
		//cache('region_arr',NULL);
		$cache = cache('region_arr');
		if(isset($cache) && $cache){
			$data = $cache;
		}else{
			$res = Db::name("region")
					->alias("r")->field("r.id,r.name,r.parent_id,r2.name as province")
					->join("think_region r2","r.parent_id=r2.id","LEFT")
					->where("r.level","2")
					->select(); 

			$list = array();
			foreach($res as $k=>$v){
				if(isset($list[$v['parent_id']])){
					$list[$v['parent_id']]['city'][$v['id']] = $v['name'];
				}else{
					$list[$v['parent_id']]['name'] =$v['province'];
					$list[$v['parent_id']]['city'][$v['id']] = $v['name'];
				}
			}
			//把省份对应的地区数组，整理为一个地区字符串
			$provice = array();
			foreach($list as $k=>$v){
				$key_arr = array_keys($v['city']);
				$key_str = implode(',',$key_arr);
				$provice[$k]['name'] = $v['name'];
				$provice[$k]['city'] = $key_str;
			}
			//地区对应的省份id
			$region = array(
							'华东' => array(310000,320000,330000,340000,360000),
							'华北' => array(110000,120000,140000,370000,130000,150000),
							'华中' => array(430000,420000,410000),
							'华南' => array(440000,450000,350000,460000),
							'东北' => array(210000,220000,230000),
							'西北' => array(610000,620000,630000,640000,650000),
							'西南' => array(500000,510000,520000,530000,540000),
							'港澳台' => array(710000,810000,820000)
							);
			$arr = array();
			foreach($list as $k=>$v){
				foreach($region as $k2=>$v2){
					if(in_array($k,$v2)){
						$arr[$k2][$k] = $v;
						break;
					}
				}
			}
			$data = array('region'=>$arr,'provice'=>$provice);
			cache('region_arr',$data,3600);
		}
		return $data;
	}
	//读取数据库模板信息，重构用于显示在编辑页的数据
	function get_info($id){
		$res = Db::name("freight_template")->where("id",$id)->find();
		$res['data'] =  unserialize($res['data']);
		$res['data_arr'] =  json_decode($res['data'],true);
		//获取每个分组，地区所属的省份
		$region_info = $this->get_region();
		$provice = $region_info['provice'];//二级地区按省份分组的 数组
		foreach($provice as $k=>$v){
			$provice[$k]['city_arr'] = explode(',',$v['city']);
		}
		
		if(isset($res['data_arr']['general'])){
			foreach($res['data_arr']['general'] as $k=>$v){
				$region_arr = explode(',',$v['region']);
				$provice_str = '';
				foreach($provice as $k2=>$v2){
					if(!empty(array_intersect($v2['city_arr'],$region_arr)) && stripos($v2['name'],$provice_str)>=0){
						$provice_str .= $provice_str==''?$v2['name']:','.$v2['name'];
					}
				}
				$res['data_arr']['general'][$k]['provice'] = $provice_str;
			}
		}
		if(isset($res['data_arr']['special'])){
			foreach($res['data_arr']['special'] as $k=>$v){
				$region_arr = explode(',',$v['region']);
				$provice_str = '';
				foreach($provice as $k2=>$v2){
					if(!empty(array_intersect($v2['city_arr'],$region_arr)) && stripos($v2['name'],$provice_str)>=0){
						$provice_str .= $provice_str==''?$v2['name']:','.$v2['name'];
					}
				}
				$res['data_arr']['special'][$k]['provice'] = $provice_str;
			}
		}
		//print_r($res);
		return $res;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}