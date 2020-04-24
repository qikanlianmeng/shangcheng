<?php

namespace app\admin\validate;

use think\Validate;

class GoodsBrandValidate extends Validate
{
    // 验证规则
    protected $rule = [
        ['name','require','品牌名称必须填写'],
        ['name','checkName','品牌名称已存在'],
        ['logo', 'require', '品牌logo必须上传'],
        ['url','require','品牌网址必须填写'],
        ['url','url','品牌网址不合法'],
        //['parent_cat_id','require','所属分类必须选择']
    ];
    protected function checkName($value, $rule, $data){
        $checkBrandWhere = [
            'name'=>$value,
            //'parent_cat_id'=>$data['parent_cat_id'],
            //'cat_id'=>$data['cat_id'],
        ];
        if(isset($data['id'])){
            $checkBrandWhere['id'] = ['<>',$data['id']];
        }
        $res = model('GoodsBrand')->where($checkBrandWhere)->find();
        //print_r($checkBrandWhere);exit;
        return !empty($res) ? false : true;
    }
}