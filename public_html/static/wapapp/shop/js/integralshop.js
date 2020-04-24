define(function(require,exports,module){
    document.documentElement.style.fontSize = document.documentElement.clientWidth / 720*10 + 'px';
    require("jquery");
    var common = require("common");
    var template = require("template");
    var layer = require("layer");
    var page = 1;
    getData();
    getUserinfo();

    $(".userImg").on("click",function(){
        if(UID < 1){
            common.login();
        }else{
            window.location.href="/wapapp/usercenter/usercenter";
        }
    })

    function getData(){
        $get("/api/goods/special_goods",{type:"is_deduction",page:page,num:10},function(ret){
            if(ret.status){
                var str = "";
                for(var i = 0;i < ret.data.goods_list.length;i++){
                    var leftListHeight = $(".RecommendList").height();
                    var rightListHeight = $(".RecommendList_second").height();
                    str = template("goodslist_box",ret.data.goods_list[i]);
                    // console.log(leftListHeight,rightListHeight)
                    if(rightListHeight >= leftListHeight){
                        $(".RecommendList").append(str);
                    }else{
                        $(".RecommendList_second").append(str);
                    }
                }
            }
        })
    }
    function getUserinfo(){
        $get("api/user/getuserinfo",function(ret){
            if(ret.code){
                $(".logined").show();
                $(".notlogin").hide();
                $(".integralNum e").text(ret.data.integral);
                if(ret.data.head_img == null || ret.data.head_img == "" ){

                }else{
                    $(".userImg img").attr("src",ret.data.head_img);
                }
                if(ret.data.sex == null){
                    $("input[name=sex]").val("");
                    $("#sex").text("未知").css("color","#666")
                }else{
                    $("input[name=sex]").val(ret.data.sex);
                    $("#sex").text(ret.data.sex)
                }
            }
        })
    }
    //上拉加载
    $(window).scroll(function() {
      if ($(document).scrollTop() >= $(document).height() - $(window).height()) {
          page++;
          $get("/api/goods/special_goods",{type:"is_deduction",page:page,num:10},function(ret){
              if(ret.status){
                  // console.log(ret.data);
                  var str = "";
                  for(var i = 0;i < ret.data.goods_list.length;i++){
                      var leftListHeight = $(".RecommendList").height();
                      var rightListHeight = $(".RecommendList_second").height();
                      str = template("goodslist_box",ret.data.goods_list[i]);
                      if(rightListHeight >= leftListHeight){
                          $(".RecommendList").append(str);
                      }else{
                          $(".RecommendList_second").append(str);
                      }
                  }
              }else if(ret.status == 0){
                  $(".dropDown p").show();
              }
          })
      }
    })
})
