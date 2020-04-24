<?php

namespace app\admin\validate;

use think\Validate;

class OrderValidate extends Validate
{
    // 验证规则
    protected $rule = [
        ['consignee','require','收货人必填'],
        ['mobile','require|regex:^1[34578]\d{9}$','手机号必填|手机号格式不正确'],
        ['province', 'require|gt:0', '地址必须选择|地址必须选择'],
        ['address', 'require', '地址必须填写'],
        ['goods_list', 'require', '商品必须选择']
    ];
}