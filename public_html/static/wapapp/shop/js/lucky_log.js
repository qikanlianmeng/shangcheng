define(function(require, exports, module) {
    var common = require('common'),
        layer = require('layer'),
        template = require('template');
    //获取页面变量
    var $allOrder = $('#allOrder'),
        $waitPayment = $('#waitPayment'),
        $noAttend = $('.noAttend'),
        $slider = $('#slider'),
        $dropdown = $('.dropdown');
    var par = {
        page: 1,
        status: 0
    }
    var loadOver = false,
        tag = true;

    // 初始化
    setTimeout(function() {
        $('.orderTit').find('.mui-control-item').removeClass('mui-active').eq(tab_num).addClass('mui-active');
        $('.mui-control-content').removeClass('mui-active');
        if (tab_num == 1) {
            par.status = 1;
            $waitPayment.parent().addClass('mui-active');
            $waitPayment.empty();
            getOrder($waitPayment, par);
        } else {
            par.status = 0;
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
        $allOrder.html(loading);
        $dropdown.html('');
        par.page = 1;
        loadOver = false;
        tag = false;
        $('.noAttend').hide();
        var obj; //存储当前tab页id
        switch ($this.index()) {
            case 0:
                obj = $allOrder;
                par.status = 0;
                execFun(obj);
                return;
            case 1:
                obj = $waitPayment;
                par.status = 1;
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
            // $('.noAttend').show();
        } else {
            $dropdown.html('没有更多数据了');
        }
        $get('/api2/prize/log', par, function(data) {
            if (data.status) {
                var temp = data.list;
                if (temp.length == 0) {
                    $('.noAttend').show();
                } else {
                    var str = "",
                        c = '';
                    for (var i of temp) {
                        i.desc=i.status>0?i.desc:'';
                        i.status==0?(i.status='未中奖',c='color-666'):i.status==1?(i.status='已中奖未发放',c='color-orange'):i.status==2?(i.status='中奖已发放',c='color-lv'):'';
                        i.type=i.type==1?'余额抽奖':i.type==2?'赠送抽奖机会':'';
                        i.create_time = common.getLocalTime(new Date(i.create_time * 1000));
                        str += '<li><p><span class="color-black"><span>' + i.title + '</span><span class="color-666">('+i.type+')</span></span><span class="fr price ' + c + '" style="font-weight:600;">' + i.status + '</span></p><p><span>' + i.create_time + '</span><span class="fr color-main">' + i.desc + '</span></p></li>'
                    }
                    obj.html(str);
                }
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
                        par.status = 0;
                        getOrder($allOrder);
                        break;
                    case 1:
                        par.status = 1;
                        getOrder($waitPayment);
                        break;
                }
            }
        }, 1)
    });
});