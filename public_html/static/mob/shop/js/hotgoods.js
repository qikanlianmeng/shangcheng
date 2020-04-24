define(function(require,exports,module){
    require("jquery");
    var common = require("common");
    var template = require("template");

    //排序方式
    var order = "desc";
    //存放筛选的数据
    var filterstr = "";
    var page = 1;
    getData();
    // 点击选择排序
    $(".screen span:not(.price-span)").on("tap",function(){
        var $this = $(this);
        if($this.hasClass("active")){//判断已选中但不是price-span
        }else{
            // 重置列表页数
            research_screen($this);
        }
    })
    // 点击价格排序
    $(".screen span.price-span").on("tap",function(){
        var $this = $(this);
        // 重置列表页数
        research_screen($this);

    })
    //选择规格
    $(".specWrap").on("tap",".goods-attribute p span",function(){
        var dataKey = $(this).attr("data-key");
        var $this = $(this);
        //判断是不是规格和金钱
        if(dataKey == "spec-" || dataKey == "price-"){
            $this.siblings("span").removeClass("selected");
        }
        if($(this).hasClass("selected")){
            $(this).removeClass("selected");
        }else{
            $(this).addClass("selected");
        }
        // 重新搜索物品
        research();
    })
    //重置筛选
    $(".reset").on("tap",function(){
        $(".spec-span").each(function(index,value){
            $(value).removeClass("selected");
        })
        research();
    })
    //上拉加载
    $(window).scroll(function() {
      if ($(document).scrollTop() >= $(document).height() - $(window).height()) {
          page++;
          filter(filterstr,page);
      }
    });

    function getData(){
        $get("/api/goods/special_goods",{type:"is_recommend",num:6},function(ret){
            if(ret.status){
                $(".noAttend").hide();
                $(".dropDown p").hide();
                // console.log(ret.data);
                var str = "";
                for(var i = 0;i < ret.data.goods_list.length;i++){
                    // console.log(ret.data.goods_list[i])
                    str += template("goodslist",ret.data.goods_list[i]);
                }
                //筛选
                var sepcstr = "";
                //存放分类
                var sepc;
                for(i = 0; i < ret.data.filter.length;i++){
                    for(k in ret.data.filter[i][0]){
                        var specstrsecond = "";
                        // console.log(ret.data.filter[i][0][k])
                        for(j in ret.data.filter[i][0][k]){
                            // console.log(ret.data.filter[i][0][k][j])
                            specstrsecond += '<span data-key="'+ ret.data.filter[i][0][k][j].key +'-" data-id="'+ ret.data.filter[i][0][k][j].value +'_" class="spec-span">'+ ret.data.filter[i][0][k][j].name +'</span>'
                        }
                        sepcstr += '<div class="goods-attribute">\
                                        <p>'+ k +'</p>\
                                        <p>'+ specstrsecond +'</p>\
                                    </div>'
                    }
                }
                $(".specWrap").html(sepcstr);
                $("#goodsWrap").html(str);
            }else{
                $(".noAttend").show();
            }
        })
    }

    //排序请求
    function filter(filterstr,page){
        console.log(page);
        if(arguments[1] == undefined){
            var orderby = $(".screen span.active").attr("data-orderby");
            if($(".screen span.active").hasClass("up")){
                order = "asc";
            }else{
                order = "desc";
            }
            // console.log(order);
            $get("/api/goods/special_goods",{type:"is_recommend",num:6,order_by:orderby,order:order,screen:filterstr},function(ret){
                if(ret.status){
                    $(".noAttend").hide();
                    $(".dropDown p").hide();
                    // 搜索框添加数据
                    $("#searchInput").val(ret.data.category_name)
                    // console.log(ret.data);
                    var str = "";
                    for(var i = 0;i < ret.data.goods_list.length;i++){
                        // console.log(ret.data.goods_list[i])
                        str += template("goodslist",ret.data.goods_list[i]);
                    }
                    $("#goodsWrap").html(str);
                }else{
                    $("#goodsWrap").html(" ");
                    $(".noAttend").show();
                }
            })
        }else{
            var orderby = $(".screen span.active").attr("data-orderby");
            if($(".screen span.active").hasClass("up")){
                order = "asc";
            }else{
                order = "desc";
            }
            $get("/api/goods/special_goods",{type:"is_recommend",page:page,num:6,order_by:orderby,order:order,screen:filterstr},function(ret){
                if(ret.status){
                    $(".noAttend").hide();
                    $(".dropDown p").hide();
                    // 搜索框添加数据
                    $("#searchInput").val(ret.data.category_name)
                    // console.log(ret.data);
                    var str = "";
                    for(var i = 0;i < ret.data.goods_list.length;i++){
                        // console.log(ret.data.goods_list[i])
                        str += template("goodslist",ret.data.goods_list[i]);
                    }
                    $("#goodsWrap").append(str);
                }else{
                    $(".dropDown p").show();
                }
            })
        }
    }

    function research(){
        filterstr = "";
        page = 1;
        $(".goods-attribute").each(function(index,value){
            var filter = $(this).find(".spec-span.selected").attr("data-key");
            if(filter != undefined){
                filterstr += filter;
                $(this).find(".spec-span").each(function(index,value){
                    if($(this).hasClass("selected")){
                        filterstr += $(this).attr("data-id");
                        // console.log(filterstr)
                    }
                })
                filterstr = filterstr.substring(0,filterstr.length-1);
                filterstr += "@"
            }
        })
        filterstr = filterstr.substring(0,filterstr.length-1);
        // console.log(filterstr)
        filter(filterstr);
    }

    function research_screen($this){
        page = 1;
        $(".dropDown p").hide();
        $(".screen span.active").removeClass("active");
        $this.addClass("active");
        if($(".price-span").hasClass("active")){
            $this.find(".shengjiangxu").hide();
            if($this.hasClass("up")){
                $this.removeClass("up").addClass("down");
            }else if($this.hasClass("down")){
                $this.removeClass("down").addClass("up");
            }else{
                $this.addClass("up");
            }
        }else{
            $(".price-span").removeClass("up").removeClass("down").find(".shengjiangxu").show();
        }
        filter(filterstr);
    }
})
