<?php
// +----------------------------------------------------------------------
// | 各种收益分成计算类
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
// | date:	2019.7. 29
// +----------------------------------------------------------------------
// | Author: wpy
// +----------------------------------------------------------------------
namespace app\common\service;

use think\Db;
use think\Exception;

class Income {
/*
收益type类型
1：推荐代理奖励，
2：购买代营商品收益，
3：代理用户下级购买代营商品时，用户获取的收益
4：代理用户下下级购买代营商品时，用户获取的收益
5：体验中心用户，无限级下级成为代理时，用户获取的收益
6：代理用户，下级体验中心用户无限级下级成为代理时，用户获取的收益
7:代理用户上级购买代理商品时，获取的收益
*/

    /*本人购买收益*/
    //购买代营商品收益，(该项收益在定时任务时执行，其他收益在支付回调成功时执行)
    public static function buy_income(){
		$fp = fopen(APP_PATH.'../public_html/file_lock.txt', "r");
		// 加锁
        if(flock($fp, LOCK_EX)){
			
			//获取结算时间在当前时间内的 未结算代营订单
			$now_time = strtotime(date('Y-m-d H:').'30');
			//每次取100条，避免内存溢出
			$order = Db::name('order')->field('order_id,user_id,total_amount,order_sn,dy_income')->where(['order_group'=>2,'pay_status'=>1,'is_settle'=>0])->where('settle_time','<=',$now_time)->where('settle_time','>',0)->limit(500)->select();
			
			if($order){
				$order_id_arr = [];
				foreach($order as $v){
					$order_id_arr[] = $v['order_id'];
				}
				//把所有未结算订单改为已结算
				Db::name('order')->where('order_id','in',$order_id_arr)->update(['is_settle'=>1]);
				// 启动事务
				Db::startTrans();
				try {
					foreach($order as $v){
					   $log_data = [
						'order_sn'=>$v['order_sn'],
						'principal'=>$v['total_amount'],
						'create_time'=>time(),
						'income_uid'=>$v['user_id']
						];
					   $res = self::update_user_money($v['user_id'],['income'=>$v['dy_income']],2,$log_data);
					   if($res !== true){
							throw new \Exception('收益分发异常');
					   }
					}
					// 提交事务
					Db::commit();
					echo 'success~';
				} catch (\Exception $e) {
					// 回滚事务
					Db::rollback();
					Db::name('order')->where('order_id','in',$order_id_arr)->update(['is_settle'=>0]);
					echo $e->getMessage();
				}
			}else{
				echo 'no order~';
			}
			//执行完成解锁
            flock($fp,LOCK_UN);
		}else{
			echo 'LOCK_ing';
		}
        //关闭文件
        fclose($fp);

    }

    /*推荐人员收益*/

    public static function recommend_income($order_sn){
        $config = cache('db_config_data');
        $order = Db::name('order')->where('order_sn',$order_sn)->find();
        $log_data = ['order_sn'=>$order['order_sn'],'create_time'=>time(),'money'=>$order['total_amount'],'income_uid'=>$order['user_id']];
        $this_user = Db::name('member')->where('id',$order['user_id'])->find();//当前用户
        Db::startTrans();
        try {
            if($order['order_group'] == 1){
                //给直系上级分成
                self::update_user_money($this_user['ruid'],$config['recommend_income'],1,$log_data);
                //给间推人分成
                $s_user = Db::name('member')->where('id',$this_user['ruid'])->find();//上级用户
                if($s_user['ruid'] > 0){
                    $ss_user = Db::name('member')->where('id',$s_user['ruid'])->find();//上上级用户
                    if($ss_user['dl_time'] >0 || $ss_user['is_center']==1) {
                        self::update_user_money($s_user['ruid'], $config['t_recommend_income'], 16, $log_data);
                    }
                }
                //平级分润
                $same_user = Db::name('member')->where(['ruid'=>$this_user['ruid'],'closed'=>0,'id'=>['<>',$this_user['id']]])->where(function($query){
                    $query->where('dl_time','>',0)->whereor('is_center', 1);
                })->select();
                if($same_user){
                    $total_income = $order['total_amount']*$config['dl_same_income']/100;
                    $one_income = round($total_income/count($same_user),2);
                    foreach($same_user as $v){
                        self::update_user_money($v['id'],['income'=>$one_income],17,$log_data);
                    }
                }
                //获取用户有上级体验中心
                if($this_user['pcen_uid'] > 0){
                    $pcen_uid = $this_user['pcen_uid'];
                }else{
                    $pcen_uid = self::get_pcen_uid($this_user['id']);
                    if($pcen_uid===false){
                        throw new \Exception('上级推荐人关系异常');
                    }
                }
                if($pcen_uid > 0){
                    //给体验中心分成
                    self::update_user_money($pcen_uid,$config['center_income'],5,$log_data);
                    //查询体验中心是否有上级收益人，有且是个人的话再给体验中心上级分成
                    $center_user = Db::name('member')->where('id',$pcen_uid)->find();//体验中心用户
                    if($center_user['income_uid'] >0){
                        $s_user = Db::name('member')->where('id',$center_user['income_uid'])->find();//体验中心上级受益用户
                        //受益用户不是体验中心的话，才有分成
                        if($s_user['is_center']==0){
                            self::update_user_money($s_user['id'],$config['person_center_income'],6,$log_data);
                        }
                    }
                }
            }elseif($order['order_group'] == 2){
                //用户必须是代理用户或者体验中心才能获得提成
                //上级用户提成
                $s_user = Db::name('member')->where('id',$this_user['ruid'])->find();//上级用户
                if($s_user['dl_time'] >0 || $s_user['is_center']==1){
                    self::update_user_money($this_user['ruid'],['income'=>$config['buy_income'],'percent'=>$config['s_buy_income']],3,$log_data);
                }
                //上上级用户提成
                if($s_user['ruid'] > 0){
                    $ss_user = Db::name('member')->where('id',$s_user['ruid'])->find();//上上级用户
                    if($ss_user['dl_time'] >0 || $ss_user['is_center']==1) {
                        self::update_user_money($s_user['ruid'], ['income' => $config['buy_income'], 'percent' => $config['ss_buy_income']], 4, $log_data);
                    }
                }
                //下级用户提成
                //$son_user = Db::name('member')->where(['ruid'=>$order['user_id'],'closed'=>0])->whereOr('dl_time','>',0)->whereOr('is_center',1)->select();
                $son_user = Db::name('member')->where(['ruid'=>$order['user_id'],'closed'=>0])->where(function($query){
                    $query->where('dl_time','>',0)->whereor('is_center', 1);
                })->select();
                if($son_user){
                    $total_income = $order['total_amount']*$config['buy_income']/100*$config['x_buy_income']/100;
                    $one_income = round($total_income/count($son_user),2);
                    foreach($son_user as $v){
                        self::update_user_money($v['id'],['income'=>$one_income],7,$log_data);
                    }
                }
            }elseif($order['order_group']==3){
                //上级用户提成
                $s_user = Db::name('member')->where('id',$this_user['ruid'])->find();//上级用户
                if($s_user['dl_time'] >0 || $s_user['is_center']==1){
                    self::update_user_money($this_user['ruid'],$config['zy_s_income'],20,$log_data);
                }
                //上上级用户提成
                if($s_user['ruid'] > 0){
                    $ss_user = Db::name('member')->where('id',$s_user['ruid'])->find();//上上级用户
                    if($ss_user['dl_time'] >0 || $ss_user['is_center']==1) {
                        self::update_user_money($this_user['ruid'],$config['zy_ss_income'],21,$log_data);
                    }
                }
                //平级分润
                $same_user = Db::name('member')->where(['ruid'=>$this_user['ruid'],'closed'=>0,'id'=>['<>',$this_user['id']]])->where(function($query){
                    $query->where('dl_time','>',0)->whereor('is_center', 1);
                })->select();
                if($same_user){
                    $total_income = $order['total_amount']*$config['zy_same_income']/100;
                    $one_income = round($total_income/count($same_user),2);
                    foreach($same_user as $v){
                        self::update_user_money($v['id'],['income'=>$one_income],22,$log_data);
                    }
                }
                //获取用户有上级体验中心
                if($this_user['pcen_uid'] > 0){
                    $pcen_uid = $this_user['pcen_uid'];
                }else{
                    $pcen_uid = self::get_pcen_uid($this_user['id']);
                    if($pcen_uid===false){
                        throw new \Exception('上级推荐人关系异常');
                    }
                }
                if($pcen_uid > 0){
                    //给体验中心分成
                    self::update_user_money($pcen_uid,$config['zy_center_income'],18,$log_data);
                    //查询体验中心是否有上级收益人，有且是个人的话再给体验中心上级分成
                    $center_user = Db::name('member')->where('id',$pcen_uid)->find();//体验中心用户
                    if($center_user['income_uid'] >0){
                        $s_user = Db::name('member')->where('id',$center_user['income_uid'])->find();//体验中心上级受益用户
                        //受益用户不是体验中心的话，才有分成
                        if($s_user['is_center']==0){
                            self::update_user_money($s_user['id'],$config['zy_person_center_income'],19,$log_data);
                        }
                    }
                }

            }
            // 提交事务
            Db::commit();
            return ['status'=>1,'msg'=>''];
        }catch(\Exception $e){
            // 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>'分成操作异常'.$e->getMessage()];
        }

    }
    private static function update_user_money($uid,$percent,$type,$log_data){
        $log_data['uid'] = $uid;
        

        //看看当前的人员动态收益是否已经大于静态提现的两倍，高了就不计入结算
      //根据用户id获取当前用户的静态可提现，动态可提现，以及以往已经提现成功的
    $yusuan=DB::name('order')->where(['user_id' => $uid, 'pay_status' => 1])->where('pay_code','=',1)->where('pay_time','<',1586238961)->sum('total_amount');

      $jingti=DB::name('pay_log')->where(['uid' => $uid, 'status' => 1])->where('pay_type','>',1)->sum('money');
      $dongti=DB::name('log_income')->where(['uid' => $uid])->where('type','in',[1,3,4,5,6,7,16,17,18,19,20,21,22])->where('money','>',0)->sum('money');
      //动态收益小于2倍的静态收益，还可以继续计算奖金
      $jintai=$yusuan+$jingti;
      if($dongti > 2*$jintai){
        //不再计算
        return true;
      }



        if(is_array($percent)){
            if(isset($percent['percent'])){
                $log_data['money'] = $log_data['money']*$percent['income']/100*$percent['percent']/100;
            }else{
                $log_data['money'] = $percent['income'];
            }
        }else{
            $log_data['money'] = $log_data['money']*$percent/100;
        }
        $log_data['type'] = $type;
        Db::name('log_income')->insert($log_data);
        Db::name('member')->where('id',$uid)->setInc('money',$log_data['money']);
        //更新用户产生的代理收益
        if(in_array($type,[1,5,6,16,17])){
            Db::name('member')->where('id',$log_data['income_uid'])->setInc('create_dl_income',$log_data['money']);
        }
        //更新用户产生的代营收益
        if(in_array($type,[2,3,4,7])){
            Db::name('member')->where('id',$log_data['income_uid'])->setInc('create_dy_income',$log_data['money']);
        }
        return true;
    }
    //获取用户上级体验中心
    private static function get_pcen_uid($uid,$parent_arr=[]){
        if(in_array($uid,$parent_arr))  return false;
        array_push($parent_arr,$uid);
        $user = Db::name('member')->where('id',$uid)->find();
        if($user['ruid'] == 0){
            return 0;
        }
        $p_user = Db::name('member')->where('id',$user['ruid'])->find();
        if($p_user['is_center']){
            return $p_user['id'];
        }else{
            return self::get_pcen_uid($p_user['id'],$parent_arr);
        }
    }

}
