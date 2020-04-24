<?php
namespace app\admin\model;
use think\Model;
use think\Db;

class MemberGrade extends Model
{
	protected $autoWriteTimestamp = true;
	protected $name = 'member_grade';
	/**
	 * 等级添加
	 * @param array $param
	 */
	public function addGrade($param){
		$result = $this->allowField(true)->save($param);
		if($result){
			return array('status'=>1,'msg'=>'添加成功');
		}else{
			return array('status'=>0,'msg'=>'添加失败');
		}
	}
	
	Public function updataGrade($param){
		$result = $this->validate('UserGradeValidate')->isUpdate(true)->allowField(true)->save($param);
		if($result){
			return array('status'=>1,'msg'=>'更新成功');
		}else{
			return array('status'=>0,'msg'=>$this->getError());
		}
	}
}