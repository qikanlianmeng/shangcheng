<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2017/9/12
 * Time: 15:17
 */
return [

    'ORDER_STATUS' =>[
        0 => '待确认',//用户下单后默认为待确认
        1 => '已确认',//后台管理员发货
        2 => '已收货',//订单已收货
        3 => '已取消',//订单已取消
        4 => '已完成',//评价完毕显示已完成
        5 => '已作废'
    ],

    'SHIPPING_STATUS' => [
        0 => '未发货',
        1 => '已发货',
        2 => '部分发货'
    ],

    'PAY_STATUS' => [
        0 => '未支付',
        1 => '已支付',
        2 => '申请退款',
        3 => '已退款',
        4 => '拒绝退款'
    ],

    'COUPON_TYPE' => [
        0 => '下单赠送',
        1 => '指定发放',
        2 => '免费领取',
        3 => '线下发放',
    ],

    'PROM_TYPE' => [
        0 => '默认',
        1 => '抢购',
        2 => '团购',
        3 => '优惠'
    ],

    'MANAGE_ORDER_BTN' => [
        'pay'              => '付款',
        'pay_cancel'       => '设为未付款',
        'confirm'          => '确认',
        'cancel'           => '取消确认',
        'invalid'          => '无效',
        'delivery'         => '去发货',
        'delivery_confirm' => '确认收货',
        'refund'           => '申请退货',
        'front_remove'           => '前端移除',
        'back_remove'           => '后端移除',
        'all_remove'           => '前后端移除',
    ]
];