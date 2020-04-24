<?php

namespace app\admin\model;

use think\Model;

class GoodsType extends Model
{
    /**
     * 商品规格-规格项
     */
/*    public function GoodsSpecItem()
    {

        //hasManyThrough(‘关联模型名’,‘中间模型名’,‘外键名’,‘中间模型关联键名’,‘当前模型主键名’,[‘模型别名定义’]);
        return $this->hasManyThrough('GoodsSpecItem', 'GoodsSpec', 'type_id', 'spec_id', 'id');
    }*/

    /**
     * 商品属性
     */
    public function goodsAttribute()
    {
        return $this->hasMany('GoodsAttribute', 'type_id', 'id')->order('order asc');
    }

    /**
     * 商品规格
     */

    public function goodsSpec()
    {
        return $this->hasMany('GoodsSpec', 'type_id', 'id', 'a')->order('order asc');
    }

}

