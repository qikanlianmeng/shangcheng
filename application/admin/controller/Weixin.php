<?php
namespace app\admin\controller;
use Qiniu\json_decode;
use app\admin\model\AutoReply;
use app\admin\model\WxinMenu;
use app\admin\model\ReplyInfo;
class Weixin extends Base
{
	public function test(WxinMenu $model){
		try {
			throw new \Exception('类型有误',0);
		}catch (\Exception $e){
			echo $e->getMessage();
		}
	}
	//自定义回复
	public function autoreply(){
		$list=db('wxin_autoreply')->field('id,rule_name,TRIM(BOTH "|" FROM key_word) as key_word,content,status')->order('id desc')->paginate(10);
		$this->assign('list', $list);
		$this->assign('page',$list->render());
		return $this->fetch();
	}
	//自定义回复
	public function autoreply_add(){
		return $this->fetch();
	}
	//自定义回复
	public function autoreply_edit(){
		$id=input('id');
		$row=db('wxin_autoreply')->field('id,rule_name,TRIM(BOTH "|" FROM key_word) as key_word,content,status')->find($id);
		$this->assign('row',$row);
		return $this->fetch();
	}
	/**
	 * 添加回复规则内容
	 */
	public function add_reply(){
		$param=input('post.');
		if(!trim($param['key_word'])){
			$this->error('关键词不可以是空');
		}
		$reply=new AutoReply();
		$info=$reply->addReply($param);
		if($info['status']){
			$this->success('添加成功',url('autoreply'));
		}else{
			$this->error($info['msg']);
		}
	}
	/**
	 * 修改
	 */
	public function edit_reply(){
		$param=input('post.');
		if(!trim($param['key_word'])){
			$this->error('关键词不可以是空');
		}
		$param['key_word']=trim($param['key_word']);
		$param['key_word']='|'.trim($param['key_word'],'|').'|';
		if(db('wxin_autoreply')->update($param)){
			$this->success('更新成功',url('autoreply'));
		}else{
			$this->error('更新失败');
		}
	}
	/**
	 * 删除微信自定义回复
	 */
	public function autoreply_del(){
		$id=input('id');
		if(db('wxin_autoreply')->delete($id)){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}
	/**
	 * 更改状态
	 */
	public function change_status(){
		$status=input('status');
		$id=input('id');
		if($status=='1'){
			$status='0';
			$su='启用成功';
			$err='启用失败';
		}else{
			$status='1';
			$su='禁用成功';
			$err='禁用失败';
		}
		if(db('wxin_autoreply')->where(array('id'=>$id))->update(array('status'=>$status))){
			$this->success($su);
		}else{
			$this->error($err);
		}
	}
	//菜单设置
	public function menu(WxinMenu $menu){
		$list=$menu->menulist();
		return view('',array('list'=>$list));
	}
	/**
	 *微信菜单添加
	 */
	public function menu_add(WxinMenu $option){
		$menu=$option->optionlist(array('pid'=>0));
		$this->assign('menu',$menu);
		return $this->fetch();
	}
	public function add_menu(){
		$param=input('post.');
		try{
			$model=new WxinMenu();
			$result = $model->addmenu($param);
			if($result['status']){
				$this->success('添加成功',url('menu'));
			}else{
				if(!$result['msg'])$result['msg']='添加失败';
				$this->error($result['msg']);
			}
		}catch( PDOException $e){
			$this->error($e->getMessage());
		}
	}
	/**
	 * 生成微信菜单
	 * @return
	 */
	public function wxin_menu(){
		$model=new WxinMenu();
		$res = $model->menugenerate();
		return json($res);
	}
	/**
	 * 自定义菜单修改页
	 */
	public function menu_edit(WxinMenu $model,$id){
		$option=$model->optionlist(array('pid'=>0,'id'=>array('neq',$id)));
		$row=$model->rowWxin($id);
		return view('',array('menu'=>$option,'row'=>$row));
	}
	/**
	 * 自定义菜单修改
	 */
	public function edit_menu(WxinMenu $model){
		$param=input('post.');
		$info=$model->editMenu($param);
		if($info['status']){
			$this->success('修改成功',url('menu'));
		}else{
			if(!$info['msg'])$info['msg']='没有修改任何内容';
			$this->error($info['msg']);
		}
	}
	/**
	 * 菜单删除
	 */
	public function menu_del(WxinMenu $model,$id){
		if($model->optionlist(array('pid'=>$id))){
			$this->error('下面有子菜单，不能删除.');
		}
		if(db('wxin_menu')->delete($id)){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}
	public function msg_add($id){
		$row=db('wxin_reply_info')->where(array('wxin_menu_id'=>$id))->find();
		$this->assign('row',$row);
		$this->assign('id',$id);
		return $this->fetch();
	}
	/**
	 * 回复内容添加
	 */
	public function add_msg(ReplyInfo $model){
		$param=input('post.');
		if($row=db('wxin_reply_info')->where(array('wxin_menu_id'=>$param['wxin_menu_id']))->find()){
			$result=$model->updataData($param);
			if($result['status']=='1'){
				$this->success('保存成功',url('menu'));
			}else{
				$this->error($result['msg']);
			}
		}else{
			unset($param['id']);
			$result=$model->saveData($param);
			if($result['status']=='1'){
				$this->success('保存成功',url('menu'));
			}else{
				$this->error($result['msg']);
			}
		}
	}
	/**
	 * 图文添加
	 */
	public function thumb_add($id){
		$row=db('wxin_reply_info')->where(array('wxin_menu_id'=>$id))->find();
		$this->assign('row',$row);
	   	$this->assign('id',$id);
	   	return $this->fetch();
   }
}