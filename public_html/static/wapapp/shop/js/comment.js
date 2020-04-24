/**
 * 商品评论
 */
define(function(require, exports, module) {
    var common = require('common'),
        layer = require('layer'),
        template = require('template');
    //变量获取
    var $comment = $('#comment');
    //订单信息
    $get('/api2/order/order_detail', { 'id': order_id }, function(data) {
            if (data.status) {
                var str = '';
                var goods = data.data.goods_list;
                for (var i = 0, len = goods.length; i < len; i++) {
                    str += template('commentTem', goods[i]);
                }
                $comment.html(str);
            }
        })
        // 评星
    $comment.on('click', '.starBox span', function() {
        var $this = $(this),
            $starBox = $this.parents('.starBox');

        switch ($this.index()) {
            case 0:
                $starBox.html('<span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-shoucang color-999"></span>\
                <span class="iconfont icon-shoucang color-999"></span>\
                <span class="iconfont icon-shoucang color-999"></span>\
                <span class="iconfont icon-shoucang color-999"></span>');
                $starBox.siblings('.starText').text('很差').attr('data-rank', 1);
                break;
            case 1:
                $starBox.html('<span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-shoucang color-999"></span>\
                <span class="iconfont icon-shoucang color-999"></span>\
                <span class="iconfont icon-shoucang color-999"></span>');
                $starBox.siblings('.starText').text('差').attr('data-rank', 2);
                break;
            case 2:
                $starBox.html('<span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-shoucang color-999"></span>\
                <span class="iconfont icon-shoucang color-999"></span>');
                $starBox.siblings('.starText').text('一般').attr('data-rank', 3);
                break;
            case 3:
                $starBox.html('<span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-shoucang color-999"></span>');
                $starBox.siblings('.starText').text('好').attr('data-rank', 4);
                break;
            case 4:
                $starBox.html('<span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-sc1"></span>\
                <span class="iconfont icon-sc1"></span>');
                $starBox.siblings('.starText').text('非常好').attr('data-rank', 5);
                break;
        };
    });
    // 评价
    $comment.on('click', '.comment-bottom .commentBtn', function() {
        var $this = $(this),
            $opera = $this.parents('.comment-bottom'),
            $li = $this.parents('li.mui-media');

        var _type = $opera.attr('data-type'),
            _goodsid = $li.attr('data-goodsid'),
            _content = '',
            is_anony = 0,
            pic_url = [],
            data = {};

        if ($li.find('.content textarea').val() == '') {
            common.toast('请输入评价内容');
            return false;
        } else {
            _content = $li.find('.content textarea').val();
        }
        if ($opera.find('.mui-checkbox')) {
            if ($opera.find('input').is(':checked')) {
                is_anony = 1;
            }
        }
        data = {
            'goods_id': _goodsid,
            'order_id': order_id,
            'content': _content,
            'type': _type,
            'is_anony': is_anony
        };

        if ($li.find('.starText')) {
            data.rank = $li.find('.starText').attr('data-rank');
        }
        if ($li.find('.img-list li.pic').length != 0) {
            $li.find('.img-list li.pic').each(function() {
                pic_url.push($(this).attr('data-src'));
            });
            data.img = pic_url;
        }
        // console.log(data);
        $get('/api2/goods/comment', data, function(data1) {
            if (data1.status) {
                layer.open({
                    content: data1.msg,
                    time: 1,
                    end: function() {
                        window.history.back();
                    }
                });
            } else {
                common.toast(data1.msg);
            }
        })
    });
});