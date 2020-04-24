<?php
namespace app\admin\validate;
use think\Validate;
class ReplyVaildate extends Validate
{
    protected $rule = [
	    ['description', 'require', '描述不能是空']
    ];
}