/**
 * 管理收货地址页面
 */
define(function(require, exports, module) {
    //微信中刷新页面
    window.onpageshow = function() {
        if (parseInt(IS_WEIXIN)) {
            var needRefresh = sessionStorage.getItem("need-refresh");
            if (needRefresh) {
                sessionStorage.removeItem("need-refresh");
                window.location.reload();
            }
        }
    };
    var common = require('common'),
        layer = require('layer'),
        template = require('template');

    // 变量获取
    var $addressList = $('#addressList');

    //设置默认收货地址
    $("body").on('click', '.mui-radio', function() {
        var $this = $(this);
        var id = $this.closest('.address-list').attr('data-id');
        $get('/api/address/set_normal', { id: id }, function(data) {
            if (data.status) {
                $this.closest(".address-list").siblings().removeClass('cur');
                $this.closest(".address-list").addClass('cur');
                //common.toast('设置默认地址成功');
            } else {
                $this.find('input').removeAttr('checked');
                common.toast(data.msg);
            }
        })
    });

    //收货地址列表
    $get('/api2/address/get_addressList', {}, function(data) {
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

    //删除地址
    $("body").on('click', '.delAdd', function() {
        var $this = $(this);
        var length = $('.address-list').length;
        var isSet = $this.closest('.address-opr').find('input[name="default"]').is(':checked');
        if (!isSet) {
            common.confirm('确定要删除该收货地址么？',
                function() {
                    var $delObj = $this.closest('.address-list');
                    var id = $delObj.attr('data-id');
                    $get('/api/address/del_address', { id: id }, function(data) {
                        if (data.status) {
                            common.toast(data.msg);
                            if (length == 1) {
                                $('.noAttend').show();
                            }
                            $delObj.remove();
                        } else {
                            common.toast(data.msg);
                        }
                    })
                },
                function() {}, ['确认', '取消']);
        } else {
            common.toast('默认地址不能删除！');
        }
    });
});