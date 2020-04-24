/**
 * 确认订单
 */
define(function(require, exports, module){
    var common = require('common'),
        layer = require('layer'),
        template = require('template');

    // 变量获取
    var $hasAddress = $('.has-address'),//地址
        $good_info = $('#good_info');//商品
    // 收货地址
    if(address_id == ''){
        $get('/api/address/get_normal_address',{},function(data){
            if(data.status==1){
                var str = template('firmAddress',data.data);
                address_id = data.data.address_id;
                $hasAddress.html(str);
                getOrder();
            }else{
                $('.no-address').show();
                $('.has-address').hide();
                common.confirm('您还未填写收货地址，马上去填写',function(){
                    window.location.href='/wap/usercenter/add_address/opr/add/address_id/new?id=1';
                },function(){

                },['确定'])
            }
        });
    }else{
        $get('/api/address/get_address',{id:address_id},function(data){
            if(data.status){
                var str = template('firmAddress',data.data);
                $hasAddress.html(str);
                getOrder();
            }
        })
    }
    // 支付总额，羊币抵扣金额，余额抵扣金额,羊币抵扣数
    var totalAmount, scoreMoney, userMoney, scoreNum;
    var $scoreInput = $('input[name="score"]'),
        $xuebiInput = $('input[name="xuebi"]');
    // 获取待提交订单信息
    function getOrder(){
        $get('/api/order/confirm_order',{},function(data){
            if(data.status){
                var obj = data.data;
                userMoney = obj.user_money;
                totalAmount = obj.goods_price.order_amount;
                scoreMoney = obj.exchange_integral_money;
                scoreNum = obj.exchange_integral;

                $good_info.html(template('good_info_tem',obj));
                $('#payFor').html(totalAmount);
                $('#hasScore').html(scoreNum);
                $('#scoreMoney').html(scoreMoney);
                $('#hasMoney').html(userMoney);
                $('#costMoney').html(userMoney);
                if(scoreMoney==0){
                    $scoreInput.attr('disabled','disabled');
                }
                if(userMoney==0){
                    $xuebiInput.attr('disabled','disabled');
                }
            }else{
                $('.noContent').show();
                $('#good_info').hide();
            }
        })
    }

    //是否选中羊币抵扣，是否选中余额抵扣，计算之后的总价
    var tagScore = 0, tagXuebi = 0,calMoney = 0;
    //羊币抵扣checkbox状态改变
    $scoreInput.change(function(){
        var $this = $(this);
        if($this.is(':checked')){
            tagScore = 1;
        }else{
            tagScore = 0;
        }
        calMoneyFun();
    });
    //余额抵扣checkbox状态改变
    $xuebiInput.change(function(){
        var $this = $(this);
        if($this.is(':checked')){
            tagXuebi = 1;
        }else{
            tagXuebi = 0;
        }
        calMoneyFun();
    });
    //计算总价
    function calMoneyFun(){
        if(tagScore && tagXuebi){
            calMoney = totalAmount - scoreMoney - userMoney;
        }else if(tagScore && !tagXuebi){
            calMoney = totalAmount - scoreMoney;
        }else if(!tagScore && tagXuebi){
            calMoney = totalAmount - userMoney;
        }else{
            calMoney = totalAmount;
        }
        if(calMoney <= 0){
            calMoney = 0;
        }
        $('#payFor').html(calMoney);
    }
    //提交订单
    $('#submitOrder').on('click',function(){
        var content = $('#content').val();
        var pay_points = 0, user_money = 0;
        if(tagScore){
            pay_points = scoreNum;
        }
        if(tagXuebi){
            user_money = userMoney;
        }
        var par = {
            address_id: address_id,
            pay_points: pay_points,
            user_money: user_money,
            content: content
        }
        $get('/api/order/add_order',par,function(data){
            if(data.status){
                var obj = data.data;
                if(obj.order_amount > 0){
                    window.location.href = '/wap/order/pay_order/order_id/' + obj.order_id;
                }else{
                    window.location.href = '/wap/order/pay_success/order_id/' + obj.order_id;
                }
            }else{
                common.toast('订单提交失败，请重试');
            }
        })
    });
});
