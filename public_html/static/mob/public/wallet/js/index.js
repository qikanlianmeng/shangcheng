/*
 * @Author: 王世文
 * @Date: 2018-09-28 17:18:07
 * @Last Modified by: 王世文
 * @Last Modified time: 2018-11-12 09:25:03
 */
// 返回按钮
$('.back').on('tap',function(){
    api.closeWin();
});


  // 修改密码
  $(".to_modify_password").on('tap', function() {
    api.openWin({
      name: 'modify_password',
      url: 'modify_password.html',
      pageParam: {
          name: 'value'
      },
      delay:550
    });
});

$(".to_login").on('tap', function() {
    api.openWin({
         name: 'login',
         url: 'login.html',
         reload:true,
        pageParam: {
              name: 'value'
        },
        delay:550
    });
});
// 注册
$(".to_register").on('tap', function() {
    api.openWin({
         name: 'register',
         url: 'register.html',
         reload:true,
         allowEdit:true,
        pageParam: {
              name: 'value'
        },
        delay:550
    });
});
