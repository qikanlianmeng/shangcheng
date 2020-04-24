<?php

namespace app\admin\controller;

use app\admin\model\GoodsSpecItem;
use think\Db;
use app\admin\model\Goods as GoodsModel;
use app\admin\model\GoodsType;
use app\admin\model\GoodsAttribute;
use app\admin\model\GoodsSpec;
use app\admin\model\GoodsCategory;
use app\admin\model\GoodsBrand;
use think\Request;

class Goods extends Base
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
    /**
     * 商品管理
     */
    #列表
    public function Goods()
    {
        $data = input('get.');
        //组装筛选条件
        $map = [];
        //排序条件
        $order = '';
        if ($data) {
            if (isset($data['cat_id']) && !empty($data['cat_id'])) {
                $catSon = model('GoodsCategory')->getCategorySon($data['cat_id']);
                $cats = [];
                foreach ($catSon as $v) {
                    $cats[] = $v->id;
                }
                $cats[] = $data['cat_id'];
                $map['cat_id'] = ['in', $cats];
            }
            
            if (isset($data['is_on_sale']) && !empty($data['is_on_sale'])) {
                $map['is_on_sale'] = $data['is_on_sale'];
            }
            
            if (isset($data['keywords']) && !empty($data['keywords'])) {
                $map['goods_name'] = ['like', '%' . $data['keywords'] . '%'];
            }
            if(isset($data['status']) && !empty($data['status'])){
                $map[$data['status']] = 1;
            }
            if(isset($data['sales_num']) && !empty($data['sales_num'])){
               $order .= $order?',sales_num '.$data['sales_num']:'sales_num '.$data['sales_num'];
            }
            if(isset($data['click_count']) && !empty($data['click_count'])){
               $order .= $order?',click_count '.$data['click_count']:'click_count '.$data['click_count'];
            }
        }

        $list = GoodsModel::order($order)->where($map)->paginate(10, false, ['query' => $data]);
        $categoryList = model('GoodsCategory')->getCategorySon();
        
        return view('', ['list' => $list, 'data' => $data,'category'=>$categoryList]);
    }
    //添加商品相册
    private function add_images($id,$images){
        Db::name('goods_images')->where(['goods_id'=>$id])->delete();
        foreach ($images as $k => $v) {
            $data = ['goods_id' => $id, 'image_url' => $v];
            Db::name('goods_images')->insert($data);
        }
    }
    #增加
    public function addgoods(GoodsCategory $category, GoodsModel $goods)
    {
        
        if ($this->request->isPost()) {

            $data = input('post.');
            $data['dy_price'] = $data['dy_zhekou']?$data['dl_price']*$data['dy_zhekou']/10:0;
            //var_dump($data);exit;
            $validate = \think\Loader::validate('GoodsValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
            //$goods->data($data, true);
            //$goods->allowField(true)->save();
            $goods_images = $data['goods_images']??[];
            unset($data['file'],$data['goods_images']);
            
            if(($id=Db::name('goods')->insertGetId($data)) > 0){
                $back = ['code' => 1, 'msg' => '添加成功'];
                if(!empty($goods_images)){
                    $this->add_images($id,$goods_images);
                }
            }else{
                $back = ['code' => 0, 'msg' => '添加失败'];
            }
            return json($back);
        }
        $categoryList = model('GoodsCategory')->getCategorySon();
        $brandList     = GoodsBrand::order('order asc, id desc')->select();
        $fid           = Db::name('freight_template')->field('id, name')->select();
        return view('', ['category'=>$categoryList,'brandList' => $brandList, 'fid' => $fid]);
    }
    #编辑
    public function editgoods(GoodsCategory $category, GoodsModel $goods, $id)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['dy_price'] = $data['dy_zhekou']?$data['dl_price']*$data['dy_zhekou']/10:0;
            $validate = \think\Loader::validate('GoodsValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
            //$goods = $goods::get($id);
            //$goods->allowField(true)->save($data);
            $goods_images = $data['goods_images']??[];
            unset($data['file'],$data['goods_images']);
            if(Db::name('goods')->where('id',$id)->update($data) !== false){
                $back = ['code' => 1, 'msg' => '编辑成功'];
                if(!empty($goods_images)){
                    $this->add_images($id,$goods_images);
                }
            }else{
                $back = ['code' => 0, 'msg' => '编辑失败'];
            }
            return json($back);
        }
        $info = $goods::get($id);
        if (!$info) {
            $this->error('查无此商品');
        }
        
        //图片图库
        $info->goods_images = Db::name('goods_images')->where('goods_id', $id)->order('id asc')->column('image_url');
        $categoryList = model('GoodsCategory')->getCategorySon();
        $brandList     = GoodsBrand::order('order asc, id desc')->select();
        $fid           = Db::name('freight_template')->field('id, name')->select();
        return view('', ['info' => $info,'category'=>$categoryList,'brandList' => $brandList, 'fid' => $fid]);
    }
    #删除
    public function delgoods(GoodsModel $goods, $id)
    {
        $goods->destroy($id);
        return json(['code' => 1, 'msg' => '删除成功']);
    }
    /**
     * 商品类型
     */
    #列表
    public function Goodstype()
    {
        $list = GoodsType::paginate(10);
        return view('', ['list' => $list]);
    }
    #添加
    public function addgoodstype(GoodsType $type)
    {
        if ($this->request->isPost()) {
            if (!$name = input('post.name')) {
                return json(['code' => 0, 'msg' => '类型名称不允许为空']);
            }
            $type->name = $name;
            $type->save();
            return json(['code' => 1, 'msg' => '添加成功']);
        }
        return view();
    }
    #编辑
    public function editgoodstype(GoodsType $type, $id)
    {
        if ($this->request->isPost()) {
            if (!$name = input('post.name')) {
                return json(['code' => 0, 'msg' => '类型名称不允许为空']);
            }
            $type = $type::get($id);
            $type->name = $name;
            $type->save();
            return json(['code' => 1, 'msg' => '编辑成功']);
        }
        $info = $type::get($id);
        if (!$info) {
            $this->error('查无此类型');
        }
        return view('', ['info' => $info]);
    }
    #删除
    public function delgoodstype(GoodsType $type, $id)
    {
        if (GoodsAttribute::where('type_id', $id)->find()) {
            return json(['code' => 0, 'msg' => '该商品类型下有属性项，不能删除']);
        }
        if (GoodsSpec::where('type_id', $id)->find()) {
            return json(['code' => 0, 'msg' => '该商品类型下有规格项，不能删除']);
        }
        $type->destroy($id);
        return json(['code' => 1, 'msg' => '删除成功']);
    }

    /**
     * 商品属性
     */
    #列表
    public function Goodsattribute($type_id = 0)
    {
        if ($type_id){
            $list     = GoodsAttribute::where('type_id', $type_id)->order('order asc, id desc')->paginate(10);
        } else {
            $list     = GoodsAttribute::order('order asc, id desc')->paginate(10);
        }
        $typeList = GoodsType::select();
        $input_type = array(0 => '手工录入', 1 => '从列表中选择', 2 => '多行文本框');
        return view('', ['list' => $list, 'typeList' => $typeList, 'type_id' => $type_id, 'input_type' => $input_type]);
    }
    #添加
    public function addgoodsattribute(GoodsAttribute $attribute, $type_id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $validate = \think\Loader::validate('GoodsAttributeValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
            $attribute->data($data);
            $attribute->save();
            return json(['code' => 1, 'msg' => '添加成功']);
        }
        $typeList = GoodsType::select();
        return view('', ['typeList' => $typeList, 'type_id' => $type_id]);
    }
    #编辑
    public function editgoodsattribute(GoodsAttribute $attribute,  $id, $type_id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $validate = \think\Loader::validate('GoodsAttributeValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }

            $type = $attribute::get($id);
            $type->save($data);
            return json(['code' => 1, 'msg' => '编辑成功']);
        }
        $info = $attribute::get($id);
        if (!$info) {
            $this->error('查无此属性');
        }
        $typeList = GoodsType::select();
        return view('', ['info' => $info, 'typeList' => $typeList]);
    }
    #删除
    public function delgoodsattribute(GoodsAttribute $attribute, $id)
    {
        $attribute->destroy($id);
        return json(['code' => 1, 'msg' => '删除成功']);
    }
    /**
     * 商品规格
     */
    #列表
    public function Goodsspec($type_id = 0)
    {
        if ($type_id){
            $list     = GoodsSpec::where('type_id', $type_id)->order('order asc, id desc')->paginate(10);
        } else {
            $list     = GoodsSpec::order('order asc, id desc')->paginate(10);
        }
        $typeList = GoodsType::select();
        return view('',  ['list' => $list, 'typeList' => $typeList, 'type_id' => $type_id]);
    }
    #添加
    public function addgoodsspec(GoodsSpec $spec, $type_id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $validate = \think\Loader::validate('GoodsSpecValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
            $items = $data['items'];
            unset($data['items']);
            $spec->data($data);
            $spec->save();
            $lastID = $spec->getLastInsID();
            $spec->afterSave($lastID, $items);
            return json(['code' => 1, 'msg' => '添加成功']);
        }
        $typeList = GoodsType::select();
        return view('', ['typeList' => $typeList, 'type_id' => $type_id]);
    }
    #编辑
    public function editgoodsspec(GoodsSpec $spec,  $id, $type_id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $validate = \think\Loader::validate('GoodsSpecValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
            $items = $data['items'];
            unset($data['items']);
            $spec = $spec->get($id);
            $spec->save($data);
            $spec->afterSave($id, $items);
            return json(['code' => 1, 'msg' => '添加成功']);
        }
        $info = $spec::get($id);
        if (!$info) {
            $this->error('查无此规格');
        }
        $typeList = GoodsType::select();
        return view('', ['typeList' => $typeList, 'info' => $info]);
    }
    #删除
    public function delgoodsspec(GoodsSpec $spec, $id)
    {

        if (GoodsSpecItem::destroy(['spec_id'=> $id])) {
            $spec->destroy($id);
            return json(['code' => 1, 'msg' => '删除成功']);
        }else{
            return json(['code' => 0, 'msg' => '该商品规格下有规格项，不能删除']);
        }

    }
    /**
     * 商品品牌
     */
    #列表
    public function goodsbrand()
    {
        $list     = GoodsBrand::order('order asc, id desc')->paginate(10);
        return view('', ['list' => $list]);
    }
    #添加
    public function addgoodsbrand(GoodsCategory $category, GoodsBrand $brand)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $validate = \think\Loader::validate('GoodsBrandValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
            unset($data['file']);
            $brand->data($data);
            $brand->save();
            return json(['code' => 1, 'msg' => '添加成功']);
        }
        $categoryList = $category->getCategorySon(0,0);
        return view('', ['categoryList' => $categoryList]);
    }
    #编辑
    public function editgoodsbrand(GoodsCategory $category, GoodsBrand $brand, $id)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $validate = \think\Loader::validate('GoodsBrandValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
            unset($data['file']);
            $brand = $brand::get($id);
            $brand->save($data);
            return json(['code' => 1, 'msg' => '编辑成功']);
        }
        $info = $brand::get($id);
        if (!$info) {
            $this->error('查无此品牌');
        }
        $categoryList = $category->getCategorySon(0,0);
        return view('', ['categoryList' => $categoryList, 'info' => $info]);
    }
    #删除
    public function delgoodsbrand(GoodsBrand $brand, $id)
    {
        $brand->destroy($id);
        return json(['code' => 1, 'msg' => '删除成功']);
    }
    /**
     * 商品分类
     */
    #列表
    public function goodscategory(GoodsCategory $category)
    {
        $list = $category->getCategorySon();
        return view('', ['list' => $list]);
    }
    #添加
    public function addgoodscategory(GoodsCategory $category)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $validate = \think\Loader::validate('GoodsCategoryValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }

            $category->saveCategory($data);
            return json(['code' => 1, 'msg' => '添加成功']);
        }
        $categoryList = $category->getCategorySon(0,0);
        $typeList = GoodsType::select();
        return view('', ['categoryList' => $categoryList, 'typeList' => $typeList]);
    }
    #编辑
    public function editgoodscategory(GoodsCategory $category, $id)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $validate = \think\Loader::validate('GoodsCategoryValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
            if ($id == $data['parent_id_1'] || $id == $data['parent_id_2']) {
                return json(['code' => 0, 'msg' => '自己不能成为自己的顶级分类']);
            }
            $category->saveCategory($data);
            return json(['code' => 1, 'msg' => '编辑成功']);
        }
        $info = $category::get($id);
        if (!$info) {
            $this->error('查无此分类');
        }
        #家谱树
        $parent_id_path = explode('_', $info->parent_id_path);
        $info->topParentId = $info->level > 1 ? $parent_id_path[1] : 0;
        $info->secParentId = $info->level > 2 ? $parent_id_path[2] : 0;
        $categoryList = $category->getCategorySon(0,0);
        #绑定属性
        $info->spec_list = null;
        if ($info->spec_id_str) {
            $info->spec_list = GoodsSpec::all(explode(',', $info->spec_id_str));
            //var_dump($info->spec_list);die;
        }
        $typeList = GoodsType::select();
        return view('', ['categoryList' => $categoryList, 'info' => $info, 'typeList' => $typeList]);
    }
    #删除
    public function delgoodscategory(GoodsCategory $category, $id)
    {
        if ($category::where('parent_id', $id)->find()) {
            return json(['code' => 0, 'msg' => '该商品分类下有子分类，不能删除']);
        }
        if (GoodsModel::where('cat_id', $id)->find()) {
            return json(['code' => 0, 'msg' => '该商品分类下有商品，不能删除']);
        }
        $category->destroy($id);
        return json(['code' => 1, 'msg' => '删除成功']);
    }
    /**
     * ajax根据type_id获取规格和属性
     */
    public function ajaxGetAttr(GoodsType $type, $type_id, $goods_id = 0)
    {
        $info      = $type->get($type_id);
        $items     = array();
        $items_pic = array();
        if ($goods_id) {
            $keys = Db::name('spec_goods')->where('goods_id', $goods_id)->column('key');
            if ($keys) {
                foreach ($keys as $k => $v) {
                    $items = array_merge(explode('_', $v), $items);
                }

                $items = array_unique($items);
            }
            //找items图片
            foreach ($items as $k => $v) {
                $items_pic[$v] = Db::name('spec_image')->where('goods_id', $goods_id)->where('spec_item_id', $v)->value('src');
            }
        }
        //规格模板
        $spec    = $info->goodsSpec;
        $specTpl = '<tr class="long-td"><td colspan="2" style="text-align:left"><b>商品规格</b></td></tr>';
        foreach ($spec as $k => $v) {
            $specTpl .= '<tr class="long-td "><td>'. $v->name .'：</td><td>';
            foreach ($v->GoodsSpecItem as $k2 => $v2) {
                $choosed = '';
                $pic     = '';
                if (in_array($v2->id, $items)) {
                    $choosed = 'choosed';
                    $pic     = $items_pic[$v2->id];
                }
                $pic = $pic ? $pic : '';
                $specTpl .= '<button class="btn '.$choosed.'" type="button" onclick="choosed(this);" spec_id = "'.$v->id.'" item_id = "'.$v2->id.'">'.$v2->item.'</button><label for="image'.$k2.'"><span class="spec_img" id="item_pic_'.$v2->id.'" item_id = "'.$v2->id.'" pic="'.$pic.'">
                </span><input class="updata_img" type="text" value="'.$pic.'" name="item_img['.$v2->id.']"></label>';
            }
            $specTpl .= '</td></tr>';
        }
        //属性模板
        $attribute = $info->goodsAttribute;
        $attr = array();
        if ($goods_id) {
            $attr = Db::name('goods_attr')->where('goods_id', $goods_id)->column('attr_value', 'attr_id');
        }
        $attributeTpl = '<tr class="long-td"><td colspan="2" style="text-align:left"><b>商品属性</b></td></tr>';
        foreach ($attribute as $k => $v) {
            $val = '';
            if (isset($attr[$v->id])) {
                $val = $attr[$v->id];
            }
            switch ($v->input_type) {
                case 0:
                    $attributeTpl .= '<tr class="long-td secondfloor"><td>'.$v->name.'</td><td><input type="text" class="form-control" name="attr['.$v->id.']" value="'.$val.'"></td></tr>';
                    break;
                case 1:
                    $values = explode('|', $v->values);
                    $attributeTpl .= '<tr class="long-td secondfloor"><td>'.$v->name.'</td><td><select class="form-control col-md-3 chosen-select" style="width: 200px; margin-right: 10px;" name="attr['.$v->id.']"><option value="">无</option>';
                    foreach ($values as $value) {
                        $selected = '';
                        if ($val == $value) {
                            $selected = 'selected="selected"';
                        }
                        $attributeTpl .= '<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
                    }
                    $attributeTpl .= '</select></td></tr>';
                    break;
                case 2:
                    $attributeTpl .= '<tr class="long-td secondfloor"><td>'.$v->name.'</td><td><textarea  type="text" class="form-control" name="attr['.$v->id.']" >'.$val.'</textarea></td></tr>';
                    break;
            }
        }
        $data = ['specTpl' => $specTpl, 'attributeTpl' => $attributeTpl];
        return json(['code' => 1, 'msg' => $data]);
    }
    /**
     * ajax获取表格数据
     */
    public function ajaxGetSpecInput(GoodsModel $goods, Array $spec_arr = array(), $goods_id = 0)
    {
        return json(['code' => 1, 'msg' => $goods->getSpecInput($goods_id, $spec_arr)]);
    }
    /**
     * ajax获取分类
     */
    public function ajaxGetCategory($pid, GoodsCategory $category)
    {
        $list = $category->getCategorySon($pid, 0);
        return $list ? json(['code' => 1, 'msg' => $list]) : json(['code' => 0, 'msg' => '该分类下无子分类']);
    }

    /**
     * ajax更改信息
     */
    public function changeinfo($id, $val, $attr, $model)
    {
        Db::name($model)->where('id', $id)->update([$attr=>$val]);
        return json(['code' => 1, 'msg' => '编辑成功']);
    }
    /**
     * 通用更改表单信息
     */
    public function ajaxGetGoodsSpec(GoodsSpec $spec, $id)
    {
        $list = $spec->where('type_id', $id)->order("order asc")->select();
        return $list ? json(['code' => 1, 'msg' => $list]) : json(['code' => 0, 'msg' => '该类型下无规格']);
    }
    /**
     * 通用更改排序方法
     */
    public function changesort($id, $sort, $model)
    {
        Db::name($model)->where('id', $id)->update(['order'=>$sort]);
        return json(['code' => 1, 'msg' => '更改排序成功']);
    }

    public function commentlist()
    {
        $data = input('get.');
        $data['start_time'] = $data['start_time']??'';
        $data['end_time'] = $data['send_time']??'';
        $data['rank'] = $data['rank']??0;
        $map = [];
        if($data['start_time'] && $data['end_time']){
            $map['con.c_time'] = ['between',[strtotime($data['start_time']),strtotime($data['end_time'])]];
        }else{
            if($data['start_time']){
                $map['con.c_time'] = ['>',strtotime($data['start_time'])];
            }
            if($data['end_time']){
                $map['con.c_time'] = ['<',strtotime($data['end_time'])];
            }
        }
        if($data['rank'] > 0){
            $map['con.rank'] = $data['rank'];
        }
        //print_r($map);exit;
        
        $list = Db::name('goods_comment')->alias('com')->field('com.uid,com.infoId,com.orderId,con.*')->join('goods_comment_content con', 'com.id = con.commentId', 'right')->where($map)->order('con.id desc')->paginate(10,false,['query'=>$data]);
        $extend = [];
        foreach ($list as $k => $v) {
            $extend[$k]['goods_name'] = Db::name('goods')->where('id', $v['infoId'])->value('goods_name');
            $extend[$k]['user_name']  = Db::name('member')->where('id', $v['uid'])->value('account');
            $extend[$k]['type']       = $v['type'] == 1 ? '追评' : '评论';
            $extend[$k]['c_time']     = date('Y-m-d H:i:s', $v['c_time']);
            $extend[$k]['rank']       = $v['rank'];
            $extend[$k]['content']    = $v['content'];
            $extend[$k]['id']         = $v['id'];
        }
        return view('', ['list' => $list, 'extend' => $extend,'data'=>$data]);
    }

    public function replycomment($id)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            if (!$data['reply_content']) {
                return json(['code' => 0, 'msg' => '回复内容不能为空']);
            }
            $update['reply_content'] = $data['reply_content'];
            $update['reply_time']    = time();
            $update['reply_uid']     = session('uid');
            Db::name('goods_comment_content')->where('id', $id)->update($update);
            return json(['code' => 1, 'msg' => '回复成功']);
        }
        $reply_content = Db::name('goods_comment_content')->where('id', $id)->value('reply_content');
        return view('', ['id' => $id, 'reply_content' => $reply_content]);
    }

    public function delcomment()
    {
        $param = input('param.');
        $id = $param['id'];
        $where = [];
        if(is_array($id)){
            $where['id'] = ['in',$id];
        }else{
            $where['id'] = $id;
        }
        if(Db::name('goods_comment_content')->where($where)->delete()){
            return json(['code' => 1, 'msg' => '删除成功']);
        }else{
            echo Db::name('goods_comment_content')->getLastSql();
            return json(['code' => 0, 'msg' => '删除失败']);
        }
        
        
    }
}
