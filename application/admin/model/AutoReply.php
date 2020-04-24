<?php
namespace app\admin\model;
use think\Model;
use think\Db;

class AutoReply extends Model
{
     protected $name = 'wxin_autoreply';
     /**
      * 微信自动回复添加 
      * @param array $param
      */
     public function addReply($param){
     	try{
     		$param['key_word']=trim($param['key_word']);
     		$param['key_word']='|'.trim($param['key_word'],'|').'|';
     		$result = $this->allowField(true)->save($param);
     		if($result){
     			return array('status'=>1,'msg'=>'添加成功');
     		}else{
     			return array('status'=>0,'msg'=>'添加失败');
     		}
     	}catch( PDOException $e){
     		return array('status'=>0,'msg'=>$e->getMessage());
     	}
     }  
}