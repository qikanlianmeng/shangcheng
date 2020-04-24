define(function(require,exports,module){
    require("jquery");
    // require("vconsole");
    var common = require("common");
    var template = require("template");
    // var vconsole = new VConsole();
    getData();
    function getData(){
        $get("/api/user/msg_list",{page:1},function(ret){
            if(ret.code){
                // console.log(ret.info.noread)
                if(ret.info.list.length == 0){
                    $(".message-info").text("暂时没有消息");
                    $(".messageTime").text("");
                }else{
                    $(".message-info").text(ret.info.list[0].title);
                    $(".messageTime").text(ret.info.list[0].send_time)
                }
                if(ret.info.noread != 0){
                    $(".message-num").text(ret.info.noread).show();
                }
            }
        })
    }
})
