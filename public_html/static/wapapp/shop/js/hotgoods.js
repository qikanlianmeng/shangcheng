define(function(require, exports, module) {
    require("jquery");
    var common = require("common");
    var layer = require('layer');
    var template = require("template");
    group == 1 ? $('.mui-title').html('样品专区') : group == 2 ? $('.mui-title').html('代售专区') : group == 3 ? $('.mui-title').html('订货专区') : '';
    //排序方式
    var order = "desc";
    //存放筛选的数据
    var filterstr = "";
    var page = 1;
    getData();
    //跳转商品详情
    $('body').on('tap', '.to_goods', function() {
        var _this = this;
        var id = $(_this).attr('data-id');
        window.location.href = "/wapapp/goods/goods/group/" + group + "/id/" + id;
    });
    // 点击选择排序
    $(".screen span:not(.price-span)").on("tap", function() {
            var $this = $(this);
            if ($this.hasClass("active")) { //判断已选中但不是price-span
            } else {
                // 重置列表页数
                research_screen($this);
            }
        })
        // 点击价格排序
    $(".screen span.price-span").on("tap", function() {
            var $this = $(this);
            // 重置列表页数
            research_screen($this);

        })
        //选择规格
    $(".specWrap").on("tap", ".goods-attribute p span", function() {
            var dataKey = $(this).attr("data-key");
            var $this = $(this);
            //判断是不是规格和金钱
            if (dataKey == "spec-" || dataKey == "price-") {
                $this.siblings("span").removeClass("selected");
            }
            if ($(this).hasClass("selected")) {
                $(this).removeClass("selected");
            } else {
                $(this).addClass("selected");
            }
            // 重新搜索物品
            research();
        })
        //重置筛选
    $(".reset").on("tap", function() {
            $(".spec-span").each(function(index, value) {
                $(value).removeClass("selected");
            })
            research();
        })
        //上拉加载
    $(window).scroll(function() {
        if ($(document).scrollTop() >= $(document).height() - $(window).height()) {
            page++;
            filter(filterstr, page);
        }
    });

    function getData() {
        $get("/api2/goods/lists", { group: group, keywords: keywords, cid: cid }, function(ret) {
            if (ret.status == 1) {
                $(".noAttend").hide();
                $(".dropDown p").hide();
                // console.log(ret.data);
                var str = "";
                for (var i = 0; i < ret.data.length; i++) {
                    // console.log(ret.data.goods_list[i])
                    str += template("goodslist", ret.data[i]);
                }
                $("#goodsWrap").html(str);
            } else {
                // common.toast(ret.msg);
                $(".noAttend").show();
            }
        })
    }

    //排序请求
    function filter(filterstr, page) {
        if (arguments[1] == undefined) {
            var orderby = $(".screen span.active").attr("data-orderby");
            if ($(".screen span.active").hasClass("up")) {
                order = "asc";
            } else {
                order = "desc";
            }
            // console.log(order);
            $get("/api2/goods/lists", { group: group, keywords: keywords, order_by: orderby, order: order, cid: cid, page: page }, function(ret) {
                if (ret.status == 1) {
                    $(".noAttend").hide();
                    $(".dropDown p").hide();
                    // 搜索框添加数据
                    // $("#searchInput").val(ret.data.category_name)
                    // console.log(ret.data);
                    var str = "";
                    for (var i = 0; i < ret.data.length; i++) {
                        // console.log(ret.data.goods_list[i])
                        str += template("goodslist", ret.data[i]);
                    }
                    $("#goodsWrap").html(str);
                } else {
                    // common.open(ret.msg);
                    $("#goodsWrap").html(" ");
                    $(".noAttend").show();
                }
            })
        } else {
            var orderby = $(".screen span.active").attr("data-orderby");
            if ($(".screen span.active").hasClass("up")) {
                order = "asc";
            } else {
                order = "desc";
            }
            $get("/api2/goods/lists", { group: group, order_by: orderby, order: order, keywords: keywords, cid: cid, page: page }, function(ret) {
                if (ret.status) {
                    $(".noAttend").hide();
                    $(".dropDown p").hide();
                    // console.log(ret.data);
                    var str = "";
                    for (var i = 0; i < ret.data.length; i++) {
                        // console.log(ret.datadata[i])
                        str += template("goodslist", ret.data[i]);
                    }
                    $("#goodsWrap").append(str);
                } else {
                    $(".dropDown p").show();
                }
            })
        }
    }

    function research() {
        filterstr = "";
        page = 1;
        $(".goods-attribute").each(function(index, value) {
            var filter = $(this).find(".spec-span.selected").attr("data-key");
            if (filter != undefined) {
                filterstr += filter;
                $(this).find(".spec-span").each(function(index, value) {
                    if ($(this).hasClass("selected")) {
                        filterstr += $(this).attr("data-id");
                        // console.log(filterstr)
                    }
                })
                filterstr = filterstr.substring(0, filterstr.length - 1);
                filterstr += "@"
            }
        })
        filterstr = filterstr.substring(0, filterstr.length - 1);
        // console.log(filterstr)
        filter(filterstr);
    }

    function research_screen($this) {
        page = 1;
        $(".dropDown p").hide();
        $(".screen span.active").removeClass("active");
        $this.addClass("active");
        if ($(".price-span").hasClass("active")) {
            $this.find(".shengjiangxu").hide();
            if ($this.hasClass("up")) {
                $this.removeClass("up").addClass("down");
            } else if ($this.hasClass("down")) {
                $this.removeClass("down").addClass("up");
            } else {
                $this.addClass("up");
            }
        } else {
            $(".price-span").removeClass("up").removeClass("down").find(".shengjiangxu").show();
        }
        filter(filterstr);
    }
})