<?php

namespace app\admin\controller;
use app\admin\model\LinkModel;
class Link extends Base
{
	//友情连接
	public function index(){
		$kw=input('kw','');
		$order=input('order','');
		$arr=array('var_page'=>'page','query' => array('kw'=>$kw));
		$list = db('link')->where(array('title'=>array('like',"%{$kw}%")))->order('sort','asc')->paginate(10,false,$arr);
		$page = $list->render();
		$this->assign('page',$page);
		$this->assign('kw', $kw);
		$this->assign('list', $list);
		return $this->fetch();
	}
	 
	public function index_add(){
		return $this->fetch();
	}
	/**
	 * 添加友情链接数据
	 * @param Link $model
	 */
	public function add_data(LinkModel $model){
		$param=input('post.');
		$result=$model->addData($param);
		return json($result);
	}
	/**
	 * 更新操作
	 */
	public function update_fields(){
		$model=new LinkModel();
		$data=input();
		$result=$model->updataData($data);
		if($result['status']=='1'){
			$this->success('操作成功',url('index'));
		}else{
			$this->success('操作失败');
		}
	}
	/**
	 * ajax更新数据
	 */
	public function ajax_update(){
		$model=new LinkModel();
		$data=input();
		$result=$model->updataData($data);
		return json($result);
	}
	/**
	 * 更新友情链接数据
	 */
	public function update_data(LinkModel $model){
		$param=input('post.');
		$result=$model->saveData($param);
		return json($result);
	}
	public function index_edit(){
		$id=input('id');
		$row=db('link')->find($id);
		$this->assign('row',$row);
		return $this->fetch();
	}
	/**
	 * 删除友情链接
	 */
	public function index_del(){
		$id=input('id');
		if(db('link')->delete($id)){
			$this->success('删除成功');
		}else{
			$this->success('删除失败');
		}
	}




	 


}