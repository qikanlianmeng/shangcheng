/**
 * 物流详情
 */
define(function(require, exports, module){
    var common = require('common'),
        layer = require('layer'),
        template = require('template');
    //变量获取
    var $wuliuTitle = $('.wuliuTitle'),
        $wuliuNews = $('.wuliuNews'),
        $loading = $('.mui-loading');
    //物流信息
    $get('/api/order/get_send_info',{'order_id':order_id},function(data){
        if (data.status == 0) {
            $wuliuTitle.find('p span').text(data.result.number);
            var wuliuList = data.result.list;
            var str = '';
            for (var i = 0; i < wuliuList.length; i++) {
                str += template('wuliuTem',wuliuList[i]);
            }
            $wuliuNews.find('ul').html(str);
            $loading.hide();
            $wuliuTitle.show();
            $wuliuNews.show();
        }else{
            $loading.hide();
            $('.noAttend').show();
        }
    })
});
