/**
 * 选择地址页面
 */
define(function(require, exports, module) {
    var common = require('common'),
        layer = require('layer'),
        template = require('template');

    // 变量获取
    var $addressList = $('#addressList');
    var isIOS = !!navigator.userAgent.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/);
    if (isIOS) {
        var needRefresh = sessionStorage.getItem("add_back");
        if (needRefresh) {
            sessionStorage.removeItem("add_back");
            window.location.reload();
        }
    }
    //收货地址列表
    $get('/api/address/get_addressList', {}, function(data) {
        if (data.status) {
            var str = '';
            for (var i = 0; i < data.data.length; i++) {
                str += template('addressItem', data.data[i]);
            }
            $addressList.html(str);
        } else {
            $('.noAttend').show();
        }
    });
    //点击列表项
    $('body').on('click', '.select-address', function() {
        var $this = $(this);
        var address_id = $this.attr('data-id');
        window.location.href = '/wapapp/order/firm_order/address_id/' + address_id;
    });
});