<?php

namespace app\admin\controller;
use app\admin\Model\DemoModel;
use think\Db;

class Demo extends Base
{

    /**
     * [email 发送邮件]
     * @author [田建龙] [864491238@qq.com]
     */
    public function email(){

        return $this->fetch();
        
    }


    /**
     * [vuejs ]
     * @author [田建龙] [864491238@qq.com]
     */
    public function vuejs(){

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 10;// 获取总条数
        $count = Db::name('log')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('log')->page($Nowpage, $limits)->order('add_time desc')->select();       
        // foreach($lists as $k=>$v){
        //     $lists[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
        // }  
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();       
    }


    /**
     * [dotjs ]
     * @author [田建龙] [864491238@qq.com]
     */
    public function dotjs(){

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 10;// 获取总条数
        $count = Db::name('log')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('log')->page($Nowpage, $limits)->order('add_time desc')->select();       
        foreach($lists as $k=>$v){
            $lists[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
        }  
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();       
    }


    /**
     * [短信发送]
     * @author [田建龙] [864491238@qq.com]
     */
    public function sms()
    {
        if(request()->isAjax()){

            $phone = input('param.phone');
            $data['code'] = '1234';
            $data['product'] = '为创';
            //发送短信(签名,模板（数组）,模板ID，手机号)
            sms('为创',$data,'SMS_47235054',$phone); 
            return json(['code' => 1, 'msg' => '发送成功']);
        }
        return $this->fetch();

    }
	
	
	
	//测试生成二维码
    public function code(){

        $order_id = 89;
        $url = "https://www.baidu.com";
        $token = $order_id;
        $file = Qrcode_User($token, $url);
        dump($file);exit;
        
    }
	
	public function ceshi(){

        $str1 = null;
        $str2 = false;
        echo $str1 == $str2 ? '相等':'不相等';

        $str3 = '';
        $str4 = 0;
        echo $str3 == $str4 ? '相等':'不相等';

        $str5 = 0;
        $str6 = '0';
        echo $str5 === $str6 ? '相等':'不相等';
    }

    public function ceshi1(){

        $username = 'admin';
        $password = '1234567';

        $token = md5($username . $password);
        echo $token;

    }

}