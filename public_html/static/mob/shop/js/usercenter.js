/**
 * 确认订单
 */
define(function(require, exports, module){
    var common = require('common'),
        layer = require('layer'),
        template = require('template');

    // 控制标签点击判断登录
    mui('.ucenter').on('tap','.islogin',function(){
        if ($(this).hasClass('mui-control-item')) return;
        var needLogin = $(this).attr('need-login');
        if ( needLogin == "1" && UID < 1 ) {
            common.login() ;
            return;
        }
        location.href = this.getAttribute('href');
    });
    //底部链接跳转
    mui('.mui-bar-tab').on('tap','a',function(){
        var shopcarTab = document.getElementById("shopcarbtn");
        var classificationTab = document.getElementById("classificationbtn");
        var integralshopTab = document.getElementById("integralshopbtn");
        if (this == shopcarTab || this == classificationTab || this == integralshopTab) {
            location.href = this.getAttribute('href');
            return false;
        }else{
            location.href = this.getAttribute('href');
        }
    })
    // 获取购物车商品数量
    $get("/api/goods/get_cart_goods_num",function(data){
        if(data.status){
            if(data.data != 0){
                $(".mui-bar-tab .shopcart-num").show().html(data.data);
            }
        }else{
            // common.toast(data.msg)
        }
    });
    // 变量获取
    var $ucenter = $('.ucenter'),
        $needhide = $ucenter.find('.needhide'),
        $notlogin = $ucenter.find('.notlogin');
    if(UID < 1){
        $needhide.hide();
        $notlogin.show();
    }else{
        $needhide.show();
        $notlogin.hide();
        //登录后获取数据
        $get('/api/user/getuserinfo',{},function(data){
            console.log(JSON.stringify(data));
            
            if(data.code){
                var obj = data.data;
                $('.user-info').html(template('userInfoTem',obj));
                $('.mui-xuebi').html(obj.money+'余额');
                $('.mui-score').html(obj.integral+'羊币');
                if(obj.unpay_order_num > 0){
                    $('.mui-daifukuan').show().html(obj.unpay_order_num);
                }
                if(obj.unconfirm_order_num > 0){
                    $('.mui-daishouhuo').show().html(obj.unconfirm_order_num);
                }
                if(obj.unpingjia_order_num > 0){
                    $('.mui-daipingjia').show().html(obj.unpingjia_order_num);
                }
                if(obj.unread_msg > 0){
                    $('.mui-xiaoxi').show().html(obj.unread_msg);
                }
            }else{
                common.toast(data.msg);
            }
        })
        //退出登录
        $ucenter.on('click','.existLogin',function(){
            $get('/api/user/logout',{},function(data){
                common.toast(data.info);
                setTimeout('window.location.reload();',1000);
            })
        });
    }

    $(document).on("tap",".register",function(){
        window.location.href = $(this).attr("href");
    })
});
