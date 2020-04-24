define(function(require, exports, module) {
    require("jquery");
    // var l = require("layer");
    var common = require("common_l");
    //点击注册
    $(".tosubmit").on("tap", function() {
        tosubmit();
    })
    $("body").on("tap", '#sendbtn', function() {
        var s = $("#sendbtn").attr('s');
        var mobile = $("#account").val();
        if (!mobile) {
            mui.toast('请输入手机号');
        } else if (mobile.search(/^\d{11}$/) == -1) {
            mui.toast('手机号码格式不正确');
        } else {

            if (s > 0) {
                mui.toast("请稍后再试")
                return false;
            } else {
                confirm(mobile);
            }
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
                    $get("/api2/user/send_verify_bind", { mobile: mobile, img_code: code }, function(ret) {
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

    var app_url = $('#app_url').val();

    function tosubmit() {
        var agree = $("input:checked").attr('checked');
        var account = $('#account').val();
        var password = $('#password').val();
        var rcode = $('#rcode').val();
        var verify = $('#verify').val();
        if (!agree) {
            mui.toast("必须同意用户协议");
        } else if (!account) {
            mui.toast("手机号不能为空");
        } else if (!rcode) {
            mui.toast("请输入邀请码");
        } else if (!password) {
            mui.toast("密码不能为空");
        } else if (password.length < 6) {
            mui.toast("密码长度需大于6个字符");
        } else if (!verify) {
            mui.toast("验证码不能为空");
        } else if (account.search(/^\d{11}$/) == -1) {
            mui.toast('手机号码格式不正确');
        } else {
            $get("/api2/user/mobile_register", { mobile: account, password: password, rcode: rcode, verify: verify },
                function(ret) {
                    mui.toast(ret.msg);
                    if (ret.status) {
                        mui.confirm('注册成功，前往下载APP', '', ['取消', '立即下载'], function(e) {
                            if (e.index == 1) {
                                window.location.href = app_url;
                            }
                        });
                    } else {}
                }
            )
        }
    }
})