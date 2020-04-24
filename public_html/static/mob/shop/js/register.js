define(function(require,exports,module){
    require("jquery");
    var layer = require("layer");
    var common = require("common");
    //点击发送验证码
    $("#sendbtn").on("tap",function(){
        checkedaccount();
    })

    //点击注册
    $(".tosubmit").on("tap",function(){
        tosubmit();
    })
    function checkedaccount(){
        var phone = $(".mobile-num").val();
        if(phone != ""){
            //判断用户时候存在
            $get("/api/user/check_user_account",{account:phone},function(ret){
                if(ret.code == 1){
                    send_code();
                }else{
                    mui.toast(ret.msg);
                    return false;
                }

            })
        }else{
            mui.toast("请输入手机号");
        }
    }
    //发送验证码
    function send_code(){
        var s = $("#sendbtn").attr('s');
        if(s > 0){
            return false;
            mui.toast("请稍后再试")
        }
        var phone = $(".mobile-num").val();
        $get("/api/user/send_verify",{id:1,param:phone,type:"sms"},function(ret){
            if(ret.code == 1){
                setTime();
            }else{
                mui.toast(ret.msg)
            }
        });
    }
    function setTime(){
        console.log("Aa")
        $("#sendbtn").attr('s',60);
        $("#sendbtn").html("60秒后再发");
        window.set_time = setInterval(function(){
            var s = $("#sendbtn").attr('s');
            if(s > 0){
                s--;
                $("#sendbtn").attr('s',s);
                $("#sendbtn").html(s + "秒后再发");
            }else{
                clearInterval(window.set_time);
                $("#sendbtn").html("获取动态码");
            }
        },1000);
    }

    function tosubmit(){
        var agree=$("input:checked").attr('checked');
        var mobile=$("input[name='mobile']").val();
        var tmobile=$("input[name='tmobile']").val();//推荐人手机号
        var password=$("input[name='password']").val();
        var code = $("input[name='code']").val();
        var reg_mobile=/^1[0-9][0-9]{9}$/;
        if(!agree){
            mui.toast("必须同意用户协议");
        }else if(!mobile){
            mui.toast("手机号不能为空");
        }else if(!reg_mobile.test(mobile)){
            mui.toast("请输入正确手机号");
        }else if(!reg_mobile.test(tmobile)){
            mui.toast("请正确输入推荐人手机号");
        }else if(!password){
            mui.toast("密码不能为空");
        }else if(password.length<6 || password.length>18){
            mui.toast("密码长度为6-20个字符");
        }else {
            $get("/api/user/register", {account: mobile, password: password,verify:code,tusername:tmobile},
                function (ret) {
                    if (ret.code) {
                        console.log(mobile,password)
                        layer.open({
                            content: ret.msg,
                            time: 2,
                            success:function(){
                                setTimeout(function(){
                                 //   $post("api/user/login",{account: mobile, password: password},function(){
                                       window.location.href = "/wap/usercenter/usercenter";
                                 //   })
                                },2000)
                            }
                        });
                    } else {
                        layer.open({
                            content: ret.msg,
                            time: 1
                        });
                    }
                }
            )
        }
    }
})
