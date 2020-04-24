<?php
namespace app\admin\controller;
use think\Config;
use think\Db;
use think\Session;
use think\Request;
use com\Database;

class Data extends Base
{
    public function _initialize()
    {
        parent::_initialize();
        set_time_limit(0);
    }
    /**
     * 数据备份首页
     * @author 田建龙 <864491238@qq.com>
     */
    public function index() {
        $Db = Db::connect();
        $tmp = $Db->query('SHOW TABLE STATUS');
        $data = array_map('array_change_key_case', $tmp);
        $value['data'] = $data;
        return $this->view->assign($value ?: null)->fetch();
    }


    /**
     * 备份数据库
     * @param  String  $ids 表名
     * @param  Integer $id     表ID
     * @param  Integer $start  起始行数
     * @author 田建龙 <864491238@qq.com>
     */
    public function export($ids = null, $id = null, $start = null) {
        $Request = Request::instance();
        if ($Request->isPost() && !empty($ids) && is_array($ids)) { //初始化
            $path = Config::get('data_backup_path');
            is_dir($path) || mkdir($path, 0755, true);
            //读取备份配置
            $config = [
                'path' => realpath($path) . DIRECTORY_SEPARATOR,
                'part' => Config::get('data_backup_part_size'),
                'compress' => Config::get('data_backup_compress'),
                'level' => Config::get('data_backup_compress_level'),
            ];

            //检查是否有正在执行的任务
            $lock = "{$config['path']}backup.lock";
            if (is_file($lock)) {
                return $this->error('检测到有一个备份任务正在执行，请稍后再试！');
            }
            file_put_contents($lock, $Request->time()); //创建锁文件
            //检查备份目录是否可写
            is_writeable($config['path']) || $this->error('备份目录不存在或不可写，请检查后重试！');
            Session::set('backup_config', $config);

            //生成备份文件信息
            $file = [
                'name' => date('Ymd-His', $Request->time()),
                'part' => 1,
            ];
            Session::set('backup_file', $file);
            //缓存要备份的表
            Session::set('backup_tables', $ids);

            //创建备份文件
            $Database = new \com\Database($file, $config);
            if (false !== $Database->create()) {
                $tab = ['id' => 0, 'start' => 0];
                return $this->success('初始化成功！', '', ['tables' => $ids, 'tab' => $tab]);
            } else {
                return $this->error('初始化失败，备份文件创建失败！');
            }
        } elseif ($Request->isGet() && is_numeric($id) && is_numeric($start)) { //备份数据
            $tables = Session::get('backup_tables');
            //备份指定表
            $Database = new \com\Database(Session::get('backup_file'), Session::get('backup_config'));
            $start = $Database->backup($tables[(int) $id], $start);
            if (false === $start) { //出错
                $this->error('备份出错！');
            } elseif (0 === $start) { //下一表
                if (isset($tables[++$id])) {
                    $tab = ['id' => $id, 'start' => 0];
                    return $this->success('备份完成！', '', ['tab' => $tab]);
                } else { //备份完成，清空缓存
                    unlink(Session::get('backup_config.path') . 'backup.lock');
                    Session::set('backup_tables', null);
                    Session::set('backup_file', null);
                    Session::set('backup_config', null);
                    return $this->success('备份完成！');
                }
            } else {
                $tab = ['id' => $id, 'start' => $start[0]];
                $rate = floor(100 * ($start[0] / $start[1]));
                return $this->success("正在备份...({$rate}%)", '', ['tab' => $tab]);
            }
        } else { 

            return json(['msg' => '请选择要备份的数据表！']);
        }
    }

    /**
     * 优化表
     * @param  String $ids 表名
     */
    public function optimize($ids = null) {
        if (empty($ids)) {
            return $this->error("请指定要优化的表！");
        }
        $Db = Db::connect();
        if (is_array($ids)) {
            $tables = implode('`,`', $ids);
            $list = $Db->query("OPTIMIZE TABLE `{$tables}`");
            if($list){
                $this->success("数据表优化完成！");
            } else {
                $this->error("数据表优化出错请重试！");
            }
        } else {

            $list = $Db->query("OPTIMIZE TABLE `{$ids}`");
            if($list){
                $this->success("数据表'{$ids}'优化完成！");
                //return json("数据表'{$ids}'优化完成！");
            } else {
                $this->error("数据表'{$ids}'优化出错请重试！");
            }
        }
    }



    /**
     * 修复表
     * @param  String $ids 表名
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function repair($ids = null) {
        if (empty($ids)) {
            return $this->error("请指定要修复的表！");
        }
        $Db = Db::connect();
        if (is_array($ids)) {
            $tables = implode('`,`', $ids);
            $list = $Db->query("REPAIR TABLE `{$tables}`");
            if($list){
                $this->success("数据表修复完成！");
            } else {
                $this->error("数据表修复出错请重试！");
            }
        } else {
            $list = $Db->query("REPAIR TABLE `{$ids}`");
            if($list){
                $this->success("数据表'{$ids}'修复完成！");
                //return json("数据表'{$ids}'优化完成！");
            } else {
                $this->error("数据表'{$ids}'修复出错请重试！");
            }
        }
    }




    /**
     * 还原数据库
     * @param 类型 参数 参数说明
     * @author staitc7 <static7@qq.com>
     */

    public function import() {
        //列出备份文件列表
        $path_tmp = Config::get('data_backup_path');
        is_dir($path_tmp) || mkdir($path_tmp, 0755, true);
        $path = realpath($path_tmp);
        $flag = \FilesystemIterator::KEY_AS_FILENAME;
        $glob = new \FilesystemIterator($path, $flag);

        $list = array();
        foreach ($glob as $name => $file) {
            if (preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql(?:\.gz)?$/', $name)) {
                $name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');
                $date = "{$name[0]}-{$name[1]}-{$name[2]}";
                $time = "{$name[3]}:{$name[4]}:{$name[5]}";
                $part = $name[6];
                if (isset($list["{$date} {$time}"])) {
                    $info = $list["{$date} {$time}"];
                    $info['part'] = max($info['part'], $part);
                    $info['size'] = $info['size'] + $file->getSize();
                } else {
                    $info['part'] = $part;
                    $info['size'] = $file->getSize();
                }
                $extension = strtoupper(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                $info['compress'] = ($extension === 'SQL') ? '-' : $extension;
                $info['time'] = strtotime("{$date} {$time}");
                $list["{$date} {$time}"] = $info;
            }
        }
        $value['data'] = $list;
        $this->view->metaTitle = '数据还原';
        return $this->view->assign($value ?: null)->fetch();
    }

    /**
     * 删除备份文件
     * @param  Integer $time 备份时间
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function del($time = 0) {
        empty($time) && $this->error('参数错误！');
        $name = date('Ymd-His', $time) . '-*.sql*';
        $path = realpath(Config::get('data_backup_path')) . DIRECTORY_SEPARATOR . $name;
        array_map("unlink", glob($path));
        if (count(glob($path))) {
            return $this->error('备份文件删除失败，请检查权限！');
        } else {
            return $this->success('备份文件删除成功！');
        }
    }

    /**
     * 还原数据库
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function revert($time = 0, $part = null, $start = null) {
        if (is_numeric($time) && is_null($part) && is_null($start)) { //初始化
            //获取备份文件信息
            $name = date('Ymd-His', $time) . '-*.sql*';
            $path = realpath(Config::get('data_backup_path')) . DIRECTORY_SEPARATOR . $name;
            $files = glob($path);
            $list = [];
            foreach ($files as $name) {
                $basename = basename($name);
                $match = sscanf($basename, '%4s%2s%2s-%2s%2s%2s-%d');
                $gz = preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql.gz$/', $basename);
                $list[$match[6]] = array($match[6], $name, $gz);
            }
            ksort($list);
            $last = end($list);//检测文件正确性
            if (count($list) === $last[0]) {
                Session::set('backup_list', $list); //缓存备份列表
                return $this->success('初始化完成,请等待！', '', ['part' => 1, 'start' => 0]);
            } else {
                return $this->error('备份文件可能已经损坏，请检查！');
            }
        } elseif (is_numeric($part) && is_numeric($start)) {
            $list = Session::get('backup_list');
            $db = new \com\Database($list[$part], [
                'path' => realpath(Config::get('data_backup_path')) . DIRECTORY_SEPARATOR,
                'compress' => $list[$part][2]
                ]
            );
            $start = $db->import($start);
            if (false === $start) {
                return $this->error('还原数据出错！');
            } elseif (0 === $start) { //下一卷
                if (isset($list[++$part])) {
                    $data = array('part' => $part, 'start' => 0);
                    $this->success("正在还原...#{$part}", '', $data);
                } else {
                    Session::set('backup_list', null);
                    $this->success('数据库还原完成！');
                }
            } else {
                $data = array('part' => $part, 'start' => $start[0]);
                if ($start[1]) {
                    $rate = floor(100 * ($start[0] / $start[1]));
                    return $this->success("正在还原...#{$part} ({$rate}%)", '', $data);
                } else {
                    $data['gz'] = 1;
                    return $this->success("正在还原...#{$part}", '', $data);
                }
            }
        } else {
            return $this->error('参数错误！');
        }
    }

    /**************************************数据迁移*************************************************/
    /**************************************迁移迁移后一些字段值为null，需手动调整为对应的默认值，以免程序出错*************************************************/
    /**************************************原始数据表记得加索引*************************************************/

    /***************************************************************
     * 迁移用户信息         用户表信息，原用户密码都为密码直接md5加密
     * ***************************************************************
     */
     
     
     /* * *    
     * 第一步 连表导入信息   登录信息表  sys_user
     *  把ns_member_account按照对应uid，更新余额数据到用户表
     *  把nfx_promoter 按照对应uid，更新推荐人信息到用户表
     *  把ns_member按照对应uid，更新用户等级信息到用户表    
     *
     *
     * */
     public function step_1(){
         $sql = 'INSERT INTO think_member (
            id,
            account,
            mobile,
            password,
            nickname,
            head_img,
            create_time,
            money,
            ruid,
            rcode,
            level
         ) SELECT 
            think1_sys_user.uid,
            think1_sys_user.user_name,
            think1_sys_user.user_tel,
            think1_sys_user.user_password,
            think1_sys_user.nick_name,
            think1_sys_user.user_headimg,
            think1_sys_user.reg_time,
            think1_ns_member_account.balance,
            think1_nfx_shop_member_association.source_uid,
            think1_nfx_promoter.promoter_no,
            think1_ns_member.member_level 
            FROM think1_sys_user 
            left join think1_ns_member on think1_sys_user.uid=think1_ns_member.uid
            left join think1_ns_member_account on think1_sys_user.uid=think1_ns_member_account.uid
            left join think1_nfx_promoter on think1_sys_user.uid=think1_nfx_promoter.uid
            left join think1_nfx_shop_member_association on think1_sys_user.uid=think1_nfx_shop_member_association.uid
            ';
         Db::query($sql);
         echo 'done';
     }
    //第二步， 更新用户的等级信息和头像
    //代理和体验中心时间，因为原表中没有这些信息，统设为注册时间
    //level 47普通会员，48代理会员，49体验中心
    public function step_2(){
        $res1 = Db::name('member')->where(['level'=>48])->update(['dl_time'=>Db::raw('create_time')]);
        $res2 = Db::name('member')->where(['level'=>49])->update(['center_time'=>Db::raw('create_time'),'is_center'=>1]);
        Db::query('update think_member set head_img = concat("/uploads/",head_img) where head_img <> "" ');
        //echo Db::name('member')->getLastsql();exit;
        echo '代理：'.$res1.'，体验中心：'.$res2;
    }
    //第三步    迁移提现记录表 ns_member_balance_withdraw
    public function step_3(){
        $sql = 'INSERT INTO think_log_cash (
            uid,
            account_type,
            money,
            get_money,
            account,
            name,
            mobile,
            bank,
            status,
            create_time,
            update_time,
            msg
         ) SELECT 
            uid,
            transfer_type,
            cash2,
            cash,
            account_number,
            realname,
            mobile,
            bank_name,
            status,
            ask_for_date,
            modify_date,
            memo
            FROM think1_ns_member_balance_withdraw
            ';
         Db::query($sql);
         echo 'done';
    }
    //第四步，修改审核状态值
    public function step_4(){
        $res1 = Db::name('log_cash')->where(['status'=>-1])->update(['status'=>3]);
        $res2 = Db::name('log_cash')->where(['status'=>1])->update(['status'=>2]);
        echo '不同意：'.$res1.'，同意：'.$res2;
    }
    //第五步  导入银行卡信息
    public function step_5(){
        $sql = 'INSERT INTO think_member_bank (
            uid,
            name,
            mobile,
            card_number,
            bank
         ) SELECT 
            uid,
            realname,
            mobile,
            account_number,
            branch_bank_name
            FROM think1_ns_member_bank_account where is_default=1
            ';
        Db::query($sql);
        echo 'done';
    }
    
    
    /** ***************************************************************
     * 迁移商品信息
     *****************************************************************
     * */


     //第一步，迁移商品基本信息   （主要保证商品id一致，其他信息可以再编辑）
     public function goods_1(){
         $sql = 'INSERT INTO think_goods (
            id,
            goods_name,
            original_img,
            dl_price,
            zy_price,
            sales_num,
            click_count,
            goods_content,
            keywords,
            goods_remark
         ) SELECT 
            goods_id,
            goods_name,
            think1_sys_album_picture.pic_cover_big,
            price,
            price,
            sales,
            clicks,
            description,
            keywords,
            introduction
            FROM think1_ns_goods left join think1_sys_album_picture on think1_ns_goods.picture=think1_sys_album_picture.pic_id
            ';
         Db::query($sql);
         echo 'done';
     }
     //第二步  修改商品封面图路径,修改代营价格
     public function goods_2(){
         //修改图片路径
         Db::query('update think_goods set original_img = concat("/uploads/",original_img)');
         //修改代营价格
         Db::name('goods')->where('id > 1')->update(['dy_zhekou'=>8,'dy_price'=>Db::raw('dl_price*0.8')]);
         //修改描述中的图片路径
         $list = Db::name('goods')->select();
         foreach($list as $v){
             $content = str_replace('src="/upload','src="/uploads/upload',$v['goods_content']);
             //$content = str_replace('src="/uploads/uploads/upload','src="/uploads/upload',$v['goods_content']);
             $content = str_replace('src="http://jcnywzj.com/upload','src="/uploads/upload',$content);

             Db::name('goods')->where('id',$v['id'])->update(['goods_content'=>$content]);
         }
         echo 'done';
     }

        /** ***************************************************************
         * 迁移订单信息
         ** ***************************************************************
         * */
     //第一步  导入订单 ,  TODO 注意，导入时先把shouyi表order_id有重复的值删掉一条，不然插入数据会出错
    public function order_1(){
        $sql = 'INSERT INTO think_order (
            order_id,
            order_sn,
            user_id,
            order_status,
            shipping_status,
            pay_status,
            consignee,
            address,
            mobile,
            pay_code,
            goods_price,
            user_money,
            order_amount,
            total_amount,
            add_time,
            pay_time,
            settle_time,
            is_settle,
            dy_income,
            deleted,
            dl,
            dy,
            zy
         ) SELECT 
            think1_ns_order.order_id,
            think1_ns_order.order_no,
            think1_ns_order.buyer_id,
            think1_ns_order.order_status,
            think1_ns_order.shipping_status,
            think1_ns_order.pay_status,
            think1_ns_order.receiver_name,
            think1_ns_order.receiver_address,
            think1_ns_order.receiver_mobile,
            think1_ns_order.payment_type,
            think1_ns_order.goods_money,
            think1_ns_order.user_platform_money,
            think1_ns_order.pay_money,
            think1_ns_order.goods_money,
            think1_ns_order.create_time,
            think1_ns_order.pay_time,
            think1_ns_shouyi.touch_time,
            think1_ns_shouyi.is_use,
             think1_ns_shouyi.amount,
            think1_ns_order.is_deleted,
            think1_ns_order.order_full,
            think1_ns_order.order_eight,
            think1_ns_order.order_half
            FROM think1_ns_order left join think1_ns_shouyi on think1_ns_order.order_id=think1_ns_shouyi.order_id order by think1_ns_order.order_id asc;
            ';
        Db::query($sql);
        echo 'done';
    }
    //第二步     修改订单信息
    public function order_2(){
        //修改支付方式和名称， pay_code:1微信支付 设为3，5余额支付设为1
        Db::name('order')->where('pay_code',1)->update(['pay_code'=>3,'pay_name'=>'微信支付']);
        Db::name('order')->where('pay_code',5)->update(['pay_code'=>1,'pay_name'=>'余额支付']);
        // 修改状态值：
        //原网站状态值
        //  订单状态：0待付款，1待发货，2已发货，3已收货，4已完成，5已关闭，-1退款中
        //订单付款状态：0待付款，2已支付
        //订单配送状态：0未发货，1已发货,2已收货
        //新网站状态值
        //订单状态：0待确认,1已确认,2已收货,3已取消,4已完成,5已作废 （原值2改为1，原值3改为2）
        //支付状态：0未支付,1已支付,3已退款,4拒绝退款（原值2改为1）
        //发货状态：0未发货,1已发货,2已送达   （ 完全对应不用改）
        Db::name('order')->where('order_status',2)->update(['order_status'=>1]);
        Db::name('order')->where('order_status',3)->update(['order_status'=>2]);
        Db::name('order')->where('pay_status',2)->update(['pay_status'=>1]);
        //修改订单类型值
        Db::name('order')->where('dl',1)->update(['order_group'=>1]);
        Db::name('order')->where('dy',1)->update(['order_group'=>2]);
        Db::name('order')->where('zy',1)->update(['order_group'=>3]);
        echo 'done';
    }
    //第三步  导入订单商品
    public function order_3(){
        $sql = 'INSERT INTO think_order_goods (
            order_id,
            goods_id,
            goods_name,
            goods_num,
            goods_price
         ) SELECT 
            think1_ns_order_goods.order_id,
            think1_ns_order_goods.goods_id,
            think1_ns_order_goods.goods_name,
            think1_ns_order_goods.num,
            think1_ns_order_goods.price
            FROM think1_ns_order_goods order by order_goods_id asc
            ';
        Db::query($sql);
        echo 'done';
    }

     
     
     
     
     
    

}