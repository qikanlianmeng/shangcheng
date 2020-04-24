<?php

namespace app\admin\model;

use think\Model;

class GoodsAttribute extends Model
{
    protected static function init()
    {
        Self::event('before_insert', function ($goodsAttribute) {
            if ($goodsAttribute->values && $goodsAttribute->input_type == 1) {
                $goodsAttribute->values = str_replace("\n", "|", trim($goodsAttribute->values));
            } else {
                $goodsAttribute->values = '';
            }
        });
        Self::event('before_update', function ($goodsAttribute) {
            if ($goodsAttribute->values && $goodsAttribute->input_type == 1) {
                $goodsAttribute->values = str_replace("\n", "|", trim($goodsAttribute->values));
            } else {
                $goodsAttribute->values = '';
            }
        });
    }
    public function goodsType()
    {
        return $this->hasOne('GoodsType', 'id', 'type_id');
    }
}

