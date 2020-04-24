<?php

namespace app\admin\controller;
use app\common\service\Upload as UploadService;

class Upload extends Base
{
	//图片上传
    public function upload(){
        $uploadService = new UploadService();
        $res = $uploadService->upload();
        echo $res['data'];
    }

    //会员头像上传
    public function uploadface(){
        $uploadService = new UploadService();
        $res = $uploadService->upload();
        echo $res['data'];
    }

    //图片上传至七牛
    public function upload2qiniu(){
        $uploadService = new UploadService();
        return json($uploadService->upload());
    }

}
