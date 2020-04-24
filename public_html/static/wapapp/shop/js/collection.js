define(function(require, exports, module) {
    require("jquery");
    var common = require("common");
    var layer = require("layer");
    var template = require("template");
    var page = 1;
    $(".edit").on("click", function() {
            $(".mui-table-view").addClass("editing");
            $(".mui-table-view-cell a").wrapInner('<div class="mui-input-row mui-checkbox mui-left"><label></label><input name="checkbox" type="checkbox" ></div>');
            $(this).hide();
            $(".confirm").show();
            $(".finish").show();
            $("input[type=checkbox]").change(function() {
                var checkedNum = 0;
                $("input[type=checkbox]").each(function(index) {
                    if ($(this).is(':checked')) {
                        checkedNum++;
                    }
                })
                $(".confirm e").text(checkedNum);
            })
        })
        //点击完成
    $(".finish").on("click", function() {
            $(".mui-table-view").removeClass("editing");
            $(".mui-media-body").unwrap().unwrap();
            $(".mui-media-body").siblings("input").remove();
            $(this).hide();
            $(".confirm").hide();
            $(".edit").show();
        })
        //点击删除
    $(".delete").on("tap", function() {
        deletecollect();
    })
    getData();

    function getData() {
        $get("/api2/goods/collection_list", { page: page, count: 10 }, function(ret) {
            if (ret.status) {
                console.log(ret);
                var str = "";

                for (i in ret.data) {
                    var g = ret.data[i].goods_group
                    ret.data[i].g_g = g == 1 ? '样品区商品' : g == 2 ? '代营区商品' : g == 3 ? '订货区商品' : '';
                    str += template("goodslist", ret.data[i]);
                }
                $(".goodslist").append(str);
            } else if (ret.status == 0) {
                $(".edit").hide();
                $(".noAttend").show();
            }
        })
    }
    // function deletecollect(){
    //     var ids = "";
    //     $("input[name = checkbox]").each(function(index,item){
    //         if(item.checked){
    //             var oneid = $(item).parents(".mui-media").attr("data-id")
    //             ids += oneid;
    //         }
    //     })
    //     ids = ids.substring(0,ids.length-1);
    //     $get("/api/goods/uncollection",{ids:ids},function(ret){
    //         if(ret.status){
    //             mui.toast("删除成功");
    //             $("input[name = checkbox]").each(function(index,item){
    //                 if(item.checked){
    //                     $(item).parents(".mui-media").addClass("fadeOut");
    //                     setTimeout(function(){
    //                         $(item).parents(".mui-media").remove();
    //                     },500)
    //                     $(".confirm e").text("0");
    //                 }
    //             })
    //         }
    //     })
    // }
    // $('body').on('tap', '.list_a', function(e) {
    //     e.preventDefault();
    //     var $this = $(this);
    //     var id = $this.parents('li').attr('data-id');
    //     var group = $this.parents('li').attr('data-group');
    //     window.location.href = "/wapapp/goods/goods/group/" + group + "/id/" + id;
    // });
    $('body').on('tap', '#to_cancel', function(e) {
        e.stopImmediatePropagation();
        var $this = $(this);
        var id = $this.parents('li').attr('data-id');
        var group = $this.parents('li').attr('data-group');
        $get('/api2/goods/collection', { group: group, id: id }, (ret) => {
            common.toast(ret.msg);
            if (ret.status == 1) {
                $this.parents('li').remove();
                if ($('.goodslist').find('li').length == 0) {
                    $(".noAttend").show();
                }
            }
        });
    });
    //上拉加载
    $(window).scroll(function() {
        if ($(document).scrollTop() >= $(document).height() - $(window).height()) {
            page++;
            $get("/api2/goods/collection_list", { page: page, count: 10 }, function(ret) {
                if (ret.status) {
                    console.log(ret);
                    var str = "";
                    for (i in ret.data) {
                        str += template("goodslist", ret.data[i]);
                    }
                    $(".goodslist").append(str);
                } else if (ret.status == 0) {
                    $(".dropDown p").show();
                }
            })
        }
    })
})