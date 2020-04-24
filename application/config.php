<?php

return [

    // +----------------------------------------------------------------------
    // | auth配置
    // +----------------------------------------------------------------------
    'auth_config'  => [
        'auth_on'           => 1, // 权限开关
        'auth_type'         => 1, // 认证方式，1为实时认证；2为登录认证。
        'auth_group'        => 'think_auth_group', // 用户组数据不带前缀表名
        'auth_group_access' => 'think_auth_group_access', // 用户-用户组关系不带前缀表
        'auth_rule'         => 'think_auth_rule', // 权限规则不带前缀表
        'auth_user'         => 'think_admin', // 用户信息不带前缀表
    ],

    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------
    'url_route_on' => true,     //开启路由功能
    'route_config_file' =>  ['admin'],   // 设置路由配置文件列表

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'app_trace' =>  false,      //开启应用Trace调试
    'trace' => [
        'type' => 'html',       // 在当前Html页面显示Trace信息,显示方式console、html
    ],
    'sql_explain' => false,     // 是否需要进行SQL性能分析  
    'extra_config_list' => ['database', 'route', 'validate'],//各模块公用配置
    'app_debug' => true,
	'default_module' => 'wapapp',//默认模块
    //'default_filter' => ['strip_tags', 'htmlspecialchars'],

    //默认错误跳转对应的模板文件
    'dispatch_error_tmpl' => APP_PATH.'admin/view/public/error.tpl',
    //默认成功跳转对应的模板文件
    'dispatch_success_tmpl' => APP_PATH.'admin/view/public/success.tpl',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------
    'log'       => [       
        'type'  => 'file',// 日志记录方式，内置 file socket 支持扩展
        'path'  => LOG_PATH,// 日志保存目录      
        'level' => [],// 日志记录级别
    ],


    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------
    'cache' => [     
        'type'   => 'file',// 驱动方式        
        'path'   => CACHE_PATH,// 缓存保存目录        
        'prefix' => '',// 缓存前缀       
        'expire' => 0,// 缓存有效期 0表示永久缓存
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------
    'session'            => [
        'id'             => '',
        'var_session_id' => '',// SESSION_ID的提交变量,解决flash上传跨域
        'prefix'         => 'think',// SESSION 前缀
        'type'           => '',// 驱动方式 支持redis memcache memcached
        'auto_start'     => true,// 是否自动开启 SESSION
        'expire'         => 3600,// 保存时间
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'        => [      
        'prefix'    => '',// cookie 名称前缀      
        'expire'    => 0,// cookie 保存时间      
        'path'      => '/',// cookie 保存路径      
        'domain'    => '',// cookie 有效域名      
        'secure'    => false,//  cookie 启用安全传输      
        'httponly'  => '',// httponly设置      
        'setcookie' => true,// 是否使用 setcookie
    ],

    //分页配置
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 15,
    ],
    

    // +----------------------------------------------------------------------
    // | 数据库设置
    // +----------------------------------------------------------------------
    'data_backup_path'     => '../data/',   //数据库备份路径必须以 / 结尾；
    'data_backup_part_size' => '20971520',  //该值用于限制压缩后的分卷最大长度。单位：B；建议设置20M
    'data_backup_compress' => '1',          //压缩备份文件需要PHP环境支持gzopen,gzwrite函数        0:不压缩 1:启用压缩
    'data_backup_compress_level' => '9',    //压缩级别   1:普通   4:一般   9:最高


    // +----------------------------------------------------------------------
    // | 极验验证,请到官网申请ID和KEY，http://www.geetest.com/
    // +----------------------------------------------------------------------
    'verify_type' => '1',   //验证码类型：0极验验证， 1数字验证码
    'gee_id'  => 'ca1219b1ba907a733eaadfc3f6595fad',
    'gee_key' => '9977de876b194d227b2209df142c92a0',
    'auth_key' => 'JUD6FCtZsqrmVXc2apev4TRn3O8gAhxbSlH9wfPN', //默认数据加密KEY
    'pages'    => '10',//分页数 
    'salt'     => 'wZPb~yxvA!ir38&Z',//加密串
    // +----------------------------------------------------------------------
    // | 支付回调设置
    // +----------------------------------------------------------------------
    'alipay'=>[
        'use_sandbox'               => false,// 是否使用沙盒模式
        'sign_type'                 => 'RSA2',// RSA  RSA2
        'limit_pay'                 => [
            //'balance',// 余额
            //'moneyFund',// 余额宝
            //'debitCardExpress',// 	借记卡快捷
            //'creditCard',//信用卡
            //'creditCardExpress',// 信用卡快捷
            //'creditCardCartoon',//信用卡卡通
            //'credit_group',// 信用支付类型（包含信用卡卡通、信用卡快捷、花呗、花呗分期）
        ],// 用户不可用指定渠道支付当有多个渠道时用“,”分隔
        'notify_url'=>['api/snotify/index',['type'=>'ali_charge']],
        'return_url'=>['api/snotify/return_url',['type'=>'ali_charge']],
        'return_raw'=>true,
    ],
    'wxpay'=>[
        'use_sandbox' => false,// 是否使用 微信支付仿真测试系统
        'sign_type'=>'MD5',
        'limit_pay'=>[],
        'fee_type'=>'CNY',
        'notify_url'=>['api2/snotify/index',['type'=>'wx_charge']],
        'redirect_url'=>['api2/snotify/return_url',['type'=>'wx_charge']],
        'return_raw'=>true,

    ],
    'income_type'   => [
        1 => '推荐奖',
      //2 => '代营收益奖',
        2 => '代售结算',
        3 => 'A组收益奖',
        4 => 'B组收益奖',
        5 => '体验中心分润奖',
        6 => '推荐体验中心分润奖',
        7 => '收益加权分润奖',
        8 => '管理员添加',
        9 => '余额充值',
        10=> '提现',
        11=> '支付订单',
        12=> '管理员扣除',
        13 => '订单取消余额返还',
        14 => '提现失败返还余额',
        15 => '支付抽奖活动',
        16 => '间推代理收益',
        17 => '代理平级分润',
        18 => '自营体验中心收益',
        19 => '自营推荐体验中心分润',
        20 => '自营直推收益',
        21 => '自营间推收益',
        22 => '自营平级分润',
		23 => '认购扣除',
        24 => '认购失败返还',
        25 => '股票已交易挂卖扣除',
    ]

];