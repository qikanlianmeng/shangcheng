<?php

namespace app\admin\controller;
use think\Config;
use think\Loader;
use think\Db;

class Index extends Base
{
    public function index()
    {
        return $this->fetch('/index');
    }


    /**
     * [indexPage 后台首页]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function indexPage()
    {
        if($this->uid != 1){
            $this->assign([
                'web_server' => $_SERVER['SERVER_SOFTWARE'],
                'onload'     => ini_get('upload_max_filesize'),
                'think_v'    => THINK_VERSION,
                'phpversion' => phpversion(),
            ]);
            return $this->fetch('index2');
        }
        $start = input('param.start','');
        $end = input('param.end','');
        $where = [];
        $dl_where = [];
        $order_where = [];
		$order_where_str = '';
        $date_limit = '截止到当前时间';
        if($start && $end){
            $where['create_time'] = ['between',[strtotime($start),strtotime($end)]];
            $dl_where['dl_time'] = ['between',[strtotime($start),strtotime($end)]];
            $order_where['add_time'] = ['between',[strtotime($start),strtotime($end)]];
			$order_where_str = ' where add_time>='.strtotime($start).' and add_time <='.strtotime($end);
            $date_limit = $start.'到'.$end;
        }else{
            if($start){
                $where['create_time'] = ['>',strtotime($start)];
                $dl_where['dl_time'] = ['>',strtotime($start)];
                $order_where['add_time'] = ['>',strtotime($start)];
				$order_where_str = ' where add_time>='.strtotime($start);
                $date_limit = '从'.$start.'到当前时间';
            }
            if($end){
                $where['create_time'] = ['<',strtotime($end)];
                $dl_where['dl_time'] = ['between',[1,strtotime($end)]];
                $order_where['add_time'] = ['<',strtotime($end)];
				$order_where_str = ' where add_time <='.strtotime($end);
                $date_limit = '截止到'.$end;
            }
        }
        //会员信息统计
        //会员总数，代理总数，体验中心总数
        $total_member = DB::name('member')->where('closed',0)->where($where)->count();
        $total_dl = DB::name('member')->where(['closed'=>0,'dl_time'=>['>',0]])->where($dl_where)->count();
        $total_center = DB::name('member')->where(['closed'=>0,'is_center'=>1])->where($where)->count();

        //订单信息统计
        //订单总数，已支付，支付总额，各个支付方式的总额
        /* $total_order = Db::name('order')->where($order_where)->count();
        $total_pay  = Db::name('order')->where('pay_status',1)->where($order_where)->count();
        $total_amount  = Db::name('order')->where('pay_status',1)->where($order_where)->sum('total_amount');
        $ali_amount =  Db::name('order')->where(['pay_status'=>1,'pay_code'=>2])->where($order_where)->sum('order_amount');
        $wx_amount =  Db::name('order')->where(['pay_status'=>1,'pay_code'=>3])->where($order_where)->sum('order_amount');
        $yue_amount =  Db::name('order')->where(['pay_status'=>1,'user_money'=>['>',0]])->where($order_where)->sum('user_money'); */
		$sql_str = 'select count(order_id)as total_order,count(case when pay_status=1 then order_id end)as total_pay,sum(case when pay_status=1 then total_amount end) as total_amount,sum(case when pay_status=1 and pay_code=2 then total_amount end) as ali_amount,sum(case when pay_status=1 and pay_code=3 then order_amount end) as wx_amount,sum(case when pay_status=1 and user_money>0 then user_money end) as yue_amount from think_order'.$order_where_str;
		$total_info = Db::query($sql_str);
        //收益信息统计
        //用户产生总收益，各类收益总额
        //$total_income = Db::name('log_income')->where($where)->where(['type'=>['in',[1,2,3,4,5,6,7,16,17,18,19,20,21,22]]])->sum('money');
        $total_income = 0;
        $income_arr = Db::name('log_income')->where($where)->where(['type'=>['in',[1,2,3,4,5,6,7,16,17,18,19,20,21,22]]])->field('type,money')->group('type')->column('sum(money)','type');
        //echo Db::name('log_income')->getLastSql();exit;
        $income_type = [
            1 => '推荐奖',
            //2 => '代营收益奖',
            2 => '代售结算',
            3 => 'A组收益奖',
            4 => 'B组收益奖',
            5 => '体验中心分润奖',
            6 => '推荐体验中心分润奖',
            7 => '收益加权分润奖',
            16 => '间推代理收益',
            17 => '代理平级分润',
            18 => '自营体验中心收益',
            19 => '自营推荐体验中心分润',
            20 => '自营直推收益',
            21 => '自营间推收益',
            22 => '自营平级分润'
        ];
        foreach($income_type as $k=>$v){
            $val['name'] = $v;
            if(isset($income_arr[$k])){
                $val['money'] = $income_arr[$k];
            }else{
                $val['money'] = 0;
            }
            $income_type[$k] = $val;
            $total_income += $val['money'];
        }

        //收支统计
        $chongzhi = Db::name('log_income')->where($where)->where(['type'=>9])->sum('money');
        $order_cash = Db::name('order')->where($order_where)->where(['pay_status'=>1,'pay_code'=>['in',[2,3]]])->sum('order_amount');//获取所有非月支付的订单收益
        $total_cash = Db::name('log_cash')->where($where)->where(['status'=>2])->sum('get_money');
        //用户产生总支出，各类支出总额
        /*$total_cost = Db::name('log_income')->where($where)->where(['type'=>['in',[10,11]]])->sum('money');
        $cost_arr = Db::name('log_income')->where($where)->where(['type'=>['in',[10,11]]])->field('type, money')->group('type')->column('sum(money)','type');
        $cost_type = [
            10=> '提现',
            11=> '支付订单'
        ];
        foreach($cost_type as $k=>$v){
            $val['name'] = $v;
            if(isset($cost_arr[$k])){
                $val['money'] = $cost_arr[$k];
            }else{
                $val['money'] = 0;
            }
            $cost_type[$k] = $val;
        }*/
        $this->assign([
            'total_member'  => $total_member,
            'total_dl'      => $total_dl,
            'total_center'  => $total_center,
            'total_order'   => $total_info[0]['total_order'],
            'total_pay'     => $total_info[0]['total_pay'],
            'total_amount'  => $total_info[0]['total_amount'],
            'ali_amount'    => $total_info[0]['ali_amount'],
            'wx_amount'     => $total_info[0]['wx_amount'],
            'yue_amount'    => $total_info[0]['yue_amount'],
            'total_income'  => $total_income,
            'income_type'   => $income_type,
            //'total_cost'    => $total_cost,
            //'cost_type'     => $cost_type,
            'chongzhi'      => $chongzhi,
            'order_cash'    => $order_cash,
            'total_cash'    => $total_cash,
            'date_limit'    => $date_limit,
            'web_server' => $_SERVER['SERVER_SOFTWARE'],
            'onload'     => ini_get('upload_max_filesize'),
            'think_v'    => THINK_VERSION,
            'phpversion' => phpversion(),
        ]);
        return $this->fetch('index');
    }



    /**
     * [userEdit 修改密码]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function editpwd(){

        if(request()->isAjax()){
            $param = input('post.');
            $user=Db::name('admin')->where('id='.session('uid'))->find();
            if(md5(md5($param['old_password']) . config('auth_key'))!=$user['password']){
               return json(['code' => -1, 'url' => '', 'msg' => '旧密码错误']);
            }else{
                $pwd['password']=md5(md5($param['password']) . config('auth_key'));
                Db::name('admin')->where('id='.$user['id'])->update($pwd);
                session(null);
                cache('db_config_data',null);//清除缓存中网站配置信息
                return json(['code' => 1, 'url' => 'index/index', 'msg' => '密码修改成功']);
            }
        }
        return $this->fetch();
    }


    /**
     * 清除缓存
     */
    public function clear() {
        if (delete_dir_file(CACHE_PATH) || delete_dir_file(TEMP_PATH)) {
            return json(['code' => 1, 'msg' => '清除缓存成功']);
        } else {
            return json(['code' => 0, 'msg' => '清除缓存失败']);
        }
    }

}
