<?php

namespace app\api2\controller;
use think\Db;


class Rz extends Base
{
    //获取一个当前正在进行的认购活动
	public function index(){
        $today = date('Y-m-d',time());
        $where = [
            'start_time'    => ['<=',$today],
            'end_time'      => ['>',$today]
        ];
        $info = Db::name('rz')->where($where)->field('id,title')->find();
        if($info){
            $back = ['status'=>1,'data'=>$info];
        }else{
            $back = ['status'=>0,'data'=>$info];
        }
        return json($back);
    }
    //获取一个认购活动详情
	public function info($id){
        $info = Db::name('rz')->where('id',$id)->find();
        return json(['status'=>1,'data'=>$info]);
    }
    //提交认购申请
    public function apply($id,$num){
		if($num < 200){
            return json(['status'=>0,'msg'=>'认购数量必须大于200']);
        }
        $info = Db::name('rz')->where('id',$id)->find();
        $today = date('Y-m-d',time());

        if($today < $info['start_time'] || $today>= $info['end_time']){
            return json(['status'=>0,'msg'=>'认购活动未开启']);
        }
        $total_price = $info['price']*$num;
        $user = Db::name('member')->where('id',$this->uid)->find();
        if($user['money'] < $total_price){
            return json(['status'=>0,'msg'=>'余额不足']);
        }
        Db::startTrans();
        try{
            $data = [
                'uid'   => $this->uid,
                'rid'   => $id,
                'num'   => $num,
                'price' => $info['price'],
                'total_price' => $total_price,
                'create_time' => time(),
            ];
            $res1 = Db::name('rz_log')->insert($data);
            $res2 = Db::name('member')->where('id',$this->uid)->setDec('money',$total_price);
            $res3 = Db::name('log_income')->insert(['uid'=>$this->uid,'type'=>23,'money'=>-$total_price,'create_time'=>time()]);
            if(!$res1 || !$res2 || !$res3){
                throw new Exception('操作失败'.$res1.$res2,$res3);
            }
            Db::commit();
            $back = ['status'=>1,'msg'=>'申请成功'];
        }catch(\Exception $e){
            Db::rollback();
            $back = ['status'=>0,'msg'=>$e->getMessage()];
        }
        return json($back);
    }
    //用户认购记录
    public function lists($page=1,$count=10){
        $list = Db::name('rz_log')->alias('rl')
            ->join('think_rz r','r.id=rl.rid','left')
            ->field('rl.*,r.title')
            ->where('uid',$this->uid)->order('status asc,create_time desc')->page($page,$count)->select();
        if($list){
            foreach($list as $k=>$v){
                $list[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
            }
        }
        return json(['status'=>1,'data'=>$list]);
    }
}