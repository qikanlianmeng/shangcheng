<?php

namespace app\admin\validate;

use think\Validate;

class GoodsAttributeValidate extends Validate
{
    // 验证规则
    protected $rule = [
        ['name','require','属性名称必须填写'],
        ['type_id', 'require', '所属商品类型必须选择'],
        ['input_type', 'require', '录入方式必须选择'],
        ['input_type','checkValues','可选值列表不能为空'],
    ];
    protected function checkValues($input_type,$rule)
    {
        if((trim(input('values') == '')) && ($input_type == '1'))
            return false;
        else
            return true;
    }
}