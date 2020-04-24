<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class Goods extends Model
{
    public function goodsCategory()
    {
        return $this->hasOne('GoodsCategory', 'id', 'cat_id');
    }
    protected static function init()
    {
        Self::event('before_insert', function ($goods) {
            $goods->on_time = time(); // 上架时间
            $goods->last_update = time();//最后更新时间
            /* $goods->cat_id_1 && $goods->cat_id = $goods->cat_id_1;
            $goods->cat_id_2 && $goods->cat_id = $goods->cat_id_2;
            $goods->cat_id_3 && $goods->cat_id = $goods->cat_id_3;
            $goods->extend_cat_id_1 && $goods->extend_cat_id = $goods->extend_cat_id_1;
            $goods->extend_cat_id_2 && $goods->extend_cat_id = $goods->extend_cat_id_2;
            $goods->extend_cat_id_3 && $goods->extend_cat_id = $goods->extend_cat_id_3; */
            $goods->spec_type = $goods->goods_type;
            $goods->keywords = str_replace('，', ',', $goods->keywords);
        });
        Self::event('before_update', function ($goods) {
            $goods->last_update = time();//最后更新时间
            //商品货号
            /* $goods->cat_id_1 && $goods->cat_id = $goods->cat_id_1;
            $goods->cat_id_2 && $goods->cat_id = $goods->cat_id_2;
            $goods->cat_id_3 && $goods->cat_id = $goods->cat_id_3;
            $goods->extend_cat_id_1 && $goods->extend_cat_id = $goods->extend_cat_id_1;
            $goods->extend_cat_id_2 && $goods->extend_cat_id = $goods->extend_cat_id_2;
            $goods->extend_cat_id_3 && $goods->extend_cat_id = $goods->extend_cat_id_3; */
            $goods->spec_type = $goods->goods_type;
            $goods->keywords = str_replace('，', ',', $goods->keywords);
        });
        Self::event('after_insert', function ($goods) {
            //商品货号
            //$goods->goods_sn = $goods->goods_sn ? $goods->goods_sn : "vxue".str_pad($goods->id, 9, "0", STR_PAD_LEFT);
            //Db::name('goods')->where('id', $goods->id)->update(['goods_sn' => $goods->goods_sn]);
            //商品图库
            //商品原图是否存在于图库中
            $goods_images = isset($goods->goods_images) && !empty($goods->goods_images) ? $goods->goods_images : array();
            if (!in_array($goods->original_img, $goods_images)) {
                array_unshift($goods_images, $goods->original_img);
            }
            foreach ($goods_images as $k => $v) {
                $data = ['goods_id' => $goods->id, 'image_url' => $v];
                Db::name('goods_images')->insert($data);
            }
            //规格处理
            if (isset($goods->item) && !empty($goods->item)) {
                $store_count = 0;//总库存
                foreach ($goods->item as $k => $v) {
                    $v['price'] = trim($v['price']) ? trim($v['price']) : $goods->shop_price;//如果没有标价格，就按商品价走
                    $v['store_count'] = trim($v['store_count']); // 记录商品总库存
                    $store_count += $v['store_count'];
                    $v['sku'] = trim($v['sku']);
                    $data = ['goods_id' => $goods->id, 'key' => $k, 'key_name' => $v['key_name'], 'price' => $v['price'], 'store_count' => $v['store_count'], 'sku' => $v['sku']];
                    Db::name('spec_goods')->insert($data);
                }
                //修改库存
                $store_count < 0 ? 0 : $store_count;
                Db::name('goods')->where('id', $goods->id)->update(['store_count' => $store_count]);
                //商品规格图片处理
                if (isset($goods->item_img) && !empty($goods->item_img)) {
                    $goods->item_img = array_filter($goods->item_img);
                    foreach ($goods->item_img as $k => $v) {
                        $data = ['goods_id' => $goods->id, 'spec_item_id' => $k, 'src' => $v];
                        Db::name('spec_image')->insert($data);
                    }
                }
            }
            //属性处理
            if (isset($goods->attr) && !empty($goods->attr)) {
                foreach ($goods->attr as $k => $v) {
                    $data = ['goods_id' => $goods->id, 'attr_id' => $k, 'attr_value' => $v];
                    Db::name('goods_attr')->insert($data);
                }
            }
        });
        Self::event('after_update', function ($goods) {
            //商品货号
            //$goods->goods_sn = $goods->goods_sn ? $goods->goods_sn : "vxue".str_pad($goods->id, 9, "0", STR_PAD_LEFT);
            //Db::name('goods')->where('id', $goods->id)->update(['goods_sn' => $goods->goods_sn]);
            //商品图库
            //先删除所有图库
            Db::name('goods_images')->where('goods_id', $goods->id)->delete();
            //商品原图是否存在于图库中
            $goods_images = isset($goods->goods_images) && !empty($goods->goods_images) ? $goods->goods_images : array();
            if (!in_array($goods->original_img, $goods_images)) {
                array_unshift($goods_images, $goods->original_img);
            }
            foreach ($goods_images as $k => $v) {
                $data = ['goods_id' => $goods->id, 'image_url' => $v];
                Db::name('goods_images')->insert($data);
            }
            //规格处理
            //先删除所有规格
            Db::name('spec_goods')->where('goods_id', $goods->id)->delete();
            if (isset($goods->item) && !empty($goods->item)) {
                $store_count = 0;//总库存
                foreach ($goods->item as $k => $v) {
                    $v['price'] = trim($v['price']) ? trim($v['price']) : $goods->shop_price;//如果没有标价格，就按商品价走
                    $v['store_count'] = trim($v['store_count']); // 记录商品总库存
                    $store_count += $v['store_count'];
                    $v['sku'] = trim($v['sku']);
                    $data = ['goods_id' => $goods->id, 'key' => $k, 'key_name' => $v['key_name'], 'price' => $v['price'], 'store_count' => $v['store_count'], 'sku' => $v['sku']];
                    Db::name('spec_goods')->insert($data);
                    //修改商品后购物车的商品价格也修改一下
                    Db::name('cart')->where(['goods_id' => $goods->id, 'spec_key' => $k, 'prom_type' => 0])->update([
                        'market_price' => $v['price'], //市场价
                        'goods_price' => $v['price'], // 本店价
                        'member_goods_price' => $v['price'], // 会员折扣价
                    ]);
                }
                //修改库存
                $store_count < 0 ? 0 : $store_count;
                Db::name('goods')->where('id', $goods->id)->update(['store_count' => $store_count]);
                //商品规格图片处理
                //先删除所有图片
                Db::name('spec_image')->where('goods_id', $goods->id)->delete();
                if (isset($goods->item_img) && !empty($goods->item_img)) {
                    $goods->item_img = array_filter($goods->item_img);
                    foreach ($goods->item_img as $k => $v) {
                        $data = ['goods_id' => $goods->id, 'spec_item_id' => $k, 'src' => $v];
                        Db::name('spec_image')->insert($data);
                    }
                }
            }
            //属性处理
            //先删除所有属性
            Db::name('goods_attr')->where('goods_id', $goods->id)->delete();
            if (isset($goods->attr) && !empty($goods->attr)) {
                foreach ($goods->attr as $k => $v) {
                    $data = ['goods_id' => $goods->id, 'attr_id' => $k, 'attr_value' => $v];
                    Db::name('goods_attr')->insert($data);
                }
            }
            //购物车改价
            Db::name('cart')->where(['goods_id' => $goods->id, 'spec_key' => ''])->update([
                'market_price' => $goods->market_price,    // 市场价
                'goods_price' => $goods->shop_price,       // 本店价
                'member_goods_price' => $goods->shop_price // 会员折扣价
            ]);

        });
        Self::event('before_delete', function ($goods) {
            //先删除所有图库
            Db::name('goods_images')->where('goods_id', $goods->id)->delete();
            //再删除所有规格
            Db::name('spec_goods')->where('goods_id', $goods->id)->delete();
            //再删除所有图片
            Db::name('spec_image')->where('goods_id', $goods->id)->delete();
            //再删除所有属性
            Db::name('goods_attr')->where('goods_id', $goods->id)->delete();

        });
    }
    /*$spec_arr = array(
            20 => array('7','8','9'),
            10=>array('1','2'),
            1 => array('3','4'),
            spec_id=>array(item_id, item_id)
        );*/
    public function getSpecInput($goods_id = 0, $spec_arr)
    {
        if (!$spec_arr) {
            return '';
        }
        foreach ($spec_arr as $k => $v)
        {
            $spec_arr_sort[$k] = count($v);
        }
        asort($spec_arr_sort);
        foreach ($spec_arr_sort as $key =>$val)
        {
            $spec_arr2[$key] = $spec_arr[$key];
        }
        $spec_arr = $spec_arr2;
        unset($spec_arr2);
        //获取 规格的 列名称
        $clo_name = array_keys($spec_arr);
        //获取 规格的 笛卡尔积
        $spec_arr = combineDika($spec_arr);
        //规格
        $spec = model('GoodsSpec')->order('order asc')->column('name','id');
        $base_sort = array_keys($spec);
        //规格项
        $specItem = model('GoodsSpecItem')->column('item,spec_id','id');
        $keySpecGoods = Db::name('spec_goods')->where('goods_id', $goods_id)->column('key_name,price,store_count,bar_code,sku','key');//规格项目
        //var_dump($keySpecGoods);die;
        //开始构造表单
        //构造表头
        $html = '<tr class="long-td">';
        foreach ($clo_name as $v) {
            $html .= '<td style="text-align:left"><b>'.$spec[$v].'</b></td>';
        }
        $html .= '<td style="text-align:left"><b>价格</b></td><td style="text-align:left"><b>库存</b></td><td style="text-align:left"><b>SKU</b></td></tr>';
        foreach ($spec_arr as $k => $v) {
            $html .= '<tr class="long-td secondfloor">';
            $_v = explode('_', $v);
            foreach($_v as $k1 => $v1) {
                $html .= '<td>' . $specItem[$v1]['item'] . '</td>';
            }
            //自定义排序一波
            usort($_v, function($s1, $s2)use($base_sort, $specItem){
                $s1_index = array_search($specItem[$s1]['spec_id'], $base_sort);
                $s2_index = array_search($specItem[$s2]['spec_id'], $base_sort);
                return $s1_index < $s2_index ? -1 : 1;
            });
            $key_name = '';
            foreach ($_v as $v1) {
                $key_name .= $spec[$specItem[$v1]['spec_id']] . ':' . $specItem[$v1]['item'].' ';
            }
            //取价格，库存，sku,key_name
            //$price = $keySpecGoods[implode('_', $_v)]
            $_v = implode('_', $_v);
            $price       = isset($keySpecGoods[$_v]) ?  $keySpecGoods[$_v]['price'] : 0;
            $store_count = isset($keySpecGoods[$_v]) ?  $keySpecGoods[$_v]['store_count'] : 0;
            $sku         = isset($keySpecGoods[$_v]) ?  $keySpecGoods[$_v]['sku'] : 0;
            $html .= '<td><input type="text" class="form-control" name="item['.$_v.'][price]" value ="'.$price.'"></td>';
            $html .= '<td><input type="text" class="form-control" name="item['.$_v.'][store_count]"  value="'.$store_count.'"></td>';
            $html .= '<td><input type="text" class="form-control" name="item['.$_v.'][sku]" value="'.$sku.'"></td>';
            $html .= '<td><input type="hidden" class="form-control" name="item['.$_v.'][key_name]" value="'.trim($key_name).'"></td>';
            $html .= '</tr>';
        }
        return $html;
    }
}

