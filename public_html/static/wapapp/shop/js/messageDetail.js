define(function(require,exports,module){
    require("jquery");
    var common = require("common");
    var template = require("template");

    getData();
    function getData(){
        $get("/api/user/read_msg",{id:id},function(ret){
            if(ret.code){
                console.log(ret);
                $(".message-title").html(ret.info.title);
                $(".message-content").html(ret.info.content);
            }
        })
    }
})
