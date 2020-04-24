<?php
/**
 * 公共商品类
 * User: tianfeiwen
 * Date: 2017/9/19
 * Time: 14:47
 */
namespace app\common\model;

use think\Model;
use think\Db;
use app\admin\model\GoodsCategory;

class Goods extends Model
{
    /**
     * @param int $cat_id    所属分类
     * @param int $brand_id  所属品牌
     * @param int $recom_type类型 '' 所有  1is_new 2is_hot 3re_recommend
     * @param $keywords      商品关键词
     * @param $is_spec       区分spec规格   0不区分，默认  1区分
     * @param $is_on_sale    -1所有的，默认    1上架的  0未上架的
     * @return 商品列表
     */
    public function getGoodsList($cat_id = 0, $brand_id = 0, $recom_type = '', $keywords = '', $is_spec = 0, $is_on_sale = -1)
    {
        //组装筛选条件
        $map = [];
        if ($cat_id) {
            $GoodsCategory = new GoodsCategory();
            $catSon = $GoodsCategory->getCategorySon($cat_id);
            $cats = [];
            foreach ($catSon as $v) {
                $cats[] = $v->id;
            }
            $cats[] = $cat_id;
            $map['cat_id'] = ['in', $cats];
        }
        $brand_id && $map['brand_id'] = $brand_id;
        in_array($recom_type, ['is_new', 'is_hot', 'is_recommend']) && $map[$recom_type] = 1;
        $keywords && $map['goods_name'] = ['like', '%' . $keywords . '%'];
        $is_on_sale != -1 && $map['is_on_sale'] = $is_on_sale;//是否上架
        $list = Db::name('goods')->where($map)->select();
        if ($list) {
            if ($is_spec) {
                //需查出所有的规格商品
                foreach ($list as $k => $v) {
                    //查询是否有规格
                    $list[$k]['spec_list'] = Db::name('spec_goods')->where('goods_id', $v['id'])->select();
                }
            }
            return ['code' => 1, 'msg' => '查询成功', 'data' => $list];
        }
        return ['code' => 0, 'msg' => '没有数据', 'data' => ''];
    }

    /**'
     * @param $goods_id_arr    商品价格数组
     * @param int $lev      分多少层
     * @return array
     */
    public function get_filter_price($goods_id_arr, $lev = 5)
    {
        if (!$goods_id_arr) {
            return [];
        }
        $price_arr = Db::name('goods')->where(['id' => ['in', $goods_id_arr]])->column('shop_price');
        $max = ceil(max($price_arr));
        $min = ceil(min($price_arr));
        $p = $max - $min;
        if ($p <= 0) {
            return [];
        }
        $psize = ceil($p / $lev);
        for ($i = 0; $i < 5; $i++) {
            $s   = $min + $i * $psize;
            $e   = $s + $psize - 1;
            if ($i == 4) {
                $e = $max;
            }
            foreach ($price_arr as $v) {
                if ($v >= $s && $v <= $e) {
                    $parr[] = ['name' => $s .'-'. $e .'元', 'value' => $s .'_'.$e, 'key' => 'price'];
                    break;
                }
            }
        }
        return $parr;
    }

    public function get_filter_brand($goods_id_arr)
    {
        if (!$goods_id_arr) {
            return [];
        }
        $brand_arr = Db::name('goods')->where(['id' => ['in', $goods_id_arr]])->column('brand_id');
        if (!$brand_arr) {
            return [];
        }
        $brand_arr = array_unique($brand_arr);
        $brand_list = Db::name('goods_brand')->field('id as value, name')->where(['id' => ['in', $brand_arr]])->select();
        foreach ($brand_list as $k => $v) {
            $brand_list[$k]['key'] = 'brand';
        }
        return $brand_list;
    }

    public function get_filter_spec($cid, $goods_id_arr)
    {
        if (!$goods_id_arr) {
            return [];
        }
        //该分类是否设置有筛选属性
        $spec_id_str = Db::name('goods_category')->where('id', $cid)->value('spec_id_str');
        if (!$spec_id_str) {
            return [];
        }
        $key_arr = Db::name('spec_goods')->where(['goods_id' => ['in', $goods_id_arr]])->column('key');
        $key_arr = array_unique(explode('_', implode('_', $key_arr)));
        //筛选规格
        $spec = Db::name('goods_spec')->where(['id' => ['in', $spec_id_str]])->select();
        //var_dump($key_arr);
        $spec_arr = [];
        foreach ($spec as $k => $v) {
            $sepc_id_item = Db::name('goods_spec_item')->field('id as value,item as name')->where(['spec_id' => $v['id']])->select();
            //var_dump($sepc_id_item);
            foreach ($sepc_id_item as $k1 => $v1) {
                if (in_array($v1['value'], $key_arr)) {
                    $v1['key'] = 'spec';
                    $spec_arr[$v['name']][] = $v1;
                }
            }
        }
        return $spec_arr;
    }


}

