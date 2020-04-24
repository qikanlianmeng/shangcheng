<?php

namespace app\api2\controller;
use think\Db;

/**
 * swagger: 抽奖活动
 */
class Prize extends Base
{
	public function lists(){
	    $list = Db::name('prize')->where(['start_time'=>['<',time()],'end_time'=>['>',time()]])->select();
	    return json(['status'=>1,'list'=>$list]);
    }
    //获取一个抽奖活动信息
    public function info($id){
        $info = Db::name('prize')->where('id',$id)->find();
        $list = Db::name('prize_goods')->where('pid',$id)->order('order asc')->field('name,num,left_num')->select();
        if($list){
            array_push($list,['name'=>'感谢参与','num'=>0,'zj_num'=>0]);
        }
        //获取免费次数
        $free_num =  Db::name('prize_free')->where(['uid'=>$this->uid,'pid'=>$id,'status'=>0])->count();
        $data = ['info'=>$info,'list'=>$list,'free_num'=>$free_num];
        return json(['status'=>1,'data'=>$data]);
    }
    //获取用户的抽奖记录
    public function log($status=0,$page=1,$count=10){
	    $map = ['uid'=>$this->uid];
	    if($status==1){
	        $map['status'] = ['<>',0];
        }
	    $list = Db::name('prize_log')->alias('pl')
            ->join('think_prize p','p.id=pl.pid','left')
            ->field('pl.*,p.title')
            ->where($map)
            ->page($page,$count)->select();
        return json(['status'=>1,'list'=>$list]);
    }
    //会员抽奖
    public function draw($id){
        $info = Db::name('prize')->where('id',$id)->find();
        if($info){
            //判断会员是否有资格抽奖
            //判断是否有免费机会
            $free = Db::name('prize_free')->where(['uid'=>$this->uid,'pid'=>$id,'status'=>0])->find();
            if($free){
                $type = 2;
            }else{
                $user_money = Db::name('member')->where('id',$this->uid)->value('money');
                if($user_money<$info['price']){
                    return json(['status'=>0,'msg'=>'余额不足']);
                }else{
                    $type = 1;
                }
            }
            Db::startTrans();
            try {
                $get_prize = $this->get_prize($id);//抽取奖品
                //判断此次抽奖后，奖金池金额是否大于已送出奖品金额，若大于，把此次抽奖结果设置为空
                if($get_prize['id'] > 0 && ($info['jc_price']+$info['price'] < $info['xf_price']+$get_prize['price'])){
                    $get_prize = ['id'=>0,'name'=>'感谢参与~','price'=>0];
                }
                $log_data = ['uid'=>$this->uid,'pid'=>$id,'status'=>0,'desc'=>$get_prize['name'],'price'=>$info['price'],'goods_price'=>$get_prize['price'],'type'=>$type,'create_time'=>time()];

                //修改已送出奖品金额
                $price_data['xf_price'] = $info['xf_price']+$get_prize['price'];

                if($type == 2){
                    //修改免费机会为已使用
                    Db::name('prize_free')->where('id',$free['id'])->update(['status'=>1,'used_time'=>time()]);
                    $log_data['price'] = 0;
                    
                }elseif($type == 1){
                    //修改会员余额
                    Db::name('member')->where('id',$this->uid)->setDec('money',$info['price']);
                    //添加收支记录
                    Db::name('log_income')->insert(['uid'=>$this->uid,'money'=>-$info['price'],'type'=>15,'create_time'=>time()]);
                    //修改抽奖活动的奖池金额
                    $price_data['jc_price'] = $info['jc_price']+$info['price'];
                }
                Db::name('prize')->where('id',$id)->update($price_data);
                if($get_prize['id']>0){
                    $log_data['status']=1;
                    //减去奖品的剩余数量
                    Db::name('prize_goods')->where('id',$get_prize['id'])->setDec('left_num',1);
                }
                //插入抽奖记录
                Db::name('prize_log')->insert($log_data);
                //判断是否赠送免费次数
                if($type == 1 && $info['buy_num']>0 && $info['free_num']>0){
                    $last_log = DB::name('prize_log')->where('uid',$this->uid)->order('create_time desc')->limit($info['buy_num'])->column('type');
                    if(count($last_log)==$info['buy_num'] && !in_array(2,$last_log)){
                        for($i=1;$i<=$info['free_num'];$i++){
                            Db::name('prize_free')->insert(['uid'=>$this->uid,'pid'=>$id,'create_time'=>time()]);
                        }
                    }
                }

                Db::commit();
                $back = ['status'=>1,'msg'=>$get_prize['name']];
            } catch (\Exception $e) {
                Db::rollback();
                $back = ['status'=>0,'msg'=>$e->getMessage().$e->getFile().$e->getLine()];
            }
            return json($back);

        }else{
            $back = ['status'=>0,'msg'=>'抽奖活动不存在'];
        }
        return json($back);
    }
    //抽奖程序
    private function get_prize($id){
        $info = Db::name('prize_goods')->where(['pid'=>$id,'left_num'=>['>',0]])->order('rate asc')->column('rate','id');
        $empty_rate = 100000-(array_sum($info));
        //array_push($info,$empty_rate);
		$info[0] = $empty_rate;
        //概率数组的总概率精度
        $proSum = array_sum($info);
        //概率数组循环
        $goods_id = 0;
        foreach ($info as $key =>$rate) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $rate) {
                $goods_id = $key;
                break;
            } else {
                $proSum -= $rate;
            }
        }
        $goods_info = Db::name('prize_goods')->where('id',$goods_id)->find();
        //若未获取到奖品信息，说明是空奖
        if($goods_info){
            $data = ['id'=>$goods_id,'name'=>$goods_info['name'],'price'=>$goods_info['price']];
        }else{
            $data = ['id'=>0,'name'=>'感谢参与','price'=>0];
        }
        return $data;
    }
}