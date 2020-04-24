<?php

namespace app\admin\controller;
use app\admin\model\ConfigModel;
use think\Db;

class Config extends Base
{
    //日期配置
    public function dates(){
        if(request()->isPost()){
            $data= input('post.','');
            $new_data = [];
            $dy_open = $data['dy_open'];
            $dy_order_limit = $data['dy_order_limit'];
            unset($data['dy_open'],$data['dy_order_limit']);
            if(!empty($data)){
                //把一周七天的数组键值转换为数字，方便之后对比
                foreach($data as $k=>$v){
                    $key = intval(str_replace('date','',$k));
                    //var_dump($key);exit;
                    $new_data[$key] = $v;
                }
                $new_data = json_encode($new_data);
            }
            Db::name('config')->where('name','dy_discount')->update(['value'=>$data['dy_discount']]);
            Db::name('config')->where('name','dy_dates')->update(['value'=>$new_data]);
            Db::name('config')->where('name','dy_open')->update(['value'=>$dy_open]);
            Db::name('config')->where('name','dy_order_limit')->update(['value'=>$dy_order_limit]);
            Db::name('config')->where('name','dy_goods_num')->update(['value'=>$data['dy_goods_num']]);
            Db::name('config')->where('name','dy_real_info')->update(['value'=>$data['dy_real_info']]);
            cache('db_config_data',null);//清除缓存
            $this->success('保存成功！');
        }
        $dates = config('dy_dates');
        $dy_open = config('dy_open');
        $dy_order_limit = config('dy_order_limit');
        $dy_goods_num = config('dy_goods_num');
        if(!empty($dates)){
            $time_arr = json_decode($dates,true);
        }else{
            $time_arr = [];
        }
        //print_r($time_arr);exit;
        $this->assign([
            'time_arr'  => $time_arr,
            'dy_open'   => $dy_open,
            'dy_order_limit'=> $dy_order_limit,
            'dy_goods_num'  => $dy_goods_num,
            'dy_discount'   =>config('dy_discount'),
            'dy_real_info'   =>config('dy_real_info')
        ]);
        return $this->fetch();
    }
    /**
     * [index 配置列表]
     * @author [田建龙] [864491238@qq.com]
     */
    public function index(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['title'] = ['like',"%" . $key . "%"];          
        }       
        $config = new ConfigModel();
        $nowpage = input('get.page') ? input('get.page'):1;
        $limits = 10;// 获取总条数
        $count = $config->getAllCount($map);//计算总页面
        $allpage = intval(ceil($count / $limits));      
        $list = $config->getAllConfig($map, $nowpage, $limits);  
        $this->assign('nowpage', $nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        $this->assign('list', $list);
        return $this->fetch();
    }
    

    /**
     * [add_config 添加配置]
     * @author [田建龙] [864491238@qq.com]
     */
    public function add_config()
    {
        if(request()->isAjax()){

            $param = input('post.');
            $config = new ConfigModel();
            $flag = $config->insertConfig($param);
            cache('db_config_data',null);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }


    /**
     * [edit_config 编辑配置]
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit_config()
    {
        $config = new ConfigModel();
        if(request()->isAjax()){
            $param = input('post.');
            $flag = $config->editConfig($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info = $config->getOneConfig($id);
        $this->assign('info', $info);
        return $this->fetch();
    }


    /**
     * [del_config 删除配置]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del_config()
    {
        $id = input('param.id');
        $config = new ConfigModel();
        $flag = $config->delConfig($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * [user_state 配置状态]
     * @author [田建龙] [864491238@qq.com]
     */
    public function status_config()
    {
        $id = input('param.id');
        $status = Db::name('config')->where(array('id'=>$id))->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('config')->where(array('id'=>$id))->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('config')->where(array('id'=>$id))->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    
    }


    /**
     * [获取某个标签的配置参数]
     * @author [田建龙] [864491238@qq.com]
     */
    public function group() {
        $id   = input('id',1);
        $type = config('config_group_list'); 
        $list = Db::name("Config")->where(array('status'=>1,'group'=>$id))->field('id,name,title,extra,value,remark,type')->order('sort')->select();
        if($list) {
            $this->assign('list',$list);
        }
        $this->assign('id',$id);
        return $this->fetch();
    }



    /**
     * [批量保存配置]
     * @author [田建龙] [864491238@qq.com]
     */
    public function save($config){
        if($config && is_array($config)){
            $Config = Db::name('Config');
            foreach ($config as $name => $value) {
                $map = array('name' => $name);
                $Config->where($map)->setField('value', $value);
            }
        }
        cache('db_config_data',null);
        $this->success('保存成功！');
    }

}