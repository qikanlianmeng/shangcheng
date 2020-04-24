define(function(require, exports, module) {
    require("jquery");
    var common = require("common");
    var template = require("template");

    var filterstr = ""
    var page = 1;
    getData(group);

    // 输入框搜索请求
    if (keywords) {
        $("#searchInput").val(keywords);
        $(".mui-placeholder").hide();
        $('.mui-search').addClass('mui-active');
    }
    $(".search").on("tap", function() {
        var searchval = $("input[name = searchgoods]").val();
        if (searchval == "") {
            mui.toast("请输入商品信息");
        } else {
            window.location.href = "/wapapp/goods/searchlist/group/" + group + "/keywords/" + searchval;
        }
    })
    $('input[name = searchgoods]').bind('keypress', function(event) {
        if (event.keyCode == "13" || event.keyCode == 9) {
            var searchval = $("input[name = searchgoods]").val();
            if (searchval == "") {
                mui.toast("请输入商品信息");
            } else {
                window.location.href = "/wapapp/goods/searchlist/group/" + group + "/keywords/" + searchval;
            }
        }
    });
    //选择group
    $(".screen span.g_span .goods_group").on("tap", function() {
        $('.s_box').toggle();
    })
    $(".s_box").on("tap", 'p', function(e) {
            e.stopPropagation();
            var _this = this;
            $(_this).addClass('on').siblings().removeClass('on');
            $('.g_group').html($(_this).html());
            getData($(_this).index() + 1);
            $('.s_box').fadeOut();
        })
        // 点击选择排序
    $(".screen span.col_span,.screen span.sales_span").on("tap", function() {
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

    //上拉加载
    $(window).scroll(function() {
        if ($(document).scrollTop() >= $(document).height() - $(window).height()) {
            page++;
            filter(filterstr, page);
        }
    });

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
        //重置搜索
    $(".reset").on("tap", function() {
        $(".spec-span").each(function(index, value) {
            $(value).removeClass("selected");
        })
        research();
    })


    function getData(g) {
        group = g;
        page = 1;
        var orderby = $(".screen span.active").attr("data-orderby"),
            order = '';
        if ($(".screen span.active").hasClass("up")) {
            order = "asc";
        } else {
            order = "desc";
        }
        $get("/api2/goods/lists", { cid: cid, group: g, keywords: keywords, orderby: orderby, order: order }, function(ret) {
            if (ret.status) {
                $(".noAttend").hide();
                // 搜索框添加数据
                // $("#searchInput").val(ret.data.category_name)
                if (ret.data.length == 0 || ret.data == '') {
                    $(".noAttend").show();
                } else {
                    $(".noAttend").hide();
                    var str = "";
                    for (var i = 0; i < ret.data.length; i++) {
                        // console.log(ret.data[i])
                        str += template("goodslist", ret.data[i]);
                    }
                    $("#goodsWrap").html(str);
                }

            } else {
                $(".noAttend").show();
            }
        })
    }
    $('body').on('tap', '.to_goods', function() {
        var id = $(this).attr('data-id');
        window.location.href = "/wapapp/goods/goods/id/" + id + '/group/' + group;
    });
    //请求列表
    function filter(filterstr, page) {
        if (arguments[1] == undefined) {
            var orderby = $(".screen span.active").attr("data-orderby");
            if ($(".screen span.active").hasClass("up")) {
                order = "asc";
            } else {
                order = "desc";
            }
            $get("/api2/goods/lists", { cid: cid, group: group, keywords: keywords, order_by: orderby, order: order, page: page }, function(ret) {
                if (ret.status) {
                    $(".noAttend").hide();
                    // 搜索框添加数据
                    // $("#searchInput").val(ret.data.category_name)
                    // console.log(ret.data);
                    var str = "";
                    for (var i = 0; i < ret.data.length; i++) {
                        // console.log(ret.data[i])
                        str += template("goodslist", ret.data[i]);
                    }
                    $("#goodsWrap").html(str);
                } else {
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
            $get("/api2/goods/lists", { cid: cid, group: group, keywords: keywords, order_by: orderby, order: order, page: page }, function(ret) {
                if (ret.status) {
                    $(".noAttend").hide();
                    // 搜索框添加数据
                    // $("#searchInput").val(ret.data.category_name)
                    // console.log(ret.data);
                    var str = "";
                    for (var i = 0; i < ret.data.length; i++) {
                        // console.log(ret.data[i])
                        str += template("goodslist", ret.data[i]);
                    }
                    $("#goodsWrap").append(str);
                } else {
                    $(".dropDown p").show();
                }
            })
        }

    }

    //重排参数之后请求
    function research() {
        page = 1;
        filterstr = "";
        var tempfilter = "";
        $(".goods-attribute").each(function(index, value) {
            var filter = $(this).find(".spec-span.selected").attr("data-key");
            if (filter != undefined) {
                if (filter == tempfilter) {
                    $(this).find(".spec-span").each(function(index, value) {
                        if ($(this).hasClass("selected")) {
                            filterstr = filterstr.substring(0, filterstr.length - 1);
                            filterstr += "_"
                            filterstr += $(this).attr("data-id");
                            // console.log(filterstr)
                        }
                    })
                } else {
                    tempfilter = filter;
                    filterstr += filter;
                    $(this).find(".spec-span").each(function(index, value) {
                        if ($(this).hasClass("selected")) {
                            filterstr += $(this).attr("data-id");
                            // console.log(filterstr)
                        }
                    })
                }
                filterstr = filterstr.substring(0, filterstr.length - 1);
                filterstr += "@"
            }
        })
        filterstr = filterstr.substring(0, filterstr.length - 1);
        console.log(filterstr)
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