<?php

namespace app\common\validate;
use think\Validate;

class YangTenderValidate extends Validate
{
    protected $rule = [
        'title|标题'  => 'require',
        'num|数量'  => '>:0',
    ];

}