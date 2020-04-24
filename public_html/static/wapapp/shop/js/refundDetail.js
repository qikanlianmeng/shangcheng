/**
 * 申请退款详情
 */
define(function(require, exports, module){
    var common = require('common'),
        layer = require('layer'),
        template = require('template');
    // 获取退款详情
    $get('/api/order/return_detail',{'id':id},function(data){
        if (data.status) {
            var str = template('refundTem',data.data);
            $('.refunddetail').prepend(str);
        }else{
            common.toast(data.msg);
        }
    });
    // 返回上一页
    pushHistory();
    window.addEventListener("popstate", function(e) {
        //根据自己的需求实现自己的功能
        var preUrl = document.referrer;
        if (preUrl.indexOf('/refund/rec_id') > 0 ) {
            window.history.go(-2);
        }else{
            window.history.go(-1);
        }
    }, false);
    function pushHistory() {
        var state = {
            title: "title",
            url: "#"
        };
        window.history.pushState(state, "title", "#");
    }
});
