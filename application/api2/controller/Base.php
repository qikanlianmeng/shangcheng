<?php

namespace app\api2\controller;
use think\Controller;
use app\common\service\Upload as UploadService;
use think\Request;
use think\Db;

class Base extends Controller
{
    public $uid=0;
    public function _initialize()
    {
        $config = cache('db_config_data');
//print_r($config);exit;
        if(!$config){
            $config = api('Config/lists');
            cache('db_config_data',$config);
        }
        config($config);
        //验证登录
        $controller = Request::instance()->controller();
        $action = Request::instance()->action();
        $no_login_action = ['register','login','mobile_register','mobile_login','send_verify2','send_verify_login','send_verify_bind','img_verify','show_img','share_goods_detail','getback_password'];
        if(!in_array($action,$no_login_action) && $controller!='Snotify'){
            $uid = get_uid();
            if(!$uid){
                header('content-type:application/json');
                echo json_encode(['status'=>0,'msg'=>'未登录']);exit;
            }else{
                $user = Db::name('member')->where('id',$uid)->find();
                if($user['session_id']!= session_id()){
                    header('content-type:application/json');
                    session('user_auth', null);
                    session('user_auth_sign', null);
                    echo json_encode(['status'=>3,'msg'=>'账号已在其他设备登录']);exit;
                }
                if(!$user['mobile'] && ($action!='bind_mobile'&& $action!='logout')){
                    header('content-type:application/json');
                    echo json_encode(['status'=>2,'msg'=>'未绑定手机号']);exit;
                }
            }
            $this->uid = $uid;
        }
        
    }
    //图片上传至七牛
    public function upload(){

        if (!$user_id = get_uid()) {
            return json(['status' => 0, 'msg' => '未登录，请先登录']);
        }
        error_reporting(0);
        # 兼容图片压缩后无后缀的处理
        foreach ($_FILES as $key => &$value) {
            # 判断有无后缀
            if ( strpos($value['name'],".") > 0 ) {
                continue ;
            }
            # 判断mime类型
            $bsufix = array_pop( explode('/',strtolower($value['type'])) ) ;
            if ( $bsufix == 'jpeg') {
                $value['name'] = $value['name'].'.jpg' ;
            }else{
                $value['name'] = $value['name'].'.'.$bsufix ;
            }
        }
        $uploadService = new UploadService();
        return json($uploadService->upload());
    }
}