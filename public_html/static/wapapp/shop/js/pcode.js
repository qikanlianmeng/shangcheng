define(function(require, exports, module) {
    var common = require('common'),
        layer = require('layer'),
        template = require('template');
    //获取页面变量
    var $allOrder = $('#allOrder'),
        $waitPayment = $('#waitPayment'),
        $waitSendout = $('#waitSendout'),
        $noAttend = $('.noAttend'),
        $slider = $('#slider'),
        $dropdown = $('.dropdown');
    var par = {
        page: 1,
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
    //获取订单信息
    function getOrder(obj) {

        if (par.page == 1) {
            $('.noAttend').show();
        } else {
            $dropdown.html('没有更多数据了');
        }
        // $get('/api/order/order_list', par, function(data) {
        //     if (data.status) {
        //         var temp = data.data;
        //     } else {
        //         if (par.page == 1) {
        //             $('.noAttend').show();
        //         } else {
        //             $dropdown.html('没有更多数据了');
        //         }
        //         loadOver = true;
        //         tag = true;
        //     }
        // })
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
                }
            }
        }, 1)
    });
});