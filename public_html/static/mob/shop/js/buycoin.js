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
        var bmoney = $(".recharge.selected").attr('data-money');
        var btype = $('.paymethod').find('input:radio:checked').val();
        $post('/api/user/wap_recharge',{'money':bmoney,'pay_type':btype},function(data){
            if (btype == 'alipay') {
                if (data.code) {
                    window.location.href = data.data;
                } else {
                    common.open(data.msg,2);
                }
            } else if (btype == 'wxpay') {
                if (data.code) {
                    //微信JS调用
                    ret = data.data;
                    WeixinJSBridge.invoke(
                        'getBrandWCPayRequest', {
                            "appId":ret.appId,     //公众号名称，由商户传入
                            "timeStamp":ret.timeStamp, //时间戳，自1970年以来的秒数
                            "nonceStr":ret.nonceStr, //随机串
                            "package":ret.package,
                            "signType":ret.signType,         //微信签名方式：
                            "paySign":ret.paySign //微信签名
                        },
                        function(res){
                            if(res.err_msg == "get_brand_wcpay_request:ok" ){
                                // 使用以上方式判断前端返回,微信团队郑重提示：res.err_msg将在用户支付成功后返回    ok，但并不保证它绝对可靠。
                                window.location.href = '/wap/usercenter/usercenter';
                            } else if(res.err_msg == "get_brand_wcpay_request:fail" ){
                                common.open("支付失败请稍后再试",2);
                            }
                        }
                    );
                } else {
                    common.open(data.msg,2);
                }
            }
        })
    });
});
