<?php
/**
 * 运费计算通用类
 * Created by notepad++.
 * User: wpy
 * Date: 2017/9/118
 * Time: 10:28
 */
namespace app\common\service;

use think\Db;

class Freight
{
	//获得运费信息
	//@param    $fid    商品对应的运费模板id
	//@param	$rid	地区id（二级地区）
    static function goods_freight($fid,$rid){
		$res = Db::name('freight_template')->where('id',$fid)->find();
		$tp = json_decode(unserialize($res['data']),true);

		if(isset($tp) && !empty($tp)){
			//依次判断运费模板三个类型的运费信息中，是否有当前区域。优先级为 special>general>default
			//sepcial 包邮模板
			$special = array();
			if(isset($tp['special']) && !empty($tp['special'])){
				foreach($tp['special'] as $k=>$v){
					
					if(in_array($rid,explode(',',$v['region']))){
						if($v['type'] == 'money'){
							$msg = '满'.$v['range'].'元包邮';
						}elseif($v['type'] == 'weight'){
							$msg = '在'.$v['range'].'kg内包邮';
						}
						$special = array('msg'=>$msg,'type'=>$v['type'],'range'=>$v['range']);
						break;
					}
				}
			}
			//判断计费方式（单位）
			switch ($res['charge_type']){
				case 1:
					$unit = '件';
					break;
				case 2:
					$unit = 'kg';
					break;
				case 3:
					$unit = '立方';
					break;	
			}
			//普通运费模板
			if(isset($tp['general']) && !empty($tp['general'])){
				foreach($tp['general'] as $k=>$v){
					
					if(in_array($rid,explode(',',$v['region']))){
						if($v['info']['begin_price']==0 && $v['info']['inc_price']==0){
							$msg = '包邮';
							$back = array('status'=>1,'msg'=>$msg,'general'=>'free');
						}else{
							$msg = '超'.$v['info']['begin'].$unit.'收'.$v['info']['begin_price'].'元运费，每超'.$v['info']['inc'].$unit.'加'.$v['info']['inc_price'].'元';
							if(!empty($special)){
								$msg .=','.$special['msg'];
							}
							$general = array('type'=>$res['charge_type'],'info'=>$v['info']);
							$back = array('status'=>1,'msg'=>$msg,'general'=>$general,'special'=>$special);
						}
						
						return $back;
					}
				}
			}
			//默认运费模板
			if(isset($tp['default']) && !empty($tp['default'])){
				$df = $tp['default'];
				if($df['begin_price']==0 && $df['inc_price']==0){
					$msg = '包邮';
					$back = array('status'=>1,'msg'=>$msg,'general'=>'free');
				}else{
					$msg = '超'.$df['begin'].$unit.'收'.$df['begin_price'].'元运费，每超'.$df['inc'].$unit.'加'.$df['inc_price'].'元';
					if(!empty($special)){
						$msg .=','.$special['msg'];
					}
					$general = array('type'=>$res['charge_type'],'info'=>$df);
					$back = array('status'=>1,'msg'=>$msg,'general'=>$general,'special'=>$special);
					
				}
				return $back;
			}
			
		}
		return array('status'=>0,'msg'=>'无效的运费模板');
		
	}
	//计算订单运费
	//@param $rid 收货人地址对应的二级区域id
	//@param $arr 订单中所有商品的信息列表，如：
	/* array(
		0 => array(
					'id'		=>//商品id,暂时没有用，作为保留字段
					'fid'		=>//商品运费模板id
					'num'		=>//商品数量
					'weight'	=>//商品重量
					'volume'	=>//商品体积
					'price'		=>//商品价格
					),
		1 => array()
		) 
	*/
	
	//目前计费方式为，使用同一运费模板的商品，共同参与运算。（如：满100包邮，使用这一模板的所有商品金额相加满100包邮）
	static function order_freight($rid,$arr){
		//把商品按运费模板分组
		foreach($arr as $k=>$v){
			$tp_arr[$v['fid']][] = $v;
		}
		//print_r($tp_arr);
		//依次计算各个模板组的运费
		$freight_arr = array();
		foreach($tp_arr as $k=>$v){
			//获取模板的计费信息
			$info = self::goods_freight($k,$rid);
			if($info['status'] == 1){
				if(is_string($info['general']) && $info['general'] == 'free'){
					$freight_arr[$k] = 0;
				}else{
					$general = $info['general'];
					$special = $info['special'];
					//计算出分组中商品所用相关计费属性的值
					$num=$weight=$volume=$price=0;
					foreach($v as $k2=>$v2){
						$num += $v2['num'];
						$weight += $v2['num']*$v2['weight'];
						$volume += $v2['num']*$v2['volume'];
						$price +=$v2['num']*$v2['price'];
					}
					//判断包邮模板是否存在，是否满足
					if(!empty($special)){
						if($special['type'] == 'price'){
							if($price >= $special['range']){
								$freight_arr[$k] = 0;
								continue;
							}
						}elseif($special['type'] == 'weight'){
							if($weight < $special['range']){
								$freight_arr[$k] = 0;
								continue;
							}
						}
					}
					//计算常规模式运费
					$base_num = 0;
					switch ($general['type']){
						//按件数
						case 1:
							$base_num = $num;
							break;
						//按重量	
						case 2:
							$base_num = $weight;
							break;
						//按体积	
						case 3:
							$base_num = $volume;
							break;	
					}
					if($base_num >= $general['info']['begin']){
						$begin_price = $general['info']['begin_price'];//首重运费
						$inc_price = ceil(($base_num-$general['info']['begin'])/$general['info']['inc'])*$general['info']['inc_price'];//续重运费
						$freight_arr[$k] = $begin_price+$inc_price;

					}else{
						$freight_arr[$k] = 0;
					}
				}
			}else{
				$freight_arr[$k] = $info['msg'];
			}
		}
		return array_sum($freight_arr);
	}
}






















