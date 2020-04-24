/**
 * 找回密码
 */
define(function(require, exports, module){
    var common = require('common'),
        layer = require('layer'),
        template = require('template');
    var type = 'sms';
    $(".codehide").focus(function(){
        $(this).hide();
        $(".code").val("").show().css("backgroundColor","#fff").focus();
    });
    $(".code").blur(function(){
        $(this).show().css("backgroundColor","#fff");
        $(".codehide").hide();
    });
    $(".pwdhide").focus(function(){
        $(this).hide();
        $(".repwd").val("").show().css("backgroundColor","#fff").focus();
    });
    $(".repwd").blur(function(){
        $(this).show().css("backgroundColor","#fff");
        $(".pwdhide").hide();
    });
    //改变类型
    mui(".mui-content").on('tap','.mui-control-item',function(){
        type = $(this).attr('type');
    });

    mui(".mui-content").on('tap','.get-code',function(){
        var $data = $(this).siblings('.data');
        var data = $data.val();
        var p,desc,send,par;
        if(type == 'sms'){
            p = /^1[3|4|5|7|8]\d{9}$/;
            desc = '手机号不合法';
            send = 'sms';
        }else{
            p = /^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/;
            desc = '邮箱不合法';
            send = 'email';
        }
        par={
            id:1,
            param:data,
            type:send
        }
        if(!p.test(data)){
            common.open(desc,1);
            $data.focus();
            return;
        }
        $get('/api/user/check_user_account',{'account':data},function(data){
            if(data.code){
                common.open('该账号未注册',1);
            }else{
                $.post("/api/user/send_verify",par,function(d){
                    common.open(d.msg,1);
                    if(d.code == 1){
                        setTime();
                    }
                },'json');
            }
        })

    });

    mui(".mui-content").on('tap','.check',function(){
        var $this = $(this),
            code = $this.siblings('.code').val(),
            account = $this.siblings('.data').val(),
            pwd = $this.siblings('.repwd').val();
        if(!account){
            var str='';
            if(type == 'sms'){
                str = '手机号不能为空';
            }else{
                str = '邮箱不能为空';
            }
            common.open(str,1);
            return false;
        }else if(!code){
            common.open('动态码不能为空',1);
            return false;
        }else if(!pwd){
            common.open('重置密码不能为空',1);
            return false;
        }
        var par = {
            account:account,
            password:pwd,
            verify:code
        }
        $get("/api/user/getback_password",par,function(d){
            layer.open({
                content: d.msg,
                time: 2,
                success: function(elem){
                    if(d.code){
                        setTimeout(function(){
                            window.location.href = '/wap/index/login';
                          },1000);
                    }
                }
            });
        },'json');
    });
    function setTime(){
        var id = $(".mui-control-content.mui-active").find("#sendbtn");
        id.attr('s',60);
        id.html("60秒后再发");
        window.set_time = setInterval(function(){
            var s = id.attr('s');
            if(s > 0){
                s--;
                id.attr('s',s);
                id.html(s + "秒后再发");
            }else{
                clearInterval(window.set_time);
                id.html("获取动态码");
            }
        },1000);
    }
});
