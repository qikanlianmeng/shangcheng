<?php

namespace app\admin\model;
use think\Model;

class MsgConfig extends Model
{
    
    //自定义初始化
    protected function initialize()
    {
        //需要调用Model的initialize方法
        parent::initialize();
        //TODO:自定义的初始化
    }
	//获取要发送的消息列表
	public function get_msg_list(){
		$list = $this->paginate(10);
		$page = $list->render();
		
		if($list){
			$list = collection($list->items())->toArray();
			//$auth_arr = array(1,2,4,8,16);
            $auth_arr = array(1);
			foreach($list as $key=>$val){
				//构造消息发送方式状态数组
				foreach($auth_arr as $v){
					if($val['auth'] & $v){
						$list[$key]['auth_arr'][$v] = 1;
					}else{
						$list[$key]['auth_arr'][$v] = 0;
					}
				}
			}
		}
		return array('list'=>$list,'page'=>$page);
	}
	//获得一条消息的基本信息
	public function get_msg_info($id){
		$info = $this->where('id',$id)->find();
		//构造消息发送方式数组
		$info = $info->data;
		$info['param'] = unserialize($info['param']);
		$auth_arr = array(1,2,4,8,16);
		foreach($auth_arr as $v){
			if($info['auth'] & $v){
				$info['auth_arr'][] = $v;
			}else{
                $info['auth_arr'][] = 0;
            }
		}
		return $info;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
}