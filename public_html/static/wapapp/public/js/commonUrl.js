/**
 * 定义通用的API请求地址
 * 和通用的连接跳转地址
 */
define(function(require, exports, module){
    exports.api={
        //登录
        login:'/api/user/login'
    };
    exports.url={
        //登录
        login:'/api/oalogin/login/type/weixin',
        //注册
        reg:'/wapapp/index/register',
        findPassword:'/wapapp/usercenter/find_password'
    };
});
