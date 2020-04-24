/**
 * 登录
 */
define(function(require, exports, module) {
    var common = require('common_l'),
        layer = require('layer'),
        template = require('template');
    //console.log(Cookies.get('info_username'))
    $('#go_verify').on('tap', function() {
        window.history.replaceState({}, "", '/wapapp/usercenter/usercenter.html');
        window.location.href = "/wapapp/index/login_verify.html";
    });

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
            $post("/api2/user/login", { 'account': account, 'password': password }, function(data) {
                if (data.status == 1) {
                    if (remember) {
                        Cookies.set('info_username', account);
                        Cookies.set('info_password', password);
                    }
                    layer.open({
                        content: '登录成功',
                        time: 2,
                        success: function(elem) {
                            setTimeout(function() {
                                window.location.href = '/wapapp/usercenter/usercenter.html';
                                // history.go(-1);
                            }, 500)
                        }
                    });
                } else if (data.status == 2) {
                    window.location.href = "/wapapp/usercenter/binding_m.html";
                } else {
                    common.open(data.msg, 1);
                }
            })
        }
    });
});