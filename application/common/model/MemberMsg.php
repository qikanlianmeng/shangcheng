<?php

namespace app\common\model;
use think\Model;
use think\Db;

class MemberMsg extends Model
{ 
	public function msg_list($uid,$page,$pagesize){
		$res = $this->alias('mg')
				->field('mg.*,sg.title as title2,sg.content as content2,sg.send_time as send_time2')
				->join('think_system_msg sg','mg.content_id = sg.id','left')
				->where("mg.receive_uid",$uid)
				->order('mg.id desc')
				->page($page,$pagesize)
				->select();
		$res =  collection($res)->toArray();
		$list = array();
		foreach($res as $k=>$v){
			$list[$k]['id'] = $v['id'];
			$list[$k]['status'] = $v['status'];
			if($v['content_id'] > 0){
				$list[$k]['title'] = $v['title2'];
				$list[$k]['content'] = unserialize($v['content2']);
				$list[$k]['send_time'] = date('Y-m-d',$v['send_time2']);
			}else{
				$list[$k]['title'] = $v['title'];
				$list[$k]['content'] = unserialize($v['content']);
				$list[$k]['send_time'] = date('Y-m-d',$v['send_time']);
			}
			
		}
		return array(
					'list' 		=> $list,
					'noread'	=> $this->where(['receive_uid'=>$uid,'status'=>0])->count()
						);
	}
}