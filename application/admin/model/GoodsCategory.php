<?php

namespace app\admin\model;

use think\Model;

class GoodsCategory extends Model
{
    protected static function init()
    {
        /*Self::event('after_insert', function ($goodsCategory) {
            $parent_id_path = "0_";
            if ($goodsCategory->parent_id) {
                $parent_id_parent_id = Self::where('id', $goodsCategory->parent_id)->value('parent_id');
                if ($parent_id_parent_id) {
                    $parent_id_path .= "{$parent_id_parent_id}_{$goodsCategory->parent_id}_{$goodsCategory->id}";
                } else {
                    $parent_id_path .= "{$goodsCategory->parent_id}_{$goodsCategory->id}";
                }
            } else {
                $parent_id_path .= "{$goodsCategory->id}";
            }
            $info = Self::get($goodsCategory->id);
            $info->parent_id_path = $parent_id_path;
            $info->save();
        });
        Self::event('after_update', function ($goodsCategory) {
            $parent_id_path = "0_";
            if ($goodsCategory->parent_id) {
                $parent_id_parent_id = Self::where('id', $goodsCategory->parent_id)->value('parent_id');
                if ($parent_id_parent_id) {
                    $parent_id_path .= "{$parent_id_parent_id}_{$goodsCategory->parent_id}_{$goodsCategory->id}";
                } else {
                    $parent_id_path .= "{$goodsCategory->parent_id}_{$goodsCategory->id}";
                }
            } else {
                $parent_id_path .= "{$goodsCategory->id}";
            }
            $info = Self::get($goodsCategory->id);
            $info->parent_id_path = $parent_id_path;
            $info->save();
        });*/
    }
    /**
     * @param $pid 父ID
     * @param int $is_all 是否全部，默认是，否为只获取下一级
     */
    public function getCategorySon($pid = 0, $is_all = 1)
    {
        if ($is_all) {
            $arr = $this->order("order asc")->select();
            return $this->sonTree($arr, $pid);
        } else {
            return $this->where('parent_id', $pid)->order('order asc')->select();
        }
    }
    public function getCategoryTree($is_show = 1)
    {
        $map = [];
        if ($is_show) {
            $map['is_show'] = 1;
        }
        $arr = $this->where($map)->order("order asc")->select();
        if ($arr) {
            $arr = collection($arr)->toArray();
        }
        $tree = [];
        foreach ($arr as $k => $v) {
            if ($v['parent_id'] == 0) {
                foreach ($arr as $k1 => $v1) {
                    if ($v1['parent_id'] == $v['id']) {
                        foreach ($arr as $k2 => $v2) {
                            if ($v2['parent_id'] == $v1['id']) {
                                $v1['child'][] = $v2;
                            }
                        }
                        $v['child'][] = $v1;
                    }
                }
                $tree[] = $v;
            }
        }
        return $tree;
    }
    protected function sonTree($arr, $pid = 0, $lev = 1)
    {
        static $Tree = array();
        foreach ($arr as $k=>$v) {
            if ($v->parent_id == $pid) {
                $v->lev = $lev;
                $Tree[] = $v;
                $this->sonTree($arr, $v->id, $lev+1);
            }
        }
        return $Tree;
    }
    public function saveCategory($data)
    {

        $add = array();
        $add['name']           = $data['name'];
        /*$add['level']          = $data['parent_id_2'] ? 3 : ($data['parent_id_1'] ? 2 : 1);
        $add['parent_id']      = $data['parent_id_2'] ? $data['parent_id_2'] :  ($data['parent_id_1'] ? $data['parent_id_1'] : 0);
        $add['mobile_name']    = $data['mobile_name'];
        $add['image']          = $data['image'];
        $add['is_hot']         = $data['is_hot'];
        $add['is_show']         = $data['is_show'];
        if ($data['spec_id']) {
            $data['spec_id'] = array_unique(array_filter($data['spec_id']));
            $add['spec_id_str'] = implode(',', $data['spec_id']);
        }*/
        if (isset($data['id']) && !empty($data['id'])) {
            $add['id'] = $data['id'];
            $this->isUpdate(true)->save($add);
        } else {
            $this->save($add);
        }
    }
}

