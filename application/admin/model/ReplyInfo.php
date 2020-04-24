<?php
namespace app\admin\model;
use think\Model;
use think\Db;
class ReplyInfo extends Model{
	protected $name = 'wxin_reply_info';
	protected $autoWriteTimestamp = true;
	public function saveData($param){
		$result = $this->validate('ReplyVaildate')->allowField(true)->save($param);
		if($result){
			return array('status'=>1,'msg'=>'添加成功');
		}else{
			return array('status'=>0,'msg'=>$this->getError());
		}
	}
	Public function updataData($param){
		$result = $this->validate('ReplyVaildate')->isUpdate(true)->allowField(true)->save($param);
		if($result){
			return array('status'=>1,'msg'=>'更新成功');
		}else{
			return array('status'=>0,'msg'=>$this->getError());
		}
	}
}
