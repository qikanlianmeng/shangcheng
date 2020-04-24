define(function(require, exports, module) {
    require("jquery");
    require("common");
    var template = require("template")
    mui('.mui-bar-tab').on('tap', 'a', function() {
        location.href = this.getAttribute('href');
    })
    getData();

    // 搜索
    $(".search").on("tap", function() {
        var searchval = $("input[name = searchgoods]").val();
        console.log($("input[name = searchgoods]").val());
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
    $('body').on('tap', 'to_goods', function() {
        var _this = $(this);
        var id = _this.attr('data-id');
        window.location.href = "/wapapp/goods/searchlist/group/1/cid/" + id;
    });
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

    function getData() {
        $get("/api2/goods/category", {}, function(ret) {
            if (ret.status) {
                var leftstr = "",
                    rightstr = "";
                for (var i = 0; i < ret.data.length; i++) {
                    leftstr += '<a class="mui-control-item" href="" onclick="checked(this)"><span><span class="m-l-xs">' + ret.data[i].name + '</span></span></a>';
                    rightstr += '<div class="mui-control-content"><ul>' + template('class_list', ret.data[i]) + '</ul></div>'
                        // rightstr += '<div class="mui-control-content"><a class="" href="/wapapp/goods/hotgoods/group/1/cid/' + ret.data[i].id + '">' + ret.data[i].name + '</a></div>'
                        // if (ret.data[i].child != undefined) {
                        //     // console.log(ret.data[i].child)
                        //     for (j = 0; j < ret.data[i].child.length; j++) {
                        //         if (ret.data[i].child[j].child != undefined) {
                        //             for (k = 0; k < ret.data[i].child[j].child.length; k++) {
                        //                 goodsstr = '<li class="mui-table-view-cell mui-media mui-col-xs-4">\
                        //                                 <a href="/wapapp/goods/searchlist/cid/' + ret.data[i].child[j].child[k].id + '">\
                        //                                     <img class="mui-media-object classify-img" src="' + ret.data[i].child[j].child[k].image + '" alt="">\
                        //                                     <div class="mui-media-body">' + ret.data[i].child[j].child[k].mobile_name + '</div>\
                        //                                 </a>\
                        //                             </li>'
                        //             }
                        //         }
                        //         rightstr += '<div class="mui-control-content">\
                        //                         <p style="text-align:center;margin-bottom:0;padding-top:20px;">———— <e>' + ret.data[i].child[j].mobile_name + '</e> ————</p>\
                        //                         <ul class="mui-table-view mui-grid-view" style="padding-top:20px;padding-right:0;">' + goodsstr + '</ul>\
                        //                      </div>'
                        //         var goodsstr = "";
                        //     }
                        //     // console.log(rightstr)
                        // } else { //没有二级分类的情况下加一个空的mui-control-content
                        //     rightstr += '<div class="mui-control-content"></div>'
                        // }
                }
                var tagstr = '<div class="mui-col-xs-3 classify" style="overflow-y:scroll">\
                                <div class="mui-segmented-control mui-segmented-control-inverted mui-segmented-control-vertical" id="role_content">' + leftstr + '</div>\
                              </div>\
                              <div class="mui-col-xs-9 classify-info" style="overflow-y:scroll">' + rightstr + '</div>'
                    // goodsstr += template("deduction",ret.data);
                $(".mui-content").append(tagstr);
                $(".mui-control-item").eq(0).addClass("mui-active");
                $(".mui-control-content").eq(0).addClass("mui-active");
            }
        })
    }
    if (UID == 0) {
        window.location.href = "/wapapp/index/login";
    }
})

function checked(that) {
    var index = $(that).index();
    $(".mui-control-content.mui-active").removeClass("mui-active");
    $(".mui-control-content").eq(index).addClass("mui-active");
}