define(function(require, exports, module){
    var common = require("common"),
        layer = require("layer");

    getData();
    function getData(){
        $get("api/user/getuserinfo",function(ret){
            if(ret.code){
                // console.log(ret.data.head_img)
                $("#nickname").val(ret.data.nickname);
                if(ret.data.head_img == null){
                }else{
                    $("#imgShow").attr("src","/wap/public"+ret.data.head_img)
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
    //选择性别
    $(".choose-sex").on("click",function(){
        chooseSex();
    })
    function chooseSex(){
        layer.open({
            title:"选择性别",
            btn: ['确定', '取消'],
            content:'<div class="blockWrap" id="chooseSex">\
                <div class="mui-input-row mui-radio mui-left">\
                    <label>男</label>\
                    <input name="sexchoose" type="radio" value="男">\
                </div>\
                <div class="mui-input-row mui-radio mui-left">\
                    <label>女</label>\
                    <input name="sexchoose" type="radio" value="女">\
                </div>\
            </div>',
            yes: function(index, layero){
                console.log($("input[name=sexchoose]:checked").val())
                var choosesex = $("input[name=sexchoose]:checked").val();
                if(choosesex != undefined){
                    $("input[name=sex]").val(choosesex);
                    $("#sex").text(choosesex).css("color","#000")
                }
                layer.close(index); //如果设定了yes回调，需进行手工关闭
             }
        })
    }
})
