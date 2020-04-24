/**
 * 确认订单
 */
define(function (require, exports, module) {
    var common = require('common'),
        layer = require('layer'),
        template = require('template');

    // 变量获取
    var $hasAddress = $('.has-address'), //地址
        $good_info = $('#good_info'); //商品
    var browserRule = /^.*((iPhone)|(iPad)|(Safari))+.*$/;
    if (browserRule.test(navigator.userAgent)) {
        window.onpageshow = function (event) {
            if (event.persisted) {
                window.location.reload()
            }
        };
    }
    //自选折扣率
    mui.init();
    mui.ready(function () {

        // 收货地址
        if (address_id == '') {
            $get('/api2/address/get_addressList', {}, function (data) {
                if (data.status) {
                    for (var i of data.data) {
                        if (i.is_default == 1) {
                            address_id = i.address_id;
                            var str = template('firmAddress', i);
                            $hasAddress.html(str);
                            getOrder();
                            break;
                        }
                    }

                } else {
                    $('.no-address').show();
                    $('.has-address').hide();
                    common.confirm('您还未填写收货地址，马上去填写', function () {
                        window.location.href = '/wapapp/usercenter/add_address/opr/add/address_id/new?id=1';
                    }, function () {

                    }, ['确定'])
                }
            });
        } else {
            $get('/api2/address/get_address', { id: address_id }, function (data) {
                if (data.status) {
                    var str = template('firmAddress', data.data);
                    $hasAddress.html(str);
                    getOrder();
                }
            })
        }

    });
    // 支付总额，羊币抵扣金额，余额抵扣金额,羊币抵扣数
    var totalAmount, scoreMoney, userMoney, scoreNum;
    var $scoreInput = $('input[name="score"]'),
        $xuebiInput = $('input[name="xuebi"]');
    var zk=0;//折扣率
    // 获取待提交订单信息
    function getOrder() {
        $get('/api2/order/confirm_order', {}, function (data) {
            if (data.status == 1) {
                var obj = data.data;
                var g_g=obj.goods_list[0].goods_group;
                userMoney = obj.user_money;
                totalAmount = obj.goods_price.order_amount;
                scoreMoney = obj.exchange_integral_money;
                scoreNum = obj.exchange_integral;
                obj.dy_discount=$('#dy_discount').val();
                obj.g_g=g_g;
              
              
                // console.log($('#dy_discount').val());
                $good_info.html(template('good_info_tem', obj));
                $('#payFor').html(totalAmount);
                $('#hasScore').html(scoreNum);
                $('#scoreMoney').html(scoreMoney);
                $('#hasMoney').html(userMoney);
                $('#costMoney').html(userMoney);
                if (scoreMoney == 0) {
                    $scoreInput.attr('disabled', 'disabled');
                }
                if (userMoney == 0) {
                    $xuebiInput.attr('disabled', 'disabled');
                }
                console.log(g_g);
                if(g_g==2&&obj.dy_discount==1){
                    var userPicker = new mui.PopPicker();
                    userPicker.setData([{
                        value: '0',
                        text: '0(不进行拼代营)'
                    }, {
                        value: '1',
                        text: '1折'
                    }, {
                        value: '2',
                        text: '2折'
                    }, {
                        value: '3',
                        text: '3折'
                    }, {
                        value: '4',
                        text: '4折'
                    }, {
                        value: '5',
                        text: '5折'
                    }, {
                        value: '6',
                        text: '6折'
                    }, {
                        value: '7',
                        text: '7折'
                    }, {
                        value: '8',
                        text: '8折'
                    }, {
                        value: '9',
                        text: '9折'
                    }]);
                    var showUserPicker = document.getElementById('showUserPicker');
                    showUserPicker.addEventListener('click', function (event) {
                        userPicker.show(function (items) {
                            $('#showUserPicker').val(items[0].text);
                             zk=items[0].value;
                            if(zk>0){
                                $('#payFor').html(totalAmount*zk*0.1);
                                $('#costMoney').html(totalAmount*zk*0.1);
                            }else{
                                $('#payFor').html(totalAmount);
                                $('#costMoney').html(totalAmount);
                            }
                            
                            // 返回 false 可以阻止选择框的关闭
                            // return false;
                        });
                    }, false);
                }

            } else {
                common.toast(data.msg);
                $('.noContent').show();
                $('#good_info').hide();
            }
        })
    }

    //是否选中羊币抵扣，是否选中余额抵扣，计算之后的总价
    var tagScore = 0,
        tagXuebi = 0,
        calMoney = 0;
    //羊币抵扣checkbox状态改变
    $scoreInput.change(function () {
        var $this = $(this);
        if ($this.is(':checked')) {
            tagScore = 1;
        } else {
            tagScore = 0;
        }
        calMoneyFun();
    });
    //余额抵扣checkbox状态改变
    $xuebiInput.change(function () {
        var $this = $(this);
        if ($this.is(':checked')) {
            tagXuebi = 1;
        } else {
            tagXuebi = 0;
        }
        calMoneyFun();
    });
    //计算总价
    function calMoneyFun() {
        if (tagScore && tagXuebi) {
            calMoney = totalAmount - scoreMoney - userMoney;
        } else if (tagScore && !tagXuebi) {
            calMoney = totalAmount - scoreMoney;
        } else if (!tagScore && tagXuebi) {
            calMoney = totalAmount - userMoney;
        } else {
            calMoney = totalAmount;
        }
        if (calMoney <= 0) {
            calMoney = 0;
        }
        $('#payFor').html(calMoney);
    }
    //提交订单
    $('#submitOrder').on('click', function () {
        var content = $('#content').val();
        var pay_points = 0,
            user_money = 0;
        if (tagScore) {
            pay_points = scoreNum;
        }
        if (tagXuebi) {
            user_money = userMoney;
        }
        var par = {
            address_id: address_id,
            pay_points: pay_points,
            user_money: user_money,
            content: content,
            discount:zk
        }
        console.log(par);
        $get('/api2/order/add_order', par, function (data) {
            if (data.status==1) {
                var obj = data.data;
                if (obj.order_amount > 0) {
                    window.location.href = '/wapapp/order/pay_order/order_id/' + obj.order_id;
                } else {
                    window.location.href = '/wapapp/order/pay_success/order_id/' + obj.order_id;
                }
            } else {
                common.toast('订单提交失败，请重试');
            }
        })
    });
});