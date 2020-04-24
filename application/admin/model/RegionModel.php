<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class RegionModel extends Model
{
     protected $name = 'region';  
     public function treeList(){
     	$list=$this->field('id,name,level')->where(array('parent_id'=>0))->order('sort asc,id asc')->select();
     	foreach ($list as $k=>$v){
     		if($this->where(array('parent_id'=>$v->id))->count()){
     			$list[$k]['child']=$this->field('id,name,level')->where(array('parent_id'=>$v->id))->order('sort asc')->select();
     			$list[$k]['count']=$this->field('id,name,level')->where(array('parent_id'=>$v->id))->order('sort asc')->count();
     		}else{
     			$list[$k]['child']='';
     		}
     	}
     	return $list;
     }
     /**
      *通过id获取地区 
      */
     public function getOneRegion($id){
     	 return $this->find($id);
     }
     /**
      * 通过条件获取地区条数
      * @param $pid
      * @param $name
      */
     public function getReionCount($pid,$name){
     	return $this->where(array('parent_id'=>$pid,'name'=>$name))->count();
     }
     /**
      * 获取地区
      */
     public function getArea($id){
     	$row=$this->find($id);
     	$name='';
     	if($row){
     		$name=$row['name'];
     		$cname=$this->getArea($row['parent_id']);
     		if($cname){
     			$cname=$cname.'->';
     		}
     		$name=$cname.$name;
     		return $name;
     	}else{
     		return $name;
     	}
     }
     /**
      * 获取子地区列表
      */
     public function getsubList($id){
     	 return $this->field('id,name,level,parent_id,"" as child')->where(array('parent_id'=>$id))->select();
     }
}