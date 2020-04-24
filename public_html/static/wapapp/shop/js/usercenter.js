define(function(require, exports, module) {
    var common = require('common'),
        layer = require('layer'),
        template = require('template');
    // if (mui.os.ios) {
    //     window.onpageshow = function(event) {
    //         window.location.reload();
    //     };
    // }
    $('#go_spendintegral').on('tap', () => {
        window.location.href = "/wapapp/usercenter/spendintegral.html";
    });
    $('#go_jifen').on('tap', () => {
        window.location.href = "/wapapp/usercenter/commission.html";
    });
    $('body').on('tap','#go_setting',function(){
        console.log(11);
        window.location.href="/wapapp/usercenter/setting.html";
    });
    //是否第二天，可签到了
    function check_time(time) {
        var time0 = new Date(new Date(new Date(time * 1000).toLocaleDateString()).getTime() + 24 * 60 * 60 * 1000 - 1).getTime();
        var now = getServerDate().getTime();
        if (now > time0) {
            $('.user_sign').html('签到');
        } else {
            $('.user_sign').html('已签到');
        }
    }
    // 控制标签点击判断登录
    mui('.ucenter').on('tap', '.islogin', function() {
        if ($(this).hasClass('mui-control-item')) return;
        var needLogin = $(this).attr('need-login');
        if (needLogin == "1" && UID < 1) {
            window.location.href = '/wapapp/index/login'
        }
        location.href = this.getAttribute('href');
    });
    //底部链接跳转
    mui('.mui-bar-tab').on('tap', 'a', function() {
            var shopcarTab = document.getElementById("shopcarbtn");
            var classificationTab = document.getElementById("classificationbtn");
            var integralshopTab = document.getElementById("integralshopbtn");
            if (this == shopcarTab || this == classificationTab || this == integralshopTab) {
                location.href = this.getAttribute('href');
                return false;
            } else {
                location.href = this.getAttribute('href');
            }
        })
        // 获取购物车商品数量
    $get("/api2/goods/get_cart_goods_num", function(data) {
        if (data.status==1) {
            if (data.data != 0) {
                $(".mui-bar-tab .shopcart-num").show().html(data.data);
            }
        } else {
            // common.toast(data.msg)
        }
    });
    // 签到
    $('.user-top').on('tap', '.user_sign', function(e) {
        e.stopPropagation();
        $get("/api2/user/sign_in", function(data) {
            common.toast(data.msg);
            if (data.status == 1) {
                get_data();
            }
        });
    });

    // 变量获取
    var $ucenter = $('.ucenter'),
        $needhide = $ucenter.find('.needhide'),
        $notlogin = $ucenter.find('.notlogin');
    if (UID < 1) {
        $needhide.hide();
        $notlogin.show();
    } else {
        $needhide.show();
        $notlogin.hide();
        get_data();
    }
    //退出登录
    $('#login').on('click', function() {
            $get('/api2/user/logout', {}, function(data) {
                common.toast(data.info);
                setTimeout('window.location.href="/wapapp/index/login.html";', 1000);
            });
        })
        // alert(222);
    $(document).on("tap", ".register", function() {
        window.location.href = $(this).attr("href");
    })
    if (UID == 0) {
        window.location.href = "/wapapp/index/login";
    }

    function getServerDate() {
        return new Date($.ajax({ async: false }).getResponseHeader("Date"));
    }

    function get_data() {

        //登录后获取数据
        $get('/api2/user/getuserinfo', {}, function(data) {
            // console.log(JSON.stringify(data));
            if (data.status == 2) {
                window.location.href = "/wapapp/usercenter/binding_m.html";
            }
            if (data.code == 1) {
                var obj = data.data;
                $('.user-info').html(template('userInfoTem', obj));
                check_time(data.data.sign_time);
                $('#money').html(obj.money);
                $('#jifen').html(obj.integral);
                // alert(obj.unpay_order_num+obj.unconfirm_order_num+obj.unpingjia_order_num)
                if (obj.unpay_order_num > 0) {
                    $('.mui-daifukuan').show().html(obj.unpay_order_num);
                }
                if (obj.unconfirm_order_num > 0) {
                    $('.mui-daishouhuo').show().html(obj.unconfirm_order_num);
                }
                if (obj.unpingjia_order_num > 0) {
                    $('.mui-daipingjia').show().html(obj.unpingjia_order_num);
                }
                if (obj.unshipping_order_num > 0) {
                    $('.mui-daifahuo').show().html(obj.unshipping_order_num);
                }
            } else if(data.status==3||data.status==0){
                common.toast(data.msg);
                setTimeout(() => {
                    window.location.href = "/wapapp/index/login.html";
                }, 1000);
            }
        })
    }
    get_adbtn();
    // 获取按钮
    function get_adbtn(){
        $get('/api2/ad/center_btn',{},(ret)=>{
            if(ret.status==1){
                var list='';
               for(var i of ret.list){
                   list+=template('ad_btn',i);
               } 
               $('.button-area').html(list);
            }
        });
    }
});