<?php

namespace app\api\controller;
use think\Controller;
use app\common\service\Upload as UploadService;

class Base extends Controller
{
    public function _initialize()
    {
        $config = cache('db_config_data');

        if(!$config){
            $config = api('Config/lists');
            cache('db_config_data',$config);
        }
        config($config);
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