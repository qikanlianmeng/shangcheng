/**
 * 申请退款列表
 */
define(function(require, exports, module){
    var common = require('common'),
        layer = require('layer'),
        template = require('template');
    //变量获取
    var $refundlist = $('.refundlist');
    // 获取退款列表
    $get('/api/order/return_list',function(data){
        if(data.status){
            if(data.data.length){
                var str = '',obj = data.data;
                for(var i = 0,len = obj.length;i<len;i++){
                    obj.refund_money = parseFloat(obj.refund_money);
                    obj.refund_deposit = parseFloat(obj.refund_deposit);
                    str += template('refundListTem',obj[i]);
                }
                $refundlist.html(str);
            }else{
                $refundlist.hide();
                $('.noAttend').show();
            }
        }else{
            common.toast(data.msg);
        }
    })
    //取消退款
    $refundlist.on('click','.cancel',function(){
        var $this = $(this),
            id = $this.attr('data-id'),
            len = $('.refundlist').find('li').length;
            $li = $this.parents('li');
        $get('/api/order/cancel_return',{'id':id},function(data){
            if(data.status){
                $li.find('h4').html('用户取消退款');
                if(len == 1){
                    $('.noAttend').show();
                }
                $li.remove();
                common.toast(data.msg);
            }else{
                common.toast(data.msg);
            }
        })
    })
});
