<?php
/**
 * 价格计算通用类
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/12
 * Time: 15:21
 */
namespace app\common\model;

use think\Model;
use think\Db;
use think\Config;
class Price extends Model
{
    /**
     * 计算订单金额
     * @param type $user_id        用户id
     * @param type $order_goods    购买的商品 从购物车已选择的查出
     * @param type $shipping_price 物流费用, 如果传递了物流费用 就不在计算物流费
     * @param type $province       省份
     * @param type $city           城市
     * @param type $district       县
     * @param type $pay_points     羊币
     * @param type $user_money     余额
     * @param type $coupon_id      优惠券
     * @param type $couponCode     优惠码
     * @param type $discount       订单折扣率
     * @return array
     */
    public function calculate_price($user_id = 0, $order_goods, $shipping_price = 0, $province = 0, $city = 0, $district = 0, $pay_points = 0, $user_money = 0, $coupon_id = 0, $couponCode = '',$discount=0)
    {
        $member = new Member();
        $user = $member->where('id',$user_id)->find();
        if (empty($order_goods)){
            return ['status' => 0, 'msg' => '商品列表不能为空', 'result' => ''];
        }
        $goods_ids = [];
        foreach ($order_goods as $k => $v) {
            $goods_ids[] = $v['goods_id'];
        }
        $goods_arr = Db::name('goods')->where('id', 'in', $goods_ids)->column('weight,market_price,is_free_shipping,exchange_integral,shop_price,fid', 'id');
        //查看商品
        $goods_weight   = 0;//商品重量
        $order_integral = 0;//商品可用羊币
        $goods_price    = 0;//商品总价
        $cut_fee        = 0;//节省金额
        $goods_num      = 0;//购买数量
        $arr            = [];//计算物流费用使用的数组
        foreach ($order_goods as $key => $val) {
            //如果商品不是包邮的，就计算商品重量
            if ($goods_arr[$val['goods_id']]['is_free_shipping'] == 0) {
                $goods_weight += $goods_arr[$val['goods_id']]['weight'] * $val['goods_num']; //累积商品重量
                $arr[$key]['id']     = $val['goods_id'];//商品id,暂时没有用，作为保留字段
                $arr[$key]['fid']    = $goods_arr[$val['goods_id']]['fid'];//商品运费模板id
                $arr[$key]['num']    = $val['goods_num'];//商品数量
                $arr[$key]['weight'] = $goods_arr[$val['goods_id']]['weight'];//商品重量
                $arr[$key]['volume'] = 0;//商品体积
                $arr[$key]['price']  = $val['member_goods_price'];//商品价格
            }
            //计算订单可用羊币
            if ($goods_arr[$val['goods_id']]['exchange_integral'] > 0) {
                //商品设置了羊币兑换就用商品本身的羊币。
                $order_integral +=  $goods_arr[$val['goods_id']]['exchange_integral'] * $val['goods_num'];
            }
            //小计
            $order_goods[$key]['goods_fee'] = $val['goods_num'] * $val['member_goods_price'];
            $order_goods[$key]['store_count'] = getGoodsNum($val['goods_id'], $val['spec_key']);
            //最多可购买的库存数量
            if ($order_goods[$key]['store_count'] <= 0 || $order_goods[$key]['store_count'] < $order_goods[$key]['goods_num']) {
                return ['status' => 0, 'msg' => $order_goods[$key]['goods_name'] .','.$val['spec_key_name']. "库存不足,请重新下单", 'result' => ''];
            }
            $goods_price += $order_goods[$key]['goods_fee']; // 商品总价
            $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price'];//节省金额
            $goods_num += $val['goods_num']; // 购买数量
        }
        if($discount > 0 && $discount<10){
            $goods_price = round($goods_price*$discount/10,2);
        }
        //处理物流价格 如果没传递物流价格，则需计算物流

        if ($shipping_price == 0) {
            //计算物流费用
            if ($arr) {
                $shipping_price = \app\common\service\Freight :: order_freight($city, $arr);
            }
        }

        //可用羊币处理
        if ($pay_points) {
            if ($pay_points > $order_integral) {
                $pay_points = $order_integral;//如果使用的羊币超限，则使用最多的羊币
            }
            //如果用户没有这么多羊币，则强制使用用户现有的羊币
            if ($user['integral'] < $pay_points) {
                $pay_points = $user['integral'];
            }
        }
        //商品应付金额
        $order_amount = $goods_price + $shipping_price;//商品总价+物流价格-优惠价
        //再减入羊币相对应的比例金额
        $point_rate = Config::get('point_rate');
        $point_rate = $point_rate ? $point_rate : 100;
        $pay_points = $pay_points/$point_rate;//羊币抵消应付金额 0.05
        $order_amount -= $pay_points;//减去羊币抵消应付金额 -0.05
        //可用余额
        if ($user_money>0) {
            if ($user_money > $order_amount) {
                $user_money = $order_amount;//如果使用的余额超限，则使用最多的余额
            }
            //如果用户没有这么多余额，则强制使用用户现有的余额
            if ($user['money']>0 && $user['money'] < $user_money) {
                $user_money = $user['money'];
            }
        }
        $order_amount -= $user_money;//减去余额支付的费用
        $total_amount = $goods_price + $shipping_price;//商品总价
        //订单总价  应付金额  物流费  商品总价 节约金额 共多少件商品 羊币  余额  优惠券
        $result = [
            'total_amount'   => $total_amount,     // 全部总价
            'order_amount'   => $order_amount,     // 应付金额
            'shipping_price' => $shipping_price,   // 物流费
            'goods_price'    => $goods_price,      // 商品总价
            'cut_fee'        => $cut_fee,          // 共节约多少钱
            'anum'           => $goods_num,        // 商品总共数量
            'integral_money' => $pay_points,       // 羊币抵消金额
            'user_money'     => $user_money,       // 使用余额
            'coupon_price'   => 0,                 // 优惠券抵消金额
        ];
        return ['status' => 1, 'msg' => "计算价钱成功", 'result' => $result];
    }

    /**
     * 用于计算后台管理员下单的商品价格
     * @param $goods_list 商品列表 [id:spec=>num]
     * @param $address 地址信息
     */
    public function calculate_admin_price($goods_list, $address)
    {
        $goods_weight   = 0;//商品重量
        $goods_price    = 0;//商品总价
        $cut_fee        = 0;//节省金额
        $goods_num      = 0;//购买数量
        foreach ($goods_list as $k => $v) {
            //判断是规格商品还是普通商品
            if (strstr($k, ':') === false) {
                $info = Db::name('goods')->where('id', $k)->find();
                $goods_weight += $info['weight'] * $v;
                $goods_price  += $info['shop_price'] * $v;
                $cut_fee      += ($info['market_price'] - $info['shop_price']) * $v;
                $goods_num    += $v;
            } else {
                $k = explode(':', $k);
                $specInfo  = Db::name('spec_goods')->where('goods_id', $k[0])->where('key', $k[1])->find();
                $goodsInfo = Db::name('goods')->where('id', $k[0])->find();
                $goods_weight += $goodsInfo['weight'] * $v;
                $goods_price  += $specInfo['price'] * $v;
                $cut_fee      += ($goodsInfo['market_price'] - $specInfo['price']) * $v;
                $goods_num    += $v;
            }
        }
        //TODO  处理物流价格 如果没传递物流价格，则需计算物流
        $shipping_price = 0;
        //商品应付总额
        $order_amount = $goods_price + $shipping_price;
        $result = [
            'total_amount'   => $order_amount,     // 全部总价
            'order_amount'   => $order_amount,     // 应付金额
            'shipping_price' => $shipping_price,   // 物流费
            'goods_price'    => $goods_price,      // 商品总价
            'cut_fee'        => $cut_fee,          // 共节约多少钱
            'anum'           => $goods_num         // 商品总共数量
        ];
        return ['status' => 1, 'msg' => "计算价钱成功", 'result' => $result];
    }
}