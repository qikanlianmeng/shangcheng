define(function(require,exports,module){
    var c = require("common");
    var template = require("template")
    getData();
    function getData(){
        $get("api/user/integrallog",{},function(ret){
            if(ret.code){
                var str = '';
                for (var i = 0; i < ret.data.length; i++) {
                    str += template("listTem", ret.data[i]);
                }
                $('#list').html(str);
            }else{
                //无数据时
                $(".noAttend").show();
            }
        })
    }
})
