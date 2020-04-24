<?php
namespace app\admin\validate;
use think\Validate;
class WxinVaildate extends Validate
{
   protected $rule = [
        ['name', 'require', '菜单名称不能为空']
    ];
}