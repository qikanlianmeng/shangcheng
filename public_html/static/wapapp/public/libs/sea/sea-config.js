seajs.config({
    vars: {
        "version": "@@version"
    },
    base: '/static/wapapp',
    paths: {},
    alias: {
        "jquery": "public/libs/jquery-3.2.1.min.js",
        "template": "public/libs/template.js",
        "layer": "public/libs/layer/need/layer.js",
        "echo": 'public/libs/echo.js',
        "common": "public/js/common2.js",
        "common_l": "public/js/common_login.js",
        "commonUrl": "public/js/commonUrl2.js",
        "swiper": "public/libs/swiper-3.4.2.min.js",
        "shopcommon": "public/js/shopcommon.js",
        "cookie": "public/js/cookie.js",
        "ajaxupload": "public/js/ajaxfileupload.js",
        'weixin': 'public/js/weixin',
        'vconsole': 'public/js/vconsole.min.js',
        'qrcode': 'public/js/qrcode.js'
    }
});