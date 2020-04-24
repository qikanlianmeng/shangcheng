<?php

namespace app\admin\model;

use think\Model;

class GoodsBrand extends Model
{
    /*public function goodsType()
    {
        return $this->hasOne('GoodsType', 'id', 'type_id');
    }
    public function goodsSpecItem()
    {
        return $this->hasmany('GoodsSpecItem', 'spec_id');
    }*/
    protected static function init()
    {
        Self::event('before_insert', function ($goodsBrand) {
            if ($goodsBrand->parent_cat_id) {

                $goodsBrand->cat_name = model('GoodsCategory')->where('id', $goodsBrand->parent_cat_id)->value('name');
            } elseif ($goodsBrand->cat_id) {
                $goodsBrand->cat_name = model('GoodsCategory')->where('id', $goodsBrand->cat_id)->value('name');
            }
        });
        Self::event('before_update', function ($goodsBrand) {
            if ($goodsBrand->parent_cat_id) {
                $goodsBrand->cat_name = model('GoodsCategory')->where('id', $goodsBrand->parent_cat_id)->value('name');
            } elseif ($goodsBrand->cat_id) {
                $goodsBrand->cat_name = model('GoodsCategory')->where('id', $goodsBrand->cat_id)->value('name');
            }
        });
    }
}

