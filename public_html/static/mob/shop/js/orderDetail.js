/**
 * 订单详情
 */
define(function(require, exports, module){
    var common = require('common'),
        layer = require('layer'),
        template = require('template');

    //订单信息
    $get('/api/order/order_detail',{'order_id':order_id},function(data){
        if(data.status){
            var obj = data.data;
            var order_detail = obj.goods_list;
            var comment1 = 0;
            var comment2 = 0;
            for (var j = 0 , len1 = order_detail.length; j < len1; j++) {
                if (order_detail[j].is_comment == 1) { //0是不能评论，1是能评论，2是可以追加评论
                    comment1++;
                }
                if(order_detail[j].is_comment == 2){
                    comment2++;
                }
            }
            if (comment1 != 0 ) {
                obj.is_comment = 1;
            }else if (comment2 != 0 ) {
                obj.is_comment = 2;
            }else{
                obj.is_comment = 0;
            }
            $('.wrap').html(template('orderDetailTem',obj));
            $get('/api/order/get_send_info',{'order_id':order_id},function(dataCode){
                var $logistics = $('.logistics');
                if (dataCode.status == '0') {
                    $logistics.find('p.color-main').text(dataCode.result.list[0].status);
                    $logistics.find('p.color-999').text(dataCode.result.list[0].time);
                }else{
                    $logistics.find('p.color-main').text('暂无物流信息');
                    var timestamp=new Date();
                    $logistics.find('p.color-999').text(getLocalTime(timestamp));
                }
            })
        }else{
            // common.toast(data.msg);
            console.log(data.msg);
        }
    });
    //确认收货
    $('.wrap').on('click','#subGet',function(){
        $get('/api/order/delivery_confirm',{'order_id':order_id},function(data){
            if(data.status){
                common.toast('确认收货成功');
                if(parseInt(IS_WEIXIN)){
                    var url = window.location.href;
                    var goUrl = window.location.href=url+"/"+Math.random();
                    setTimeout('goUrl',1000);
                }else{
                    setTimeout('window.location.reload(true)',1000);
                }
            }else{
                common.toast('确认收货失败，请重试');
            }
        });
    });

    //取消订单
    $('.wrap').on('click','.cancelOrder',function(){
        $get('/api/order/cancel_order',{'order_id':order_id},function(data){
            if(data.status){
                $('#status').html('已取消');
                common.toast('订单取消成功');
                $('#checkout,.cancelOrder').remove();
            }else{
                common.toast(data.msg);
            }
        });
    })
    function getLocalTime(now) {
        var year = now.getFullYear();
        var month = now.getMonth() + 1;
        var date = now.getDate();
        var hour = now.getHours();
        var minute = now.getMinutes();
        if (hour < 10) {
            hour = '0' + hour;
        }
        if (minute < 10) {
            minute = '0' + minute;
        }
        return year + "-" + month + "-" + date + "   " + hour + ":" + minute;
    }
});
