/**
 * 申请退款
 */
define(function(require, exports, module){
    var common = require('common'),
        layer = require('layer'),
        template = require('template');
    // 变量获取
    let $applyForRefund = $('.apply-refund'),
        $chooseType = $applyForRefund.find('.chooseType'),
        $reason = $applyForRefund.find('.reason'),
        $content = $('#content');
    $content.val('');

    let picker = new mui.PopPicker();
    let reasonRefund1 = [{'text':'不喜欢，不想要'},
                        {'text':'货物破损已拒签'},
                        {'text':'空包裹'},
                        {'text':'未按约定时间发货'},
                        {'text':'快递/物流一直未送到'},
                        {'text':'快递/物流无跟踪记录'}];
    let reasonRefund2 = [{'text':'质量问题'},
                        {'text':'商品描述不符'},
                        {'text':'少件(含缺少配件)'},
                        {'text':'卖家发错货'},
                        {'text':'收到商品是有破损/污渍/变形'},
                        {'text':'未按约定时间发货'},
                        {'text':'发票问题'}]
    // 选择退款类型
    $chooseType.on('click', 'p', function() {
        let $this = $(this),
            $chooseTypeP = $chooseType.find('p');

        if ($this.find('.iconfont').hasClass('icon-yuan')) {
            for (let i = 0; i < $chooseTypeP.length; i++) {
                $chooseTypeP.find('.iconfont').removeClass('icon-chenggong color-main').addClass('icon-yuan');
                $this.find('.iconfont').addClass('icon-chenggong color-main');
            }
        }
        $reason.find('input').val('');
        switch($this.index()){
            case 0:
                picker.setData(reasonRefund1);
                break;
            case 1:
                picker.setData(reasonRefund2);
                break;
        }
    });
    //选择退货原因
    $reason.click(function(){
        if ($chooseType.find('.icon-chenggong').length == 0) {
            common.toast('请先选择退款类型');
            return false;
        }
        picker.show(function (selectItems) {
            $reason.find('input').val(selectItems[0].text)
         })
    });
    // 输入退款说明
    $content.change(function() {
        if ($(this).length > 200) {
            common.toast('请输入小于200字的文字说明');
        }
    });

    // 提交申请
    $('.refundBtn').click(function() {
        var _type = '',_reason = '',describe;
        var picArr = [];
        var data = {};

        if ($chooseType.find('.icon-chenggong').length == 0) {
            common.toast('请选择退款类型');
            return false;
        }else{
            _type = $chooseType.find('.icon-chenggong').parents('p').attr('data-value');
        }
        if ($reason.find('input').val() == '') {
            common.toast('请选择退款原因');
            return false;
        }else{
            _reason = $reason.find('input').val();
        }
        describe = $content.val();
        data = {'order_id':order_id,'refund_type':_type,'reason':_reason,'describe':describe};
        var $pic = $('.img-list').find('li.pic');
        if ($pic.length > 0) {
            $pic.each(function(i){
                picArr.push( $(this).attr('data-src') );
            });
            data.imgs = picArr;
        }
        // console.log(data);
        $get('/api/order/return_goods',data,function(data1){
            if (data1.status) {
                var refundId = data1.data;
                layer.open({
                    content:data1.msg,
                    time:1,
                    end:function(){
                        window.location.href = '/wap/usercenter/refund_detail/return_id/'+refundId;
                    }
                });
            }else{
                common.toast(data1.msg);
            }
        })
    });
});
