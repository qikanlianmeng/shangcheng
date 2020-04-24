define(function(require, exports, module) {
    var common = require('common'),
        layer = require('layer'),
        template = require('template');
    //登录后获取数据
    $get('/api/user/getuserinfo', {}, function(data) {

        if (data.code == 1) {
            var obj = data.data;
            $('.head_img').attr('src', obj.head_img);
            $('.integral').html(obj.integral);
            $('#son_dl').html(obj.son_dl);
            $('#total_income').html(obj.total_income);
            $('#wait_cash').html(obj.wait_cash);
            $('#already_cash').html(obj.already_cash);
            $('#money').html(obj.money);
            $('#rank_name').html(obj.rank_name);
            $('.account').html(obj.account);
            $('#dl_income').html(obj.dl_income);
            $('#dy_income').html(obj.dy_income);
        } else {
            common.toast(data.msg);
            setTimeout(() => {
                window.location.href = "/wapapp/index/login.html";
            }, 500);
        }
    })
})