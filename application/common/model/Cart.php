<?php
/**
 * 购物车类
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/7
 * Time: 9:26
 */

namespace app\common\model;

use app\admin\model\Goods;
use think\Db;

class Cart
{
    static private $_instance = null;
    private $goods;//商品模型
    private $session_id;//session_id
    private $user_id;//user_id
    private $maxNum = 20;//购物车最多保存多少种商品

    private function __construct($user_id, $session_id)
    {
        $this->session_id = $session_id ? $session_id : session_id();
        $this->user_id    = $user_id ? $user_id : 0;
    }
    private function __clone() {
    }

    static public function getInstance($user_id = 0, $session_id = '') {
        if (self::$_instance == null) {
            self::$_instance = new self ($user_id, $session_id);
        }
        return self::$_instance;
    }

    /**
     * 修改商品模型
     */
    public function setGoods(Goods $goods)
    {
        return $this->goods = $goods;
    }

    /**
     * 修改用户ID
     */
    public function setUserId($user_id)
    {
        return $this->user_id = $user_id;
    }

    /**
     * 修改session_id唯一值
     */
    public function setSessionId($session_id)
    {
        return $this->session_id = $session_id;
    }

    /**
     * 添加商品进购物车
     */
    public function add($goods_id, $goods_num, $goods_group,$goods_spec_key = '')
    {
        if ($goodsModel = Goods::get($goods_id)) {
            $this->setGoods($goodsModel);
        }
        if (!$this->goods) {
            return ['status'=>'0', 'msg'=>'购买商品不存在', 'result'=>''];
        }
        #活动商品限制必须登录才能加入购物车
        if ($this->goods->prom_type > 0 && $this->user_id == 0) {
            return ['status'=>'0', 'msg'=>'购买活动商品必须先登录', 'result'=>''];
        }
        #判断购物车是否达到上限
        if ($this->user_id) {
            $cartNum = Db::name('cart')->where('user_id', $this->user_id)->count();
        } else {
            $cartNum = Db::name('cart')->where('session_id', $this->session_id)->count();
        }
        if ($cartNum >= $this->maxNum) {
            return ['status'=>'0', 'msg'=>'购物车最多只能放'.$this->maxNum.'种商品', 'result'=>''];
        }
        #根据prom_type类型调用不同加入购物车方法，进行添加购物车
        switch ($this->goods->prom_type) {
            case 1 :
                $res = $this->addFlashSaleCart($goods_num, $goods_spec_key);
                break;
            case 2 :
                #todo 团购
                break;
            case 3 :
                #todo 促销优惠
                break;
            case 3 :
                #todo 预售
                break;
            default :
                $res = $this->addNormalCart($goods_num,$goods_group, $goods_spec_key);
                break;
        }
        $res['result'] = $this->getUserCartGoodsNum();
        //setcookie('cn', $UserCartGoodsNum, null, '/');
        return $res;
    }

    private function addNormalCart($goods_num,$goods_group, $goods_spec_key)
    {
        #查此件商品sku
        $goodsStore = $this->goods->store_count;
        switch($goods_group){
            case 1:
                $member_goods_price=$goodsPrice = $this->goods->dl_price;
                break;
            case 2:
                $member_goods_price=$goodsPrice = $this->goods->dy_price;
                break;
            case 3:
                $member_goods_price=$goodsPrice = $this->goods->zy_price;
                //当商品是自营区商品，且用户已代理该商品，则取折扣价
                /*$dl_goods = Db::name('order')->alias('o')
                    ->join('think_order_goods og','o.order_id=og.order_id','left')
                    ->where(['o.user_id'=>$this->user_id,'o.pay_status'=>1,'o.order_group'=>1,'og.goods_id'=>$this->goods->id])
                    ->find();
                if($dl_goods){
                    $config = cache('db_config_data');
                    $member_goods_price = $member_goods_price*$config['zy_discount']/100;
                }*/
                //当用户是代理或者体验中心，则取折扣价
                $user_info = Db::name('member')->where('id',$this->user_id)->find();
                if($user_info['dl_time']>0 || $user_info['is_center']==1){
                    $config = cache('db_config_data');
                    $member_goods_price = $member_goods_price*$config['zy_discount']/100;
                }
                break;
        }


        if ($goods_spec_key) {
            $goodsSpec = Db::name('spec_goods')->where('goods_id', $this->goods->id)->where('key', $goods_spec_key)->find();
            if ($goodsSpec) {
                $goodsStore = $goodsSpec['store_count'];
                $goodsPrice = $goodsSpec['price'];
            } else {
                return ['status' => 0, 'msg' => '查无此规格商品', 'result' => ''];
            }
        }
        #查询购物车是否已经存在该件商品
        if ($this->user_id) {
            $userCartGoods = Db::name('cart')->where(['user_id'=>$this->user_id,'goods_id'=>$this->goods->id,'goods_group'=>$goods_group,'spec_key'=>$goods_spec_key])->find();
        } else {
            $userCartGoods = Db::name('cart')->where(['user_id'=>$this->user_id,'session_id'=>$this->session_id,'goods_id'=>$this->goods->id,'spec_key'=>$goods_spec_key])->find();
        }
        #进行逻辑添加操作,如果该商品已经存在购物车
        if ($userCartGoods) {
            $userWantGoodsNum = $goods_num + $userCartGoods['goods_num'];
            if($userWantGoodsNum > $goodsStore){
                return ['status' => 0, 'msg' => '商品库存不足，剩余'.$goodsStore.'件,当前购物车已有'.$userCartGoods['goods_num'].'件', 'result' => ''];
            }
            $res = Db::name('cart')->where('id', $userCartGoods['id'])->update(['goods_num' => $userWantGoodsNum, 'goods_price'=>$goodsPrice, 'member_goods_price'=>$goodsPrice]);
        } else {
            if($goods_num > $goodsStore){
                return ['status' => 0, 'msg' => '商品库存不足，剩余'.$goodsStore.'件', 'result' => ''];
            }
            $cartAddData = [
                'user_id'            => $this->user_id,               // 用户id
                'session_id'         => $this->session_id,            // sessionid
                'goods_id'           => $this->goods->id,             // 商品id
                'goods_sn'           => $this->goods->goods_sn,       // 商品货号
                'goods_name'         => $this->goods->goods_name,     // 商品名称
                'market_price'       => $this->goods->market_price,   // 市场价
                'goods_price'        => $goodsPrice,                  // 购买价
                'member_goods_price' => $member_goods_price,                  // 会员折扣价 默认为 购买价
                'goods_group'        => $goods_group,
                'goods_num'          => $goods_num,                   // 购买数量
                'add_time'           => time(),                       // 加入购物车时间
                'prom_type'          => 0,                            // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                'prom_id'            => 0,                            // 活动id
            ];
            //默认商品原图
            $cartAddData['goods_img'] = $this->goods->original_img;
            if($goods_spec_key){
                $cartAddData['spec_key']      = $goods_spec_key;
                $cartAddData['spec_key_name'] = $goodsSpec['key_name']; //规格 key_name
                $img = Db::name('spec_image')->where(['goods_id' =>$this->goods->id, 'spec_item_id' =>  ['in', explode('_', $goods_spec_key)]])->value('src');
                if ($img) {
                    //使用该规格的图片
                    $cartAddData['goods_img'] = $img;
                }
            }
            $res = Db::name('Cart')->insert($cartAddData);
        }
        #判断数据库操作返回值,并返回信息
        if($res!== false){
            return ['status' => 1, 'msg' => '成功加入购物车', 'result' => ''];
        }else{
            return ['status' => 0, 'msg' => '加入购物车失败', 'result' => ''];
        }
    }

    /**
     * 购物车添加秒杀商品
     * @param $goods_num|购买的商品数量
     * @return array
     */
    private function addFlashSaleCart($goods_num, $goods_spec_key){
        $flashSaleLogic = new FlashSale($this->goods->prom_id);
        $flashSale = $flashSaleLogic->getPromModel();
        $flashSaleIsEnd = $flashSaleLogic->checkFlashSaleIsEnd();
        $goodsStore = $this->goods->store_count;
        if ($goods_spec_key) {
            $goodsSpec = Db::name('spec_goods')->where('goods_id', $this->goods->id)->where('key', $goods_spec_key)->find();
            if ($goodsSpec) {
                $goodsStore = $goodsSpec['store_count'];
            } else {
                return ['status' => 0, 'msg' => '查无此规格商品', 'result' => ''];
            }
        }
        if($flashSaleIsEnd){
            return ['status' => 0, 'msg' => '秒杀活动已结束', 'result' => ''];
        }
        $flashSaleIsAble = $flashSaleLogic->checkActivityIsAble();
        if(!$flashSaleIsAble){
            //活动没有进行中，走普通商品下单流程
            return $this->addNormalCart($goods_num,$goods_spec_key);
        }
        //获取用户购物车的抢购商品
        if (!$this->user_id) {
            $userCartGoods = Db::name('cart')->where(['user_id'=>$this->user_id,'session_id'=>$this->session_id,'goods_id'=>$this->goods->id,'spec_key'=>$goods_spec_key, 'prom_type' => 1, 'prom_id' => $this->goods->prom_id])->find();
            $userCartGoodsNum = Db::name('cart')->where(['user_id'=>$this->user_id, 'session_id'=>$this->session_id, 'goods_id'=>$this->goods->id, 'prom_type' => 1, 'prom_id' => $this->goods->prom_id])->sum('goods_num');
        } else {
            $userCartGoods = Db::name('cart')->where(['user_id'=>$this->user_id,'goods_id'=>$this->goods->id,'spec_key'=>$goods_spec_key, 'prom_type' => 1, 'prom_id' => $this->goods->prom_id])->find();
            $userCartGoodsNum = Db::name('cart')->where(['user_id'=>$this->user_id, 'goods_id'=>$this->goods->id, 'prom_type' => 1, 'prom_id' => $this->goods->prom_id])->sum('goods_num');
        }
        $userCartGoodsNum = empty($userCartGoodsNum) ? 0 : $userCartGoodsNum;///获取用户购物车的抢购商品数量

        $userFlashOrderGoodsNum = $flashSaleLogic->getUserFlashOrderGoodsNum($this->user_id); //获取用户抢购已购商品数量
        $flashSalePurchase = $flashSale['goods_num'] - $flashSale['buy_num'];//抢购剩余库存
        $userBuyGoodsNum = $goods_num + $userFlashOrderGoodsNum + $userCartGoodsNum;
        if($userBuyGoodsNum > $flashSale['buy_limit']){
            return ['status' => 0, 'msg' => '每人限购'.$flashSale['buy_limit'].'件，您已下单'.$userFlashOrderGoodsNum.'件'.'购物车已有'.$userCartGoodsNum.'件', 'result' => ''];
        }
        $userWantGoodsNum = $goods_num + $userCartGoodsNum;//本次要购买的数量加上购物车的本身存在的数量
        if($userWantGoodsNum > $flashSalePurchase){
            return ['status' => 0, 'msg' => '活动商品抢购数量不足，剩余'.$flashSalePurchase.',当前购物车已有'.$userCartGoodsNum.'件', 'result' => ''];
        }
        if($userWantGoodsNum > $goodsStore){
            return ['status' => 0, 'msg' => '商品库存不足，剩余'.$goodsStore.'件,当前购物车已有'.$userCartGoods['goods_num'].'件', 'result' => ''];
        }
        if($userCartGoods){
            $cartResult = Db::name('cart')->where('id', $userCartGoods['id'])->setInc('goods_num', $goods_num);
        }else{
            $cartAddData = [
                'user_id'            => $this->user_id,               // 用户id
                'session_id'         => $this->session_id,            // sessionid
                'goods_id'           => $this->goods->id,             // 商品id
                'goods_sn'           => $this->goods->goods_sn,       // 商品货号
                'goods_name'         => $this->goods->goods_name,     // 商品名称
                'market_price'       => $this->goods->market_price,   // 市场价
                'goods_price'        => $flashSale['price'],                  // 购买价
                'member_goods_price' => $flashSale['price'],                  // 会员折扣价 默认为 购买价
                'goods_num'          => $goods_num,                   // 购买数量
                'add_time'           => time(),                       // 加入购物车时间
                'prom_type'          => 1,                            // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                'prom_id'            => $this->goods->prom_id,                            // 活动id
            ];
            //默认商品原图
            $cartAddData['goods_img'] = $this->goods->original_img;
            if($goods_spec_key){
                $goodsSpec = Db::name('spec_goods')->where('goods_id', $this->goods->id)->where('key', $goods_spec_key)->find();
                $cartAddData['spec_key']      = $goods_spec_key;
                $cartAddData['spec_key_name'] = $goodsSpec['key_name']; //规格 key_name
                $img = Db::name('spec_image')->where(['goods_id' =>$this->goods->id, 'spec_item_id' =>  ['in', explode('_', $goods_spec_key)]])->value('src');
                if ($img) {
                    //使用该规格的图片
                    $cartAddData['goods_img'] = $img;
                }
            }
            $cartResult = Db::name('Cart')->insert($cartAddData);
        }
        if($cartResult !== false){
            return ['status' => 1, 'msg' => '成功加入购物车', 'result' => ''];
        }else{
            return ['status' => 0, 'msg' => '加入购物车失败', 'result' => ''];
        }
    }

    /**
     * 获取购物车商品总数
     */
    public function getUserCartGoodsNum()
    {
        if ($this->user_id) {
            $goods_num = Db::name('cart')->where(['user_id' => $this->user_id])->sum('goods_num');
        } else {
            $goods_num = Db::name('cart')->where(['session_id' => $this->session_id])->sum('goods_num');
        }
        return $goods_num ? $goods_num : 0;
    }

    /**
     * 获取用户购物车欲购买的商品有多少种
     */
    public function getUserCartOrderCount(){
        if (!$this->user_id) {
            return false;
        }
        $count = Db::name('Cart')->where(['user_id' => $this->user_id , 'selected' => 1])->count();
        return $count;
    }

    /**
     * 获取用户购物车列表
     * 0为全部  1为选中
     */
    public function cartList($selected = 0)
    {
        #如果用户已经登录则按照用户id查询
        if ($this->user_id) {
            $cartWhere['user_id'] = $this->user_id;
        } else {
            $cartWhere['session_id'] = $this->session_id;
            $user['user_id'] = 0;
        }
        if ($selected == 1) {
            $cartWhere['selected'] = 1;
        }
        $cartList = DB::name('cart')->where($cartWhere)->select();  //获取购物车商品
        $total_goods_num = $select_goods_num = $total_price = $cut_fee = 0;//初始化数据。商品总共数量/商品总额/节约金额
        foreach ($cartList as $k => $val) {
            #算每种商品的总价
            $cartList[$k]['goods_fee'] = $val['goods_num'] * $val['member_goods_price'];
            #算每种商品的最多购买的数量
            $cartList[$k]['store_count'] = $store_count = getGoodsNum($val['goods_id'], $val['spec_key']);
            #大于库存强制变成库存数
            if ($store_count > 0 && $cartList[$k]['goods_num'] > $store_count) {
                $cartList[$k]['goods_num'] = $store_count;
                DB::name('Cart')->where('id', $val['id'])->update(['goods_num'=>$store_count]);
            }
            if ($store_count <= 0) {
                //没有库存，强制不选择
                DB::name('Cart')->where('id', $val['id'])->update(['selected' => 0]);
                $val['selected'] = 0;
            }
            #只算勾选中的购物车，没勾选的跳过
            if ($val['selected'] == 0){
                continue;
            }
            $total_goods_num += $val['goods_num'];
            $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price'];
            $total_price += $val['goods_num'] * $val['member_goods_price'];
        }
        $total_price = ['total_fee' => $total_price, 'cut_fee' => $cut_fee, 'num' => $total_goods_num];
        //setcookie('cn', $total_goods_num, null, '/');
        return ['cartList' => $cartList, 'total_price' => $total_price];
    }
    /**
     * 登录之后的操作
     * 将购物车未登录的user_id改为用户ID并合并商品
     */
    public function doLoginHandle($user_id)
    {
        if (!$user_id) {
            return;
        }
        //查看是否有未登录保存的购物车
        $noLoginCart = Db::name('cart')->where(['session_id' => $this->session_id, 'user_id' => 0])->select();
        if ($noLoginCart) {
            foreach($noLoginCart as $k => $v) {
                //看看购物车是否存在相同的
                if ($existCart = Db::name('cart')->where(['user_id' => $user_id, 'goods_id' => $v['goods_id'], 'spec_key' => $v['spec_key']])->find()) {
                    //已存在的数量增加
                    Db::name('cart')->where('id', $existCart['id'])->update(['goods_num' => $existCart['goods_num'] + $v['goods_num'], 'selected' => $v['selected']]);
                    //并删除此条
                    Db::name('cart')->delete($v['id']);
                } else {
                    //没有相同的就将user_id改为用户ID即可
                    Db::name('cart')->where('id', $v['id'])->update(['user_id' => $user_id]);
                }
            }
        }
    }

    /**
     * 增加数量
     * $id 购物车ID
     */
    public function increase($id, $num = 1)
    {
        $cart = Db::name('cart')->where('id', $id)->find();
        if (!$cart) {
            return ['status' => 0, 'msg' => '购物车没有该条记录'];
        }
        if ($cart['session_id'] != $this->session_id && $cart['user_id'] != $this->user_id) {
            return ['status' => 0, 'msg' => '无权限修改'];
        }
        if ($cart['prom_type'] > 0) {
            return ['status' => 0, 'msg' => '活动商品不能修改数量'];
        }
        $store_count = getGoodsNum($cart['goods_id'], $cart['spec_key']) ?: 0;
        if ($cart['goods_num'] + $num > $store_count) {
            return ['status' => 0, 'msg' => '库存已达到上限'];
        } else {
            Db::name('cart')->where('id', $id)->setInc('goods_num', $num);
            return ['status' => 1, 'msg' => '添加商品成功'];
        }
    }

    /**
     * 减少数量
     * $id 购物车ID
     */
    public function reduce($id, $num = 1)
    {
        $cart = Db::name('cart')->where('id', $id)->find();
        if (!$cart) {
            return ['status' => 0, 'msg' => '购物车没有该条记录'];
        }
        if ($cart['session_id'] != $this->session_id && $cart['user_id'] != $this->user_id) {
            return ['status' => 0, 'msg' => '无权限修改'];
        }
        if ($cart['prom_type'] > 0) {
            return ['status' => 0, 'msg' => '活动商品不能修改数量'];
        }
        if ($cart['goods_num'] - $num <= 0) {
            return ['status' => 0, 'msg' => '购物车数量最少为一件'];
        } else {
            Db::name('cart')->where('id', $id)->setDec('goods_num', $num);
            return ['status' => 1, 'msg' => '减少商品成功'];
        }
    }
    /**
     * 选中与未选中切换
     */
    public function changeSelected($id)
    {
        $cart = Db::name('cart')->where('id', $id)->find();
        if (!$cart) {
            return ['status' => 0, 'msg' => '购物车没有该条记录'];
        }
        if ($cart['session_id'] != $this->session_id && $cart['user_id'] != $this->user_id) {
            return ['status' => 0, 'msg' => '无权限修改'];
        }
        $selected = $cart['selected'] ? 0 : 1;
        Db::name('cart')->where('id', $id)->update(['selected' => $selected]);
        return ['status' => 1, 'msg' => '修改成功'];
    }
    /**
     * 全部选中
     */
    public function changeSelectedAll($selected)
    {
        $selected = $selected == 1 ? 1 : 0;
        if ($this->user_id) {
            Db::name('cart')->where(['user_id' => $this->user_id])->update(['selected' => $selected]);
        } else {
            Db::name('cart')->where(['session_id' => $this->session_id])->update(['selected' => $selected]);
        }
        return ['status' => 1, 'msg' => '修改成功'];
    }
    /**
     * 移除出购物车
     */
    public function remove($id)
    {
        $info = Db::name('cart')->where('id', $id)->find();
        if (!$info) {
            return ['status' => 0, 'msg' => '购物车没有该件商品'];
        }
        if ($info['session_id'] != $this->session_id && $info['user_id'] != $this->user_id) {
            return ['status' => 0, 'msg' => '无权限修改'];
        }
        Db::name('cart')->delete($id);
        return ['status' => 1, 'msg' => '移除商品成功'];
    }

    /**
     * 修改购物车商品规格
     */
    public function change_cart_spec($id, $spec_key, $num)
    {
        $info = Db::name('cart')->where('id', $id)->find();
        if (!$info) {
            return ['status' => 0, 'msg' => '购物车没有该件商品'];
        }
        if ($info['session_id'] != $this->session_id && $info['user_id'] != $this->user_id) {
            return ['status' => 0, 'msg' => '无权限修改'];
        }
        $this->setGoods(Goods::get($info['goods_id']));
        $goods_image = $this->goods->original_img;
        $goodsSpec = Db::name('spec_goods')->where('goods_id', $this->goods->id)->where('key', $spec_key)->find();
        if (!$goodsSpec) {
            return ['status' => 0, 'msg' => '查无此商品'];
        }
        $goodsStore = $goodsSpec['store_count'] ? $goodsSpec['store_count'] : $this->goods->store_count;//没有规格库存就按商品表库存走
        $goodsPrice = $goodsSpec['price'] ? $goodsSpec['price'] : $this->goods->shop_price;//如果没有规格价格就按商品表价格走
        $img = Db::name('spec_image')->where(['goods_id' =>$this->goods->id, 'spec_item_id' => ['in', explode('_', $spec_key)]])->value('src');
        if ($img) {
            //使用该规格的图片
            $goods_image = $img;
        }
        if($num > $goodsStore){
            return ['status' => 0, 'msg' => '商品库存不足，剩余'.$goodsStore];
        }
        $update = [
            'goods_price'        => $goodsPrice,
            'member_goods_price' => $goodsPrice,
            'goods_num'          => $num,
            'spec_key'           => $spec_key,
            'spec_key_name'      => $goodsSpec['key_name'],
            'goods_img'          => $goods_image,

        ];
        $res = Db::name('cart')->where('id', $id)->update($update);
        if ($res !== false) {
            return ['status' => 1, 'msg' => '修改成功'];
        }
        return ['status' => 0, 'msg' => '修改失败'];
    }
}