<?php
/**运费模板控制器***/
/**editor：wpy*****/
/**date:2017.9.14**/
namespace app\admin\controller;
use app\admin\model\FreightTemplate;
use think\Request;
use app\common\Api\MsgApi;
use think\Db;

class Freight extends Base
{
	private $m;
	public function _initialize()
    {
		parent::_initialize();
		$this->m = new FreightTemplate();
    }

	public function index(){
		//$res=$this->m->get_region();
		//print_r($res);
		$this->m->get_info(3);
		$this->assign('list',$this->m->get_list());
		return $this->fetch();
	}
	public function editor(){
		if (Request::instance()->isGet()){
			
			$id = input('param.id')?input('param.id'):0;
			$data = $this->m->get_region();
			$this->assign('id',$id);
			$this->assign('provice_data',json_encode($data['provice']));
			$this->assign('reg_data',$data['region']);
			$json = '';
			if($id > 0){
				$info = $this->m->get_info($id);
				$this->assign("info",$info);
				$json = $info['data'];
			}
			$this->assign("json",$json);
			return $this->fetch();
		}else{
			$res = input("post.");
			//print_r($res);
			$data = array(
							'name' 			=> $res['name'],
							'charge_type' 	=> $res['charge_type'],
							'data'			=> serialize(json_encode($res['data']))
							);
			if($res['id'] > 0){
				//修改
				echo $this->m->save($data,['id'=>$res['id']]);
			}else{
				//添加
				echo $this->m->save($data);
			}
		}
		
	}
	public function del(){
		$id = input("post.id");
		$back = array('status'=>0,"msg"=>'');
		//还要先判断下该运费模板是否有商品正在使用，否则不能删除
		if(Db::name('goods')->where('fid',$id)->find()){
		    $back['msg'] = '有商品正在使用该模板，无法删除';
            echo json_encode($back);exit;
        }
		if($this->m->where("id","=",$id)->delete()){
			$back = array('status'=>1,"msg"=>'删除成功');
		}else{
			$back['msg'] = '删除失败';
		}
		echo json_encode($back);
	}



}





































