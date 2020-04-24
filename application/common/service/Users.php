<?php

namespace app\common\service;
use app\common\model\IntegralLog;
use app\common\model\Member;
use app\common\model\MemberMsg;
use app\common\model\MoneyLog;
use app\common\model\PayLog;
use think\Model;
use think\Db;
class Users extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->Member=new Member();
		$this->Msg=new MemberMsg();
    }
	//修改密码
	public function update_pwd($uid,$old_pwd,$new_pwd){
		//$map = array('id'=>$uid,'password'=>md5(md5($old_pwd) . config('auth_key')));
		//return $this->Member->save(['password'=>md5(md5($new_pwd) . config('auth_key'))],$map);
        $map = array('id'=>$uid,'password'=>md5($old_pwd));
        return $this->Member->save(['password'=>md5($new_pwd)],$map);
	}
	//找回密码
	public function edit_pwd($account,$pwd){
		$map['account|mobile|email'] =$account;
		//return $this->Member->save(['password'=>md5(md5($pwd) . config('auth_key'))],$map);
        return $this->Member->save(['password'=>md5($pwd)],$map);
	}
	//判断用户账号是否被占用
	public function check_account($account){
		$map['account'] =$account;
		$map['closed'] = 0;
		return  $this->Member->where($map)->find();
	}
    //判断用户手机号是否被占用
	public function check_mobile($mobile){
		$map['mobile'] =$mobile;
		$map['closed'] = 0;
		return  $this->Member->where($map)->find();
	}
    //判断推荐码是否正确，并返回对应用户id
	public function check_rcode($rcode){
		$map['rcode'] =$rcode;
		$map['closed'] = 0;
		$user = $this->Member->where($map)->find();
        $ruid = 0;
        if($user){
            $ruid = $user->id;
        }
        return $ruid;
	}
	
    //用户注册
    //$type 注册类型：0账号注册，1手机注册
    public function reg($account,$password,$ruid,$type=0){
        $p_user = Db::name('member')->where('id',$ruid)->find();
        $pcen_uid = $p_user['pcen_uid'];
        if($p_user['is_center']){
            $pcen_uid = $ruid;
        }
        //生成唯一邀请码
        $rcode = 'FX000000';
        $member_num = DB::name('member')->count();
        $member_num = (string)($member_num+8000);
		$param = [
	            'account' => $account,
	            'password' =>md5($password),
                'ruid'  => $ruid,
                'rcode' => substr($rcode,0,strlen($rcode)-strlen($member_num)).$member_num,
                'pcen_uid' =>$pcen_uid
	        ];
        if($type == 1){
            $param['mobile'] = $account;
        }
		return $this->Member->save($param);
    }
	//修改用户基本信息
	public function update_info($uid,$data){
		return $this->Member->save($data,['id'=>$uid]);
	}
	
    public function login($account,$password){
        $map['closed']=0;
        $map['status']=1;
        //TODO 时间关系 这个地方没有 分别判断
        $map['account|mobile'] =$account;
        //$map['password']=md5(md5($password) . config('auth_key'));
        $map['password']=md5($password);
        return  $this->Member->where($map)->find();
    }

    //自动登录用户登录
    public function autoLogin($user){
        if(time() < strtotime(config('new_member_date'))){
            $dj_day = config('dj_limit_day');
        }else{
            $dj_day = config('new_dj_limit_day');
        }
        //echo $user['create_time'];exit;
        if((strtotime($user['create_time'])+$dj_day*86400) < time() && $user['real_info_ok']==0){
            Db::name('member')->where('id',$user['id'])->update(['status'=>0]);
            echo json_encode(['status'=>0,'msg'=>'账号未在'.$dj_day.'天内实名认证，已冻结，请联系管理员']);
            exit;
        }
        
        /* 记录登录SESSION和COOKIES */
        $auth = array(
            'uid' => $user['id'],
            'nickname' => $user['nickname'],
            'last_login_time' => $user['update_time'],
        );
        session('user_auth', $auth);
        session('user_auth_sign', data_auth_sign($auth));
        /* 更新登录信息 */
        $data = array(
            'id' => $user['id'],
            'login_num' =>  Db::raw('login_num+1'),
            'last_login_ip' => get_client_ip(0),
            'session_id'    => session_id(),
        );
        $this->Member->update($data);


    }
    //用户退出
    public function logout(){
        if (is_login()) {
            session('user_auth', null);
            session('user_auth_sign', null);
            return array('status'=>1,'info'=>'退出成功');
        }
        return array('status'=>0,'info'=>'用户未登录');
    }
    //getUserInfo
    public function getUserInfo($uid){
        $uesr =$this->Member->get($uid);

        // 会员等级
        if($uesr->is_center == 1){
            $uesr->rank_name = '体验中心';
        }else{
            if($uesr->dl_time > 0){
                $uesr->rank_name = '代理会员';
            }else{
                $uesr->rank_name = '普通会员';
            }
        }
        //获取会员下级直系代理人数
        $uesr->son_dl = Db::name('member')->where(['ruid'=>$uid,'closed'=>0,'dl_time'=>['>',0]])->count();
        //获取用户总佣金
        $uesr->total_income = Db::name('log_income')->where(['uid'=>$uid,'type'=>['in',[1,2,3,4,5,6,7]]])->sum('money');
        //获取推荐代理总佣金
        $uesr->dl_income = Db::name('log_income')->where(['uid'=>$uid,'type'=>['in',[1,5,6]]])->sum('money');
        //获取用户分销总佣金
        $uesr->dy_income = Db::name('log_income')->where(['uid'=>$uid,'type'=>['in',[2,3,4,7]]])->sum('money');
        //获取用户代审核金额
        $uesr->wait_cash = Db::name('log_cash')->where(['uid'=>$uid,'status'=>['in',[0,1]]])->sum('money');
        //获取用户已提现金额
        $uesr->already_cash = Db::name('log_cash')->where(['uid'=>$uid,'status'=>2])->sum('money');
        //$uesr->grade=$this->getUserLevel($uesr->integral);
        // 消息中心
        //$uesr->unread_msg=$this->getUserUnreadMsg($uid);
        //TODO 用户头像URL处理
        //订单状态处理
        $map = ['deleted'=>['not in',['1,3']]];
        //待付款 未支付 订单待确认或已确认
        $uesr->unpay_order_num = Db::name('order')->where($map)->where(['user_id' => $uid, 'pay_status' => 0, 'order_status' => ['in',[0, 1]], 'shipping_status' => 0])->count();
        //待发货
        $uesr->unshipping_order_num = Db::name('order')->where($map)->where(['user_id' => $uid, 'pay_status' => ['in', [1,4]], 'order_status' => 1, 'shipping_status' => 0])->count();
        //待收货
        $uesr->unconfirm_order_num = Db::name('order')->where($map)->where(['user_id' => $uid, 'pay_status' => ['in', [1,4]], 'order_status' => 1, 'shipping_status' => 1])->count();
        //待评价
        $uesr->unpingjia_order_num = Db::name('order')->where($map)->where(['user_id' => $uid, 'pay_status' => ['in', [1,4]], 'order_status' => 2, 'shipping_status' => 1])->count();
        unset($uesr['password']);
        $uesr->fuwu_cost = config('cash_fuwu_cost');
        $uesr->shouxu_cost = config('cash_shouxu_cost');
        $uesr->cash_unit = config('cash_unit');
        $uesr->cash_account = Db::name('member_bank')->where('uid',$uid)->find();
        return ['code'=>1,'data'=>$uesr];

    }

    //会员基本数据
    public  function userinfo($uid){
        $res = $this->Member->get($uid);
        //获取用户银行卡信息
        $bank = Db::name('member_bank')->where('uid',$uid)->find();
        $res->cash_account = $bank;
        return $res;
    }

    //根据 经验值 得到用户等级
    public function getUserLevel($integral){
        //根据羊币  得到当前用户登记
        $map['min']=['<=',$integral];
        $map['max']=['>=',$integral];
        $row=db('member_grade')->where($map)->find();
        if($row){
        	return ['code'=>1,'img'=>"{$row['portrait']}?imageMogr2/thumbnail/128x128!",'name'=>$row['name']];
        }else{
        	return ['code'=>1,'img'=>"http://resources-user-image.vxue360.com/b5b04ea1e7122f4d1f3b1898cf5ba60e.png?imageMogr2/thumbnail/128x128!",'name'=>'小白'];
        }
    }
    //未读消息数量
    public function getUserUnreadMsg($uid){
        return $this->Msg->where(['receive_uid'=>$uid,'status'=>0])->count();
    }

   //给用户加钱
    public function userIncMoney($uid,$money){
        return $this->Member->incMoney($uid,$money);
    }

    //给用户减钱
    public function userDecMoney($uid,$money){
        return $this->Member->decMoney($uid,$money);
    }

    //给用户加羊币
    public function userIncIntegral ($uid, $integral)
    {
        return $this->Member->incIntegral($uid, $integral);
    }
    //给用户减羊币
    public function userDecIntegral ($uid, $integral)
    {
        return $this->Member->decIntegral($uid, $integral);

    }
	//获得用户消息
	public function get_msg($uid,$page,$pagesize){
		return $this->Msg->msg_list($uid,$page,$pagesize);
	}
	//查看一条消息
	public function read_msg($uid,$id){
		//获得消息内容
		$info = $this->Msg->where("id",$id)->find();
		$info = $info->toArray();
		if($info['status'] == 0){
			//把消息标记为已读
			$this->Msg->save(['status'=>1],['receive_uid'=>$uid,'id'=>$id]);
		}
		$info['content'] = unserialize($info['content']);
		return $info;
		
	}
	//删除一条消息
	public function del_msg($uid,$id){
		return $this->Msg->where(['receive_uid'=>$uid,'id'=>$id])->delete();
	}
	

    public function userPayLog($uid,$map=['status'=>1]){
        $map['uid']=$uid;
        $PayLog=new PayLog();
        $res =$PayLog->order('id desc')->field('money,pay_type,update_time')->where($map)->select();
        if($res){
            return ['code'=>1,'data'=>$res,'msg'=>''];
        }else{
            return ['code'=>0,'data'=>'','msg'=>'没有记录'];
        }
    }
    public function userMoneyLog($uid,$map=['status'=>1]){
        $map['uid']=$uid;
        $PayLog=new MoneyLog();
        $res =$PayLog->order('create_time desc')->field('out_trade_no,money,act,remark,update_time')->where($map)->select();
        if($res){
            return ['code'=>1,'data'=>$res,'msg'=>''];
        }else{
            return ['code'=>0,'data'=>'','msg'=>'没有记录'];
        }
    }

    public function userIntegralLog($uid,$map=['status'=>1]){
        $map['uid']=$uid;
        $IntegralLog=new IntegralLog();
        $res =$IntegralLog->order('id desc')->field('id,num,act,remark,update_time')->where($map)->select();
        if($res){
            return ['code'=>1,'data'=>$res,'msg'=>''];
        }else{
            return ['code'=>0,'data'=>'','msg'=>'没有记录'];
        }
    }

    public function recharge($uid,$money){
        $MoneyLog=new MoneyLog();
        $data = $MoneyLog->operate($uid,$money,1,0,'用户充值');
        if($data){
            return ['code'=>1,'data'=>$data,'msg'=>''];
        }
        return ['code'=>0,'data'=>'','msg'=>'生成充值订单失败'];
    }



    /*
     * 认养推广奖励
     * uid 认养会员uid
     * num 认养数量
     */
    public function renyang_jiangli($uid,$num){

        $ztui = config('yang_tui1');//直推
        $jtui = config('yang_tui2');//间推
        $fuwu = config('yang_fuwu');//服务中心
        $daili = config('yang_daili');//代理
        $member = $this->Member->get($uid);
        if($member && $member['tuid']){
            $IntegralLog=new IntegralLog();

            //推荐人信息
            $tmember = $this->Member->get($member['tuid']);
            if($tmember && $tmember['yang_count']>0 && $ztui>0){
                $ztmoney = $ztui*$num;
                $this->userIncIntegral($tmember['id'], $ztmoney);
                $IntegralLog->operate($tmember['id'],$ztmoney,21,1,'直推奖励('.$member['account'].'认养'.$num.'只羊)');
            }

            //间推人信息
            if($tmember && $tmember['tuid']){

                //推荐人信息
                $jmember = $this->Member->get($tmember['tuid']);
                if($jmember && $jmember['yang_count']>0 && $jtui>0){
                    $ztmoney = $jtui*$num;
                    $this->userIncIntegral($jmember['id'], $ztmoney);
                    $IntegralLog->operate($jmember['id'],$ztmoney,22,1,'间推奖励('.$member['account'].'认养'.$num.'只羊)');
                }
            }

            //服务中心及代理奖励
            if($tmember){
                $info = $tmember;
                $dengji = 0;
                for($i=0;$i<1000;$i++){
                    if($info['dengji']>$dengji){
                        if($dengji==0){
                            if($info['dengji']==1){
                                $zmoney = $fuwu;
                                $name = '服务中心';
                                $ids = 23;

                            }else{
                                $zmoney = $daili;
                                $name = '代理';
                                $ids = 24;
                            }
                        }elseif($dengji==1){
                            $zmoney = $daili-$fuwu;
                            $name = '代理';
                            $ids = 24;
                        }
                        $this->userIncIntegral($info['id'], $zmoney);
                        $IntegralLog->operate($info['id'],$zmoney,$ids,1,$name.'奖励('.$member['account'].'认养'.$num.'只羊)');

                        $dengji = $info['dengji'];//赋值
                    }

                    //增加会员伞下认养总数+更新会员等级
                    $zyangnum = $info['tyang_count']+$num;
                    if($zyangnum<500){
                        $udengji = 0;
                    }elseif ($zyangnum<=2000){
                        $udengji =1;
                    }else{
                        $udengji =2;
                    }
                    //增加团队认养总数
                    Db::name('member')->where(['id'=>$info['id']])->update(['tyang_count'=>$zyangnum,'dengji'=>$udengji]);


                    //查找下一级
                    if($info['tuid']){
                        $mems = $this->Member->get($info['tuid']);
                        if($mems){
                            $info = $mems;
                        }else{
                            break;
                        }
                    }else{
                        break;
                    }

                }
            }
        }

        return ['code'=>1,'data'=>'','msg'=>'奖励完成'];
    }


    /*
     * 判断是否是好友牧场
     * yuid  好友id
     */
    public function refer_yang($uid,$yuid){

            if(!$uid || !$yuid){
                return ['code' =>0, 'data' => '', 'msg' => '参数错误'];
            }
            $reyang = Db::name('member')->field('account,tuid,yang_ing')->where(['id'=>$yuid])->find();
            if(!$reyang || $reyang['tuid']<=0){
                return ['code' =>0, 'data' => '', 'msg' => '参数错误'];
            }
            $treyang = Db::name('member')->field('tuid')->where(['id'=>$reyang['tuid']])->find();
            if($reyang['tuid']!=$uid && $treyang['tuid']!=$uid){
                return ['code' =>0, 'data' => '', 'msg' => '非好友牧场'];
            }else{
                return ['code' =>1, 'data' => $reyang, 'msg' => ''];
            }

    }




}

