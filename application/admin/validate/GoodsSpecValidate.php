<?php

namespace app\admin\validate;

use think\Validate;

class GoodsSpecValidate extends Validate
{
    // 验证规则
    protected $rule = [
        ['name','require','属性名称必须填写'],
        ['type_id', 'require', '商品类型必须选择'],
        ['items','require','规格项不能为空'],
    ];
}