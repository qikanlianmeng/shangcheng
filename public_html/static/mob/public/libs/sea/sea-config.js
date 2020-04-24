seajs.config({
    vars: {
        "version": "@@version"
    },
    base: '/static/mob',
    paths: {
    },
    alias: {
        "jquery":"public/libs/jquery-3.2.1.min.js",
        "template":"public/libs/template.js",
        "layer":"public/libs/layer/layer.js",
        "echo":'public/libs/echo.js',
        "common":"public/js/common.js",
		"commonUrl":"public/js/commonUrl.js",
        "swiper":"public/libs/swiper-3.4.2.min.js",
        "shopcommon":"public/js/shopcommon.js",
        "cookie":"public/js/cookie.js",
        "ajaxupload":"public/js/ajaxfileupload.js",
        'weixin':'public/js/weixin',
        'vconsole':'public/js/vconsole.min.js'
    }
});
