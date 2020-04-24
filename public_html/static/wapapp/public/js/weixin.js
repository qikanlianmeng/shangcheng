define(function(require,exports,module){
    var com = require('common');
    var weixin = require('http://res.wx.qq.com/open/js/jweixin-1.4.0.js');
    exports.init= function () {
        //获取签名
        $get('/api/weixin/getjssign',{url:window.location.href}, function (data) {
            //配置微信
            weixin.config({
                debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来。
                appId: data.appId, // 必填，公众号的唯一标识
                timestamp:data.timestamp , // 必填，生成签名的时间戳
                nonceStr: data.nonceStr, // 必填，生成签名的随机串
                signature:data.signature,// 必填，签名，见附录1
                jsApiList: ['updateAppMessageShareData','updateTimelineShareData'] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
            });
        });
    };
    /**
     * 分享到朋友圈
     * @param imgUrl  图片URL地址
     * @param sendFriendLink 连接地址
     * @param tContent 分享的标题
     * @param suc   分享成功的回调
     * @constructor
     */
    exports.ShareFriendZone= function (imgUrl,tContent,sendFriendLink,suc) {
        imgUrl=imgUrl;
        sendFriendLink=sendFriendLink||window.location.href;
        tContent=tContent||$("title").val();
        suc=suc|| function () { };
        weixin.ready(function(){
            weixin.updateTimelineShareData({
                title: tContent, // 分享标题
                link:sendFriendLink, // 分享链接
                imgUrl:imgUrl, // 分享图标
                success: function () {
                    //成功回调
                    suc();
                },
            });
        });
    };
    /**
     * 分享到微信好友
     * @param tTitle  标题
     * @param tContent  内容
     * @param sendFriendLink 发送连接
     * @param imgUrl  分享图片
     * @param suc   成功回调
     * @constructor
     */
    exports.ShareWeixin= function (imgUrl,tTitle,tContent,sendFriendLink,suc) {
        tTitle=tTitle||$("title").val();
        imgUrl=imgUrl;
        sendFriendLink=sendFriendLink||window.location.href;
        tContent=tContent;
        suc=suc|| function () { };
        weixin.ready(function(){
            weixin.updateAppMessageShareData({
                title: tTitle, // 分享标题
                desc: tContent, // 分享描述
                link: sendFriendLink, // 分享链接
                imgUrl: imgUrl, // 分享图标
                success: function () {
                    // 用户确认分享后执行的回调函数
                    suc();
                },
            });
          

        });

    }
});
