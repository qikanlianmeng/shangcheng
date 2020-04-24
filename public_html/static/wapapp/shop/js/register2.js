define(function(require,exports,module){
    require("jquery");
    var layer = require("layer");

    //点击发送验证码
    $("#sendbtn").on("click",function(){
        var phone = $(".mobile-num").val();
        if(phone==''){
            mui.toast('手机号不能为空');
           return ;
        }else{
            $('.pop').fadeIn(500);
        }

        
    });
    var img_code='';
    $('.pop_ok').on('click',function(){
        var input_val=$('#code').val();
        if(input_val==''){
            mui.toast('图形验证码不能为空');
           return ;
        }else{
            img_code = $("#code").val();
            checkedaccount();
            $('.pop').fadeOut(500);
        }
    });
  

    $('.pop_cancel').on('click',function(){
        $('.pop').fadeOut(500);
    });
    //点击注册
    $("#register").on("click",function(){
        tosubmit();
    });
    function checkedaccount(){
        // var phone = $(".mobile-num").val();
        // if(phone != ""){
        //     //判断用户时候存在
        //     $get("/api/user/check_user_account",{account:phone},function(ret){
        //         if(ret.code == 1){
        //             send_code();
        //         }else{
        //             mui.toast(ret.msg);
        //             return false;
        //         }

        //     })
        // }else{
        //     mui.toast("请输入手机号");
        // }
        send_code();
    }
    //发送验证码
    function send_code(){
        var s = $("#sendbtn").attr('s');
        if(s > 0){
            return false;
            mui.toast("请稍后再试")
        }
        var phone = $(".mobile-num").val();
      
        $get("/api/user/send_verify2",{id:1,phone:phone,img_code:img_code,type:"sms"},function(ret){
            console.log(JSON.stringify(ret));
            if(ret.code == 1){
                setTime();
                mui.toast(ret.msg)
            }else{
                mui.toast(ret.msg)
            }
        });
    }
    function setTime(){
        // console.log("Aa")
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
        var mobile=$("input[name='mobile']").val();
        var password=$("input[name='password']").val();
        var code = $("input[name='code']").val();
        var reg_mobile=/^1[2,3,4,5,6,7,8,9][0-9]{9}$/;
        if(!mobile){
            mui.toast("手机号不能为空");
        }else if(!reg_mobile.test(mobile)){
            mui.toast("请输入正确手机号");
        }else if(!password){
            mui.toast("密码不能为空");
        }else if(password.length<6 || password.length>18){
            mui.toast("密码长度为6-20个字符");
        }else {
            $get("/api/user/perfect_info", {password: password,verify:code,phone:mobile},
                function (ret) {
                    if (ret.code==1) {
                        console.log(JSON.stringify(ret));
                        mui.toast(ret.msg);
                        setTimeout(function(){
                           window.location.href = '/wapapp/index/index.html';
                        },1500);
                    } else {
                      console.log(JSON.stringify(ret));
                       mui.toast(ret.msg);
                    }
                }
            );
        }
    }
});
