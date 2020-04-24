<?php
namespace app\admin\validate;
use think\Validate;
class LinkVaildate extends Validate
{
    protected $rule = [
	    ['title', 'require', '链接名称不能为空'],
	    ['link', 'require', '链接地址不能为空'],
	    ['portrait', 'require', '图片必须上传'],
    ];
}