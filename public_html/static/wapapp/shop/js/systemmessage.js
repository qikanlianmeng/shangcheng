define(function(require,exports,module){
    require("jquery");
    var common = require("common");
    var template = require("template");
    var page = 1;
    getData();
    function getData(){
        $get("/api/user/msg_list",{page:page},function(ret){
            if(ret.code){
                if(ret.info.list.length == 0){
                    $(".noAttend").show();
                }else{
                    var str = "";
                    for(i = 0;i < ret.info.list.length;i++){
                        str += template("message",ret.info.list[i])
                    }
                    $(".messageList").append(str);
                }
            }
        })
    }
    //上拉加载
    $(window).scroll(function() {
      if ($(document).scrollTop() >= $(document).height() - $(window).height()) {
          page++;
          $get("/api/user/msg_list",{page:page},function(ret){
              if(ret.code){
                  if(ret.info.list.length == 0){
                      $(".dropDown p").show();
                  }
                  var str = "";
                  for(i = 0;i < ret.info.list.length;i++){
                      str += template("message",ret.info.list[i])
                  }
                  $(".messageList").append(str);
              }else{
                  $(".dropDown p").show();
              }
          })
      }
    })
    $(document).on("tap",".messageDetail",function(){
        var $this = $(this);
        $this.toggleClass("active");
        var id = $(this).attr("data-id");
        $get("/api/user/read_msg",{id:id},function(ret){
            if(ret.code){
                $this.find(".noread").hide();
            }
        })
    })
})
