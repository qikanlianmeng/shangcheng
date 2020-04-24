<?php

namespace app\admin\controller;

use app\common\model\YangCameraModel;
use app\common\model\YangSickModel;
use app\common\model\YangTenderModel;
use app\common\model\YangYangModel;
use app\common\model\YangYenderReceiveModel;
use think\Db;

class Yang extends Base
{

    public function index(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['title'] = ['like',"%" . $key . "%"];
        }
        $YangTenderModel = new YangTenderModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $YangTenderModel->getAllCount($map);//计算总页面
        $allpage = intval(ceil($count / $limits));       
        $lists = $YangTenderModel->getMemberByWhere($map, $Nowpage, $limits);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    public function add_index()
    {
        if(request()->isAjax()){
            $param = input('post.');
            $YangTenderModel = new YangTenderModel();
            $flag = $YangTenderModel->insertOne($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }

    public function edit_index()
    {
        $YangTenderModel = new YangTenderModel();
        if(request()->isAjax()){
            $param = input('post.');


            $info = $YangTenderModel->get($param['id']);
            if($info['status']==0){
                //if($info['num']!=$param['num'] || $info['receive_num']!=$param['receive_num'] || $info['start_time']!=$param['start_time']){}
                return json(['code' => 0, 'data' => '', 'msg' => '该认养已结束无法修改信息']);
            }else{
                if($info['receive_num']>0 && $info['start_time']!=$param['start_time']){
                    return json(['code' => 0, 'data' => '', 'msg' => '认养已开始不能修改时间']);
                }
            }

            if($param['num']<$param['receive_num']){
                return json(['code' => 0, 'data' => '', 'msg' => '已认养数量不能大于总数量']);
            }
            if($param['num']==$param['receive_num']){
                $param['status'] = 0;//认养结束
            }
            $flag = $YangTenderModel->editOne($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');

        $this->assign([
            'info' => $YangTenderModel->get($id),

        ]);
        return $this->fetch();
    }
    public function del_index()
    {
        $id = input('param.id');
        $YangTenderModel = new YangTenderModel();
        $flag = $YangTenderModel->delOne($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
    public function index_status()
    {
        $id = input('param.id');
        $status = Db::name('yang_tender')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('yang_tender')->where('id',$id)->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('yang_tender')->where('id',$id)->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    
    }


    public function receive(){

        $key = input('key');
        $tender_id = input('tender_id',0);
        $map = [];
        if($key&&$key!=="")
        {
            $map['uid|tender_id'] = ['like',"%" . $key . "%"];
        }
        if($tender_id&&$tender_id!==0){
            $map['tender_id'] = $tender_id;
        }
        $YangTenderModel = new YangYenderReceiveModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $YangTenderModel->getAllCount($map);//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = $YangTenderModel->getMemberByWhere($map, $Nowpage, $limits);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('tender_id', $tender_id);
        if(input('get.page'))
        {


            return json($lists);
        }
        return $this->fetch();
    }
    public function receive_status()
    {
        $id = input('param.id');
        $status = Db::name('yang_tender_receive')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('yang_tender_receive')->where('id',$id)->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('yang_tender_receive')->where('id',$id)->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }

    }

    //羊记录
    public function yangyang(){

        $key = input('key');
        $tender_id = input('tender_id',0);
        $receive_id = input('receive_id',0);
        $map = [];
        if($key&&$key!=="")
        {
            $map['uid|tender_id'] = ['like',"%" . $key . "%"];
        }
        if($tender_id&&$tender_id!==0){
            $map['tender_id'] = $tender_id;
        }
        if($receive_id&&$receive_id!==0){
            $map['receive_id'] = $receive_id;
        }
        $YangTenderModel = new YangYangModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $YangTenderModel->getAllCount($map);//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = $YangTenderModel->getMemberByWhere($map, $Nowpage, $limits);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('tender_id', $tender_id);
        $this->assign('receive_id', $receive_id);
        if(input('get.page'))
        {

            foreach ($lists as $v){
                $v->nickname=Db::name('member')->where(['id'=>$v->uid])->value('nickname');
                $v->title=Db::name('yang_tender')->where(['id'=>$v->tender_id])->value('title');
                $v->receive_id=Db::name('yang_tender_receive')->where(['id'=>$v->receive_id])->value('id');
                $v->start_date=date('Y-m-d', $v->start_date);
                $v->end_date=date('Y-m-d', $v->end_date);
            }

            return json($lists);
        }
        return $this->fetch();
    }

    //小羊生病记录
    public  function sick(){
        $key = input('key');
        $yang_id = input('yang_id',0);
        $receive_id = input('receive_id',0);
        $map = [];
        if($key&&$key!=="")
        {
            $map['uid|tender_id'] = ['like',"%" . $key . "%"];
        }
        if($yang_id&&$yang_id!==0){
            $map['yang_id'] = $yang_id;
        }
        if($receive_id&&$receive_id!==0){
            $map['receive_id'] = $receive_id;
        }
        $YangSickModel = new YangSickModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数

        $count = $YangSickModel->getAllCount($map);//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = $YangSickModel->getMemberByWhere($map, $Nowpage, $limits);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('yang_id', $yang_id);
        $this->assign('receive_id', $receive_id);
        if(input('get.page'))
        {

            foreach ($lists as $v){
                $v->nickname=Db::name('member')->where(['id'=>$v->uid])->value('nickname');
            //    $v->title=Db::name('yang_tender')->where(['id'=>$v->tender_id])->value('title');
             //   $v->receive_id=Db::name('yang_tender_receive')->where(['id'=>$v->receive_id])->value('id');
                $v->start_time=date('Y-m-d', $v->start_time);
                $v->end_time=date('Y-m-d', $v->end_time);
            }

            return json($lists);
        }
        return $this->fetch();
    }

    public function camera(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['title'] = ['like',"%" . $key . "%"];
        }
        $YangTenderModel = new YangCameraModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $YangTenderModel->getAllCount($map);//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = $YangTenderModel->getMemberByWhere($map, $Nowpage, $limits);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }
    public function add_camera()
    {
        if(request()->isAjax()){
            $param = input('post.');
            $YangTenderModel = new YangCameraModel();
            $flag = $YangTenderModel->insertOne($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }
    public function edit_camera()
    {
        $YangTenderModel = new YangCameraModel();
        if(request()->isAjax()){
            $param = input('post.');
            if(empty($param['password'])){
                unset($param['password']);
            }else{
                $param['password'] = md5(md5($param['password']) . config('auth_key'));
            }
            $flag = $YangTenderModel->editOne($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');

        $this->assign([
            'info' => $YangTenderModel->get($id),

        ]);
        return $this->fetch();
    }
    public function del_camera()
    {
        $id = input('param.id');
        $YangTenderModel = new YangCameraModel();
        $flag = $YangTenderModel->delOne($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
    public function camera_status()
    {
        $id = input('param.id');
        $status = Db::name('yang_camera')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('yang_camera')->where('id',$id)->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('yang_camera')->where('id',$id)->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }

    }
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}