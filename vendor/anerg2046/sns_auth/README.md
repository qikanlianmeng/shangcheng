# 通用第三方登录

## 目前支持
- 微博登录（移动&PC版）
- QQ登录（移动&PC版）
- 移动版微信
- 网站微信扫码登录

>微信可获取unionid（如有）

## 安装方法
```
composer require anerg2046/sns_auth
```

>类库使用的命名空间为`\\anerg\\OAuth2`

## 典型用法
>以ThinkPHP5为例

```php
<?php

namespace app\web\controller;

use anerg\OAuth2\OAuth;

class SnsLogin {

    /**
     * 此处应当考虑使用空控制器来简化代码
     * 同时应当考虑对第三方渠道名称进行检查
     * $config配置参数应当放在配置文件中
     * callback对应了普通PC版的返回页面和移动版的页面
     */
    public function qq() {
        $config = [
            'app_key'    => 'xxxxxx',
            'app_secret' => 'xxxxxxxxxxxxxxxxxxxx',
            'scope'      => 'get_user_info',
            'callback'   => [
                'default' => 'http://xxx.com/sns_login/callback/qq',
                'mobile'  => 'http://h5.xxx.com/sns_login/callback/qq',
            ]
        ];
        $OAuth  = OAuth::getInstance($config, 'qq');
        $OAuth->setDisplay('mobile'); //此处为可选,若没有设置为mobile,则跳转的授权页面可能不适合手机浏览器访问
        return redirect($OAuth->getAuthorizeURL());
    }

    public function callback($channel) {
        $config   = [
            'app_key'    => 'xxxxxx',
            'app_secret' => 'xxxxxxxxxxxxxxxxxxxx',
            'scope'      => 'get_user_info',
            'callback'   => [
                'default' => 'http://xxx.com/sns_login/callback/qq',
                'mobile'  => 'http://h5.xxx.com/sns_login/callback/qq',
            ]
        ];
        $OAuth    = OAuth::getInstance($config, $channel);
        $OAuth->getAccessToken();
        /**
         * 在获取access_token的时候可以考虑忽略你传递的state参数
         * 此参数使用cookie保存并验证
         */
//        $ignore_stat = true;
//        $OAuth->getAccessToken(true);
        $sns_info = $OAuth->userinfo();
        /**
         * 此处获取了sns提供的用户数据
         * 你可以进行其他操作
         */
    }

}
```
