/**
 * 找回密码
 */
define(function(require, exports, module) {
    var common = require('common_l'),
        template = require('template');
    var type = 'sms';
    $(".codehide").focus(function() {
        $(this).hide();
        $(".code").val("").show().css("backgroundColor", "#fff").focus();
    });
    $(".code").blur(function() {
        $(this).show().css("backgroundColor", "#fff");
        $(".codehide").hide();
    });
    $(".pwdhide").focus(function() {
        $(this).hide();
        $(".repwd").val("").show().css("backgroundColor", "#fff").focus();
    });
    $(".repwd").blur(function() {
        $(this).show().css("backgroundColor", "#fff");
        $(".pwdhide").hide();
    });
    //改变类型
    // mui(".mui-content").on('tap', '.mui-control-item', function() {
    //     type = $(this).attr('type');
    // });

    $("body").on("tap", '#sendbtn', function() {
        var s = $("#sendbtn").attr('s');
        var mobile = $("#data").val();
        if (s > 0) {
            return false;
            mui.toast("请稍后再试")
        } else if (!mobile) {
            mui.toast('请输入手机号');
        } else if (mobile.search(/^\d{11}$/) == -1) {
            mui.toast('手机号码格式不正确');
        } else {
            confirm(mobile);
        }
    })

    function confirm(mobile) {
        layer.confirm('<div class="inp-div inp-code" style="padding:9px 5px;margin-bottom:0;"><span class=" iconfont icon-yanzhengma"></span><input id="code" type="text" name="code" placeholder="图形验证码" class="input-login input-code" style="width:80%;background:unset;font-size:14px;"><img class="code-block" src="/api2/user/img_verify" onclick="javascript:this.src=\'/api2/user/img_verify\'" style="right:0;height: 43px;width: 48%;top:0;"></div>', { btn: ['确定', '取消'], title: "验证" },
            function(e) {
                var code = $('#code').val();
                if (!code) {
                    mui.toast('请输入图形验证码');
                    return false;
                } else {
                    $get("/api2/user/send_verify_login", { mobile: mobile, img_code: code }, function(ret) {
                        if (ret.code) {
                            mui.toast('发送成功');
                            setTime();
                            layer.close(layer.index);
                        } else {
                            mui.toast(ret.msg);
                            if (ret.msg == '图片验证码错误') {
                                confirm(mobile);
                            }
                        }
                    });
                }
            });
    }

    function setTime() {
        console.log("Aa")
        $("#sendbtn").attr('s', 60);
        $("#sendbtn").html("60秒后再发");
        window.set_time = setInterval(function() {
            var s = $("#sendbtn").attr('s');
            if (s > 0) {
                s--;
                $("#sendbtn").attr('s', s);
                $("#sendbtn").html(s + "秒后再发");
            } else {
                clearInterval(window.set_time);
                $("#sendbtn").html("获取验证码");
            }
        }, 1000);
    }
    mui(".mui-content").on('tap', '.check', function() {
        var $this = $(this),
            code = $('#verify').val(),
            account = $('#data').val(),
            pwd = $('#repwd').val();
        if (!account) {
            common.open('手机号不能为空', 1);
            return false;
        } else if (!code) {
            common.open('验证码不能为空', 1);
            return false;
        } else if (!pwd) {
            common.open('重置密码不能为空', 1);
            return false;
        }
        var par = {
            account: account,
            password: pwd,
            verify: code
        }
        $get("/api2/user/getback_password", par, function(d) {
            mui.toast(d.msg);
            if (d.status == 1) {
                setTimeout(function() {
                    window.location.href = '/wapapp/index/login';
                }, 1000);
            }
        }, 'json');
    });

});