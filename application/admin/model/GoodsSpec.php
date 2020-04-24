<?php

namespace app\admin\model;

use think\Model;

class GoodsSpec extends Model
{
    public function goodsType()
    {
        return $this->hasOne('GoodsType', 'id', 'type_id');
    }
    public function goodsSpecItem()
    {
        return $this->hasmany('GoodsSpecItem', 'spec_id')->order('id asc');
    }
    public function afterSave($id, $items)
    {
        $items = explode("\n", trim($items));
        //构造数组
        $list = array();
        foreach ($items as $k => $item) {
            if (!$item) {
                continue;
            }
            $list[$k]['spec_id'] = $id;
            $list[$k]['item']    = $item;
        }
        $list = array_values($list);
        $itemModel = model("GoodsSpecItem");
        $itemModel->where('spec_id', $id)->delete();
        $itemModel->saveAll($list);
    }
}

