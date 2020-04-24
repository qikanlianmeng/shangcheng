<?php
/**
 * 订单类
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/12
 * Time: 13:11
 */

namespace app\common\model;

use think\Model;
use think\Db;
use app\common\model\Price;
use think\Config;
use app\common\service\Payment;
use app\common\service\Users;

class Order extends Model
{
    //检查订单商品是否符合规定
    public static function check_order_goods($user_id){
        $cartList = Db::name('cart')->where(['user_id' => $user_id, 'selected' => 1])->group('goods_group')->select();
        if(count($cartList)>1){
            return ['status'=>0,'msg'=>'一个订单中不能存在多个分区的商品'];
        }
        $goods_group = $cartList[0]['goods_group'];
        $cartList = Db::name('cart')->where(['user_id' => $user_id, 'selected' => 1])->select();
        $back = ['status'=>1];
        //获取用户已购买的代理商品id
        $already_buy_dl = Db::name('order')->alias('o')
            ->join('think_order_goods og','o.order_id=og.order_id','right')
            ->where(['o.user_id'=>$user_id,'o.pay_status'=>1,'o.order_group'=>1,'deleted'=>['not in',[1,3]]])
            ->column('og.goods_id');
        if($goods_group == 1){
            //判断是否有已购买过的商品
            if($already_buy_dl){
                foreach($cartList as $k=>$v){
                    if(in_array($v['goods_id'],$already_buy_dl)){
                        return ['status'=>0,'msg'=>'商品'.$v['goods_name'].'已购买代理权，不能重复购买'];
                    }
                }
            }
            //判断单个商品数量是否大于1
            foreach($cartList as $k=>$v){
                if($v['goods_num'] > 1){
                    return ['status' => 0, 'msg' => '同一代理商品只能购买1个'];
                }
            }

        }elseif($goods_group == 2){
            $config = cache('db_config_data');
            if($config['dy_open'] == 0) return ['status'=>0,'msg'=>'代售区已关闭，暂无法购买'];
            $real_info_ok = Db::name('member')->where('id',$user_id)->value('real_info_ok');
            if($real_info_ok==0 && $config['dy_real_info']==1)  return ['status'=>0,'msg'=>'实名信息未完善，暂无法购买代售商品'];
            $total_order = Db::name('order')->where(['order_group'=>$goods_group,'add_time'=>['>',strtotime(date('Y-m-d'))]])->whereIn('order_status','0,1,2,4')->count();
            if($total_order >= $config['dy_order_limit']){
                return ['status'=>0,'msg'=>'今日代售订单总量已达上限，暂无法购买'];
            }
            if(count($cartList) > 1){
                return ['status'=>0,'msg'=>'每次只能购买一种代售商品'];
            }
            if($cartList[0]['goods_num'] > 1){
                return ['status'=>0,'msg'=>'每种代售商品每天只能购买一个'];
            }
            //判断代售区当前时间是否开放
            $week_num = date('w');
            $now_week = $week_num==0?6:$week_num-1;//设置 一周七天对应的数字是0-6
            $now_hour = intval(date('H'));
            $open_date = json_decode($config['dy_dates'],true);

            if(!isset($open_date[$now_week]) || !in_array($now_hour,$open_date[$now_week])){
                return ['status'=>0,'msg'=>'当前时间不能购买代售区商品'];
            }
            //判断是否已购买代理
            if(!in_array($cartList[0]['goods_id'],$already_buy_dl)){
                return ['status'=>0,'msg'=>'未购买该商品的代理权，无法代售该商品'];
            }
            //判断当天用户购买的代售商品数是否已超标
            //查询用户当天是否有已提交的有效代售订单（不能以已支付作为判断条件，避免用户提交多个未支付订单后，再一一支付，造成多次购买）
            $dy_order_goods = Db::name('order')->alias('o')
                ->join('think_order_goods og','og.order_id=o.order_id','left')
                ->where(['user_id'=>$user_id,'order_group'=>$goods_group,'add_time'=>['>',strtotime(date('Y-m-d'))]])
                ->whereIn('order_status','0,1,2,4')->column('goods_id');
            if(count($dy_order_goods) >= config('dy_goods_num')){
                return ['status'=>0,'msg'=>'当天代售商品数已超过'.config('dy_goods_num').'个，不能继续购买'];
            }
            //判断当前商品是否已购买
            if(in_array($cartList[0]['goods_id'],$dy_order_goods)){
                return ['status'=>0,'msg'=>'该商品今日已生成代售订单'];
            }
            //判断用户指定天数内新增直系代理是否达到，未达到，限制购买代售商品的天数
            $limit_days_end = strtotime(date('Y-m-d'))+86400;
            $limit_days_start = $limit_days_end-(86400*$config['limit_days']);
            $added_dl = Db::name('member')->where(['ruid'=>$user_id,'closed'=>0])->where('dl_time','>',$limit_days_start)->where('dl_time','<',$limit_days_end)->count();
            if($added_dl < $config['limit_people']){
                //获取最近一天代售订单
                $dy_order = Db::name('order')->where(['user_id'=>$user_id,'pay_status'=>1,'order_group'=>2])->order('add_time desc')->find();
                if($dy_order){
                    if(time()-(strtotime(date('Y-m-d',$dy_order['add_time']))+86400) < 86400*$config['limit_days_times']){
                        $back = ['status'=>0,'msg'=>$config['limit_days'].'天内新增直推代理人数未达到'.$config['limit_people'].'人，代售商品限制为'.$config['limit_days_times'].'天购买一次'];
                    }
                }
            }
        }elseif($goods_group == 3){
            //自营区订单，用户必须是代理会员或体验中心才能买
            $userinfo = Db::name('member')->where('id',$user_id)->find();
            if($userinfo['dl_time'] < 1 && $userinfo['is_center']!=1){
                $back = ['status'=>0,'msg'=>'普通用户不能购买自营区商品'];
            }
        }
        return $back;
    }
    //获取订单商品
    public function OrderGoods()
    {
        return $this->hasMany('OrderGoods','order_id','order_id');
    }
    /**
     * @notice 优惠活动暂时未做
     * @param $user_id          用户ID
     * @param $address_id       邮寄地址ID
     * @param $invoice_title    发票抬头 默认无
     * @param int $coupon_id    优惠卷ID 默认无 TODO
     * @param $car_price        商品价格信息  ['postFee'=>'物流费用','couponFee'=>'优惠卷','balance'=>'使用余额','pointsFee'=>'羊币抵消金额','payables'=>'应付金额','goodsFee'=>'商品总额','order_prom_id'=>'优惠活动ID','order_prom_amount'=>'优惠金额']
     * @param string $user_note 用户下单备注
     * @param string $pay_name  支付方式 全款，余额，羊币
     * @return array
     */
    public function addOrder($user_id, $address_id, $car_price, $user_note = '', $discount,$invoice_title = '', $coupon_id = 0, $pay_name = '')
    {
        $cartList = Db::name('cart')->where(['user_id' => $user_id, 'selected' => 1])->select();
        //获取订单类型
        $order_group = $cartList[0]['goods_group'];
        //邮寄地址
        $address  = Db::name('user_address')->where("address_id", $address_id)->find();
        $order_sn = $this->get_order_sn();
        //羊币金额兑换比例
        $point_rate = Config::get('point_rate');
        $point_rate = $point_rate ? $point_rate : 100;
        $data = [
            'order_sn'         => $order_sn, // 订单编号
            'user_id'          => $user_id, // 用户id
            'consignee'        => $address['consignee'], // 收货人
            'province'         => $address['province'],//'省份id',
            'city'             => $address['city'],//'城市id',
            'district'         => $address['district'],//'县',
            'twon'             => $address['twon'],// '街道',
            'address'          => $address['address'],//'详细地址',
            'mobile'           => $address['mobile'],//'手机',
            'zipcode'          => $address['zipcode'],//'邮编',
            'email'            => $address['email'],//'邮箱',
            'invoice_title'    => $invoice_title, //'发票抬头',
            'goods_price'      => $car_price['goodsFee'],//'商品价格',
            'shipping_price'   => $car_price['postFee'],//'物流价格',
            'user_money'       => $car_price['balance'],//使用余额
            'coupon_price'     => $car_price['couponFee'],//'使用优惠券',
            'integral'         => ($car_price['pointsFee'] * $point_rate), //'使用羊币',
            'integral_money'   => $car_price['pointsFee'],//'使用羊币抵多少钱',
            'total_amount'     => ($car_price['goodsFee'] + $car_price['postFee']),// 订单总额
            'order_amount'     => $car_price['payables'],//'应付款金额',
            'add_time'         => time(), // 下单时间
            'order_prom_id'    => $car_price['order_prom_id'],//'订单优惠活动id',
            'order_prom_amount'=> $car_price['order_prom_amount'],//'订单优惠活动优惠了多少钱',
            'user_note'        => $user_note, // 用户下单备注
            'pay_name'         => $pay_name,//支付方式，可能是余额支付或羊币兑换，后面其他支付方式会替换,
            'order_group'      => $order_group,
            'discount'         => $discount
        ];
        if($order_group == 2){
            $data['dy_income'] = $data['total_amount']*(1+config('buy_income')/100);
        }
        Db::startTrans();
        try{
            $order_id = Db::name('order')->insertGetId($data);
            if (!$order_id) {
                return ['status' => 0, 'msg' => '下订单失败'];
            }
            //记录订单操作日志
            $action_info = [
                'order_id'        => $order_id,
                'action_user'     => $user_id,
                'action_note'     => '您提交了订单，请等待系统确认',
                'status_desc'     => '提交订单',
                'log_time'        => time(),
            ];
            Db::name('order_action')->insert($action_info);
            //开始同时添加订单商品信息

            foreach ($cartList as $key => $val) {
                $goods = Db::name('goods')->where("id", $val['goods_id'])->find();
                $data2['order_id']           = $order_id; // 订单id
                $data2['goods_id']           = $val['goods_id'];      // 商品id
                $data2['goods_name']         = $val['goods_name'];    // 商品名称
                $data2['goods_sn']           = $val['goods_sn'];      // 商品货号
                $data2['goods_num']          = $val['goods_num'];     // 购买数量
                $data2['market_price']       = $val['market_price'];  // 市场价
                $data2['goods_price']        = $val['goods_price'];   // 商品价
                $data2['spec_key']           = $val['spec_key'];      // 商品规格
                $data2['spec_key_name']      = $val['spec_key_name']; // 商品规格名称
                $data2['member_goods_price'] = $val['member_goods_price']; // 会员折扣价
                $data2['cost_price']         = $goods['cost_price'];       // 成本价
                $data2['give_integral']      = $goods['give_integral'] * $val['goods_num'];    // 购买商品赠送羊币
                $data2['prom_type']          = $val['prom_type'];          // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                $data2['prom_id']            = $val['prom_id'];            // 活动id
                $data2['img']                = $val['goods_img'];          // 图片
                Db::name('order_goods')->insert($data2);
                if ($val['prom_type'] == 1) {
                    Db::name('flash_sale')->where('id', $val['prom_id'])->setInc('order_num', $val['goods_num']);
                }
            }

            $users = new Users();
            //修改优惠卷 TODO
            //扣除羊币

            if ($data['integral'] > 0) {
                $users->userDecIntegral($user_id, $data['integral']);
                IntegralLog::operate($user_id, -$data['integral'], 1, 1,'下订单羊币抵扣',0);
            }
            //扣除余额
            if ($data['user_money'] > 0) {
				//重新从数据库读取用户余额，避免余额不足造成负值
				$new_user_money = Db::name('member')->where('id',$user_id)->value('money');
				if($new_user_money < 0 || $new_user_money<$data['user_money']){
					throw new \Exception('账户余额不足，无法使用余额支付');
				}
                $users->userDecMoney($user_id, $data['user_money']);
                Db::name('log_income')->insert(['uid'=>$user_id,'order_sn'=>$order_sn,'money'=>-$data['user_money'],'type'=>11,'create_time'=>time()]);
            }
            // 如果应付金额为0  可能是余额支付 + 羊币 + 优惠券 这里订单支付状态直接变成已支付
            if ($data['order_amount'] <= 0) {
                $res = $this->update_pay_status($order_sn,['pay_code' => 1]);
                if($res['code']!=1){
                    Db::rollback();
                    return ['status' => 0,'msg' =>$res['msg']];
                }
            }
            //删除购物车已提交订单商品
            Db::name('cart')->where(['user_id' => $user_id, 'selected' => 1])->delete();

            Db::commit();
            return ['status' => 1,'msg' => '提交订单成功','result' => $order_id]; // 返回新增的订单id
        }catch( \Exception $e){
            Db::rollback();
            return ['status' => 0,'msg' => $e->getMessage()];
        }

    }


    public function addOrder2($user_id,$address_id,$goods_id,$num, $user_note = '', $invoice_title = '', $pay_name = '兑换')
    {
        //邮寄地址
        $address  = Db::name('user_address')->where("address_id", $address_id)->find();

        if (!$address) {
            return['code' => 0, 'msg' => '请先设置物流地址'];
        }
        $order_sn = $this->get_order_sn();

        $goods = Db::name('goods')->where("id", $goods_id)->find();
        $data = [
            'order_sn'         => $order_sn, // 订单编号
            'user_id'          => $user_id, // 用户id
            'consignee'        => $address['consignee'], // 收货人
            'province'         => $address['province'],//'省份id',
            'city'             => $address['city'],//'城市id',
            'district'         => $address['district'],//'县',
            'twon'             => $address['twon'],// '街道',
            'address'          => $address['address'],//'详细地址',
            'mobile'           => $address['mobile'],//'手机',
            'zipcode'          => $address['zipcode'],//'邮编',
            'email'            => $address['email'],//'邮箱',
            'invoice_title'    => $invoice_title, //'发票抬头',
            'goods_price'      => $goods['shop_price'],//'商品价格',
            'shipping_price'   => 0,//'物流价格',
            'user_money'       => 0,//使用余额
            'coupon_price'     => 0,//'使用优惠券',
            'integral'         => 0, //'使用羊币',
            'integral_money'   => 0,//'使用羊币抵多少钱',
             'total_amount'     => $goods['shop_price'] * $num,// 订单总额
            'order_amount'     => 0,//'应付款金额',
            'add_time'         => time(), // 下单时间
            'order_prom_type'    => 10,//'订单类型',
            'order_prom_id'    => 0,//'订单优惠活动id',
            'order_prom_amount'=> 0,//'订单优惠活动优惠了多少钱',
            'user_note'        => $user_note, // 用户下单备注
            'pay_name'         => $pay_name,//支付方式，可能是余额支付或羊币兑换，后面其他支付方式会替换
        ];
        $order_id = Db::name('order')->insertGetId($data);
        if (!$order_id) {
            return ['code' => 0, 'msg' => '下订单失败'];
        }
        //记录订单操作日志
        $action_info = [
            'order_id'        => $order_id,
            'action_user'     => $user_id,
            'action_note'     => '您提交了订单，请等待系统确认',
            'status_desc'     => '提交订单',
            'log_time'        => time(),
        ];
        Db::name('order_action')->insert($action_info);
        //开始同时添加订单商品信息
        $data2['order_id']           = $order_id; // 订单id
        $data2['goods_id']           = $goods['id'];      // 商品id
        $data2['goods_name']         = $goods['goods_name'];    // 商品名称
        $data2['goods_sn']           = $goods['goods_sn'];      // 商品货号
        $data2['goods_num']          = $num;     // 购买数量
        $data2['market_price']       = $goods['market_price'];  // 市场价
        $data2['goods_price']        = $goods['shop_price'];   // 商品价
        $data2['spec_key']           = '';      // 商品规格
        $data2['spec_key_name']      = ''; // 商品规格名称
        $data2['member_goods_price'] = $goods['shop_price']; // 会员折扣价
        $data2['cost_price']         = $goods['cost_price'];       // 成本价
        $data2['give_integral']      = 0;    // 购买商品赠送羊币
        $data2['prom_type']          = 0;          // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
        $data2['prom_id']            = 0;            // 活动id
        $data2['img']                = 0;          // 图片
        Db::name('order_goods')->insert($data2);

        // 如果应付金额为0  可能是余额支付 + 羊币 + 优惠券 这里订单支付状态直接变成已支付
        if ($data['order_amount'] <= 0) {
           // $this->update_pay_status($order_sn,['pay_code' => 1]);
        }
        return ['code' => 1,'msg' => '提交订单成功','data' => $order_id]; // 返回新增的订单id
    }

    /**
     * 此处加的订单默认为已确认，已支付！！！
     * @param $user_id      默认为匿名用户
     * @param $address      收货信息array [consignee,province,city,district,address,mobile]
     * @param $pay_code      支付方式
     * @param $goods_list    商品列表 [id:spec=>num]
     * @param string $invoice_title 发票抬头
     * @param string $admin_note    管理员备注
     * @return array
     */
    public function adminAddOrder($address, $pay_code, $goods_list, $invoice_title = '', $admin_note = '', $user_id = 0)
    {
        $order_sn = $this->get_order_sn();
        $pay      = Db::name('pay_config')->where("id", $pay_code)->find();
        //根据$goods_list算价
        $price = new Price();
        $price = $price->calculate_admin_price($goods_list, $address);
        $price = $price['result'];
        $data = [
            'order_sn'         => $order_sn, // 订单编号
            'user_id'          => $user_id, // 用户id
            'consignee'        => $address['consignee'], // 收货人
            'province'         => $address['province'],//'省份id',
            'city'             => $address['city'],//'城市id',
            'district'         => $address['district'],//'县',
            'address'          => $address['address'],//'详细地址',
            'mobile'           => $address['mobile'],//'手机',
            'invoice_title'    => $invoice_title, //'发票抬头',
            'goods_price'      => $price['goods_price'],//'商品价格',
            'shipping_price'   => $price['shipping_price'],//'物流价格',
            'total_amount'     => $price['total_amount'],// 订单总额
            'order_amount'     => $price['order_amount'],//'应付款金额',
            'add_time'         => time(), // 下单时间
            'admin_note'       => $admin_note ? $admin_note : '管理员后台下单', //管理员下单备注
            'pay_name'         => $pay['name'],//支付方式
            'pay_code'         => $pay_code
        ];
        $order_id = Db::name('order')->insertGetId($data);
        if (!$order_id) {
            return ['status' => 0, 'msg' => '下订单失败'];
        }
        //记录订单操作日志
        $action_info = [
            'order_id'        => $order_id,
            'action_user'     => 0,
            'action_note'     => '您提交了订单，请等待系统确认',
            'status_desc'     => '提交订单',
            'log_time'        => time(),
        ];
        Db::name('order_action')->insert($action_info);
        foreach ($goods_list as $k => $v) {
            //判断是规格商品还是普通商品
            if (strstr($k, ':') === false) {
                $info = Db::name('goods')->where('id', $k)->find();
                $specInfo = [];
                $data2['goods_id']           = $k;                 // 商品id
            } else {
                $k = explode(':', $k);
                $info = Db::name('goods')->where('id', $k[0])->find();
                $specInfo  = Db::name('spec_goods')->where('goods_id', $k[0])->where('key', $k[1])->find();
                //var_dump($specInfo);die;
                $data2['goods_id']           = $k[0];              // 商品id
                $img = Db::name('spec_image')->where(['goods_id' => $k[0], 'spec_item_id' => ['in', explode('_', $specInfo['key'])]])->value('src');
                if ($img) {
                    $specInfo['img'] = $img;
                }
            }
            $data2['order_id']           = $order_id;              // 订单id
            $data2['goods_name']         = $info['goods_name'];    // 商品名称
            $data2['goods_sn']           = $info['goods_sn'];      // 商品货号
            $data2['goods_num']          = $v;                     // 购买数量
            $data2['market_price']       = $info['market_price'];  // 市场价
            $data2['goods_price']        = isset($specInfo['price']) ? $specInfo['price'] : $info['shop_price'];   // 商品价
            $data2['spec_key']           = isset($specInfo['key']) ? $specInfo['key'] : '';      // 商品规格
            $data2['spec_key_name']      = isset($specInfo['key_name']) ? $specInfo['key_name'] : ''; // 商品规格名称
            $data2['member_goods_price'] = $data2['goods_price'];  // 会员折扣价
            $data2['cost_price']         = $info['cost_price'];   // 成本价
            $data2['give_integral']      = $info['give_integral'] * $v;    // 购买商品赠送羊币
            $data2['prom_type']          = 0;
            $data2['img']                = isset($specInfo['img']) ? $specInfo['img'] : $info['original_img'];    //使用图片判断图片
            Db::name('order_goods')->insert($data2);
        }
        //加完订单默认调用已确认，已支付方法
        Db::name('order')->where('order_id', $order_id)->update(['order_status' => 1]);
        $this->logOrder($order_id, '管理员后台下单自动确认', '确认', 0);
        $this->update_pay_status($order_sn, ['is_admin'=>1, 'note'=>'管理员后台下单自动支付']);
        return ['status' => 1,'msg' => '提交订单成功','result' => $order_id]; // 返回新增的订单id
    }
    /**
     * 修改订单  不改变订单的状态！
     * @param $user_id      默认为匿名用户
     * @param $address      收货信息array [consignee,province,city,district,address,mobile]
     * @param $pay_code      支付方式
     * @param $goods_list    商品列表 [id:spec=>num]
     * @param string $invoice_title 发票抬头
     * @param string $admin_note    管理员备注
     * @return array
     */
    public function adminUpdateOrder($order_id, $address, $pay_code, $goods_list, $invoice_title = '', $admin_note = '')
    {
        $orderInfo = Db::name('order')->where('order_id', $order_id)->find();
        if ($orderInfo['order_status'] >= 2) {
            return ['status' => 0, 'msg' => '该订单不予许修改']; // 修改订单
        }
        //$shipping = Db::name('shipping')->where("id", $shipping_code)->find();
        $pay      = Db::name('pay_config')->where("id", $pay_code)->find();
        //根据$goods_list算价
        $price = new Price();
        $price = $price->calculate_admin_price($goods_list, $address);
        $price = $price['result'];
        $update = [
            'consignee'        => $address['consignee'], // 收货人
            'province'         => $address['province'],//'省份id',
            'city'             => $address['city'],//'城市id',
            'district'         => $address['district'],//'县',
            'address'          => $address['address'],//'详细地址',
            'mobile'           => $address['mobile'],//'手机',
            'invoice_title'    => $invoice_title, //'发票抬头',
            'goods_price'      => $price['goods_price'],//'商品价格',
            'shipping_price'   => $price['shipping_price'],//'物流价格',
            'total_amount'     => $price['total_amount'],// 订单总额
            'order_amount'     => $price['order_amount'],//'应付款金额',
            'pay_name'         => $pay['name'],//支付方式
            'pay_code'         => $pay_code
        ];
        Db::name('order')->where('order_id', $order_id)->update($update); //修改订单
        //记录订单操作日志
        $action_info = [
            'order_id'        => $order_id,
            'action_user'     => 0,
            'order_status'    => $orderInfo['order_status'],
            'pay_status'      => $orderInfo['pay_status'],
            'shipping_status' => $orderInfo['shipping_status'],
            'action_note'     => $admin_note ? $admin_note : '修改订单成功',
            'status_desc'     => '修改订单',
            'log_time'        => time(),
        ];
        Db::name('order_action')->insert($action_info);
        //将关联的商品删除  重新添加
        Db::name('order_goods')->where('order_id', $order_id)->delete();
        foreach ($goods_list as $k => $v) {
            //判断是规格商品还是普通商品
            if (strstr($k, ':') === false) {
                $info = Db::name('goods')->where('id', $k)->find();
                $specInfo = [];
                $data2['goods_id']           = $k;                 // 商品id
            } else {
                $k = explode(':', $k);
                $info = Db::name('goods')->where('id', $k[0])->find();
                $specInfo  = Db::name('spec_goods')->where('goods_id', $k[0])->where('key', $k[1])->find();
                //var_dump($specInfo);die;
                $data2['goods_id']           = $k[0];              // 商品id
                $img = Db::name('spec_image')->where(['goods_id' => $k[0], 'spec_item_id' => $specInfo['id']])->value('src');
                if ($img) {
                    $specInfo['img'] = $img;
                }
            }
            $data2['order_id']           = $order_id;              // 订单id
            $data2['goods_name']         = $info['goods_name'];    // 商品名称
            $data2['goods_sn']           = $info['goods_sn'];      // 商品货号
            $data2['goods_num']          = $v;                     // 购买数量
            $data2['market_price']       = $info['market_price'];  // 市场价
            $data2['goods_price']        = isset($specInfo['price']) ? $specInfo['price'] : $info['shop_price'];   // 商品价
            $data2['spec_key']           = isset($specInfo['key']) ? $specInfo['key'] : '';      // 商品规格
            $data2['spec_key_name']      = isset($specInfo['key_name']) ? $specInfo['key_name'] : ''; // 商品规格名称
            $data2['member_goods_price'] = $data2['goods_price'];  // 会员折扣价
            $data2['cost_price']         = $info['cost_price'];   // 成本价
            $data2['give_integral']      = $info['give_integral'] * $v;    // 购买商品赠送羊币
            $data2['prom_type']          = 0;
            $data2['img']                = isset($specInfo['img']) ? $specInfo['img'] : $info['original_img'];    //使用图片判断图片
            Db::name('order_goods')->insert($data2);
        }
        return ['status' => 1,'msg' => '修改订单']; // 修改订单
    }
    /**
     * 删除订单
     * @param type $order_id
     * @return type
     */
    public function delOrder($order_id)
    {
        $data = Db::name('order')->where('order_id', $order_id)->find();
        if (!$data) {
            return ['status' => 0, 'msg' => '订单不存在'];
        } elseif ($data['deleted']) {
            return ['status' => 0, 'msg' => '订单已经删除过了'];
        } elseif (in_array($data['order_status'], [0, 1])) { //待确认，已确认
            return ['status' => 0, 'msg' => '订单未完成，不能删除'];
        }
        $row = Db::name('order')->where('order_id' ,$order_id)->update(['deleted'=>1]);
        if (!$row) {
            return ['status' => 0,'msg' => '删除失败'];
        }
        return ['status' => 1,'msg' => '删除成功'];
    }
    /**
     * 用户取消订单
     * @param $user_id  用户ID
     * @param $order_id 订单ID
     * @param string $action_note 操作备注
     * @return array
     */
    public function cancel_order($user_id, $order_id, $action_note='您取消了订单')
    {
        $order = Db::name('order')->where(['order_id' => $order_id, 'user_id' => $user_id])->find();
        if (empty($order))
            return ['status' => 0, 'msg' => '订单不存在', 'result' => ''];
        if ($order['order_status'] == 3) {
            return ['status' => 0, 'msg' => '该订单已取消', 'result'=>''];
        }
        //确认订单不能取消
        if($order['order_status'] > 0)
            return ['status' => 0, 'msg' => '支付状态或订单状态不允许', 'result' => ''];
        Db::startTrans();
        try {
            $res1=$res2=['code'=>1];
            //有羊币的情况退还羊币
            $users = new Users();
            if ($order['integral']) {
                //退还羊币
                $res1 = $users->userIncIntegral($user_id, $order['integral']);
                IntegralLog::operate($user_id, $order['integral'], 1, 1,'订单取消返还羊币',0);
            }
            $res3=1;
            if ($order['user_money']>0) {
                //退还余额
                $res2 = $users->userIncMoney($user_id, $order['user_money']);
                //MoneyLog::operate($user_id, $order['user_money'], 4, 1,'订单取消返还余额',0);
                $res3 = Db::name('log_income')->insert(['uid'=>$user_id,'order_sn'=>$order['order_sn'],'money'=>$order['user_money'],'type'=>13,'create_time'=>time()]);
                //$res3 = 0;
            }
            //TODO 退还优惠卷等
            $res4 = Db::name('order')->where(['order_id' => $order_id, 'user_id' => $user_id])->update(['order_status' => 3]);
            $data['order_id']        = $order_id;
            $data['action_user']     = $user_id;
            $data['action_note']     = $action_note;
            $data['order_status']    = 3;
            $data['pay_status']      = $order['pay_status'];
            $data['shipping_status'] = $order['shipping_status'];
            $data['log_time']        = time();
            $data['status_desc']     = '用户取消订单';
            $res5 = Db::name('order_action')->insert($data);//订单操作记录
            if($res1['code']==1 && $res2['code']==1 && $res3 && $res4 && $res5){
                Db::commit();
            }else{
                Db::rollback();
                return ['status'=>0,'msg'=>'操作失败~'];
            }
        }catch(\Exception $e){
            // 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>'操作失败！'.$e->getMessage()];
        }

            //发送短信通知
            //$order_type = [1=>'代理',2=>'代售',3=>'自营'];
            //$user_info = $users->userinfo($user_id);
            //\app\common\service\Msg::send_sms(0, $user_info->mobile, [ 'content' => '亲爱的'.$user_info->account.'，您的'.$order_type[$order['order_group']].'订单'.$order['order_sn'].'已经取消，请注意查看！']);
            return ['status' => 1, 'msg' => '操作成功', 'result' => ''];

    }

    /**
     * 支付完成修改订单 -->  将订单改为已支付订单，并减少库存，增加销量，记录操作记录
     * $ext is_admin=>1 管理员操作  note=>'操作日志'
     */
    public function update_pay_status($order_sn, $ext = [])
    {
        //将订单变为已支付订单
        $order = Db::name('order')->where("order_sn", $order_sn)->find();

        if ($order['pay_status'] == 1) {
            return ['code' => 1, 'msg' => '该订单已经支付'];
        }

        $updata = ['pay_status' => 1,'order_status'=>1,'pay_time' => time()];
        if (isset($ext['pay_code']) && !empty($ext['pay_code'])) {
            $updata['pay_code'] = $ext['pay_code'];
            $updata['pay_name'] = Db::name('pay_config')->where('id', $ext['pay_code'])->value('name');
        }
        Db::startTrans();
        try {
            $res1 = Db::name('order')->where("order_sn", $order_sn)->update($updata);
            $res2 = true;
            if($order['order_group'] == 1){
                //若订单是代理订单，判断用户是否是代理，不是的话把用户设为代理
                $user = Db::name('member')->where('id',$order['user_id'])->find();
                if($user['dl_time'] < 1){
                    $res2 = Db::name('member')->where('id',$order['user_id'])->update(['dl_time'=>time()]);
                }
            }elseif($order['order_group'] == 2){
                //若订单是代售订单，设置订单的本金和收益返还时间为7天后的下一个半点
                if(date('i') >= 30){
                    $settle_time = strtotime(date('Y-m-d H:').'30')+(86400*7)+3600;
                }else{
                    $settle_time = strtotime(date('Y-m-d H:').'30')+(86400*7);
                }
                $res2 = Db::name('order')->where('order_sn',$order_sn)->update(['settle_time'=>$settle_time]);//
            }
            $res3 = \app\common\service\Income::recommend_income($order_sn);//给各级代理分成
            if(!$res1 || !$res2 || $res3['status']==0){
                $err = '订单状态修改异常';
                if($res3['status'] == 0){
                    $err = $res3['msg'];
                }
                throw new \Exception($err);
            }
            Db::commit();
        }catch(\Exception $e){
            // 回滚事务
            Db::rollback();
            return ['code'=>0,'msg'=>$e->getMessage()];
        }

        //判断是否有抢购商品，有的话增加数量
        $order_goods = Db::name('order_goods')->where('order_id', $order['order_id'])->select();
        foreach ($order_goods as $k => $v) {
            if ($v['prom_type'] == 1) {
                Db::name('flash_sale')->where('id', $v['prom_id'])->setInc('buy_num', $v['goods_num']);
            }
        }
        //减库存
        $this->minus_stock($order['order_id']);
        //记录订单操作日志
        if (isset($ext['is_admin'])) {
            $note = isset($ext['note']) && !empty($ext['note']) ? $ext['note'] : '订单付款成功';
            $this->logOrder($order['order_id'], $note, '付款成功', 0);
        } else {
            $this->logOrder($order['order_id'], '订单付款成功', '付款成功', $order['user_id']);
        }
        //推送消息
        if ($order['user_id'] && $order['order_group']==1) {
            $user_info = Db::name('member')->where('id', $order['user_id'])->find();
            //\app\common\service\Msg::send(3, $order['user_id'], ['name' => $name, 'order_id' => $order_sn]);
            \app\common\service\Msg::send_sms(0, $user_info['mobile'], [ 'content' => '亲爱的'.$user_info['account'].'，您的代理订单'.$order_sn.'已经支付成功，马上为您安排发货，请耐心等待']);
        }
        return ['code' => 1, 'msg' => '订单支付成功'];

    }

    /**
     * 支付取消修改订单 -->  将订单改为未支付订单，并增加库存，减少销量，记录操作记录
     */
    public function update_paycancel_status($order_sn, $ext = [])
    {
        //将订单变为已支付订单
        $order = Db::name('order')->where("order_sn", $order_sn)->find();
        if ($order['pay_status'] == 0) {
            return ['code' => 0, 'msg' => '该订单还未支付'];
        }
        //区分预售订单
        if ($order['order_prom_type'] == 4) {
            //TODO
        } else {
            $updata = ['pay_status' => 0];
            $res = Db::name('order')->where("order_sn", $order_sn)->update($updata);
        }
        if ($res !== false) {
            //增加
            $this->rever_minus_stock($order['order_id']);
            //记录订单操作日志
            if (isset($ext['is_admin'])) {
                $note = isset($ext['note']) && !empty($ext['note']) ? $ext['note'] : '订单取消付款成功';
                $this->logOrder($order['order_id'], $note, '付款取消', 0);
            } else {
                $this->logOrder($order['order_id'], '订单取消付款成功', '付款取消', $order['user_id']);
            }
            return ['code' => 1, 'msg' => '订单付款取消成功'];
        }
        return ['code' => 0, 'msg' => '付款取消失败'];
    }

    /**
     * 确认收货
     */
    public function confirm_order($order_sn, $ext = [])
    {
        $order = Db::name('order')->where("order_sn", $order_sn)->find();
        if ($order['order_status'] != 1)
            return ['status' => 0,'msg' => '该订单不能收货确认'];
        if (empty($order['pay_time']) || $order['pay_status'] == 0) {
            return ['status' => 0,'msg' => '商家未确定付款，该订单暂不能确定收货'];
        }
        if ($order['pay_status'] == 3 || $order['pay_status'] == 2) {
            return ['status' => 0,'msg' => '该订单已申请退款或正在申请退款，暂不能确定收货'];
        }
        $updata['order_status'] = 2; // 已收货
        //$updata['pay_status']   = 1; // 已付款
        $updata['confirm_time'] = time(); // 收货确认时间
        $res = Db::name('order')->where("order_sn", $order_sn)->update($updata);
        if ($res == false) {
            return ['status' => 0,'msg' => '操作失败'];
        }
        $this->order_give($order);// 调用送礼物方法, 给下单这个人赠送相应的礼物
        //记录
        if (isset($ext['is_admin'])) {
            $note = isset($ext['note']) && !empty($ext['note']) ? $ext['note'] : '订单确认收货成功';
            $this->logOrder($order['order_id'], $note, '确认收货', 0);
        } else {
            $this->logOrder($order['order_id'], '订单确认收货成功', '确认收货', $order['user_id']);
        }
        return ['status' => 1,'msg' => '操作成功'];
    }

    /**
     * 退货管理
     * 订单的单个商品
     * @param $rec_id            order_goods自增主键 用来标识哪个商品退货
     * @param $refund_type       退款类型  0仅退款  1退货退款
     * @param $imgs              图片
     * @param $reason            退款原因
     * @param $reason            问题描述
     * @return array
     */
    public function add_return_goods($order_id, $refund_type = 0, $imgs = [], $reason = '', $describe = '')
    {
        //① 先根据order_id  查出订单信息和商品订单信息
        $order = Db::name('order')->where('order_id', $order_id)->find();
        if (!$order) {
            return ['code' => 0, 'msg' =>'没有找到该订单', 'result' =>''];
        }
        if (!$order['pay_status']) {
            return ['code' => 0, 'msg' =>'该订单没有支付，不能申请退款', 'result' =>''];
        }
        if ($order['pay_status'] == 2) {
            return ['code' => 0, 'msg' =>'该订单正在申请退款，请不要重复申请', 'result' =>''];
        }
        if ($order['pay_status'] == 3) {
            return ['code' => 0, 'msg' =>'该订单已申请退款成功，请不要重复申请', 'result' =>''];
        }
        //退货
        $return_money = $return_deposit = 0;
        if ($order['shipping_status'] > 0 && $order['shipping_price']) {
            //不退物流费  优先减现金
            if ($order['order_amount']) {
                $return_money = $order['order_amount'] - $order['shipping_price'];
                if ($return_money < 0) {
                    //再用余额
                    $return_deposit = $order['user_money'] + $return_money;
                    $return_money   = 0;
                }
            }
        } else {
            $return_deposit = $order['user_money'];
            $return_money   = $order['order_amount'];
        }
        if ($return_money < 0 || $return_deposit < 0) {
            return ['code' => 0, 'msg' =>'申请退款出错', 'result' =>''];
        }
        $data = [
            'order_id'        => $order_id,
            'order_sn'        => $order['order_sn'],
            'reason'          => $reason,
            'describe'        => $describe,
            'imgs'            => $imgs ? json_encode($imgs) : '',
            'addtime'         => time(),
            'status'          => 0,
            'user_id'         => $order['user_id'],
            'refund_money'    => $return_money,
            'refund_deposit'  => $return_deposit,
            'refund_integral' => $order['integral'],
            'refund_type'     => $refund_type
        ];
        $res = Db::name('return_goods')->insert($data);
        if ($res) {
            $id = Db::name('return_goods')->getLastInsID();
            //修改订单状态
            Db::name('order')->where('order_id', $order_id)->update(['pay_status' => 2]);
            return ['code' => 1, 'msg' =>'申请成功', 'result' => $id];
        } else {
            return ['code' => 0, 'msg' =>'申请失败', 'result' => ''];
        }
    }
    /*public function add_return_goods($rec_id, $refund_type = 0, $imgs = [], $reason = '', $describe = '')
    {
        //① 先根据rec_id  查出订单信息和商品订单信息
        $orderGoods = Db::name('orderGoods')->where('rec_id', $rec_id)->find();
        $order      = Db::name('order')->where('order_id', $orderGoods['order_id'])->find();
        $goods      = Db::name('goods')->where('id', $orderGoods['goods_id'])->find();
        if (!$orderGoods || !$order || !$goods) {
            return ['code' => 0, 'msg' =>'没有找到该订单', 'result' =>''];
        }
        //② 看是否已提交过退货申请
        if (Db::name('return_goods')->where(['rec_id' => $rec_id, 'status' => ['in', [0, 1]]])->find()) {
            return ['code' => 0, 'msg' =>'已经提交过退货申请', 'result' =>''];
        }
        //③ 区分订单类型
        if ($orderGoods['prom_type'] == 0) {
            //普通订单
            $return_money = $orderGoods['member_goods_price'] * $orderGoods['goods_num'];
            //计算退羊币  如果用户是钱+羊币购买的，则优先退还羊币
            $return_integral = 0;//默认不退回羊币
            if ($order['integral_money'] && $goods['exchange_integral']) {
                $exist_return_integral = Db::name('return_goods')->where(['order_id' => $orderGoods['order_id'], 'status' => ['>=', 0]])->sum('refund_integral');
                $last_integral = $order['integral'] - $exist_return_integral;
                if ($last_integral) {
                    $return_integral = $goods['exchange_integral'] * $orderGoods['goods_num'];
                    $return_integral = $return_integral  > $last_integral ? $last_integral : $return_integral;
                }
                $point_rate = Config::get('point_rate');
                $point_rate = $point_rate ? $point_rate : 100;
                $return_money -= $return_integral / $point_rate;
            }
            //计算兑换余额
            $return_deposit = 0;//默认不退回羊币
            if ($order['user_money']) {
                $exist_refund_deposit = Db::name('return_goods')->where(['order_id' => $orderGoods['order_id'], 'status' => ['>=', 0]])->sum('refund_deposit');
                $last_deposit  = $order['user_money'] - $exist_refund_deposit;
                if ($last_deposit > 0) {
                    $return_deposit = $return_money > $last_deposit ? $last_deposit : $return_money;
                }
                $return_money -= $return_deposit;
            }
            //运费的判断
            if ($order['shipping_status'] == 0) {
                //还没发货  要判断是否最后一个物品，如果是最后一个物品 要退物流费用
                if (!Db::name('orderGoods')->where(['order_id' => $order['order_id'], 'status' => 0, 'rec_id' => ['neq', $rec_id]])->count()) {
                    $return_money += $order['shipping_price'];
                }
            }
            //加入到退货申请单中
            $data = [
                'rec_id'          => $rec_id,
                'order_id'        => $orderGoods['order_id'],
                'order_sn'        => $order['order_sn'],
                'goods_id'        => $orderGoods['goods_id'],
                'goods_num'       => $orderGoods['goods_num'],
                'goods_img'       => $orderGoods['img'],
                'reason'          => $reason,
                'describe'        => $describe,
                'imgs'            => $imgs ? json_encode($imgs) : '',
                'addtime'         => time(),
                'status'          => 0,
                'user_id'         => $order['user_id'],
                'spec_key'        => $orderGoods['spec_key'],
                'refund_money'    => $return_money,
                'refund_deposit'  => $return_deposit,
                'refund_integral' => $return_integral,
                'refund_type'     => $refund_type,
                'goods_name'      => $orderGoods['goods_name']
            ];
            $res = Db::name('return_goods')->insert($data);
            if ($res) {
                $id = Db::name('return_goods')->getLastInsID();
                Db::name('orderGoods')->where('rec_id', $rec_id)->update(['status' => -1]);//修改订单标识，成为退货中的订单
                if (Db::name('orderGoods')->where(['order_id' => $order['order_id'], 'status' => 0])->count()) {
                    //修改订单状态 已取消
                }
                return ['code' => 1, 'msg' =>'申请成功', 'result' => $id];
            }
        }
        return ['code' => 0, 'msg' =>'申请失败', 'result' => ''];
    }*/
    /**
     * 退换货审核处理
     * -2用户取消-1审核不通过0待审核1通过
     */
    public function returnGoodsStatus($id, $status)
    {
        $return_goods = Db::name('return_goods')->where('id', $id)->find();
        if ($status == -2 || $status == -1) {
            Db::name('return_goods')->where('id', $id)->update(['status' => $status, 'refund_time' => time()]);
            if ($status == -2) {
                Db::name('order')->where('order_id', $return_goods['order_id'])->update(['pay_status' => 1]);
            } else {
                Db::name('order')->where('order_id', $return_goods['order_id'])->update(['pay_status' => 4]);
                //TODO  拒绝退款进行通知
                //推送消息
                $member = new Member();
                $name = $member->where('id', $return_goods['user_id'])->value('nickname');
                \app\common\service\Msg::send(6, $return_goods['user_id'], ['name' => $name, 'order_id' => $return_goods['order_sn']]);
            }
        } elseif ($status == 1) {
            //审核通过
            if ($return_goods['refund_money'] > 0) {
                //退还付款，直接调支付进行退款
                $payment = new Payment();
                $res = $payment->refunds($return_goods['order_sn'], $return_goods['refund_money']);
                if ($res['code'] != 1) {
                    return ['code' => 0, 'msg' =>$res['msg']];
                }
            }
            $users = new Users();
            if ($return_goods['refund_integral'] > 0) {
                //退还羊币
                $users->userIncIntegral($return_goods['user_id'], $return_goods['refund_integral']);
                IntegralLog::operate($return_goods['user_id'], $return_goods['refund_integral'], 2, 1,'订单退款返还羊币',0);
            }
            if ($return_goods['refund_deposit'] > 0) {
                //退还余额
                $users->userIncMoney($return_goods['user_id'], $return_goods['refund_deposit']);
                MoneyLog::operate($return_goods['user_id'], $return_goods['refund_deposit'], 4, 1,'订单退款返还余额');
            }
            //TODO 这里没有进行事务判断 需优化啊
            Db::name('return_goods')->where('id', $id)->update(['status' => $status, 'refund_time' => time()]);
            //增加库存
            $order_goods = Db::name('order_goods')->where('order_id', $return_goods['order_id'])->select();
            foreach ($order_goods as $v) {
                if ($v['spec_key']) {
                    Db::name('spec_goods')->where(['goods_id' => $v['goods_id'], 'key' => $v['spec_key']])->setInc('store_count', $v['goods_num']);
                }
                Db::name('goods')->where(['id' => $v['goods_id']])->setInc('store_count', $v['goods_num']);
            }
            //查看订单是否收货了收货了就把送的羊币拿回来
            $order = Db::name('order')->where('order_id', $return_goods['order_id'])->find();
            if ($order['order_status'] == 2 || $order['order_status'] == 4) {
                if ($give_integral = Db::name('order_goods')->where('order_id', $return_goods['order_id'])->sum('give_integral')) {
                    //收回羊币
                    $users->userDecIntegral($return_goods['user_id'], $give_integral);
                    IntegralLog::operate($return_goods['user_id'], -$give_integral, 1, 1,'订单退款收回赠送羊币',0);
                }
            }
            //判修改订单状态
            Db::name('order')->where('order_id', $return_goods['order_id'])->update(['order_status' => 3, 'pay_status' => 3]);
            //TODO  同意退款进行通知
            $member = new Member();
            $name = $member->where('id', $return_goods['user_id'])->value('nickname');
            \app\common\service\Msg::send(5, $return_goods['user_id'], ['name' => $name, 'order_id' => $return_goods['order_sn']]);
        }
        return ['code' => 1, 'msg' =>'处理成功'];
    }


    /*******************************************************************************************************************
                                                  相关工具类代码
     ******************************************************************************************************************/
    /**
     * 订单操作日志
     * 参数示例
     * @param type $order_id  订单id
     * @param type $action_note 操作备注
     * @param type $status_desc 操作状态  提交订单, 付款成功, 取消, 等待收货, 完成
     * @param type $user_id  用户id 默认为管理员
     * @return boolean
     */
    public function logOrder($order_id, $action_note, $status_desc, $user_id = 0)
    {
        $order = Db::name('order')->where("order_id", $order_id)->find();
        $action_info = [
            'order_id'        => $order_id,
            'action_user'     => $user_id,
            'order_status'    => $order['order_status'],
            'shipping_status' => $order['shipping_status'],
            'pay_status'      => $order['pay_status'],
            'action_note'     => $action_note,
            'status_desc'     => $status_desc,
            'log_time'        => time(),
        ];
        Db::name('order_action')->insert($action_info);
    }
    /**
     * 刷新商品库存, 如果商品有设置规格库存, 则商品总库存 等于 所有规格库存相加
     * @param type $goods_id  商品id
     */
    public function refresh_stock($goods_id)
    {
        $count = Db::name('spec_goods')->where("goods_id", $goods_id)->count();
        if($count == 0) return false; // 没有使用规格方式 没必要更改总库存
        $store_count = Db::name('spec_goods')->where("goods_id", $goods_id)->sum('store_count');
        Db::name('goods')->where("id", $goods_id)->update(array('store_count'=>$store_count)); // 更新商品的总库存
    }
    /**
     * 减少库存
     * @param type $order_id  订单id
     */
    public function minus_stock($order_id)
    {
        $orderGoodsArr = Db::name('order_goods')->where("order_id", $order_id)->select();
        foreach ($orderGoodsArr as $key => $val) {
            // 有选择规格的商品
            if (!empty($val['spec_key'])) {
                // 先到规格表里面扣除数量 再重新刷新一个 这件商品的总数量
                Db::name('spec_goods')->where(['goods_id' => $val['goods_id'],'key' => $val['spec_key']])->setDec('store_count', $val['goods_num']);
                $this->refresh_stock($val['goods_id']);
            } else {
                Db::name('goods')->where("id", $val['goods_id'])->setDec('store_count', $val['goods_num']); // 直接扣除商品总数量
            }
            Db::name('goods')->where("id", $val['goods_id'])->setInc('sales_num', $val['goods_num']); // 增加商品销售量
        }
    }
    /**
     * 减少库存的逆向操作
     * 用于订单取消，库存增加
     */
    public function rever_minus_stock($order_id)
    {
        $orderGoodsArr = Db::name('order_goods')->where("order_id", $order_id)->select();
        foreach ($orderGoodsArr as $key => $val) {
            // 有选择规格的商品
            if (!empty($val['spec_key'])) {
                // 先到规格表里面增加数量 再重新刷新一个 这件商品的总数量
                Db::name('spec_goods')->where(['goods_id' => $val['goods_id'],'key' => $val['spec_key']])->setInc('store_count', $val['goods_num']);
                $this->refresh_stock($val['goods_id']);
            } else {
                Db::name('goods')->where("id", $val['goods_id'])->setInc('store_count', $val['goods_num']); // 直接增加商品总数量
            }
            Db::name('goods')->where("id", $val['goods_id'])->setDec('sales_num', $val['goods_num']); // 减少商品销售量
        }
    }
    /**
     * 获取订单 order_sn
     * @return string
     */
    public function get_order_sn()
    {
        $order_sn = null;
        // 保证不会有重复订单号存在
        while(true){
            $microtime_str = microtime();
            $microtime = substr($microtime_str,strpos($microtime_str,'.')+1,6);//获取当前时间的毫秒级
            $order_sn = date('YmdHis').$microtime.rand(1000,9999); // 订单编号
            $order_sn_count = Db::name('order')->where("order_sn = ".$order_sn)->count();
            if($order_sn_count == 0)
                break;
        }

        return $order_sn;
    }
    /**
     * 赠送礼物方法
     */
    public function order_give($order)
    {
        $integral = Db::name('order_goods')->where(['order_id' => $order['order_id']])->sum('give_integral');
        if ($integral) {
            $users = new Users();
            $users->userIncIntegral($order['user_id'], $integral);
            IntegralLog::operate($order['user_id'], $integral, 2, 1,'订单完成赠送羊币',0);
        }
    }
}