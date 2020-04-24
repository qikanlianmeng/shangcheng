<?php

namespace app\admin\validate;
use think\Validate;

class UserGradeValidate extends Validate
{
    protected $rule = [
        ['name','require','等级名称必须填写'],
        ['portrait','require','等级图片必须上传'],
        ['max','require','最大值必须填写'],
        ['min','require','最小值必须填写'],
    ];

}