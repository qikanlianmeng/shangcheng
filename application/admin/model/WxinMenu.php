<?php

namespace app\admin\model;
use Qiniu\json_decode;
use Wechat\WechatMenu;
use think\Cache;
use think\Model;
use think\Db;

class WxinMenu extends Model
{
     protected $name = 'wxin_menu';
     protected $tokenurl = 'https://api.weixin.qq.com/cgi-bin/token';
     protected $menuurl = 'https://api.weixin.qq.com/cgi-bin/menu/create';
     /**
      * 微信自动回复添加 
      * @param array $param
      */
     public function addmenu($param){
     	try{
     		if($param['pid']){
     			if($this->where(array('pid'=>$param['pid']))->count()>=5){
     				return array('status'=>0,'msg'=>'二级菜单不能超出5个');
     			}
     		}else{
     			if($this->where(array('pid'=>0))->count()>=3){
     				return array('status'=>0,'msg'=>'一级菜单不能超出3个');
     			}
     		}
     		$result = $this->validate('WxinVaildate')->save($param);
     		if($result){
     			return array('status'=>1,'msg'=>'添加成功');
     		}else{
     			return array('status'=>0,'msg'=>$this->getError());
     		}
     	}catch( PDOException $e){
     		return array('status'=>0,'msg'=>$e->getMessage());
     	}
     }
     public function menulist(){
     	  $list=$this->where(array('pid'=>'0'))->order(array('sort'=>'desc','id'=>'asc'))->select();
     	  foreach ($list as $k=>$v){
     	  	  $list[$k]['child']=$this->where(array('pid'=>$v['id']))->order(array('sort'=>'desc','id'=>'asc'))->select();
     	  }
     	  return $list;
     }  
     /**
      * 分类选择菜单
      */
     public function optionlist($where){
     	return $this->field('id,pid,name')->where($where)->order(array('sort'=>'desc','id'=>'desc'))->select();
     }
     /**
      * 查找微信菜单
      */
     public function rowWxin($id){
     	return $this->find($id);
     }
     /**
      *更新菜单 
      */
     public function editMenu($param){
     	try{
     		$row=$this->find($param['id'])->toArray();
     		$param['pid']=$param['pid']?$param['pid']:0;
     		$count=$this->where(array('pid'=>$param['pid']))->count();
     		if($param['pid']){
     			if(!$row['pid']) $count++;
     			if($count > 5){
     				return array('status'=>0,'msg'=>'二级菜单不能超出5个');
     			}
     		}else{
     			if($row['pid']) $count++;
     			if($count > 3){
     				return array('status'=>0,'msg'=>'一级菜单不能超出3个');
     			}
     		}
     		$result = $this->validate('WxinVaildate')->isUpdate(true)->allowField(true)->save($param);
     		if($result){
     			return array('status'=>1,'msg'=>'更新成功');
     		}else{
     			return array('status'=>0,'msg'=>$this->getError());
     		}
     	}catch( PDOException $e){
     		return array('status'=>0,'msg'=>$e->getMessage());
     	}
     }
     /**
      * 获取access_token
      */
     public function getToken() {
     	$model=db('auth_config');
     	$config=$model->where(array('type'=>'weixin'))->value('config');
     	if($config){
     		$config=unserialize($config);
     		$url=$this->tokenurl;
     		$params=array(
     		   'grant_type'=>'client_credential',
     		   'appid'=>$config['app_key'],
     		   'secret'=>$config['app_secret']
     		);
     		$url.='?'.http_build_query($params);
     		$json=$this->https_request($url);
     		$arr= json_decode($json, true);
     		if(isset($arr['errcode'])){
     			return false;
     		}
     		return $arr["access_token"];
     	}else{
     		return false;
     	}
     }
     /**
      * 创建微信菜单
      * @return mixed {"errcode":0,"errmsg":"ok"} {"errcode":40018,"errmsg":"invalid button name size"}
      */
     public function createWxinMenu(){
     	 $menu=$this->menuButtonObj();
	     $getTakon=$this->getToken();
     	 if(!$getTakon){
     	 	return false;
     	 }
     	 $data=json_encode(array('button'=>$menu),JSON_UNESCAPED_UNICODE);
     	 $url=$this->menuurl.'?access_token='.$getTakon;
     	 $json=$this->https_request($url,$data);
     	 return json_decode($json);
     }
     /**
     * @Title: munuButtonObj
     * @Description: 菜单列表 
     * @param 
     * @return
      */
     public function menuButtonObj(){
     	$list=$this->where(array('pid'=>0))->order('sort desc,id asc')->select();
     	if(count($list)>3){
     		throw \exception('一级菜单不能超过三个');
     	}
     	if (empty($list)) {
     		throw \exception('请至少添加一个自定义菜单');
     	}
     	$menu=array();
     	foreach ($list as $k=>$v){
     		$sublist=$this->where(array('pid'=>$v['id']))->order('sort desc,id asc')->select();
     		if($sublist){
     			$menu[]=array('sub_button'=>$this->rebutton($sublist),'name'=>$v['name']);
     		}else{
     			if($v['type']=='2'){
     				$menu[]=array('type'=>'view','name'=>$v['name'],'url'=>$v['key_value']);
     			}else{
     				$menu[]=array('type'=>'click','name'=>$v['name'],'key'=>$v['key_value']);
     			}
     		}
     	}
     	return $menu;
     }
     /**
      * 二级菜单数据
      */
     public function rebutton($list){
     	$data=array();
     	foreach ($list as $k=>$v){
     		switch ($v['type']){
     			case '1':
     				$data[]=array('type'=>'click','name'=>$v['name'],'key'=>$v['key_value']);
     				break;
     			case '2':
     				$data[]=array('type'=>'view','name'=>$v['name'],'url'=>$v['key_value']);
     				break;
     		}
     	}
     	return $data;
     }
     /**
     * @Title: menugenerate
     * @Description: 第三方菜单创建
     * @param 
      */
     public function menugenerate(){
     	$data=$this->menuButtonObj();
     	$menu_list=array('button'=>$data);
     	$conf = Cache::get('db_config_data');
     	$WechatMenu=new WechatMenu([
     			'token'    => $conf['wx_token'], // 填写你设定的key
     			'appid'    =>$conf['wx_appid'], // 填写高级调用功能的app id, 请在微信开发模式后台查询
     			'appsecret'=>$conf['wx_appSecret'] // 填写高级调用功能的密钥
     	]);
     	if ($WechatMenu->createMenu($menu_list)) {
     		return array('status'=>1,'msg'=>'创建成功，等待生效','data' => '');
     	}else{
     		return array('status'=>0,'msg'=>'创建失败!错误代码'.$WechatMenu->errCode.'错误提示'.$WechatMenu->errMsg,'data' => '');
     	}
     }
     /**
      * https的get或 post,当data=null时为get请求，data是post的参数
      */
     private function https_request($url, $data = null){
     	$curl = curl_init();
     	curl_setopt($curl, CURLOPT_URL, $url);
     	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
     	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
     	if (!empty($data)){
     		curl_setopt($curl, CURLOPT_POST, 1);
     		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
     	}
     	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
     	$output = curl_exec($curl);
     	curl_close($curl);
     	return $output;
     }
}