<?php

namespace app\common\service;
use think\Cache;
use think\Controller;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Db;
use think\Model;

class Upload extends Model
{

    public function upload($path='')
    {
        $conf = Cache::get('db_config_data');
        if (!$conf['upload_switch']) {
            //七牛上传没开启
            return $this->upload2local($path);
        } else {
            //七牛上传开启
            return $this->upload2qiniu();
        }

    }

    //图片上传至七牛
    public function upload2qiniu(){
        $conf = Cache::get('db_config_data');
        $file = request()->file('file');
        $info = $file->validate(['size' => $conf['upload_image_size'] * 1024,'ext' => $conf['upload_image_extensions']])->move(ROOT_PATH . 'public_html' . DS . 'uploads/images');
        if($info){
            $filePath = UPLOAD_PATH . '/uploads/images/' . $info->getSaveName();
            $accessKey = $conf['upload_accesskey'];
            $secretKey = $conf['upload_secretkey'];
            // 构建鉴权对象
            $auth = new Auth($accessKey, $secretKey);
            // 要上传的空间
            $bucket = $conf['upload_bucket'];
            // 生成上传 Token
            $token = $auth->uploadToken($bucket);
            // 上传到七牛后保存的文件名
            $key =md5(time()).".".substr($filePath,(strripos($filePath,'.')+1));
            // 初始化 UploadManager 对象并进行文件的上传。
            $uploadMgr = new UploadManager();
            list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
            @unlink($filePath);
            if ($err == null) {
                return ['code' => 1, 'data' => $conf['upload_domain'] . $ret['key']];
            } else {
                return ['code' => 0, 'data' => "上传出错"];
            }
        }else{
            return ['code' => 0, 'data' => $file->getError()];
        }
    }

    //上传图片至本地
    public function upload2local($path)
    {
        $file = request()->file('file');
        if($path == ''){
            $path = 'images';
        }
        $info = $file->move(UPLOAD_PATH . '/uploads/'.$path);
        if($info){
            return ['code' => 1, 'data' => '/uploads/'.$path.'/' . $info->getSaveName()];
        }else{
            return ['code' => 0, 'data' => $file->getError()];
        }
    }

}

