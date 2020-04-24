<?php

namespace app\admin\validate;

use think\Validate;

class GoodsCategoryValidate extends Validate
{
    // 验证规则
    protected $rule = [
        ['name','require','分类名称必须填写'],
        //['mobile_name','require','wap分类名称必须填写']
    ];
}