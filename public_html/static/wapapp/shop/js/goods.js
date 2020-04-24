'use strict';
define(function (require, exports, module) {
    require("jquery");
    require("swiper");
    // require("vconsole");
    var common = require("common");
    var template = require("template");
    var layer = require("layer");
    var wx = require("weixin");
    var qrcode = require("qrcode");
    var startTime, endTime, runm;
    // var vconsole = new VConsole();


    $('#contact').on('click', function (e) {
        $('#newBridge #nb_icon_wrap').click();
    });
    // console.log(baseUrls);
    $('.tiaoma').on('tap', function () {
        $('#tiaoma').fadeIn();
    })
    $('body').on('tap', '.share_pop', function () {
        $('#forword').fadeOut();
        $('#tiaoma').fadeOut();
    })
    window.onload = function () {
        var contaninerh = document.body.clientWidth;
        $(".goods-container").css("height", contaninerh + "px")
    }
    // 商品图的宽高比
    mui('.mui-scroll-wrapper').scroll({
        deceleration: 0.0005, //flick 减速系数，系数越大，滚动速度越慢，滚动距离越小，默认值0.0006
        indicators: false //隐藏滚动条
    });
    var goodsSwiper = new Swiper(".goods-container", {
        pagination: '.swiper-pagination'
    })
    //存用户选择的规格信息
    var specInfo = ""
    //存储不同规格的价格
    var specMoney = {}
    getData();
    getcomment();

    // 添加足迹
    addfootprint();

    function addfootprint() {
        $get("/api2/goods/add_footprint", { id: id, group: group }, function (ret) {
            if (ret.status) {
                // console.log(ret);
            }
        })
    }
    var par = {}, to_url = '';
    //获取商品详情
    function getData() {
            par = { id: id, group: group }
            to_url = '/api2/goods/goods_detail'
        $get(to_url, par, function (ret) {
            if (ret.status) {
                console.log(ret.data)
                if (ret.data.flash_sale != undefined) {
                    startTime = ret.data.flash_sale.start_time * 1000;
                    endTime = ret.data.flash_sale.end_time * 1000;
                    $("#bar").show();
                    $(".commodity").show();
                    $(".seckill").show();
                    var progress = parseInt(100 * (ret.data.flash_sale.goods_num - ret.data.flash_sale.buy_num) / ret.data.flash_sale.goods_num);
                    console.log(progress);
                    $("#bar .finish").css("width", progress + "%");
                    $("#bar .ing").text("剩余" + progress + "%");
                    if (ret.data.flash_sale.buy_limit > 0) {
                        $(".xiangou").show().text("每人限购" + ret.data.flash_sale.buy_limit + "件")
                    }
                    $(".goods-price-info").text(ret.data.flash_sale.price);
                    $(".specMoney").text(ret.data.flash_sale.price);
                    $(".buynow").text("立即抢购");
                    $(".sales_sum").text(ret.data.sales_num);
                    runm = ret.data.flash_sale.goods_num - ret.data.flash_sale.buy_num;
                    var time = countDown(startTime, endTime, runm);
                    $(".countdown").show();
                    
                } else {
                    $(".goods-price-info").text(ret.data.price);
                    $(".specMoney").text(ret.data.price);
                }
                var str = ""
                for (var i = 0; i < ret.data.img_tuku.length; i++) {
                    str += '<div class="swiper-slide">\
                                <img src="' + ret.data.img_tuku[i] + '" alt="">\
                            </div>'
                }
                goodsSwiper.appendSlide(str);
                $(".goods_name").text(ret.data.goods_name);


                $(".specCount").text(ret.data.store_count);
                if (ret.data.exchange_integral_money == undefined) {
                    $(".exchange").hide();
                } else {
                    $(".exchange e").text(ret.data.exchange_integral_money);
                }
                if (ret.data.give_integral != 0) {
                    $(".return-integral e").text(ret.data.give_integral);
                } else {
                    $(".return-integral").hide();
                }
                $(".original-price e").text(ret.data.market_price);
                ret.data.is_free_shipping == 0 ? $(".free_shipping e").text("不包邮") : $(".free_shipping e").text("免运费");
                $(".comment_count").text(ret.data.comment_num);
                $(".sales_sum").text(ret.data.sales_num);
                //商品评价
                // if(ret.data.comment_list.length > 0){
                //
                // }else{
                //     $(".comment_list").html("<p style='text-align:center'>暂无商品评价</p>")
                //     $(".mui-card-footer").hide();
                //     $(".gotocommentList").removeAttr("href")
                // }
                // 商品图文详情
                $("#ImgandText").html(ret.data.goods_content);
                //产品参数
                var parameterstr = "";
                for (var i in ret.data.attr_arr) {
                    parameterstr += '<li>\
                                        <span>' + i + '</span>\
                                        <p>' + ret.data.attr_arr[i] + '</p>\
                                    </li>'
                }
                $("#parameter").html(parameterstr)
                //热门推荐
                var recommend_goodsstr = "";
                for (var i in ret.data.recommend_goods) {
                    var leftListHeight = $(".RecommendList").height();
                    var rightListHeight = $(".RecommendList_second").height();
                    recommend_goodsstr = template("recommend_goods", ret.data.recommend_goods[i]);
                    if (rightListHeight >= leftListHeight) {
                        $(".RecommendList").append(recommend_goodsstr);
                    } else {
                        $(".RecommendList_second").append(recommend_goodsstr);
                    }
                }
                // $(".RecommendList").html(recommend_goodsstr);

                //没有规格
                // if (ret.data.sepc_arr.length == 0) {
                //     $(".open-option").text("默认规格");
                // }
                //商品图片
                $(".spec-img").attr("src", ret.data.original_img)
                //选择分类
                var sepcstr = "";
                //存放分类
                var sepc;
                for (var i in ret.data.sepc_arr) {
                    var specstrsecond = "";
                    for (var j in ret.data.sepc_arr[i]) {
                        specstrsecond += '<span data-id="' + ret.data.sepc_arr[i][j].id + '_" class="spec-span">' + ret.data.sepc_arr[i][j].item + '</span>'
                    }
                    sepcstr += '<div class="goods-attribute">\
                                    <p>' + i + '</p>\
                                    <p>' + specstrsecond + '</p>\
                                </div>'
                }
                $(".specWrap").html(sepcstr);
                $(".goods-attribute").find("span:first-child").addClass("selected");
                specMoney = ret.data.spec_data;
                //是否收藏
                if (ret.data.is_collection == 1) {
                    $(".icon-shoucang").addClass("icon-sc1").removeClass("icon-shoucang");
                    $(".icon-sc1").css("color", "#f2a91c");
                }
                //修改分类
                specChange();
                //购物车中物品数量
                getCartNum();
            }
        })
    }
    function getcomment() {
        $get('/api2/goods/comment_list', { 'goods_id': id }, function (data) {
            if (!data.status) {
                $('#comment').html('<p class="color-hui mui-text-center" style="line-height: 44px;"><span>抱歉！暂无商品评价</span></p>');
                $('.seeAll').hide();
            } else {
                data = data.data;
                var comment_content = data[0].comment_content
                for (var j in comment_content) {
                    // 时间转换
                    var c_time = new Date(parseInt(comment_content[j].c_time) * 1000);
                    data[0].comment_content[j].c_time = common.getLocalTime(c_time);
                    if (comment_content[j].reply_time) {
                        var reply_time = new Date(parseInt(comment_content[j].reply_time) * 1000);
                        data[0].comment_content[j].reply_time = common.getLocalTime(reply_time);
                    }
                    // 获取评论人的头像用户名
                    if (comment_content[j].type == 0) {
                        data[0].user_avatar = comment_content[j].user_avatar;
                        data[0].user_nickname = comment_content[j].user_nickname;
                    }
                }
                var str = template('commentList', data[0]);
                $('#comment').prepend(str);
            }
        })
    }

    mui('.mui-bar-tab').on('tap', '.shopcart', function () {
        location.href = this.getAttribute('href');
    })

    $(window).scroll(function () {
        var scrT = $(window).scrollTop();
        //图文详情，产品参数，热门推荐
        var ImgT = $("#ImgandText")[0].offsetTop - 40;
        var parT = $("#parameter")[0].offsetTop;
        var RecT = $("#Recommend")[0].offsetTop;
        // console.log(scrT,ImgT,parT,RecT)
        screen_active(scrT, ImgT, parT, RecT);
    })
    //触发滚动
    $(window).scroll();
    //页内跳转文详情，产品参数，热门推荐
    function screen_active(scrT, ImgT, parT, RecT) {
        $(".screen span.active").removeClass("active");
        $(".returnTop").removeClass("fadeOut").addClass("fadeIn").show();
        $(".screen").css({
            "position": "fixed",
            "top": "0",
            "width": "100%"
        });
        if (scrT >= ImgT && scrT < parT) {
            $(".returnTop").removeClass("fadeOut").addClass("fadeIn").show();
            $(".screen span").eq(0).addClass("active");
        }
        if (scrT >= parT && scrT < RecT) {
            $(".screen span").eq(1).addClass("active");
        }
        if (scrT >= RecT) {
            $(".screen span").eq(2).addClass("active");
        } else if (scrT <= ImgT) {
            $(".screen").css({
                "position": "",
            });
            $(".returnTop").removeClass("fadeIn").addClass("fadeOut").hide();
            $(".screen span").eq(0).addClass("active");
        }
    }
    //跳转图文详情
    $(".scrolltoImgandText").on("tap", function (e) {
        $("#screenWrap")[0].scrollIntoView();
        $(this).find("span").addClass("active");
        $(".scrolltoparameter").find("span").removeClass("active");
        $(".scrolltoRecommend").find("span").removeClass("active");
    })
    //跳转产品参数
    $(".scrolltoparameter").on("tap", function (e) {
        $(this).find("span").addClass("active");
        $(".scrolltoImgandText").find("span").removeClass("active");
        $(".scrolltoRecommend").find("span").removeClass("active");
        setTimeout(function () {
            $("#parameter")[0].scrollIntoView();
        }, 50)
    })
    //跳转热门推荐
    $(".scrolltoRecommend").on("tap", function (e) {
        $("#Recommend")[0].scrollIntoView();
        $(this).find("span").addClass("active")
        $(".scrolltoparameter").find("span").removeClass("active");
        $(".scrolltoImgandText").find("span").removeClass("active");
    })

    //选择规格
    $(".specWrap").on("click", ".goods-attribute p span", function () {
        $(this).siblings("span").removeClass("selected");
        $(this).addClass("selected");
        specChange();
    })
    //改变规格和价钱
    function specChange() {
        // 改变已选：数组
        var str = "";
        $(".goods-attribute").find("span").each(function (index, value) {
            if ($(this).hasClass("selected")) {
                str += $(this).text() + " ";
            }
        })

        //改变key
        specInfo = "";
        $(".spec-span.selected").each(function (index, value) {
            specInfo += $(value).attr("data-id");
        })
        specInfo = specInfo.substr(0, specInfo.length - 1);
        for (var i in specMoney) {
            if (i == specInfo) {
                console.log(specMoney[specInfo])
                $(".specMoney").text(specMoney[specInfo].price);
                $(".specCount").text(specMoney[specInfo].store_count);
                $(".spec-img").attr("src", specMoney[specInfo].img);
                $(".open-option").text(specMoney[specInfo].key_value);
            }
        }
    }

    // 页面中点击“加入购物车”和“直接购买流程”
    //加入购物车和立即购买
    $(".addtocart").on("tap", function () {
        confirm();
        $("#buy_confirm").attr("data-spec", "shopcar")
    })
    $(".buynow").on("tap", function () {
        confirm();
        $("#buy_confirm").attr("data-spec", "buynow")
    })
    //页面内加入购物车和购买操作
    function confirm(option) {
            $(".goods-option").hide();
            $("#buy_confirm").show();
    }
    $('.shopcart').on('tap', function () {
            window.location.href = "/wapapp/cart/shopcar.html";
    });
    $('.seeAll,.gotocommentList').on('tap', function () {
            window.location.href = "/wapapp/order/comment_list.html/id/" + id;
    });
    //点击确定
    $("#buy_confirm").on("tap", function () {
        var option = $(this).attr("data-spec");
        addtocart(option);
    });
    //点击确定
    $("#security_confirm a").on("tap", function () {
        cli_hide();
    });

    // 弹出框中点击“加入购物车”和“直接购买流程”
    //点击商品规格选择
    $("#specBtn").on("tap", function () {
        $(".goods-option").show();
        $("#buy_confirm").hide();
    })
    //弹出框中加入购物车和立即购买
    $(".add").on("tap", function () {
        addtocart("shopcar")
    })
    $(".buy").on("tap", function (e) {
        e.stopImmediatePropagation();
        addtocart("buynow")
    })

    // 加入购物车
    function addtocart(option) {
        var num = mui(".mui-numbox").numbox().getValue();
            $get("/api2/goods/add_cart", { id: id, group: group, num: num }, function (ret) {
                console.log(id, specInfo, num, option)
                $(".badge-num").text(ret.result).show();
                if (ret.status == 1) {
                    mui.toast("添加成功，在购物车等亲~")
                    if (option == "buynow") {
                        //跳转到订单页面
                        window.location.href = "/wapapp/cart/shopcar.html"
                    }
                    return false;
                } else if (ret.status == 0) {
                    mui.toast(ret.msg);
                    return false;
                }
            })

    }
    //改变购物车物品数量
    function getCartNum() {
        $get("/api2/goods/get_cart_goods_num", {}, function (ret) {
            if (ret.status) {
                if (ret.data == 0) {
                    return false;
                } else {
                    $(".badge-num").text(ret.data).show();
                }
            }
        })
    }

    //收藏
    $(".collect").on("tap", function () {
            $get("/api2/goods/collection", { id: id, group: group }, function (ret) {
                if (ret.status == 1) {
                    mui.toast(ret.msg);
                    if (ret.msg == "收藏成功") {
                        $(".icon-shoucang").addClass("icon-sc1").removeClass("icon-shoucang");
                        $(".icon-sc1").css("color", "#f2a91c");
                    } else if (ret.msg == "取消收藏成功") {
                        $(".icon-sc1").addClass("icon-shoucang").removeClass("icon-sc1");
                        $(".icon-shoucang").css("color", "#716f70");
                    }
                } else {
                    mui.toast("请先登录")
                }
            })
    })

    // 联系客服
    // $('#contact').on('click',function(){
    //     if(share==2){
    //         window.location.href="/wapapp/index/login.html";
    //         return false;
    //     }
    // });
    //热门推荐商品点击跳转
    $(document).on("tap", ".goods-list", function () {
        var goodsId = $(this).attr("data-href")
        window.location.href = "/wapapp/goods/goods/id/" + goodsId+"/group/"+group;
    })
    //返回顶部
    $(".returnTop").on("tap", function () {
        $('html,body').animate({ scrollTop: 0 }, 700);
    })

    $(document).on('tap', '.icon-fenxiang', function () {
        zhuanfa();
    });
    $('.close_pop').on('tap', function () {
        $('#forward').fadeOut(300);
        cli_hide();
    });

    function zhuanfa() {
        $('#forward').show();
    }
    $("#layer_mask").on("tap", function () {
        cli_hide();
    })

    function cli_hide() {
        $("#layer_mask").hide();
        $('#forward').hide();
        $(".mui-backdrop.mui-backdrop-action.mui-active").remove();
        $(".mui-popover").removeClass('mui-active').hide();
        $("body").css("position", "initial");
    }

    //定时器函数
    var timer = setInterval(function () {
        var now = new Date();
        var nowTime = now.getTime();
        var time = countDown(startTime, endTime, runm);
    }, 1000);

    function countDown(sTime, eTime, rnum) {
        var now = new Date();
        var nowTime = now.getTime();
        var time;
        if (sTime > nowTime) {
            //距离开始还有多少时间
            time = parseInt((sTime - nowTime) / 1000);
        } else if (((sTime <= nowTime) && (nowTime < eTime)) && (rnum != 0)) {
            //距离结束还有多久
            time = parseInt((eTime - nowTime) / 1000);
        } else {
            //到时间截止或者秒杀完--已结束
            if (rnum == 0) { //如果数量=0，结束
                return 0;
            } else {
                //如果截止时间到了，结束
                return 1;
            }
            $(document).on("click", ".endbtn", function () {
                mui.toast("活动已结束")
            })
        }
        var d = parseInt(time / 86400);
        time = time % 86400; //分别取余数
        var h = parseInt(time / 3600);
        time %= 3600;
        var m = parseInt(time / 60);
        var s = time % 60;
        $(".hei").eq(0).text(d);
        $(".hei").eq(1).text(h);
        $(".hei").eq(2).text(m);
        $(".hei").eq(3).text(s);
        /*if(((d==0)&&(h==0)&&(m==0)&&(s==0))||(rnum == 0 )){
         window.location.reload();
         }*/
    }
})