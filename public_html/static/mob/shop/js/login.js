/**
 * 登录
 */
define(function(require, exports, module) {
    var common = require('common'),
        layer = require('layer'),
        template = require('template');
    //console.log(Cookies.get('info_username'))
    if (Cookies.get('info_username')) {
        $("input[name='username']").val(Cookies.get('info_username'));
        $("input[name='password']").val(Cookies.get('info_password'));
        $("input[name='remember']").attr('checked', true);
    }
    if (parseInt(IS_WEIXIN)) {
        $('.wx-login').show();
    } else {
        $('.qq-login').show();
    }
    $('#login').on('click', function() {
        var account = $("input[name='username']").val();
        var password = $("input[name='password']").val();
        var remember = $("input[name='remember']:checked").val();
        if (!account) {
            common.open('用户名不能为空', 1);
        } else if (!password) {
            common.open('登录密码不能为空', 1);
        } else {
            $post("/api/user/login", { 'account': account, 'password': password }, function(data) {
                if (data.code) {
                    if (remember) {
                        Cookies.set('info_username', account);
                        Cookies.set('info_password', password);
                    }
                    layer.open({
                        content: '恭喜您，登录成功',
                        time: 2,
                        success: function(elem) {
                            setTimeout(function() {
                                //  window.location.href = '/wap/yang/index';
                                history.go(-1);
                            }, 500)
                        }
                    });
                } else {
                    common.open(data.msg, 1);
                }
            })
        }
    });
});