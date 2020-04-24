<?php
namespace app\admin\model;
use Payment\Common\Weixin\Data\Charge\PubChargeData;

use think\Model;
use think\Db;
class LinkModel extends Model{
	protected $name = 'link';
	protected $autoWriteTimestamp = true;
	/**
	 * 添加友情链接
	 */
	public function addData($param){
		$result = $this->validate('LinkVaildate')->allowField(true)->save($param);
		if($result){
			return array('status'=>1,'msg'=>'添加成功');
		}else{
			return array('status'=>0,'msg'=>$this->getError());
		}
	}
	/**
	 * 修改友情链接
	 * @param $param
	 */
	public function saveData($param){
		$result = $this->validate('LinkVaildate')->isUpdate(true)->allowField(true)->save($param);
		if($result){
			return array('status'=>1,'msg'=>'更新成功');
		}else{
			return array('status'=>0,'msg'=>$this->getError());
		}
	}
	Public function updataData($param){
		$result = $this->isUpdate(true)->allowField(true)->save($param);
		if($result){
			return array('status'=>1,'msg'=>'更新成功');
		}else{
			return array('status'=>0,'msg'=>$this->getError());
		}
	}
}
