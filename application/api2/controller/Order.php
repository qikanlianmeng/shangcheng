<?php
/**
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/25
 * Time: 8:59
 */

namespace app\api2\controller;

use think\Db;
use think\Config;
use app\common\model\Price;
use app\common\model\Address;
use app\common\model\Order as OrderModel;
use think\Cache;
use app\common\service\Payment;
use app\common\model\Member;
use think\Session;


class Order extends Base
{
    public function _initialize()
    {
        header("Content-type:text/html;charset=utf-8");
        parent::_initialize(); // TODO: Change the autogenerated stub
    }
    /**
     * 确认订单  调用此方法时，用户必须添加过邮寄地址
     * @return \think\response\Json
     */
    public function confirm_order()
    {
        $user_id = $this->uid;
        //调取购物车商品信息
        $cartList  = DB::name('cart')->where(['user_id' => $user_id, 'selected' => 1])->select();
        if (!$cartList) {
            return json(['status' => 0, 'msg' => '购物车没有选中任何商品']);
        }
        $user_id = $this->uid;
        //检查商品
        $check_order_res = OrderModel::check_order_goods($user_id);
        if(is_array($check_order_res) && $check_order_res['status']==0){
            return json($check_order_res);
        }
        //调取收货地址
        $addrModel = new Address();
        $address = $addrModel->getNormalAddress();
        if (!$address['status']) {
            return json(['status' => 0, 'msg' => '请先设置物流地址']);
        }
        $address = $address['data'];

        //快递运费
        $priceModel = new Price();
        $goodsPrice = $priceModel->calculate_price($user_id, $cartList, 0, $address['province'], $address['city'], $address['district']);
        if ($goodsPrice['status'] == 0) {
            return json(['status' => 0, 'msg' => $goodsPrice['msg']]);
        }
        //羊币抵用
        $goods_ids = [];
        foreach ($cartList as $k => $v) {
            $goods_ids[] = $v['goods_id'];
        }
        $exchange_integral = Db::name('goods')->where('id', 'in', $goods_ids)->column('exchange_integral','id');
        $integral = 0;
        foreach ($cartList as $v) {
            $integral = $v['goods_num'] * $exchange_integral[$v['goods_id']];
        }
        $member   = new member();
        $user_integral     = $member->where('id', $user_id)->value('integral');
        $user_money        = $member->where('id', $user_id)->value('money');
        $user_money        = $user_money > $goodsPrice['result']['order_amount'] ? $goodsPrice['result']['order_amount'] : $user_money; //保证余额抵用不会超过商品可付金额
        //$integral = $integral > $user_integral ? $user_integral : $integral;//保证可抵扣羊币不会超过用户的所有羊币
        //$point_rate = Config::get('point_rate');
        //$exchange_integral_money = $integral/$point_rate;
        $return = [
            'address'                 => $address,                   //地址信息
            'goods_list'              => $cartList,                  //订单商品列表
            'goods_price'             => $goodsPrice['result'],      //价格清单
            //'exchange_integral'       => (int) $integral,         //可兑换羊币
            //'exchange_integral_money' => $exchange_integral_money,   //可兑换羊币对应的金额
            'user_money'              => (float) $user_money                 //用户余额
         ];
        return json(['status' => 1, 'msg' => '确认订单中', 'data' => $return]);
    }

    /**
     * 提交订单
     */
    public function add_order($address_id, $pay_points = 0, $user_money = 0, $content = '',$discount=0)
    {
        /*if (!$user_id = get_uid()) {
            return json(['status' => 0, 'msg' => '未登录，请先登录']);
        }*/
        $user_id = $this->uid;
        //检查商品
        $check_order_res = OrderModel::check_order_goods($user_id);
        if(is_array($check_order_res) && $check_order_res['status']==0){
            return json($check_order_res);
        }
        //调取购物车商品信息
        $cartList  = DB::name('cart')->where(['user_id' => $user_id, 'selected' => 1])->select();
        if (!$cartList) {
            return json(['status' => 0, 'msg' => '购物车没有选中任何商品']);
        }
        $order_group = $cartList[0]['goods_group'];
        //只有代售订单才启用订单折扣
        if($order_group!=2 || config('dy_discount')==0) $discount=0;

        foreach ($cartList as $k => $v) {
            if ($v['prom_type'] == 1) {
                //抢购订单  判断是否可以购买
                $flash_sale = new \app\common\model\FlashSale($v['prom_id']);
                if ($flash_sale->checkFlashSaleIsEnd()) {
                    return json(['status' => 0, 'msg' => $v['goods_name'].'-抢购活动已结束']);
                }
                if (!$flash_sale->checkActivityIsAble()) {
                    return json(['status' => 0, 'msg' => $v['goods_name'].'-抢购活动未开始']);
                }
                $goods_num = $flash_sale->getUserFlashResidueGoodsNum($user_id, $v['goods_num']);
                if ($v['goods_num'] > $goods_num) {
                    return json(['status' => 0, 'msg' => $v['goods_name'].'-抢购数量不足，您还能购买'.$goods_num.'件']);
                }
            }
        }
        $priceModel = new Price();
        $addrModel = new Address();
        //调取收货地址
        $address = $addrModel->getNormalAddress($address_id);
        if (!$address['status']) {
            return json(['status' => 0, 'msg' => '请先设置物流地址']);
        }
        $address = $address['data'];
        //计算商品价格
        $goodsPrice = $priceModel->calculate_price($user_id, $cartList, 0, $address['province'], $address['city'], $address['district'], $pay_points, $user_money,0,'',$discount);
        if ($goodsPrice['status'] == 0) {
            return json(['status' => 0, 'msg' => $goodsPrice['msg']]);
        }
        $goodsPrice = $goodsPrice['result'];
        $car_price = [  'postFee'          => $goodsPrice['shipping_price'],//物流费用
                        'couponFee'        => 0,//优惠卷
                        'pointsFee'        => $goodsPrice['integral_money'],//羊币抵消金额
                        'payables'         => $goodsPrice['order_amount'],//应付金额
                        'goodsFee'         => $goodsPrice['goods_price'],//商品总额
                        'balance'          => $goodsPrice['user_money'],
                        'order_prom_id'    => 0,//优惠活动ID
                        'order_prom_amount'=> 0//优惠金额
        ];
        $orderModel = new OrderModel();
        $res = $orderModel->addOrder($user_id, $address_id, $car_price, $content,$discount);
        if ($res['status']) {
            $data['order_id']     = $res['result'];
            $data['order_amount'] = $car_price['payables'];
            return json(['status' => 1, 'msg' => '提交订单成功', 'data' => $data]);
        }
        return json(['status' => 0, 'msg' => $res['msg'], 'data' => '']);
    }

    /**
     * @param int $group :1代理订单，2代售订单，3自营订单
     * @param int $type // 0全部订单   1代付款   2代发货    3待收货    4待评价
     * @param int $page
     * @param int $count
     * @return \think\response\Json
     */
    public function order_list($group=0,$type = 0, $page = 1, $count = 10)
    {
        if($group > 0){
            $map['order_group'] = $group;
        }
        
        $map['deleted'] = ['not in',[1,3]];
        $map['user_id'] = $this->uid;
        switch ($type) {
            case 1 :
                //代付款
                $map['pay_status']   = 0;
                $map['order_status'] = ['in', [0, 1]];
                $map['shipping_status'] = 0;
                break;
            case 2 :
                //代发货
                $map['pay_status']      = ['in', [1, 4]];
                $map['order_status']    = ['in', [0, 1]];
                $map['shipping_status'] = 0;
                break;
            case 3 :
                //待收货
                $map['pay_status']      = ['in', [1, 4]];
                $map['order_status']    = 1;
                $map['shipping_status'] = 1;
                break;
            case 4 :
                //待评价
                $map['pay_status']      = ['in', [1, 4]];
                $map['order_status']    = 2;
                $map['shipping_status'] = 1;
                break;
            default :
                $map['order_status']    = ['neq', 5];//不是作废订单
        }
        $subsql = Db::table('think_log_income')->where(['type'=>2,'uid'=>$this->uid])->field('money,order_sn')->buildSql();
        $orderList = Db::name('order')->where($map)->order('order_id desc')->page($page, $count)->select();
        if ($orderList) {
            //查询订单商品信息
            foreach ($orderList as $k => $v) {
                //var_dump($v['income']);exit;
                $orderList[$k]['income'] = $v['dy_income'];
                $orderList[$k]['settle_time'] = $orderList[$k]['settle_time']>0?date('Y-m-d H:i',$orderList[$k]['settle_time']):'未支付';
                //echo $v['income'];exit;
                $orderList[$k]['goods_list'] = Db::name('order_goods')->where('order_id', $v['order_id'])->select();
                $orderList[$k]['goods_num'] = 0;
                foreach ($orderList[$k]['goods_list'] as $v) {
                    $orderList[$k]['goods_num'] += $v['goods_num'];
                }
            }
            return json(['status' => 1, 'msg' => '获取订单列表成功', 'data' => $orderList]);
        }
        return json(['status' => 0, 'msg' => '没有订单数据了']);
    }

    /**
     * 获取订单详情
     * @param $id   订单ID
     * @is_simple 0返回全部  1只返回订单的基本信息
     * @return \think\response\Json
     */
    public function order_detail($id, $is_simple = 0)
    {
        $order_id = $id;
        
        $orderInfo = Db::name('order')->where(['user_id' => $this->uid, 'order_id' => $order_id])->find();
        if (!$orderInfo) {
            return json(['status' => 0, 'msg' => '订单不存在']);
        }
        //收货地址
        if (!$region = Cache::get('regiondata')) {
            $region    = Db::name('region')->column('name','id');
            Cache::set('regiondata', $region, 0);
        }
        $addr = '';
        isset($region[$orderInfo['province']]) && $addr .= $region[$orderInfo['province']] . '，';
        isset($region[$orderInfo['city']])     && $addr .= $region[$orderInfo['city']] . '，';
        isset($region[$orderInfo['district']]) && $addr .= $region[$orderInfo['district']] . '，';
        $orderInfo['address']                  && $addr .= $orderInfo['address'];
        $orderInfo['address_detail'] = trim(str_replace('，', ' ', $addr));
        if ($is_simple == 0) {
            //订单商品列表
            $orderInfo['goods_list'] = Db::name('order_goods')->where('order_id', $orderInfo['order_id'])->select();
            //购物返羊币
            $orderInfo['give_integral'] = Db::name('order_goods')->where('order_id', $orderInfo['order_id'])->sum('give_integral');
        }
        //是否存在退款
        $orderInfo['return_id'] = 0;
        if ($orderInfo['pay_status'] > 1) {
            $orderInfo['return_id'] = Db::name('return_goods')->where(['order_id' => $order_id, 'status' => ['neq', -2]])->value('id');
        }
        //时间格式转换
        $orderInfo['add_time'] && $orderInfo['add_time'] = date('Y-m-d H:i:s', $orderInfo['add_time']);
        $orderInfo['shipping_time'] && $orderInfo['shipping_time'] = date('Y-m-d H:i:s', $orderInfo['shipping_time']);
        $orderInfo['confirm_time'] && $orderInfo['confirm_time'] = date('Y-m-d H:i:s', $orderInfo['confirm_time']);
        $orderInfo['pay_time'] && $orderInfo['pay_time'] = date('Y-m-d H:i:s', $orderInfo['pay_time']);
        return json(['status' => 1, 'msg' => '获取订单详情成功', 'data' => $orderInfo]);
    }

    /**
     * 申请退款
     * @param $rec_id            商品订单
     * @param $refund_type       退款类型  0仅退款  1退货退款
     * @param $imgs              图片,多个图片用@分隔
     * @param $reason            退款原因
     * @param $describe            问题描述
     * @return \think\response\Json
     */
    public function return_goods($order_id, $refund_type = 0, $imgs = [], $reason = '', $describe = '')
    {
        if (!$user_id = get_uid()) {
            return json(['status' => 0, 'msg' => '未登录，请先登录']);
        }
        //先判断能否进行申请退款
        $orderInfo = Db::name('order')->where('order_id', $order_id)->find();
        if ($orderInfo['user_id'] != $user_id) {
            return json(['status' => 0, 'msg' => '无权限操作']);
        }
        if ($orderInfo['pay_status'] == 0) {
            return json(['status' => 0, 'msg' => '订单未支付不能退货']);
        }
        $orderModel = new OrderModel();
        $res = $orderModel->add_return_goods($order_id, $refund_type, $imgs, $reason, $describe);
        return json(['status' => $res['code'], 'msg' => $res['msg'], 'data' => $res['result']]);
    }

    /**
     * 退款详情
     * @param $id  return_goods的主键ID
     * @return \think\response\Json
     */
    public function return_detail($id)
    {
        if (!$user_id = get_uid()) {
            return json(['status' => 0, 'msg' => '未登录，请先登录']);
        }
        $returnInfo = Db::name('return_goods')->where(['user_id' => $user_id, 'id' => $id])->find();
        if (!$returnInfo) {
            return json(['status' => 0, 'msg' => '未找到该申请详情']);
        }
        $returnInfo['addtime'] = date('Y-m-d H:i:s', $returnInfo['addtime']);
        $returnInfo['refund_time'] && $returnInfo['refund_time'] = date('Y-m-d H:i:s', $returnInfo['refund_time']);
        if ($returnInfo['imgs']) {
            $returnInfo['imgs']    = json_decode($returnInfo['imgs'], true);
            //兼容前端模板写法
            $imgs = [];
            foreach ($returnInfo['imgs'] as $v) {
                $imgs[]['url'] = $v;
            }
            $returnInfo['imgs'] = $imgs;
        }
        $returnInfo['goods_list'] = Db::name('order_goods')->where('order_id', $returnInfo['order_id'])->select();
        return json(['status' => 1, 'msg' => '查询退款详情成功', 'data' => $returnInfo]);
    }

    /**
     * 退款记录列表
     * @param int $page
     * @param int $num
     * @return \think\response\Json
     */
    public function return_list($page = 1, $num = 8)
    {
        if (!$user_id = get_uid()) {
            return json(['status' => 0, 'msg' => '未登录，请先登录']);
        }
        $returnList = Db::name('return_goods')->where(['user_id' => $user_id, 'status' => ['neq', -2]])->order('id desc')->page($page, $num)->select();
        foreach ($returnList as $k => $v) {
            $returnList[$k]['goods_list'] = Db::name('order_goods')->where('order_id', $v['order_id'])->select();
        }
        return json(['status' => 1, 'msg' => '获取退款记录列表成功', 'data' => $returnList]);
    }

    /**
     * 取消退款
     * @param $id return_goods的主键ID
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function cancel_return($id)
    {
        if (!$user_id = get_uid()) {
            return json(['status' => 0, 'msg' => '未登录，请先登录']);
        }
        $returnInfo = Db::name('return_goods')->where(['user_id' => $user_id, 'id' => $id, 'status' => ['neq', -2]])->find();
        if (!$returnInfo) {
            return json(['status' => 0, 'msg' => '未找到该申请详情']);
        }
        if ($returnInfo['status'] == 1) {
            return json(['status' => 0, 'msg' => '该申请已被处理，不能取消']);
        }
        $model = new OrderModel();
        $model->returnGoodsStatus($id, -2);
        return json(['status' => 1, 'msg' => '取消退款成功']);
    }

    /**
     * 确认收货
     * @param $id
     * @return \think\response\Json
     */
    public function delivery_confirm($id)
    {
        if (!$user_id = get_uid()) {
            return json(['status' => 0, 'msg' => '未登录，请先登录']);
        }
        $orderInfo = Db::name('order')->where(['order_id' => $id, 'user_id' => $user_id])->find();
        if (!$orderInfo) {
            return json(['status' => 0, 'msg' => '未找到该订单']);
        }
        $orderModel = new OrderModel();
        $res = $orderModel->confirm_order($orderInfo['order_sn']);
        return json($res);
    }

    /**
     * 取消订单
     * @param $id
     * @return \think\response\Json
     */
    public function cancel_order($id)
    {
        
        $orderModel = new OrderModel();
        $res = $orderModel->cancel_order($this->uid, $id);
        return json(['status' => $res['status'], 'msg' => $res['msg']]);
    }
    /**
     * 删除订单
     * @param $id
     * @return \think\response\Json
     */
    public function del_order($id)
    {
        if(Db::name('order')->where(['user_id'=>$this->uid,'order_id'=>$id,'deleted'=>0])->update(['deleted'=>1])){
            $back = ['status' => 1, 'msg' => '订单已删除'];
        }else{
            $back = ['status' => 0, 'msg' => '订单删除失败'];
        }
        return json($back);
    }

    /**
     * 支付订单
     * @param $id 订单ID
     * @param $pay_type 支付方式 1余额，2支付宝，3微信
     * @return array
     */
    public function pay_order($id, $pay_type)
    {
    
        $order_id = $id;
        if (!$user_id = get_uid()) {
            return json(['status' => 0, 'msg' => '未登录，请先登录']);
        }
        $orderInfo = Db::name('order')->where(['user_id' => $user_id, 'order_id' => $order_id])->find();
        if (!$orderInfo) {
            return json(['status' => 0, 'msg' => '没有查到该订单']);
        }
        if ($orderInfo['pay_status'] == 1) {
            return json(['status' => 0, 'msg' => '该订单已支付']);
        }
        //判断是否有抢购订单
        $order_goods = Db::name('order_goods')->where('order_id', $order_id)->select();
        $goods_name = '';
        foreach ($order_goods as $k => $v) {
            if ($v['prom_type'] == 1) {
                //抢购订单  判断是否可以购买
                $flash_sale = new \app\common\model\FlashSale($v['prom_id']);
                if ($flash_sale->checkFlashSaleIsEnd()) {
                    return json(['status' => 0, 'msg' => $v['goods_name'].'-抢购活动已结束']);
                }
                if (!$flash_sale->checkActivityIsAble()) {
                    return json(['status' => 0, 'msg' => $v['goods_name'].'-抢购活动未开始']);
                }
                $goods_num = $flash_sale->getUserFlashResidueGoodsNum($user_id, $v['goods_num']);
                if ($v['goods_num'] > $goods_num) {
                    return json(['status' => 0, 'msg' => $v['goods_name'].'-抢购数量不足，您还能购买'.$goods_num.'件']);
                }
            }
            $goods_name .= '|' . $v['goods_name'];
        }
        $payment = new Payment();
        if ($pay_type == 'alipay') {
          //$res = $payment->ali_wap($user_id, $goods_name, $orderInfo['order_sn'], $orderInfo['order_amount'], ['method' => 'shoporder', 'param' => ['order_number' => $orderInfo['order_sn'], 'param' => 2]], url('wap/order/pay_success', ['order_id' => $order_id], '', true), '购买'.$goods_name, 1);

          $res =  $payment->ali_app($user_id,$goods_name,$orderInfo['order_sn'], $orderInfo['order_amount'],['method' => 'shoporder', 'param' => ['order_number' => $orderInfo['order_sn'], 'param' => 2]]);
			
        } else{
            if(is_weixin()){
            	
               // $openid = Db::name('oauth_user')->where(['from' => 'weixin', 'uid' => $user_id])->value('openid');
                //dump(Session::get());
       // $openid ='oN2IpwFja5nryhbCzp9K2FueQQTg';
    		$openid= Session::get('openid');
    	/*	$uid=Session::get('user_auth')['uid'];
              if($uid==69870){
                //写入wechat表
                $data['uid']=$uid;
                $data['openid']=$openid;
                $data['create_time']=time();
                $re=db::name('wechat')->insert($data);
              }*/
    	/*	$uid=Session::get('user_auth')['uid'];
    		if($uid==69870){
    			dump($openid);
    			exit;
    		}*/

                $res = $payment->wx_pub($openid, $user_id, $goods_name, $orderInfo['order_sn'], $orderInfo['order_amount'], ['method' => 'shoporder', 'param' => ['order_number' => $orderInfo['order_sn'], 'param' => 3]], '购买商品' . $goods_name, 1);
           
                
            }else{
                $res =  $payment->wx_app($user_id,$goods_name, $orderInfo['order_sn'], $orderInfo['order_amount'],['method' => 'shoporder', 'param' => ['order_number' => $orderInfo['order_sn'], 'param' => 3]],'购买商品');
            }
        }
        return json($res);
    }
    
   public function alice(){
  $payment = new Payment();
  $res =  $payment->ali_app(10000,'测试商品','2365489', 0.01,['method' => 'shoporder', 'param' => ['order_number' => '2365489', 'param' => 2]]);
  return json($res);
}

    
    /**
     * 快递信息
     */
    public function get_send_info($order_id) {
        $order = Db::name('order')->where(['order_id' => $order_id, 'user_id' => $this->uid])->find();
        if (!$order || !$order['shipping_code']) {
            return json(['status' => -1, 'msg' => '暂无匹配订单或该订单未发货']);
        }
        $host = "https://kop.kuaidihelp.com/api";
        $method = "POST";
        $headers = array();
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
        $querys = "";
        $time = time();
        $bodys = [
        "app_id"=>'104313',
        "method"=>'express.info.get',
        "sign"=>md5('104313'.'express.info.get'.$time."b2f730de7f9f8896327319dd8a694525415814a0"),
        "ts"=>$time,
        "data"=>'{ "waybill_no":"'.$order['invoice_no'].'", "exp_company_code":"'.$order['shipping_code'].'","result_sort":"0"}'
        ];

        $bodys = http_build_query($bodys);
        //print_r($bodys);exit;
        $url = $host;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
        $res = curl_exec($curl);
        $res = substr($res,strripos($res,PHP_EOL));
        //var_dump($res);exit;
        $res = json_decode($res,true);

        if($res['code'] == 0){

            $back = ['status'=>1,'msg'=>$res['msg'],'list'=>$res['data'][0]['data'],'info'=>['invoice_no'=>$order['invoice_no'],'shipping_name'=>$order['shipping_name']]];
        }else{
            $back = ['status'=>0,'msg'=>$res['msg']];
        }
        return json($back);
    }
}