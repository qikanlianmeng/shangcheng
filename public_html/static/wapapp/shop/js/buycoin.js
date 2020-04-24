/**
 * 充值
 */
define(function(require, exports, module){
    var common = require('common'),
        layer = require('layer');

    $(".recharge:first").addClass("selected");
    var t=$("#other").val();
    $("#other").val("").focus().val(t);
    if(parseInt(IS_WEIXIN)){
        $('.alipay-pay').hide();
    }
    //选择money
    $('.rechargelist').on('click', '.recharge', function (e) {
        e.stopPropagation();
        var $this = $(this);
        if($this.hasClass('other')){
            $this.find('p').hide();
            $this.find('div').show();
            $('#other').focus();
        }else{
            $('.other').find('p').show();
            $('.other').find('div').hide();
        }
        $this.addClass('selected');
        $this.siblings().removeClass('selected');
        var b = $this.attr('data-money');
    });
    $('#submit').on('click',function(){
        var $obj = $('.recharge.selected');
        if($obj.hasClass('other')){
            var other=$.trim($("#other").val());
            var s_other=parseFloat(other).toFixed(2);
            if(!other){
                common.open('其他金额不能为空',2);
                return false;
            }else if(s_other<0.01){
                common.open('其他金额必须大于0.01',2);
                return false;
            }
            $obj.attr('data-money',s_other);
        }
        var bmoney = Number($(".recharge.selected").attr('data-money'));
        var btype = $('.paymethod').find('input:radio:checked').val();
        $post('/api2/user/wap_recharge',{'money':bmoney,'pay_type':btype},function(data){
            console.log(JSON.stringify(data))
            if (btype == 'alipay') {
                // if (data.code) {
                //     window.location.href = data.data;
                // } else {
                //     common.open(data.msg,2);
                // }
                var aliPay = api.require('aliPay');
                aliPay.payOrder({
                    orderInfo: data.data
                }, function(ret, err) {
                    // alert(ret.code);
                    if (ret.code==9000) {
                        window.location.href = '/wapapp/usercenter/usercenter.html';
                      } else {
                          // alert(err.code);
                          common.open("支付失败请稍后再试",2);
                      }
                });
            } else if (btype == 'wxpay') {
                if (data.code) {
                    //微信JS调用
                    var wxPay = api.require('wxPay');
                    wxPay.payOrder({
                        apiKey: data.data.appid,
                        orderId: data.data.prepayid,
                        mchId: data.data.partnerid,
                        nonceStr: data.data.noncestr,
                        timeStamp: data.data.timestamp,
                        package: data.data.package,
                        sign: data.data.sign
                    }, function(ret, err) {
                        if (ret.status) {
                            // alert('支付成功')
                            //支付成功
                            window.location.href = '/wapapp/usercenter/usercenter.html';
                        } else {
                            common.open("支付失败请稍后再试",2);
                        }
                    });
                } else {
                    common.open(data.msg,2);
                }
            }
        })
    });
});
