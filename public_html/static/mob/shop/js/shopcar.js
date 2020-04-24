/**
 * 购物车
 */
define(function(require, exports, module){
    //微信中刷新页面
    window.onpageshow = function() {
        if(parseInt(IS_WEIXIN)){
            var needRefresh = sessionStorage.getItem("need-refresh");
            if(needRefresh){
                sessionStorage.removeItem("need-refresh");
                window.location.reload();
            }
        }
    };
    var common = require('common'),
        layer = require('layer'),
        template = require('template');

    // 变量获取
    var $shopcaropera = $('.oprBtn'),
        $shopcarKong = $('.shopcarKong'),
        $shopcarBottom = $('.shopcarBottom'),
        $shopcarList = $('#shopcarList'),
        $shopcarListOld = $('#shopcarListOld'),
        $allSelectIcon = $shopcarBottom.find('.allSelect .iconfont');
    //存用户选择的规格信息
    var specInfo = "";
    //存储不同规格的价格、图片、库存等信息
    var specData = {};
    //购物车列表数据
    $get('/api/goods/get_cart_list',{},function(data){
        if(data.data){
            $shopcarKong.hide();
            $shopcaropera.show();
            $shopcarBottom.show();
            var cart_list = data.data.cartList;
            var str = '', strOld = '';
            data.data.selected_num = 0;//储存选中的数量
            data.data.sum = 0;//储存有货的商品的数量
            for (var i = 0; i < cart_list.length; i++) {
                if (cart_list[i].store_count == 0) {
                    strOld += template('shopCell',cart_list[i]);
                }else{
                    if(cart_list[i].selected){
                        data.data.selected_num ++;
                    }
                    data.data.sum ++;
                    str += template('shopCell',cart_list[i]);
                }
            }
            $shopcarList.html(str).show();
            $shopcarListOld.html(strOld).show();
            if($shopcarList.find('li').length == 0){
                $shopcarBottom.hide();
            }
            if(data.data.selected_num == data.data.sum){
                $allSelectIcon.removeClass('icon-yuan').addClass('icon-chenggong color-main');
            }
            getPrice();
            getgoodsNum();
        }else{
            $shopcarKong.show();
        }
    })
    //获取购物车总数量
     $get("/api/goods/get_cart_goods_num",function(data){
        if(data.status){
            if(data.data != 0){
                $('.mui-title span').text(data.data);
            }
        }else{
            // common.toast(data.msg)
        }
    });
    // 点击编辑
    $('.oprBtn').on('click', function() {
        var $this = $(this);
        if ($this.text() == '编辑') {
            $shopcarList.find('.orinum,.prices').hide();
            $shopcarList.find('.list-opr,.oprnum').show();
            $shopcarListOld.find('.list-opr').show();
            // $shopcarListOld.find('.b-name').hide();
            $shopcarList.find('.b-spec').addClass('b-opr').attr('href','#spec');
            $this.text('完成');
        }else{
            $shopcarList.find('.orinum,.prices').show();
            $shopcarList.find('.b-spec').removeClass('b-opr').removeAttr('href');
            $shopcarList.find('.list-opr,.oprnum').hide();
            $shopcarListOld.find('.list-opr').hide();
            // $shopcarListOld.find('.b-name').show();
            $this.text('编辑');
        }
    });
    //重新选取规格
    $shopcarList.on('click','.b-opr',function(){
        var $this = $(this),
            $parentLi = $this.parents('li'),
            id = $parentLi.attr('data-gid'),//商品id
            goodsid = $parentLi.attr('goodsid'),//购物车商品id
            spec_key = $parentLi.attr('data-spec'),//规格key
            num = $parentLi.find('.oprnum .num').text();
        $('#spec').attr('data-id',id);
        $('#spec').attr('goodsid',goodsid);
        $get('/api/goods/goods_spec_for_cart',{'id':id},function(data){
            if(data.status){
                var obj = data.data;
                obj.ori_spec_key = spec_key;
                var specArr = spec_key.split('_');
                $('.specTop').html(template('specTopTem',obj));
                //选择分类
                var sepcstr = "";
                for(i in obj.sepc_arr){
                    var specstrsecond = "";
                    for(j in obj.sepc_arr[i]){
                        specstrsecond += '<span data-id="'+ obj.sepc_arr[i][j].id +'_" class="spec-span">'+ obj.sepc_arr[i][j].item +'</span>'
                    }
                    sepcstr += '<div class="goods-attribute">\
                                    <p class="m-b-sm">'+ i +'</p>\
                                    <p>'+ specstrsecond +'</p>\
                                </div>'
                }
                $(".specWrap").html(sepcstr);
                var $goodsAttr = $('.specWrap .goods-attribute');
                //初始化选中规格
                for(var i = 0,len = $goodsAttr.length; i<len;i++){
                    var $span = $goodsAttr.eq(i).find('span');
                    for(var j = 0,len1 = $span.length;j<len1;j++){
                        if($span.eq(j).attr('data-id') == specArr[i]+'_'){
                            $span.eq(j).addClass('selected');
                        }
                    }
                }
                //初始化数量
                mui('.mui-numbox').numbox().setValue(num)
                //缓存规格spec_data数据
                specInfo = spec_key;
                specData = obj.spec_data;
            }else{
                common.toast(data.msg);
            }
        })
    })
    //选择规格项目
    $(".spec").on("click",'.goods-attribute span',function(){
        $(this).siblings("span").removeClass("selected");
        $(this).addClass("selected");
        specChange();
    })
    //改变规格和价钱
    function specChange(){
        specInfo = "";
        $(".spec-span.selected").each(function(index,value){
            specInfo += $(value).attr("data-id");
        })
        specInfo = specInfo.substr(0,specInfo.length-1);
        for(i in specData){
            if(i == specInfo){
                $(".spec-img").attr('src',specData[specInfo].img);
                $(".price-info").text(specData[specInfo].price);
                $(".specCount").text(specData[specInfo].store_count);
                $(".specValue").text(specData[specInfo].key_value);
            }
        }
    }
    //选择规格确定
    $('.spec').on('click','.spec-confim',function(){
        var $this = $(this),
            $parentSpec = $this.parents('.spec'),
            goodsid = $parentSpec.attr('goodsid');
        var num = mui(".mui-numbox").numbox().getValue();
        $get('/api/goods/change_cart_spec',{'id':goodsid,'spec_key':specInfo,'num':num},function(data){
            if(data.status){
                var $obj = $('#goods_'+goodsid)
                $obj.find('.oprnum .num').html(num);
                $obj.find('.b-spec').html(specData[specInfo].key_name);
                mui('.spec').popover('hide');
            }else{
                common.toast(data.msg);
            }
        })
    })
    // 全选
    $shopcarBottom.on('click','.allSelect',function(){
        var $this = $(this);
        if ($this.find('.iconfont').hasClass('icon-chenggong')) {
            $get('/api/goods/change_cart_selected_all',{'selected':0},function(data){
                if(data.status){
                    $this.find('i').addClass('icon-yuan').removeClass('icon-chenggong color-main');
                    $shopcarList.find('.iconfont').addClass('icon-yuan').removeClass('icon-chenggong color-main');
                    getPrice();
                    getgoodsNum();
                }else{
                    common.toast(data.msg);
                }
            })
        }else{
            $get('/api/goods/change_cart_selected_all',{'selected':1},function(data){
                if(data.status){
                    $this.find('i').removeClass('icon-yuan').addClass('icon-chenggong color-main');
                    $shopcarList.find('.iconfont').removeClass('icon-yuan').addClass('icon-chenggong color-main');
                    getPrice();
                    getgoodsNum();
                }else{
                    common.toast(data.msg);
                }
            })
        }
    })
    // 商品的选中与取消
    $shopcarList.on('click', '.iconfont', function() {
        var $this = $(this),
            $parentLi = $this.parents('li'),
            id = $parentLi.attr('goodsid');//购物车商品id
        $get('/api/goods/change_cart_selected',{'id':id},function(data){
            if(data.status){
                if ($this.hasClass('icon-chenggong')) {
                    $this.removeClass('icon-chenggong color-main').addClass('icon-yuan');
                }else{
                    $this.addClass('icon-chenggong color-main').removeClass('icon-yuan');
                }
                if ($allSelectIcon.hasClass('icon-chenggong')) {
                    $allSelectIcon.addClass('icon-yuan').removeClass('icon-chenggong color-main');
                }
                getPrice();
                getgoodsNum();
            }else{
                common.toast(data.msg);
            }
        });
        return false;
    });
    // 商品加减删除的时候需要考虑到价格的变化
    //商品加
    $('#shopcarList').on('click', '.addNum', function() {
        var $this = $(this),
            $parentLi = $this.parents('li'),
            goodsid = $parentLi.attr('goodsid');
        if($this.hasClass('color-999')){
            common.toast('商品已达到上限，库存不足了');
        }else{
            $get('/api/goods/change_cart_num',{'id':goodsid},function(data){
                if(data.status){
                    var _num = $this.siblings('.num').text();
                    _num++;
                    $this.siblings('.num').text(_num);
                    $this.parents('.priceNum').find('.orinum span').text(_num);
                    if (_num >= parseInt($this.attr('goods_storemount'))) {
                        $this.addClass('color-999');
                    }
                    $this.siblings('.num').text(_num);
                    if ($parentLi.find('.iconfont').hasClass('icon-chenggong')) {
                        getPrice();
                    }
                    if (_num > 1) {
                        $this.siblings('.subNum').removeClass('color-999');
                    }
                    getgoodsNum();
                    getshopcargoodsNum();
                }else{
                    common.toast(data.msg);
                }
            })
        }
    });

    //商品减
    $('#shopcarList').on('click', '.subNum', function() {
        var $this = $(this),
            $parentLi = $this.parents('li'),
            goodsid = $parentLi.attr('goodsid');
        if ($this.hasClass('color-999')) {
            common.toast('就剩一件了，不可以再少了~');
        }else{
            $get('/api/goods/change_cart_num',{'id':goodsid,'mode':'-'},function(data){
                if(data.status){
                    var _num = $this.siblings('.num').text();
                    _num--;
                    $this.parents('.priceNum').find('.orinum span').text(_num);
                    if (_num <= 1) {
                        $this.addClass('color-999');
                    }
                    $this.siblings('.num').text(_num);
                    if ($parentLi.find('.iconfont').hasClass('icon-chenggong')) {
                        getPrice();
                    }
                    if (_num < parseInt($this.siblings('.addNum').attr('goods_storemount'))) {
                        $this.siblings('.addNum').removeClass('color-999');
                    }
                    getgoodsNum();
                    getshopcargoodsNum();
                }else{
                    common.toast(data.msg);
                }
            })
        }
    });
    // 计算价格函数
    function getPrice(){
        var yuanPrice = 0,str='';
        $shopcarList.find('.mui-media').each(function(){
            var $this = $(this);
            var _num = $this.find('.priceNum .num').text();
            if ($this.find('.iconfont').hasClass('icon-chenggong')) {
                yuanPrice += parseFloat($this.find('.yuan').text())*_num;
            }
        });
        yuanPrice = yuanPrice.toFixed(2);
        if(!yuanPrice){
            str = '￥0.00';
        }else{
            str = '￥' + yuanPrice;
        }
        $shopcarBottom.find('.totalcost span').html(str);
    }
    // 计算选中商品的数量
    function getgoodsNum(){
        var _num = 0;
        $shopcarList.find('.mui-media').each(function(){
            if ($(this).find('.iconfont').hasClass('icon-chenggong')) {
                var li_num = parseInt($(this).find('.orinum span').text());
                _num += li_num;
            }
        })
        $shopcarBottom.find('.allNum').text(_num);
    }
    // 计算购物车商品的数量
    function getshopcargoodsNum(){
        var _num = 0;
        $shopcarList.find('.mui-media').each(function(){
            if ($(this).find('.iconfont')) {
                var li_num = parseInt($(this).find('.orinum span').text());
                _num += li_num;
            }
        })
        $('.mui-title span').text(_num);
    }
    // 商品删除
    $('.shopcarList').on('click', '.del', function() {
        var $this = $(this),
            $parentLi = $this.parents('li'),
            goodsid = $parentLi.attr('goodsid');
        var goods_num = parseInt($parentLi.find('.num').text());

        var ind = common.confirm('你确定要删除该商品吗？',function(){
            $('.layermbox').remove();
            $get('/api/goods/remove_cart',{'id':goodsid},function(data){
                if (data.status) {
                    $parentLi.remove();
                    var count = parseInt($('.mui-title span').text());
                    $('.mui-title span').text(count-goods_num);
                    if ($parentLi.find('.iconfont').hasClass('icon-chenggong')) {
                        if ($allSelectIcon.hasClass('icon-chenggong')) {
                            $allSelectIcon.removeClass('icon-chenggong color-main').addClass('icon-yuan');
                        }
                        getPrice();
                        getgoodsNum();
                        getshopcargoodsNum();
                    }
                    if ($('.mui-title span').text() == '0') {
                        $shopcarKong.show();
                        $shopcaropera.hide();
                        $shopcarBottom.hide();
                        $shopcarList.hide();
                        $shopcarListOld.hide();
                    }
                    common.toast(data.msg);
                }else{
                    common.toast(data.msg);
                }
                layer.close(ind);
            })
        },function(){});
    });
    // 收藏||取消收藏
    $('.shopcarList').on('click', '.atten', function() {
        var $this = $(this),
            id = $this.parents('li').attr('goodsid');
        $get('api/goods/collection',{'id':id},function(data){
            if(data.status){
                if ($this.text() == '收藏') {
                    $this.text('已收藏').addClass('color-main');
                }else{
                    $this.text('收藏').removeClass('color-main');
                }
            }else{
                common.login();
            }
        });
    });
    //去结算
    $('.account').on('click','.goCal',function(){
        if(UID < 1){
            common.login() ;
            return false;
        }
        window.location.href = '/wap/order/firm_order';
    });
    //联系客服
    $('#contact').on('click',function(){
        if(UID < 1){
            common.login() ;
            return false;
        }
    });
    $shopcarBottom.on('click', 'button', function() {
        window.location.href="{:url('order/firm_order')}";
    });
});
