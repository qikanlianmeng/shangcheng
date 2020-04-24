define(function(require, exports, module) {
    require("jquery");
    require("cookie");
    var commonUrl = require("commonUrl");
    //将jq暴漏到全局
    window.$ = $;
    exports.initPage = function() {
        mui('body').on('tap', 'a', function() {
            var id = this.getAttribute('id');
            if (id == 'a11' || id == 'a12' || id == 'a13' || id == 'a14') {} else {
                location.href = this.getAttribute('href');
            }
        });
    };
    exports.pageScroll = function() {
        (function($) {
            $('.mui-scroll-wrapper').scroll({
                bounce: false,
                indicators: true //是否显示滚动条
            });
        })(mui);
    };
    exports.downMenu = function() {
        var headH = document.getElementById('header').offsetHeight;
        var bodyH = document.body.scrollHeight;
        document.getElementById('downMain').style.height = bodyH - headH + 2 + 'px';
    };
    /**
     * 吐丝
     * @param str string 提示的内容
     */
    exports.toast = function(str) {
        layer.open({
            content: str,
            time: 1
        });
    };
    /**
     * layer提示
     **/
    exports.open = function(con, time) {
        layer.open({
            content: con,
            time: time
        });
    };
    /**
     * 询问对话框
     * @param title string 提示内容
     * @param func1 function 第二个按钮的回调函数
     * @param func2 function 第一个个按钮的回调函数
     * @param btn array 按钮文字
     */
    exports.confirm = function(title, func1, func2, btn) {
            btn = btn || ['确认', '取消'];
            func2 = func2 || function() {};
            layer.open({
                content: title,
                btn: btn,
                shadeClose: false,
                yes: func1,
                no: function() {
                    func2();
                    layer.close();
                }
            });
        }
        //返回顶部
    exports.gotoTop = function(min_height) {
            var gotoTop_html = '<div id="gotoTop"><a href="javascript:;"><i class="mui-icon mui-icon-arrowup mui-block"></i>顶部</a> </div>';
            $("#page").append(gotoTop_html);
            mui('#gotoTop').on('tap', 'a', function() {
                mui('.mui-content.mui-scroll-wrapper').scroll().scrollTo(0, 0, 700);
                $('#gotoTop').hide();
            });
            min_height ? min_height = min_height : min_height = $(window).height();
            $(window).scroll(function() {
                var s = $('div#scroll_main').css('transform');
                s = s.match(/\d+(?=\))/)[0];
                if (s > min_height) {
                    $("#gotoTop").fadeIn(100);
                } else {
                    $("#gotoTop").fadeOut(200);
                }
            })
        }
        //推荐四屏切换，首屏显示其他隐藏
    exports.hideScreen = function() {
        echo.init({
            offset: 0,
            throttle: 0
        });
        var slideScreen = $('.slideScreen');
        slideScreen.each(function() {
            $(this).children('li:gt(3)').each(function() {
                $(this).addClass('cur');
            });
        });
        echo.render();
    };

    //切换动作
    exports.screen = function(num) {
        var _num = num ? num : 4;
        mui('.rec-top').on('tap', '.changes,.mui-icon-loop', function() {
            echo.init({
                offset: 0,
                throttle: 0
            });
            var ul = $(this).parent().parent().next();
            var totalScreenNum = Math.ceil(ul.children().size() / _num);
            var screen = parseInt(ul.attr('data-screen'));
            ul.children().addClass('cur');
            if (screen == (totalScreenNum - 1)) {
                var nextScreen = 0;
            } else {
                var nextScreen = screen + 1;
            }
            var num = (nextScreen) * _num;
            var i = num;
            for (i; i < num + _num; i++) {
                ul.children().eq(i).removeClass('cur');
            }
            ul.attr('data-screen', nextScreen);
            echo.render();
        });
    };
    /**
     * url 生成
     * @param url
     * @param param
     * @param suffix
     * @param model
     * @returns {*}
     */
    exports.url = function(url, param, suffix, model) {
        //不是字符串直接返回空
        if (typeof url != "string") return false;
        //替换前后两个斜杠
        var surl = url.replace(/^(\/)|(\/)$/g, ''),
            vparem = '';

        param = param || {};
        suffix = suffix || '.html';
        //循环参数
        for (var i in param) vparem += '/' + i + '/' + param[i];
        //去掉开头的/
        vparem = vparem.replace(/^(\/)|(\/)$/, '');
        vparem = vparem == '' ? '' : '/' + vparem;
        //拼接返回
        return SITE_URL + surl + vparem + suffix;
    };
    exports.post = function(url, param, success) {
        var surl = url.replace(/^(\/)|(\/)$/g, '');
        $.post(SITE_URL + surl, param, function(data) {
            success(data);
            if (data.status ==3) {
                mui.toast(data.msg);
                setTimeout(() => {
                    window.location.href = "/wapapp/index/login.html";
                }, 1000);
            }
        }, 'json');
    };
    //暴漏到全局
    window.$post = exports.post;
    exports.get = function(url, param, success) {
        var surl = url.replace(/^(\/)|(\/)$/g, '');
        $.get(SITE_URL + surl, param, function(data) {
            success(data);
            if (data.status ==3) {
                mui.toast(data.msg);
                setTimeout(() => {
                    window.location.href = "/wapapp/index/login.html";
                }, 1000);
            }
        }, 'json');
    };
    //暴漏到全局
    window.$get = exports.get;

    /**
     * 上拉加载
     */
    exports.upLoad = function(obj, dropDown, callback) {
        var $doc = $(document);
        dropDown.html('上拉加载');
        //执行上拉加载
        obj.scroll(function() {
            var tag = true;
            //加载完成
            var suc = function() {
                dropDown.html('加载完成');
            };
            //没有数据了
            var err = function() {
                    tag = false;
                    dropDown.html('没有更多数据了');
                }
                //加载中
            var loading = function() {
                dropDown.html('正在加载中...');
            }
            if ($doc.scrollTop() + $(window).height() >= $doc.height() && tag) {
                //正在加载
                loading();
                //回调函数
                callback(suc, err);
            }
        })
    }

    /**
     * 判断登录
     * 并且执行登录
     * @param loginOkcallback  登录成功的 回调  比如 重设页面的UID 的值 更换头像等
     */
    exports.login = function(loginOkcallback) {
        window.location.href = '/wapapp/index/login.html';
        return true;
        //如果是微信 了就提示问他是否去跳转登录
        // if(parseInt(IS_WEIXIN)){
        //     exports.confirm(
        //         "您还没有登录？",
        //         function(){
        //             //跳转到登录页面
        //             window.location.href=exports.url(commonUrl.url.login);
        //         },
        //         function(){},
        //         ['去登录', '取消']
        //     );
        // }else{
        var reg = exports.url(commonUrl.url.reg);
        var find = exports.url(commonUrl.url.findPassword);
        //不是微信了 弹出一个  登录对话框
        var str = '<div class="backdrop" id="loginbackdrop"></div>\
            <div class="showlogin" id="showlogin" >\
            <h5 class="text-18 mui-text-center">用户登录</h5>\
            <ul class="showlogin-box">\
            <li class="inp-li">\
                <span class="iconfont icon-crmtubiao32"></span>\
                <input name="" id="login_account" type="text" class="input11" placeholder="请输入登录手机/邮箱" style="padding: 0; margin: 0; height: auto;"/>\
            </li>\
            <li class="inp-li">\
            <span class="iconfont icon-mima"></span>\
            <input type="password" id="login_psword" name="" placeholder="请输入密码" class="input11" style="padding: 0; margin: 0; height: auto;"/>\
            </li>\
                <li>\
                    <button class="inp-but" id="login_but">登录</button>\
                </li>\
            </ul>\
            <div class="mui-input-row mui-checkbox mui-pull-left">\
            <input name="remember" type="checkbox" class="mui-pull-right">\
                <label class="text-14 color-black">记住账号</label>\
            </div>\
            <p class="mui-pull-right text-14 p-t-sm"><a href="' + reg + '" class="color-main register">立即注册</a></p>\
            <p class="mui-pull-right text-14 p-t-sm m-r"><a href="' + find + '" class="color-666">忘记密码？</a></p>\
        <div class="mui-clearfix"></div>\
        <div class="otherLogin mui-text-center">\
            <p class=" mui-text-center"><span>使用其他账号登录</span></p>\
            <a href="/api/oalogin/login/type/qq" class="mui-btn mui-btn-outlined mui-btn-blue pop_qq_login">\
            <i class="mui-icon iconfont icon-qq text-16"></i>QQ登录</a>\
        <a href="/api/oalogin/login/type/weixin" class="mui-btn mui-btn-outlined mui-btn-green pop_weixin_login">\
            <i class="mui-icon mui-icon-weixin m-r-sm text-18"></i>微信登录</a>\
        </div>\
        </div>';
        //插入元素
        $("body").prepend(str);
        if (parseInt(IS_WEIXIN)) {
            $('.pop_qq_login').hide();
        } else {
            $('.pop_weixin_login').hide();
        }
        //显示元素
        $('#showlogin').slideDown(300);
        $('#loginbackdrop').show(1);
        var _height = $(window).height();
        $('body').css({ "height": _height, "overflow": "hidden" });
        //隐藏元素
        $("#loginbackdrop").click(function() {
            delModel();
        });
        var delModel = function() {
            $('#showlogin').fadeOut('fast');
            $('#loginbackdrop').fadeOut(500);
            $('body').css('overflow', 'auto');
            $("#loginbackdrop").unbind("click");
            $("#login_but").unbind("click");
            setTimeout(function() {
                $('#showlogin').remove();
                $('#loginbackdrop').remove();
            }, 500);
        };
        console.log(Cookies.get('info_username'))
        if (Cookies.get('info_username')) {
            $("input[name='username']").val(Cookies.get('info_username'));
            $("input[name='password']").val(Cookies.get('info_password'));
            $("input[name='remember']").attr('checked', true);
        }
        //绑定登录
        $("#login_but").click(function() {
            var account = $("#login_account").val();
            if (account == '') {
                $("#login_account").focus();
                exports.toast('帐号不能为空');
                return false;
            }

            var psword = $("#login_psword").val();
            if (psword == '') {
                $("#login_psword").focus();
                exports.toast('密码不能为空');
                return false;
            }
            //登录成功 的回调
            loginOkcallback = loginOkcallback || function() {
                //FIXME 请注意此处 用对话狂登录 并没有获取到 用户UID 要么让API返回uid 要么从新获取一次
                UID = 1;
            };
            $post(commonUrl.api.login, { account: account, password: psword }, function(data) {
                var remember = $(".showlogin input[name='remember']:checked").val();
                if (data.code) {
                    if (remember) {
                        Cookies.set('info_username', account);
                        Cookies.set('info_password', psword);
                    }
                    exports.toast(data.msg);
                    loginOkcallback();
                    delModel();
                    setTimeout("window.location.reload()", 1000);
                } else {
                    exports.toast(data.msg);
                }
            });
        });
        // }
    };
    /**
     * 成功加对号吐丝效果
     */
    exports.successToast = function(str) {
        layer.open({
            content: '<i class="iconfont icon-focusd text-gr"></i><br/>' + str,
            time: 1
        });
    }

    /**
     * 侧边栏
     * obj  object 触发侧边栏的按钮
     */
    exports.aside = function(obj) {
        //变量选取
        var $aside = $("aside"),
            $sidebar = $(".sidebar"),
            $muiWrap = $(".mui-inner-wrap"),
            $muiBar = $(".mui-bar"),
            $wrapper = $(".wrapper");

        //侧边栏
        obj.click(function() {
            $sidebar.show();
            $aside.addClass("active");
            $muiWrap.removeClass("back").addClass("go");
            $muiBar.addClass("go");
            $wrapper.addClass("active");
        })
        $sidebar.click(function() {
            $muiWrap.removeClass("go").addClass("back");
            $sidebar.hide();
            setTimeout(function() {
                $aside.removeClass("active");
                $wrapper.removeClass("active");
                $muiBar.removeClass("go");
                $muiWrap.css({ "min-height": $(window).height(), "background": '#efeff4' });
            }, 500)
        })
    }

    //回到顶部  不使用mui滚动
    exports.gotoTopNoMui = function(min_height) {
        //预定义返回顶部的html代码，它的css样式默认为不显示
        var gotoTop_html = '<div id="gotoTop"><a href="javascript:;"><i class="mui-icon mui-icon-arrowup mui-block"></i>顶部</a> </div>';
        //将返回顶部的html代码插入页面上id为page的元素的末尾
        $(".mui-inner-wrap").append(gotoTop_html);
        $("#gotoTop").click( //定义返回顶部点击向上滚动的动画
            function() {
                $('html,body').animate({ scrollTop: 0 }, 700);
            }).hover( //为返回顶部增加鼠标进入的反馈效果，用添加删除css类实现
            function() { $(this).addClass("hover"); },
            function() {
                $(this).removeClass("hover");
            });
        //获取页面的最小高度，无传入值则默认为600像素
        min_height = $(window).height();
        //为窗口的scroll事件绑定处理函数
        $(window).scroll(function() {
            //获取窗口的滚动条的垂直位置
            var s = $(window).scrollTop();
            //当窗口的滚动条的垂直位置大于页面的最小高度时，让返回顶部元素渐现，否则渐隐
            if (s > min_height) {
                $("#gotoTop").fadeIn(100);
            } else {
                $("#gotoTop").fadeOut(200);
            }
        });
    }

    /*时间戳转日期*/
    exports.getLocalTime = function(now) {
        var year = now.getFullYear();
        var month = now.getMonth() + 1;
        var date = now.getDate();
        var hour = now.getHours();
        var minute = now.getMinutes();
        var second = now.getSeconds();
        var nowData = new Date(),
            nowYear = nowData.getFullYear(),
            nowMonth = nowData.getMonth() + 1,
            nowDate = nowData.getDate();
        if (hour < 10) {
            hour = '0' + hour;
        }
        if (minute < 10) {
            minute = '0' + minute;
        }

        if (year == nowYear) {
            if (month == nowMonth && date == nowDate) {
                return "今天   " + hour + ":" + minute;
            } else {
                return month + "月" + date + "日   " + hour + ":" + minute;
            }
        } else {
            return year + "年" + month + "月" + date + "日   " + hour + ":" + minute;
        }
    }
});