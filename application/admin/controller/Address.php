<?php

namespace app\admin\controller;
use app\admin\model\RegionModel;
use think\Db;
class Address extends Base
{
    //地址
    public function index(){
    	$model=new RegionModel();
    	$list=db('region')->field('id,name,level,parent_id,"" as child')->where(array('parent_id'=>'0'))->paginate(1);
    	$result=$this->ObjtoArr($list)['data'];
    	foreach ($result as $k=>$v){
    		$result[$k]['child']=$this->ObjtoArr($model->getsubList($v['id']));
    		if($result[$k]['child']){
	    		foreach ($result[$k]['child'] as $kk=>$vv){ 	
	    			$result[$k]['child'][$kk]['child']=$this->ObjtoArr($model->getsubList($vv['id']));
	    		}
    		}
    	}
    	$this->assign('list', $result);
    	$this->assign('page',$list->render());
        return $this->fetch();
    }
    public function index_add(){
       $model = new RegionModel();
       $list=$model->treeList();
       $this->assign('list',$list);
       return $this->fetch();
    }
    public function index_edit(){
      $model=new RegionModel();
      $id=input('id');
      $row=db('region')->find($id);
      if($row['parent_id']){
		  $name=$model->getArea($row['parent_id']);
      }else{
      	  $name='无';
      }
      $row['pname']=$name;
	  $this->assign('row',$row); 
      return $this->fetch();
   }
   /**
    * 修改地区
    */
   public function ajax_edit(){
   	  $model=db('region');
   	  $id=input('id');
   	  $name=input('name');
   	  if($model->where(array('id'=>$id,'name'=>$name))->find()){
   	  	return json(array('status'=>1,'msg'=>'保存成功'));
   	  }
   	  if($model->where(array('id'=>$id))->update(array('name'=>$name))){
   	  	return json(array('status'=>1,'msg'=>'保存成功'));
   	  }else{
   	  	return json(array('status'=>0,'msg'=>'保存失败'));
   	  }
   }
   public function index_del(){
     return $this->fetch();
   }
   /**
    * 删除地区
    */
   public function ajax_del(){
   	   $model=db('region');  
   	   $id=input('id');
   	   if($model->where(array('parent_id'=>$id))->count()>0){
   	    	return json(array('status'=>0,'msg'=>'有下级地区，无法删除'));
   	   }
   	   if($model->delete($id)){
   	   		return json(array('status'=>1,'msg'=>'删除成功'));
   	   }else{
   	   	    return json(array('status'=>0,'msg'=>'删除失败'));
   	   }
   }
   /**
    * ajax地址添加
    */
   public function ajax_add_address(){
   	   $model=new RegionModel();  
   	   $data['parent_id']=input('parent_id','0');
   	   $data['name']=input('name');
   	   if($model->getReionCount($data['parent_id'],$data['name'])>0){
   	   	  return json(array('status'=>0,'msg'=>'地区已存在'));
   	   }
   	   if($data['parent_id']=='0'){
   	   	  $data['level']=1;
   	   }else{
	   	   $row=$model->getOneRegion($data['parent_id']);
	   	   if(!$row){
	   	   	 return json(array('status'=>0,'msg'=>'添加失败'));
	   	   }
	   	   $data['level']=$row->level+1;
   	   }
   	   $sc=db('region')->insert($data);
   	   if($sc){
   	   	 return json(array('status'=>1,'msg'=>'添加成功'));
   	   }else{
   	   	 return json(array('status'=>0,'msg'=>'添加失败'));
   	   }
   }
   /**
    * 对象专数组
    */
   public function ObjtoArr($obj){
   	if(!$obj){
   		return "";
   	}
   	$json=json_encode($obj);
   	return json_decode($json,true);
   }


   


}