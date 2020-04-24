/**
 * 支付订单
 */

define(function (require, exports, module) {
  var common = require('common'),
    layer = require('layer'),
    template = require('template');

  sessionStorage.setItem("need-refresh", true);

  var $pay_method = $('.pay-method');
  //判断该显示哪种支付方式
  if (parseInt(IS_WEIXIN)) {
    // $pay_method.find('.pay-alipay').hide();
    $pay_method.find('.pay-wxpay input').attr('checked', 'checked');
  } else {
    // $pay_method.find('.pay-wxpay').hide();
    $pay_method.find('.pay-alipay input').attr('checked', 'checked');
  }
  // 订单详情
  $get('/api2/order/order_detail', {'id': order_id}, function (data) {
    if (data.status) {
      var obj = data.data;
      $('.order-view').html(template('orderViewTem', obj));
      $('.sum_money').find('#payFor').html(obj.order_amount);
    } else {
      common.toast(data.msg);
    }
  });

  //去支付
  $('#submitOrder').on('click', function () {
    var pay_type = $pay_method.find('input:radio:checked').val();

    $get('/api2/order/pay_order', {'id': order_id, 'pay_type': pay_type}, function (data) {
      if (data.code == 1) {

        console.log(JSON.stringify(data));
        if (pay_type == 'alipay') {
          var aliPay = api.require('aliPay');
          aliPay.payOrder({
            orderInfo: data.data
          }, function (ret, err) {
            console.log(JSON.stringify(ret));
            // 支付成功
            if (ret.code == 9000) {
              window.location.href = '/wapapp/order/pay_success/order_id/' + order_id;
            } else {

              console.log(JSON.stringify(err));
              window.location.href = '/wapapp/order/pay_fail/order_id/' + order_id;
            }
          });

        } else if (pay_type == 'wxpay') {

          if (data.code == 1) {
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
            }, function (ret, err) {
              console.log(JSON.stringify(err));
              if (ret.status) {
                // alert('支付成功')
                //支付成功
                window.location.href = '/wapapp/order/pay_success/order_id/' + order_id;
              } else {
                window.location.href = '/wapapp/order/pay_fail/order_id/' + order_id;
              }
            });
          } else {

            common.open(data.msg, 2);

          }
        }
      } else {
        $.ajax({
          type: "POST",
          url: "/api2/order/pay_order",
          data: {
            'id': order_id,
            'pay_type': pay_type
          },
          dataType: "json",
          success: function (json) {
            //layer.close(index);//关闭加载
            if (json.code == 2) {
              console.log('微信支付的结果')


              WeixinJSBridge.invoke(
                'getBrandWCPayRequest', {
                  "appId": json.data.appId,     //公众号名称，由商户传入
                  "timeStamp": json.data.timeStamp,         //时间戳，自1970年以来的秒数
                  "nonceStr": json.data.nonceStr, //随机串
                  "package": json.data.package,
                  "signType": json.data.signType,         //微信签名方式：
                  "paySign": json.data.paySign //微信签名
                },
                function (res) {
                  if (res.err_msg == "get_brand_wcpay_request:ok") {

                    location.href = '/wapapp/order/pay_success/order_id/' + order_id;
//                                if (getQueryString("state") == 1) {
//                                    location.href = 'paysuccessipad.html?order_id=' + json.orderid;
//                                } else {
//                                    location.href = 'paysuccess.html?order_id=' + json.orderid;
//                                }
                  } else {
                    location.href = '/wapapp/order/pay_fail/order_id/' + order_id;
                  }

                }
              );

            }
            else {
              layer.msg(json.msg);
            }
          }
        });
      }
    });

  });


  // $('#submitOrder').on('click',function(){
  //     var pay_type = $pay_method.find('input:radio:checked').val();
  //     $get('/api/order/pay_order',{'order_id':order_id,'pay_type':pay_type},function(data){
  //         if (pay_type == 'alipay') {
  //             if (data.code) {
  //                 window.location.href = data.data;
  //             } else {
  //                 window.location.href = '/wapapp/order/pay_fail/order_id/' + order_id;
  //             }
  //         } else if (pay_type == 'wxpay') {
  //             if (data.code) {
  //                 //微信JS调用
  //                 ret = data.data;
  //                 WeixinJSBridge.invoke(
  //                     'getBrandWCPayRequest', {
  //                         "appId":ret.appId,     //公众号名称，由商户传入
  //                         "timeStamp":ret.timeStamp, //时间戳，自1970年以来的秒数
  //                         "nonceStr":ret.nonceStr, //随机串
  //                         "package":ret.package,
  //                         "signType":ret.signType,         //微信签名方式：
  //                         "paySign":ret.paySign //微信签名
  //                     },
  //                     function(res){
  //                         if(res.err_msg == "get_brand_wcpay_request:ok" ){
  //                             // 用以上方式判断前端返回,微信团队郑重提示：res.err_msg将在用户支付成功后返回    ok，但并不保证它绝对可靠。
  //                             window.location.href = '/wapapp/order/pay_success/order_id/' + order_id;
  //                         } else if(res.err_msg == "get_brand_wcpay_request:fail" ){
  //                             // alert(JSON.stringify(res))
  //                             common.open("支付失败请稍后再试",2);
  //                         }else if(res.err_msg == "get_brand_wcpay_request:cancel" ){
  //                             common.open("取消支付",2);
  //                             // window.location.href = '/wap/order/pay_fail/order_id/' + order_id;
  //                         }
  //                     }
  //                 );
  //             } else {
  //                 common.open(data.msg,2);
  //             }
  //         }
  //     });
  // });


  //查看订单
  $('#checkOrder').on('click', function (data) {
    window.location.href = '/wapapp/order/order_detail/order_id/' + order_id;
  });
  //返回首页
  $('#findGoods').on('click', function (data) {
    window.location.href = '/wapapp/index/index';
  });
  // 返回上一页
  // pushHistory();
  // window.addEventListener("popstate", function(e) {
  //     //根据自己的需求实现自己的功能
  //     var preUrl = document.referrer;
  //     if (preUrl.indexOf('firm_order') > 0) {
  //         window.history.go(-2);
  //     } else if (preUrl.indexOf('pay_order') > 0) {
  //         window.location.href = '/wapapp/order/order_detail/order_id/' + order_id;
  //     } else {
  //         window.history.go(-1);
  //     }
  // }, false);

  // function pushHistory() {
  //     var state = {
  //         title: "title",
  //         url: "#"
  //     };
  //     window.history.pushState(state, "title", "#");
  // }
});