/**
 * 订单管理
 */
define(function(require, exports, module) {
    var common = require('common'),
        layer = require('layer'),
        template = require('template');
    order_type == 1 ? $('.mui-title').html('样品订单') : order_type == 2 ? $('.mui-title').html('代售订单') : order_type == 3 ? $('.mui-title').html('订货订单') : '订单管理';
    //获取页面变量
    var $allOrder = $('#allOrder'),
        $waitPayment = $('#waitPayment'),
        $waitSendout = $('#waitSendout'),
        $waitTake = $('#waitTake'),
        $waitAssess = $('#waitAssess'),
        $noAttend = $('.noAttend'),
        $slider = $('#slider'),
        $dropdown = $('.dropdown');
    var par = {
        page: 1,
        group: order_type,
        type: 0
    }
    var loadOver = false,
        tag = true;

    // 初始化
    setTimeout(function() {
        $('.orderTit').find('.mui-control-item').removeClass('mui-active').eq(tab_num).addClass('mui-active');
        $('.mui-control-content').removeClass('mui-active');
        if (tab_num == 1) {
            par.type = 1;
            $waitPayment.parent().addClass('mui-active');
            $waitPayment.empty();
            getOrder($waitPayment, par);
        } else if (tab_num == 2) {
            par.type = 2;
            $waitSendout.parent().addClass('mui-active');
            $waitSendout.empty();
            getOrder($waitSendout, par);
        } else if (tab_num == 3) {
            par.type = 3;
            $waitTake.parent().addClass('mui-active');
            $waitTake.empty();
            getOrder($waitTake, par);
        } else if (tab_num == 4) {
            par.type = 4;
            $waitAssess.parent().addClass('mui-active');
            $waitAssess.empty();
            getOrder($waitAssess, par);
        } else {
            par.type = 0;
            $allOrder.parent().addClass('mui-active');
            $allOrder.empty();
            getOrder($allOrder, par);
        };
    }, 500);
    //切换
    var loading = '<div class="mui-loading"><div class="mui-spinner"></div></div>';
    $allOrder.html(loading);
    $slider.on('tap', '.mui-control-item', function() {
        var $this = $(this);
        $waitPayment.html(loading);
        $waitSendout.html(loading);
        $waitTake.html(loading);
        $waitAssess.html(loading);
        $dropdown.html('');
        par.page = 1;
        loadOver = false;
        tag = false;
        $('.noAttend').hide();
        var obj; //存储当前tab页id
        switch ($this.index()) {
            case 0:
                obj = $allOrder;
                par.type = 0;
                execFun(obj);
                return;
            case 1:
                obj = $waitPayment;
                par.type = 1;
                execFun(obj);
                return;
            case 2:
                obj = $waitSendout;
                par.type = 2;
                execFun(obj);
                return;
            case 3:
                obj = $waitTake;
                par.type = 3;
                execFun(obj);
                return;
            case 4:
                obj = $waitAssess;
                par.type = 4;
                execFun(obj);
                return;
        }
    })

    function execFun(obj) {
        if (obj.has('.mui-loading')) {
            setTimeout(function() {
                obj.empty();
                getOrder(obj);
            }, 500);
        }
    }

    function update(obj) {
        setTimeout(function() {
            obj.empty();
            getOrder(obj);
        }, 500);
    }
    //获取订单信息
    function getOrder(obj) {
        $get('/api2/order/order_list', par, function(data) {
            if (data.status == 1) {
                var temp = data.data;
                var str = '';
                for (var i = 0, len = temp.length; i < len; i++) {
                    var g = temp[i].order_group;
                    temp[i].order_type = par.group;
                    temp[i].o_g = temp[i].order_group;
                    if (!order_type) {
                        temp[i].order_group = g == 1 ? '样品订单' : g == 2 ? '代售订单' : g == 3 ? '订货订单' : '';
                    };
                    if (g == 2) {
                        var t = temp[i].settle_time,
                            s = temp[i].is_settle;
                        temp[i].is_settle = s == 0 ? '代售中' : '已完成';
                    }
                    var order_detail = temp[i].goods_list;
                    var comment1 = 0;
                    var comment2 = 0;
                    for (var j = 0, len1 = order_detail.length; j < len1; j++) {
                        if (order_detail[j].is_comment == 1) { //0是不能评论，1是能评论，2是可以追加评论
                            comment1++;
                        }
                        if (order_detail[j].is_comment == 2) {
                            comment2++;
                        }
                    }
                    if (comment1 != 0) {
                        temp[i].is_comment = 1;
                    } else if (comment2 != 0) {
                        temp[i].is_comment = 2;
                    } else {
                        temp[i].is_comment = 0;
                    }
                    str += template('orderListTem', temp[i]);
                }
                obj.append(str);
                if (temp.length < 5 && par.page == 1) {
                    $dropdown.html('');
                } else {
                    $dropdown.html('下拉加载');
                }
                tag = true;
            } else {
                if (par.page == 1) {
                    $('.noAttend').show();
                } else {
                    $dropdown.html('没有更多数据了');
                }
                loadOver = true;
                tag = true;
            }
        })
    }
    //上拉加载
    var $doc = $(document)
    $doc.scroll(function() {
        setTimeout(function() {
            if ($doc.scrollTop() + $(window).height() >= $doc.height() && !loadOver && tag) {
                par.page++;
                $dropdown.html('数据加载中...');
                switch ($slider.find('.orderTit .mui-active').index()) {
                    case 0:
                        par.type = 0;
                        getOrder($allOrder);
                        break;
                    case 1:
                        par.type = 1;
                        getOrder($waitPayment);
                        break;
                    case 2:
                        par.type = 2;
                        getOrder($waitSendout);
                        break;
                    case 3:
                        par.type = 3;
                        getOrder($waitTake);
                        break;
                    case 4:
                        par.type = 4;
                        getOrder($waitAssess);
                        break;
                }
            }
        }, 1)
    });
    //确认收货
    $('.mui-content').on('click', '.subGet', function() {
            var $this = $(this),
                $li = $this.parents('li'),
                order_id = $li.attr('data-id');
            $get('/api2/order/delivery_confirm', { 'id': order_id }, function(data) {
                if (data.status == 1) {
                    var str = '<a href="/wapapp/order/comment/order_id/' + order_id + '" class="mui-btn mui-btn-outlined mui-btn-outlined-main">评价</a>';
                    $li.find('.opera').html(str);
                    common.toast('确认收货成功');
                } else {
                    common.toast('确认收货失败，请重试');
                }
            });
        })
        //取消订单
    $('.mui-content').on('click', '.cancelOrder', function() {
            var $this = $(this),
                $li = $this.parents('li'),
                order_id = $li.attr('data-id'),
                obj = $li.parent();
            $get('/api2/order/cancel_order', { 'id': order_id }, function(data) {
                if (data.status == 1) {
                    if ($(obj).attr('id') == 'waitPayment') {
                        $li.remove();
                        if ($(obj).find('li').length == 0) {
                            $noAttend.show();
                        }
                    } else {
                        $li.find('h3').html('交易关闭');
                        var str = '<a href="/wapapp/order/order_detail/order_id/' + order_id + '" class="mui-btn mui-btn-outlined">查看订单</a><a href="javascript:;" class="mui-btn mui-btn-outlined to_del">删除订单</a>';
                        $li.find('.opera').html(str);
                    }
                    common.toast('订单取消成功');
                } else {
                    common.toast(data.msg);
                }
            });
        })
        //删除订单
    $('.mui-content').on('click', '.to_del', function() {
        var $this = $(this),
            $li = $this.parents('li'),
            order_id = $li.attr('data-id'),
            obj = $li.parent();
        $get('/api2/order/del_order', { 'id': order_id }, function(data) {
            if (data.status == 1) {
                $li.remove();
                if ($(obj).find('li').length == 0) {
                    $noAttend.show();
                }
                common.toast('订单删除成功');
            } else {
                common.toast(data.msg);
            }
        });
    })
});