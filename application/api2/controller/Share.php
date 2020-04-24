<?php
/**
 * Created by Russell.
 * User: Administrator
 * Date: 2020/3/01
 * Time: 10:06
 */

namespace app\api2\controller;

use Think\Controller;
use think\Db;

class Share extends Controller
{

    private $wxpayConfig;
    private $wxpay;
    private $parameters;
    private $returnParameters;

    public function _initialize()
    {
        // vendor('Wxpay.jssdk.log_');
        $this->wxpayConfig = array('CURL_TIMEOUT' => 30);

        $this->wxpayConfig['appid'] = "wx36eb1cf950efdc18";      // 微信公众号身份的唯一标识
        $this->wxpayConfig['appsecret'] = "71fb2daeb4d085d1023295d08f6d8b79";  // JSAPI接口中获取openid

    }

    //用户充值

    function request_by_curl($remote_server, $post_string)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, '111111111');
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     *  获取openid
     */
    public function get_userinfo()
    {




            // 通过code获得openid
            if (!isset($_GET['code'])) {

                // 触发微信返回code码
                $url = $this->createOauthUrlForCodeSnsapi_userinfo($this->get_url());
                Header("Location: " . $url);
            } else {

                // 获取code码,以获取openid
                $code = $_GET['code'];
                $data = $this->get_data($code);

                $access_token = $data["access_token"];
                $openid = $data["openid"];
                //echo $access_token;
                //这里是获取用户信息
               // $url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN";
                //$res = $this->https_request($url);
                //$res = json_decode($res, true);

               // $this->add($res);
                //return $res;
              return $openid;
            }


    }

    //进入数据库
    public function add($res)
    {
        $openid = $res["openid"];
        $res_exist = Db::name("wxuser")
            ->where("openid='$openid'")
            ->find();

        if($res_exist){
           Db::name("wxuser")
                ->where("openid='$openid'")
                ->update(array("nickname" => $res["nickname"], "headimgurl" => $res["headimgurl"]));

        }else{
            Db::name("wxuser")->insert(array("openid" => $res["openid"], "nickname" => $res["nickname"], "headimgurl" => $res["headimgurl"]));

        }

    }

    function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    /**
     *  获取openid
     */
    public function get_openid()
    {
        $openid = $_COOKIE['openid'];

        if (empty($openid)) {
            // 通过code获得openid
            if (!isset($_GET['code'])) {
                // 触发微信返回code码
                $url = $this->createOauthUrlForCode($this->get_url());
                Header("Location: " . $url);
            } else {
                // 获取code码,以获取openid
                $code = $_GET['code'];
                $openid = $this->getOpenId($code);
                cookie('openid', $openid);
            }
        }
        return $openid;
    }

    /**
     * 获取当前页面完整URL地址
     */
    public function get_url()
    {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
    }

    /**
     *  作用：生成可以获得code的url
     * 静默授权
     */
    public function createOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->wxpayConfig['appid'];
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_userinfo";//snsapi_userinfo  非静默授权
        $urlObj["state"] = "STATE" . "#wechat_redirect";
        $bizString = $this->formatBizQueryParaMap($urlObj, false);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
    }

    /**
     *  作用：生成可以获得code的url
     * 静默授权
     */
    public function createOauthUrlForCodeSnsapi_userinfo($redirectUrl)
    {
        $urlObj["appid"] = 'wx36eb1cf950efdc18';
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_base";//snsapi_userinfo   snsapi_base 非静默授权
        $urlObj["state"] = "STATE" . "#wechat_redirect";
        $bizString = $this->formatBizQueryParaMap($urlObj, false);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
    }

    /**
     *  作用：通过curl向微信提交code，以获取openid
     */
    public function getOpenid($code)
    {
        $url = $this->createOauthUrlForOpenid($code);
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->wxpayConfig['CURL_TIMEOUT']);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //运行curl，结果以jason形式返回
        $res = curl_exec($ch);
        curl_close($ch);
        //取出openid
        $data = json_decode($res, true);
        $this->openid = $data['openid'];
        return $this->openid;
    }

    /**
     *  作用：通过curl向微信提交code，以获取asstoken
     */
    public function get_data($code)
    {
        $url = $this->createOauthUrlForOpenid($code);
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->wxpayConfig['CURL_TIMEOUT']);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //运行curl，结果以jason形式返回
        $res = curl_exec($ch);
        curl_close($ch);
        //取出openid
        $data = json_decode($res, true);
        $this->data = $data;
        return $this->data;
    }

    /**
     *  作用：生成可以获得openid的url
     */
    public function createOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = 'wx36eb1cf950efdc18';
        $urlObj["secret"] = '71fb2daeb4d085d1023295d08f6d8b79';
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->formatBizQueryParaMap($urlObj, false);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?" . $bizString;
    }

    /**
     *  作用：格式化参数，签名过程需要使用
     */
    public function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    /**
     *  作用：设置请求参数
     */
    private function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    private function trimString($value)
    {
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
    }

    /**
     * 获取prepay_id
     */
    private function getPrepayId()
    {
        $response = $this->postXml();
        $result = $this->xmlToArray($response);
        $prepay_id = $result["prepay_id"];
        return $prepay_id;
    }

    /**
     *  作用：post请求xml
     */
    private function postXml()
    {
        $xml = $this->createXml();
        $response = $this->postXmlCurl($xml, $this->wxpayConfig['url'], $this->wxpayConfig['CURL_TIMEOUT']);
        return $response;
    }

    /**
     * 生成接口参数xml
     */
    private function createXml()
    {
        try {
            // 检测必填参数
            if ($this->parameters["out_trade_no"] == null) {
                throw new \Exception("缺少统一支付接口必填参数out_trade_no！" . "<br>");
            } elseif ($this->parameters["body"] == null) {
                throw new \Exception("缺少统一支付接口必填参数body！" . "<br>");
            } elseif ($this->parameters["total_fee"] == null) {
                throw new \Exception("缺少统一支付接口必填参数total_fee！" . "<br>");
            } elseif ($this->parameters["notify_url"] == null) {
                throw new \Exception("缺少统一支付接口必填参数notify_url！" . "<br>");
            } elseif ($this->parameters["trade_type"] == null) {
                throw new \Exception("缺少统一支付接口必填参数trade_type！" . "<br>");
            } elseif ($this->parameters["trade_type"] == "JSAPI" &&
                $this->parameters["openid"] == NULL
            ) {
                throw new \Exception("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！" . "<br>");
            }
            $this->parameters["appid"] = $this->wxpayConfig['appid'];     // 公众账号ID
            $this->parameters["mch_id"] = $this->wxpayConfig['mchid'];        // 商户号
            $this->parameters["spbill_create_ip"] = $_SERVER['REMOTE_ADDR'];// 终端ip
            $this->parameters["nonce_str"] = $this->createNoncestr();     // 随机字符串
            $this->parameters["sign"] = $this->getSign($this->parameters); // 签名
            return $this->arrayToXml($this->parameters);
        } catch (\Exception $e) {
            die($e->errorMessage());
        }
    }

    /**
     *  作用：产生随机字符串，不长于32位
     */
    private function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     *  作用：生成签名
     */
    private function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->wxpayConfig['key'];
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        //echo "【result】 ".$result_."</br>";
        return $result_;
    }

    /**
     *  作用：array转xml
     */
    private function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     *  作用：以post方式提交xml到对应的接口url
     */
    private function postXmlCurl($xml, $url, $second = 30)
    {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        curl_close($ch);
        //返回结果
        if ($data) {
            //curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

    /**
     *  作用：将xml转为array
     */
    private function xmlToArray($xml)
    {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    /**
     *  作用：设置jsapi的参数
     */
    private function getParameters($prepay_id)
    {
        $jsApiObj["appId"] = $this->wxpayConfig['appid'];
        $timeStamp = time();
        $jsApiObj["timeStamp"] = "$timeStamp";
        $jsApiObj["nonceStr"] = $this->createNoncestr();
        $jsApiObj["package"] = "prepay_id=$prepay_id";
        $jsApiObj["signType"] = "MD5";
        $jsApiObj["paySign"] = $this->getSign($jsApiObj);
        $this->parameters = json_encode($jsApiObj);
    }

    private function checkSign($data)
    {
        $tmpData = $data;
        unset($tmpData['sign']);
        $sign = $this->getSign($tmpData);//本地签名
        if ($data['sign'] == $sign) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 设置返回微信的xml数据
     */
    private function setReturnParameter($parameter, $parameterValue)
    {
        $this->returnParameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    /**
     * 将xml数据返回微信
     */
    private function returnXml()
    {
        $returnXml = $this->arrayToXml($this->returnParameters);
        return $returnXml;
    }

    /*----以下是JSSDK的文件----*/
    private function getSignPackage()
    {
        $jsapiTicket = $this->getJsApiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr = $this->createNonceStr2();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId" => $this->wxpayConfig['appid'],
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    private function createNonceStr2($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getJsApiTicket()
    {
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        // $data = json_decode(file_get_contents("jsapi_ticket.json"));
        $data = json_decode($_COOKIE['jsapi_ticket_json']);
        if ($data->expire_time < time()) {
            $accessToken = $this->getAccessToken();
            // 如果是企业号用以下 URL 获取 ticket
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url));
            $ticket = $res->ticket;
            if ($ticket) {
                //$data->expire_time = time() + 7000;
                //$data->jsapi_ticket = $ticket;
                //$fp = fopen("jsapi_ticket.json", "w");
                //fwrite($fp, json_encode($data));
                //fclose($fp);
                $tempArr = array('jsapi_ticket' => $ticket, 'expire_time' => time() + 7000);
                setcookie('jsapi_ticket_json', json_encode($tempArr), $tempArr['expire_time']);
            }
        } else {
            $ticket = $data->jsapi_ticket;
        }

        return $ticket;
    }

    private function getAccessToken()
    {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        // $data = json_decode(file_get_contents("access_token.json"));
        $data = json_decode($_COOKIE["access_token_json"]);
        if ($data->expire_time < time()) {
            // 如果是企业号用以下URL获取access_token
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this->wxpayConfig['appid'] . "&secret=" . $this->wxpayConfig['appsecret'];
            $res = json_decode($this->httpGet($url));
            $access_token = $res->access_token;
            if ($access_token) {
                //$data->expire_time = time() + 7000;
                //$data->access_token = $access_token;
                //$fp = fopen("access_token.json", "w");
                //fwrite($fp, json_encode($data));
                //fclose($fp);
                $tempArr = array('access_token' => $access_token, 'expire_time' => time() + 7000);
                setcookie('access_token_json', json_encode($tempArr), $tempArr['expire_time']);
            }
        } else {
            $access_token = $data->access_token;
        }
        return $access_token;
    }

    private function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }
}