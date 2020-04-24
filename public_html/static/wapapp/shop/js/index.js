define(function(require, exports, module) {
    require("jquery");
    require("swiper");
    // require("shopcommon");
    var template = require("template");
    var common = require("common");
    getdata();
    // 获取购物车商品数量
    getchopcartnum();

    function getchopcartnum() {
        $get("/api2/goods/get_cart_goods_num", function(ret) {
            if (ret.status) {
                if (ret.data != 0) {
                    $(".mui-bar-tab .mui-badge").text(ret.data).show();
                }
            }
        })
    }
    // 资讯轮播
    //  $get('/api/article/article', { page: 1 }, function (data) {
    //     if (data.status == 1) {
    //         var list = '';
    //         for (var i of data.data) {
    //             i.create_time = timetrans1(i.create_time);
    //             list += template('banner', i);
    //         }
    //         $('#notice_lists').html(list);
    //创建资讯swiper
    var notice_banner = new Swiper('.notice_banner', {
        direction: 'vertical', // 垂直切换选项
        loop: true, // 循环模式选项
        autoplay: 3500,
        //注意此参数，默认为true
    });
    //     } else {

    //     }
    // });
    //控制菜单栏点击跳转
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

    document.documentElement.style.fontSize = document.documentElement.clientWidth / 720 * 100 + 'px';
    var mySwiper = new Swiper('.lunbo', {
            autoplay: 3500,
            loop: true,
            pagination: '.swiper-pagination',

        })
        // var deductionSwiper = new Swiper ('.deduction', {
        //     pagination: '.swiper-pagination'
        // })
        // var isreturnSwiper = new Swiper ('.isreturn', {
        //     pagination: '.swiper-pagination'
        // })
        // var isrecommendSwiper = new Swiper ('.isrecommend', {
        //     pagination: '.swiper-pagination'
        // })
        // var seckillSwiper = new Swiper ('.seckill',{
        //     pagination: '.swiper-pagination'
        // })
        // 搜索
    $(".icon-qianwang").on("tap", function() {
        var searchval = $("input[name = searchgoods]").val();
        if (searchval == "") {
            mui.toast("请输入商品信息");
        } else {
            window.location.href = "/wapapp/goods/searchlist/group/1/keywords/" + searchval;
        }
    });
    $('input[name = searchgoods]').bind('keypress', function(event) {
        if (event.keyCode == "13" || event.keyCode == 9) {
            var searchval = $("input[name = searchgoods]").val();
            if (searchval == "") {
                mui.toast("请输入商品信息");
            } else {
                window.location.href = "/wapapp/goods/searchlist/group/1/keywords/" + searchval;
            }
        }
    });
    $('body').on('tap', '.kaifaing', function() {
        mui.toast('正在开发中...');
    });
    get_goods(1);
    get_goods(2);
    get_goods(3);

    function get_goods(g) {
        $get("/api2/goods/lists", { group: g }, function(ret) {
            if (ret.status) {
                var d = ret.data,
                    str = '';
                var n = d.length > 4 ? 4 : d.length;
                for (var i = 0; i < n; i++) {
                    d[i].group = g;
                    str += template('return', d[i]);
                }
                $('.c_' + g).html('<div class="mui-row">' + str + '</div>');
            } else {

            }
        })
    }
    // getdata();
    // 调取商品数据
    function getdata() {
        //获取广告数据
        $get("/api2/ad/index", function(ret) {
            console.log(ret);
            if (ret.list) {
                var str = "";
                for (var i = 0; i < ret.list.length; i++) {
                    str += template("adarea", ret.list[i]);
                }
                mySwiper.appendSlide(str);
            }
        })

    }
    is_bind();

    function is_bind() {
        $get('/api2/user/getuserinfo', {}, (ret) => {
            if (ret.status == 2) {
                window.location.href = "/wapapp/usercenter/binding_m.html";
            }
        })
    };
    get_adbtn();
    // 获取首页按钮
    function get_adbtn(){
        $get('/api2/ad/index_btn',{},(ret)=>{
            if(ret.status==1){
                var list='';
               for(var i of ret.list){
                   list+=template('ad_btn',i);
               } 
               $('.button-area').html(list);
            }
        });
    }
})