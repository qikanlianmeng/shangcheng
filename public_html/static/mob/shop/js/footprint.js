define(function(require, exports, module){
    require("jquery");
    var common = require("common"),
        layer = require("layer"),
        template = require("template");
    var page = 1;
    getData();
    function getData(){
        $get("/api/goods/footprint",{page:page,num:10},function(ret){
            if(ret.status){
                console.log(ret);
                var str = '';
                var _data = {};
                for (var key in ret.data) {
                    _data.time = key;
                    var tmp = ret.data[key].list;
                    _data.list = tmp;
                    str += template("footprint", _data);
                }
                $(".footprintlist").append(str)
            }else if(ret.status == 0){
                $(".delete").hide();
                $(".noAttend").show();
            }
        })
    }
    //清空
    $(".delete").on("click",function(){
        layer.open({
            title:"",
            content:"确定要清空足迹吗？",
            btn: ['确定', '取消'],
            yes: function(index, layero){
                $get("/api/goods/clear_footprint",function(ret){
                    if(ret.status){
                        $(".footprintlist").html("");
                        $(".noAttend").show();
                        $(".delete").hide();
                    }
                    console.log(ret)
                })
                layer.close(index); //如果设定了yes回调，需进行手工关闭
             }
        })
    })
    //上拉加载
    $(window).scroll(function() {
      if ($(document).scrollTop() >= $(document).height() - $(window).height()) {
          page++;
          $get("/api/goods/footprint",{page:page,num:10},function(ret){
              if(ret.status){
                  console.log(ret);
                  var str = '';
                  var _data = {};
                  for (var key in ret.data) {
                      _data.time = key;
                      var tmp = ret.data[key].list;
                      _data.list = tmp;
                      str += template("footprint", _data);
                  }
                  $(".footprintlist").append(str)
              }else if(ret.status == 0){
                  $(".dropDown p").show();
              }
          })
      }
    })
})
