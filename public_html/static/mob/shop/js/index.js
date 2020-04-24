define(function(require,exports,module){
    require("jquery");
    require("swiper");
    // require("shopcommon");
    var template = require("template");
    var common = require("common");
    // 获取购物车商品数量
    getchopcartnum();
    function getchopcartnum(){
        $get("/api/goods/get_cart_goods_num",function(ret){
            if(ret.status){
                if(ret.data != 0){
                    $(".mui-bar-tab .mui-badge").text(ret.data).show();
                }
            }
        })
    }
    //控制菜单栏点击跳转
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

    document.documentElement.style.fontSize = document.documentElement.clientWidth / 720*100 + 'px';
    var mySwiper = new Swiper ('.lunbo', {
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
    $(".icon-qianwang").on("tap",function(){
        var searchval = $("input[name = searchgoods]").val();
        console.log($("input[name = searchgoods]").val());
        if(searchval == ""){
            mui.toast("请输入商品信息");
        }else{
            window.location.href="/wap/goods/searchlist_name/searchname/" + searchval;
        }
    })

    getdata();
    // 调取商品数据
    function getdata(){
        //获取广告数据
        $get("api/ad/wap_index",function(ret){
            console.log(ret);
            var str = "";
            for(var i = 0;i < ret[1].length;i++){
                str+=template("adarea",ret[1][i]);
            }
            mySwiper.appendSlide(str);
            $(".goods-new-img").attr("src",ret[2].images);
            $(".goods-new-href").attr("href",ret[2].link_url);
            $(".goods-discover-img").attr("src",ret[3].images);
            $(".goods-discover-href").attr("href",ret[3].link_url);
            $(".goods-recommend-img").attr("src",ret[4].images);
            $(".goods-recommend-href").attr("href",ret[4].link_url);
            $(".goods-recommend-sceoundimg").attr("src",ret[5].images);
            $(".goods-recommend-sceoundhref").attr("href",ret[5].link_url);
        })
        /* 抵扣 */
        $get("api/goods/special_goods",{type:"is_deduction",num:"6"},function(ret){
            if(ret.status == 1){
                var str = "";
                var datastr  = "";
                for(var i = 0;i < ret.data.goods_list.length;i++){
                    str += template("deduction",ret.data.goods_list[i]);
              
                    
                    if(i%2 == 1 || i == ret.data.goods_list.length - 1){
                        datastr += '<div class="mui-row">'+str+'</div>';
                        str = "";
                    }
                }
                $('.deduction').html(datastr);
                // deductionSwiper.appendSlide(datastr);
            }
        });
        /* 购物返羊币 */
        $get("api/goods/special_goods",{type:"is_return",num:"6"},function(ret){
            if(ret.status == 1){
                var str = "";
                var datastr  = "";
                for(var i = 0;i < ret.data.goods_list.length;i++){ 

                    str += template("return",ret.data.goods_list[i]);
                    // console.log(str);
                    // console.log(JSON.stringify(ret.data.goods_list[i]));
                    
                   
                    if(i%2 == 1 || i == ret.data.goods_list.length - 1){
                        datastr += '<div class="mui-row">'+str+'</div>';
                        str = "";
                    }
                }
                $('.isreturn').html(datastr);
            }
        });
        /* 热门推荐 */
        $get("api/goods/special_goods",{type:"is_recommend",num:"6"},function(ret){
            if(ret.status == 1){
                var str = "";
                var datastr  = "";
                for(var i = 0;i < ret.data.goods_list.length;i++){
                    str += template("deduction",ret.data.goods_list[i])
                    if(i%2 == 1 || i == ret.data.goods_list.length - 1){
                        datastr += '<div class="mui-row">'+str+'</div>';
                        str = "";
                    }
                }
                $('.isrecommend').html(datastr);
                // isrecommendSwiper.appendSlide(datastr);
            }
        });
        // $get("/api/goods/flash_sale",{page:1,num:"6",active:1},function(ret){
        //     console.log(ret.data)
        //     if(ret.status == 1){
        //         var str = "";
        //         var datastr  = "";
        //         for(var i = 0;i < ret.data.length;i++){
        //             var bai = parseInt(100*(ret.data[i].goods_num-ret.data[i].buy_num)/ret.data[i].goods_num);
        //             ret.data[i].bai = bai;
        //             str += template("seckill",ret.data[i])
        //             if(i%2 == 1 || i == ret.data.length - 1){
        //                 datastr += '<div class="swiper-slide"><div class="mui-row">'+str+'</div></div>';
        //                 str = "";
        //             }
        //         }
        //         seckillSwiper.appendSlide(datastr);
        //     }
        // })
    }
})
