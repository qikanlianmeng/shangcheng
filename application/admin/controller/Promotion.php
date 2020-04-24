<?php
/**
 * 优惠活动管理
 * prom_type 1=>抢购  2=>团购   3=>促销    4=>预售
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/22
 * Time: 9:01
 */
namespace app\admin\controller;

use think\Db;
use think\Request;
use app\admin\model\GoodsBrand;
use app\common\model\Goods;
class Promotion extends Base
{
    protected $request;
    protected $backUrl;

    public function __construct(Request $request)
    {
        parent::__construct();
        header("Content-type:text/html;charset=utf-8");
        $this->request = $request;
        $this->backUrl = isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
        $this->assign("backUrl", $this->backUrl);
    }

    //抢购管理
    public function flash_sale()
    {
        $list = Db::name('flash_sale')->order('id desc')->paginate(10);
        return view('', ['list' => $list]);
    }

    //抢购编辑管理
    public function flash_sale_info($id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $validate = \think\Loader::validate('FlashSale');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time']   = strtotime($data['end_time']);
            if (empty($data['id'])) {
                $insert_id = Db::name('flash_sale')->insertGetId($data);
                $r = Db::name('goods')->where('id', $data['goods_id'])->update(['prom_id' => $insert_id, 'prom_type' => 1]);
            } else {
                $r = Db::name('flash_sale')->where('id' ,$data['id'])->update($data);
                Db::name('goods')->where(['prom_type' => 1, 'prom_id' => $data['id']])->update(array('prom_id' => 0, 'prom_type' => 0));
                Db::name('goods')->where("id", $data['goods_id'])->update(array('prom_id' => $data['id'], 'prom_type' => 1));
            }
            if ($r !== false) {
                $return = ['code' => 1, 'msg' => '编辑抢购活动成功', 'result' => ''];
            } else {
                $return = ['code' => 0, 'msg' => '编辑抢购活动失败', 'result' => ''];
            }
            return json($return);
        }
        $info = [];
        if ($id > 0) {
            $info =  Db::name('flash_sale')->where("id", $id)->find();
            $info['start_time'] = date('Y-m-d H:i', $info['start_time']);
            $info['end_time'] = date('Y-m-d H:i', $info['end_time']);
            $info['goods_info'] = Db::name('goods')->where("id", $info['goods_id'])->find();
        }
        $categoryList = model('GoodsCategory')->getCategorySon();
        $brandList = GoodsBrand::order('order asc')->select();
        return view('', ['categoryList' => $categoryList, 'brandList' => $brandList, 'info' => $info]);
    }
    public function flash_sale_del($id)
    {
        Db::name('flash_sale')->where("id", $id)->delete();
        Db::name('goods')->where(['prom_type' => 1, 'prom_id' => $id])->update(array('prom_id' => 0, 'prom_type' => 0));
        return json(['code' => 1, 'msg' => '删除活动成功', 'result' => '']);
    }
    public function getGoodsList()
    {
        $data  = input('get.');
        $model = new Goods();
        $res = $model->getGoodsList($data['cat_id'], $data['brand_id'], $data['recom_type'], $data['keywords'], 0, 1);
        if ($res['code']) {
            return json(['code' => 1, 'msg' => $res['data']]);
        }
        return json(['code' => 0, 'msg' => '没有数据']);
    }
}