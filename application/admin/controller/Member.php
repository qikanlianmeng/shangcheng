<?php

namespace app\admin\controller;
use app\admin\model\MemberModel;
use app\admin\model\MemberGroupModel;
use app\common\model\IntegralLog;
use app\common\model\YangBill;
use think\Request;
use app\common\service\Users;
use think\Db;
use think\Loader;
use app\common\service\Payment;
use app\admin\model\MemberInfoModel;

class Member extends Base
{
    //*********************************************会员组*********************************************//
    /**
     * [group 会员组]
     * @author [田建龙] [864491238@qq.com]
     */
    public function group(){

        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['group_name'] = ['like',"%" . $key . "%"];          
        }      
        $group = new MemberGroupModel(); 
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');
        $count = $group->getAllCount($map);         //获取总条数
        $allpage = intval(ceil($count / $limits));  //计算总页面      
        $lists = $group->getAll($map, $Nowpage, $limits);  
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     * [add_group 添加会员组]
     * @author [田建龙] [864491238@qq.com]
     */
    public function add_group()
    {
        if(request()->isAjax()){
            $param = input('post.');
            $group = new MemberGroupModel();
            $flag = $group->insertGroup($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }


    /**
     * [edit_group 编辑会员组]
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit_group()
    {
        $group = new MemberGroupModel();
        if(request()->isPost()){           
            $param = input('post.');
            $flag = $group->editGroup($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign('group',$group->getOne($id));
        return $this->fetch();
    }


    /**
     * [del_group 删除会员组]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del_group()
    {
        $id = input('param.id');
        $group = new MemberGroupModel();
        $flag = $group->delGroup($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * [group_status 会员组状态]
     * @author [田建龙] [864491238@qq.com]
     */
    public function group_status()
    {
        $id=input('param.id');
        $status = Db::name('member_group')->where(array('id'=>$id))->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('member_group')->where(array('id'=>$id))->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('member_group')->where(array('id'=>$id))->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }   
    } 


    //*********************************************会员列表*********************************************//
    
    //清空普通会员余额
    public function clear_money(){
        $back = ['code'=>1,'msg'=>'操作成功'];
        $where = ['closed'=>0,'is_center'=>0,'dl_time'=>0,'money'=>['>',0]];
        $id_arr = Db::name('member')->where($where)->column('money','id');
        //print_r($id_arr);exit;
        if($id_arr){
            Db::startTrans();
            try {
                Db::name('member')->where($where)->update(['money'=>0]);
                foreach($id_arr as $k=>$v){
                    $res = Db::name('log_income')->insert(['uid'=>$k,'money'=>-$v,'type'=>12,'act_admin'=>$this->uid,'act_msg'=>'管理员清空余额','create_time'=>time()]);
                    
                }
                // 提交事务
                Db::commit();
                writelog($this->uid,$this->username,'清空普通会员余额');
                
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $back = ['code'=>0,'msg'=>$e->getMessage()];
            }
        }
        
        return json($back);
    }
    
    /**
     * 会员列表
     * @author [田建龙] [864491238@qq.com]
     */
    public function index(){

        $key = input('key');
        $start = input('start','');
        $end = input('end','');
        $start2 = input('start2','');
        $end2 = input('end2','');
        $money = input('money','');
        $dl_money = input('dl_money','');
        $dy_money = input('dy_money','');
        $group = input('group',0);
        $uid = input('uid',0);
        $id = input('id',0);
        $ruid = input('ruid',0);

        if($uid > 0)  $map['think_member.id'] = $uid;
        
        
        if($group > 0){
            switch($group){
                case 1:
                    $map = ['think_member.dl_time'=>0,'think_member.is_center'=>0];
                    break;
                case 2:
                    $map = ['think_member.dl_time'=>['>',0],'think_member.is_center'=>0];
                    break;
                case 3:
                    $map = ['think_member.is_center'=>1];
                    break;
            }
        }
        if($id > 0)  $map['think_member.id'] = $id;
        if($money){
            $money_limit = explode('-',$money);
            if(count($money_limit) == 2){
                $map['think_member.money'] = ['between',[$money_limit[0],$money_limit[1]]];
            }
        }
        if($dl_money){
            $money_limit = explode('-',$dl_money);
            if(count($money_limit) == 2){
                $map['think_member.create_dl_income'] = ['between',[$money_limit[0],$money_limit[1]]];
            }
        }
        if($dy_money){
            $money_limit = explode('-',$dy_money);
            if(count($money_limit) == 2){
                $map['think_member.create_dy_income'] = ['between',[$money_limit[0],$money_limit[1]]];
            }
        }
        if($start && $end){
            $map['think_member.create_time'] = ['between',[strtotime($start),strtotime($end)]];
        }else{
            if($start){
                $map['think_member.create_time'] = ['>',strtotime($start)];
            }
            if($end){
                $map['think_member.create_time'] = ['<',strtotime($end)];
            }
        }
        if($start2 && $end2){
            $map['think_member.dl_time'] = ['between',[strtotime($start2),strtotime($end2)]];
        }else{
            if($start2){
                $map['think_member.dl_time'] = ['>',strtotime($start2)];
            }
            if($end2){
                $map['think_member.dl_time'] = ['between',[1,strtotime($end2)]];
            }
        }
        $user = [];
        if($ruid > 0){
            $map['think_member.ruid'] = $ruid;
            $user = Db::name('member')->where('id',$ruid)->find();
        }

        if($key&&$key!=="")
        {
            $map['think_member.account|think_member.mobile|think_member.id|think_member.nickname'] = ['like',"%" . $key . "%"];
        }
        if(isset($map['think_member.id']) && count($map)>1){
            unset($map['think_member.id']);
        }
        $map['think_member.closed'] = 0;//0未删除，1已删除

        //print_r($map);exit;
        $member = new MemberModel();       
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        //$count = $member->getAllCount($map);//计算总页面
        $count_info = $member->getMemberByWhere2($map, $Nowpage, $limits,1);
        $count = $count_info['total'];
        $allpage = intval(ceil($count / $limits));       
        $lists = $member->getMemberByWhere2($map, $Nowpage, $limits);
        //echo DB::name('member')->getLastSql();exit;
        foreach($lists as $k=>$v){
            if($v->dl_time > 0){
                $lists[$k]->data('dl_date',date('Y-m-d H:i:s',$v->dl_time));
            }else{
                $lists[$k]->data('dl_date','--');
            }
            if($v->center_time > 0){
                $lists[$k]->data('center_date',date('Y-m-d H:i:s',$v->center_time));
            }else{
                $lists[$k]->data('center_date','--');
            }
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        if(input('get.page'))
        {
            //echo Db::name('member')->getLastSql();exit;
            //print_r($map);
            return json($lists);
        }
        //统计总数
        /* $total_people_num = Db::name('member')->where($map)->count();
        $dl_people_num = Db::name('member')->where(['dl_time'=>['>',0]])->where($map)->count();
        $center_num = Db::name('member')->where(['is_center'=>1])->where($map)->count(); */
        $total_people_num = $count_info['total'];
        $dl_people_num = $count_info['dl'];
        $center_num = $count_info['center'];
        $group_name = [0=>'',1=>'普通会员',2=>'代理会员',3=>'体验中心'];
        $this->assign([
            'total_people_num'=>$total_people_num,
            'dl_people_num'=>$dl_people_num,
            'center_num' => $center_num,
            'start' => $start,
            'end'   => $end,
            'start2' => $start2,
            'end2'   => $end2,
            'money' => $money,
            'dl_money'   => $dl_money,
            'dy_money'   => $dy_money,
            'group' => $group,
            'group_name' => $group_name[$group],
            'uid'   => $uid,
            'ruid'  => $ruid,
            'id'  => $id,
            'user'  => $user
        ]);

        return $this->fetch();
    }
    //会员导出
    public function member_export(){
        $key = input('key');
        $start = input('start','');
        $end = input('end','');
        $start2 = input('start2','');
        $end2 = input('end2','');
        $money = input('money','');
        $dl_money = input('dl_money','');
        $dy_money = input('dy_money','');
        $group = input('group',0);
        $uid = input('uid',0);
        $id = input('id',0);
        $ruid = input('ruid',0);

        if($uid > 0)  $map['think_member.id'] = $uid;
        
        
        if($group > 0){
            switch($group){
                case 1:
                    $map = ['think_member.dl_time'=>0,'think_member.is_center'=>0];
                    break;
                case 2:
                    $map = ['think_member.dl_time'=>['>',0],'think_member.is_center'=>0];
                    break;
                case 3:
                    $map = ['think_member.is_center'=>1];
                    break;
            }
        }
        if($id > 0)  $map['think_member.id'] = $id;
        if($money){
            $money_limit = explode('-',$money);
            if(count($money_limit) == 2){
                $map['think_member.money'] = ['between',[$money_limit[0],$money_limit[1]]];
            }
        }
        if($dl_money){
            $money_limit = explode('-',$dl_money);
            if(count($money_limit) == 2){
                $map['think_member.create_dl_income'] = ['between',[$money_limit[0],$money_limit[1]]];
            }
        }
        if($dy_money){
            $money_limit = explode('-',$dy_money);
            if(count($money_limit) == 2){
                $map['think_member.create_dy_income'] = ['between',[$money_limit[0],$money_limit[1]]];
            }
        }
        if($start && $end){
            $map['think_member.create_time'] = ['between',[strtotime($start),strtotime($end)]];
        }else{
            if($start){
                $map['think_member.create_time'] = ['>',strtotime($start)];
            }
            if($end){
                $map['think_member.create_time'] = ['<',strtotime($end)];
            }
        }
        if($start2 && $end2){
            $map['think_member.dl_time'] = ['between',[strtotime($start2),strtotime($end2)]];
        }else{
            if($start2){
                $map['think_member.dl_time'] = ['>',strtotime($start2)];
            }
            if($end2){
                $map['think_member.dl_time'] = ['between',[1,strtotime($end2)]];
            }
        }
        $user = [];
        if($ruid > 0){
            $map['think_member.ruid'] = $ruid;
            $user = Db::name('member')->where('id',$ruid)->find();
        }
        $map['think_member.closed'] = 0;//0未删除，1已删除
        if($key&&$key!=="")
        {
            $map['think_member.account|think_member.mobile|think_member.id|think_member.nickname'] = ['like',"%" . $key . "%"];
        }
        //print_r($map);exit;
        $member = new MemberModel();             
        $lists = $member->getMemberByWhere2($map, 1, 10000000);
        $list = collection($lists)->toArray();
        writelog($this->uid,$this->username,'导出会员信息');
        //print_r($list);exit;
        Loader::import('.PHPExcel.PHPExcel');
        Loader::import('.PHPExcel.PHPExcel.IOFactory');
        $phpexcel= new \PHPExcel();
        $filename=str_replace('.xls', '', '会员信息').'.xls';
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $phpexcel->getActiveSheet()->setCellValue('A1','会员编号');
        $phpexcel->getActiveSheet()->setCellValue('B1','会员账号');
        $phpexcel->getActiveSheet()->setCellValue('C1','会员电话');
        $phpexcel->getActiveSheet()->setCellValue('D1','会员等级');
        $phpexcel->getActiveSheet()->setCellValue('E1','产生代理收益');
        $phpexcel->getActiveSheet()->setCellValue('F1','产生代营收益');
        $phpexcel->getActiveSheet()->setCellValue('G1','会员余额	');
        $phpexcel->getActiveSheet()->setCellValue('H1','会员积分');
        $phpexcel->getActiveSheet()->setCellValue('I1','推荐人账号');
        $phpexcel->getActiveSheet()->setCellValue('J1','推荐人手机号');
        $phpexcel->getActiveSheet()->setCellValue('K1','注册时间');
        $phpexcel->getActiveSheet()->setCellValue('L1','成为代理时间');
        $phpexcel->getActiveSheet()->setCellValue('M1','昵称');
        $phpexcel->getActiveSheet()->setCellValue('N1','真实姓名');
        $phpexcel->getActiveSheet()->setCellValue('O1','真实年龄');
        $i = 2;
        foreach ($list as $vv){
            if($vv['dl_time'] > 0){
                $user_group = '代理会员';
            }else{
                if($vv['is_center'] == 1){
                    $user_group = '体验中心';
                }else{
                    $user_group = '普通会员';
                }
            }
            if($vv['real_info_ok']==1){
                $real_name = $vv['real_name'];
                $real_age = $vv['real_age'];
            }else{
                $real_name=$real_age='未认证';
            }
            $phpexcel->getActiveSheet()->setCellValue('A' . $i,$vv['id']);
            $phpexcel->getActiveSheet()->setCellValue('B' . $i,$vv['account']);
            $phpexcel->getActiveSheet()->setCellValue('C' . $i,$vv['mobile']);
            $phpexcel->getActiveSheet()->setCellValue('D' . $i,$user_group);
            $phpexcel->getActiveSheet()->setCellValue('E' . $i,$vv['income']);
            $phpexcel->getActiveSheet()->setCellValue('F' . $i,$vv['income_dy']);
            $phpexcel->getActiveSheet()->setCellValue('G' . $i,$vv['money']);
            $phpexcel->getActiveSheet()->setCellValue('H' . $i,$vv['integral']);
            $phpexcel->getActiveSheet()->setCellValue('I' . $i,$vv['r_account']);
            $phpexcel->getActiveSheet()->setCellValue('J' . $i,$vv['r_mobile']);
            $phpexcel->getActiveSheet()->setCellValue('K' . $i,$vv['create_time']);
            $phpexcel->getActiveSheet()->setCellValue('L' . $i,$vv['dl_time']?date('Y-m-d H:i:s',$vv['dl_time']):'--');
            $phpexcel->getActiveSheet()->setCellValue('M' . $i,$vv['nickname']);
            $phpexcel->getActiveSheet()->setCellValue('N' . $i,$real_name);
            $phpexcel->getActiveSheet()->setCellValue('O' . $i,$real_age);
            $i++;
        }

        $phpexcel->getActiveSheet()->setTitle('Sheet1');
        $phpexcel->setActiveSheetIndex(0);

        $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$filename");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $aa=new \IOFactory();
        $objwriter = $aa::createWriter($phpexcel, 'Excel5');
        $objwriter->save('php://output');
    }
    

    /**
     * 添加会员
     * @author [田建龙] [864491238@qq.com]
     */
    public function add_member()
    {
        if(request()->isAjax()){
            $param = input('post.');
            $parent_user = Db::name('member')->where(['id'=>$param['ruid'],'closed'=>0])->find();
            if(!$parent_user){
                return json(['code'=>0,'msg'=>'输入的上级会员ID不存在']);
            }
            //$param['password'] = md5(md5($param['password']) . config('auth_key'));
            $param['password'] = md5($param['password']);
            if($param['group_id'] == 2){
                $param['dl_time'] = time();
            }elseif($param['group_id'] == 3){
                $param['is_center'] = 1;
                $param['center_time'] = time();
            }
            //获取用户上级体验中心id
            if($parent_user['is_center'] == 1){
                $param['pcen_uid'] = $param['ruid'];
            }else{
                $param['pcen_uid'] = $parent_user['pcen_uid'];
            }
            unset($param['group_id']);
            //生成唯一邀请码
            $rcode = 'FX000000';
            $member_num = DB::name('member')->count();
            $member_num = (string)($member_num+8000);
            $param['rcode'] = substr($rcode,0,strlen($rcode)-strlen($member_num)).$member_num;

            $member = new MemberModel();
            $flag = $member->insertMember($param);
            if($flag['code']==1)    writelog($this->uid,$this->username,'添加会员',1,'','',$flag['data']);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $group = new MemberGroupModel();
        $this->assign('group',$group->getGroup());
        return $this->fetch();
    }


    /**
     * 编辑会员
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit_member()
    {
        $member = new MemberModel();

        if(request()->isAjax()){
            $param = input('post.');
            $parent_user = Db::name('member')->where(['id'=>$param['ruid'],'closed'=>0])->find();
            if(!$parent_user){
                return json(['code'=>0,'msg'=>'输入的上级会员ID不存在']);
            }
            if(empty($param['password'])){
                unset($param['password']);
            }else{
                //$param['password'] = md5(md5($param['password']) . config('auth_key'));
                $param['password'] = md5($param['password']);
            }
            $user = $member->getOneMember($param['id']);
            switch($param['group_id']){
                case 2:
                    if($user['dl_time'] > 0 && $user['is_center'] == 1){
                        $param['is_center'] = 0;
                    }else{
                        if($user['is_center'] == 1){
                            $param['is_center'] = 0;
                            $param['dl_time'] = time();
                            $param['center_time'] = 0;
                        }
                        if($user['is_center'] == 0 && $user['dl_time']<1){
                            $param['dl_time'] = time();
                        }
                    }
                    break;
                case 3:
                    if($user['is_center'] != 1){
                        $param['center_time'] = time();
                        $param['is_center'] = 1;
                    }
                    break;
                default:
                    $param['center_time'] = 0;
                    $param['is_center'] = 0;
                    $param['dl_time'] = 0;
            }
            //获取用户上级体验中心id
            if($parent_user['is_center'] == 1){
                $param['pcen_uid'] = $param['ruid'];
            }else{
                $param['pcen_uid'] = $parent_user['pcen_uid'];
            }
            $group_id = $param['group_id'];
            unset($param['group_id']);
            $old_info = Db::name('member')->where('id',$param['id'])->find();
            $flag = $member->editMember($param);

            if($flag['code'] == 1 && $group_id!=3 && $user['is_center']==1){
                //当用户由体验中心修改为普通或代理用户后，修改体验中心继承数据
                Db::name('member')->where('pcen_uid',$param['id'])->update(['pcen_uid'=>0]);
            }
            //获取用户修改前后信息
            if($flag['code'] == 1){
                $new_info =  Db::name('member')->where('id',$param['id'])->find();
                $before=$after='';
                foreach($new_info as $k=>$v){
                    if($v != $old_info[$k]){
                        switch($k){
                            case 'nickname':
                                $before .= '昵称：'.$old_info[$k].'，';
                                $after .= '昵称：'.$v.'，';
                                break;
                            case 'password':
                                $after .= '密码：已修改';
                                break;
                            case 'dl_time':
                                if($v>0){
                                    $before .= '代理：否，';
                                    $after .= '代理：是，';
                                }else{
                                    $before .= '代理：是，';
                                    $after .= '代理：否，';
                                }
                                break;
                            case 'is_center':
                                if($v>0){
                                    $before .= '体验中心：否，';
                                    $after .= '体验中心：是，';
                                }else{
                                    $before .= '体验中心：是，';
                                    $after .= '体验中心：否，';
                                }
                                break;
                            case 'ruid':
                                $before .= '推荐人id：'.$old_info[$k].'，';
                                $after .= '推荐人id：'.$v.'，';
                                break;
                            case 'income_uid':
                                $before .= '受益人id：'.$old_info[$k].'，';
                                $after .= '受益人id：'.$v.'，';
                                break;
                            case 'head_img':
                                $after .= '头像：已修改';
                                break;
                            case 'mobile':
                                $before .= '手机号码：'.$old_info[$k].'，';
                                $after .= '手机号码：'.$v.'，';
                                break;
                        }
                    }
                }
                writelog($this->uid,$this->username,'编辑会员信息',1,$before,$after,$param['id']);
            }
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $this->assign([
            'member' => $member->getOneMember($id)
        ]);
        return $this->fetch();
    }
    /**
     * 编辑会员银行卡
     */
    public function edit_bank()
    {
        $member = new MemberModel();

        if(request()->isAjax()){
            $param = input('post.');
            $old_info = Db::name('member_bank')->where('id',$param['id'])->find();
            if(Db::name('member_bank')->where('id',$param['id'])->update($param)){

                $new_info = Db::name('member_bank')->where('id',$param['id'])->find();
                $before=$after='';
                foreach($new_info as $k=>$v){
                    if($v != $old_info[$k]){
                        switch($k){
                            case 'name':
                                $before .= '姓名：'.$old_info[$k].'，';
                                $after .= '姓名：'.$v.'，';
                                break;
                            case 'mobile':
                                $before .= '预留手机号：'.$old_info[$k].'，';
                                $after .= '预留手机号：'.$v.'，';
                                break;
                            case 'card_number':
                                $before .= '银行卡号：'.$old_info[$k].'，';
                                $after .= '银行卡号：'.$v.'，';
                                break;
                            case 'bank':
                                $before .= '开户银行：'.$old_info[$k].'，';
                                $after .= '开户银行：'.$v.'，';
                                break;
                            case 'bank_local':
                                $before .= '银行归属地：'.$old_info[$k].'，';
                                $after .= '银行归属地：'.$v.'，';
                                break;
                            case 'bank_son':
                                $before .= '开户行支行：'.$old_info[$k].'，';
                                $after .= '开户行支行：'.$v.'，';
                                break;
                            case 'bank_number':
                                $before .= '银行联行号：'.$old_info[$k].'，';
                                $after .= '银行联行号：'.$v.'，';
                                break;
                        }
                    }
                }
                writelog($this->uid,$this->username,'编辑会员银行卡信息',1,$before,$after,$old_info['uid']);
                return json(['code' => 1, 'msg' => '修改成功']);
            }else{
                return json(['code' => 0, 'msg' => '修改失败']);
            }
            
        }

        $id = input('param.id');
        $bank = Db::name('member_bank')->where('uid',$id)->find();
        if(!$bank){
            $this->error('银行卡信息为空');
        }
        $user = Db::name('member')->where('id',$id)->find();
        $this->assign('bank',$bank);
        $this->assign('user',$user);
        return $this->fetch();
    }
     /**
     * 编辑会员银行卡
     */
    public function edit_address()
    {
        if(request()->isAjax()){
            $param = input('post.');
           
            if(Db::name('user_address')->where('address_id',$param['address_id'])->update($param)){
                writelog($this->uid,$this->username,'编辑会员收货信息');
                return json(['code' => 1, 'msg' => '修改成功']);
            }else{
                return json(['code' => 0, 'msg' => '修改失败']);
            }
            
        }

        $id = input('param.id');
        $address = Db::name('user_address')->where(['user_id'=>$id,'is_default'=>1])->find();
        if(!$address){
            $this->error('地址为空');
        }
        $user = Db::name('member')->where('id',$id)->find();
        $region_list = [
            'province_list' => $this->get_region(0),
            'city_list' => $this->get_region($address['province']),
            'district_list' => $this->get_region($address['city']),
        ];
        $this->assign('address',$address);
        $this->assign('user',$user);
        $this->assign('region_list',$region_list);
        return $this->fetch();
    }
     /**
     * 获取地区列表
     */
    public function get_region($pid,$type='arr'){
        $list = Db::name('region')->where('parent_id',$pid)->field('name,id')->select();
        if($type == 'json'){
            //echo 'json';
            echo json_encode($list);
        }else{
            return $list;
        }
        
    }
    public function update_money(){
        return $this->edit_amount();
    }
    public function update_integral(){
        return $this->edit_amount();
    }
    private function edit_amount(){
        $data = input('param.');
        if(is_numeric($data['num']) && $data['num']!=0){
            $user = DB::name('member')->where('id',$data['uid'])->find();
            if($data['mod'] == 'money'){
                $desc = '调整余额';
                if($data['num']<0 && abs($data['num'])>$user['money']){
                    return json(['code' => 0, 'msg' => '调整额度大于用户余额']);
                }
            }elseif($data['mod'] == 'integral'){
                $desc = '调整积分';
                if($data['num']<0 && abs($data['num'])>$user['integral']){
                    return json(['code' => 0, 'msg' => '调整额度大于用户积分']);
                }
            }
            if(empty($data['msg'])){
                $data['msg'] = '管理员调整';
            }
            $log_income = ['uid'=>$data['uid'],'money'=>$data['num'],'create_time'=>time(),'act_admin'=>$this->uid,'act_msg'=>$data['msg']];
            $log_integral = [
                'uid'   => $data['uid'],
                'num'   => $data['num'],
                'create_time' => time(),
                'executor' => $this->uid,
                'act'   => 43,
                'remark'=> $data['msg']
            ];
            $before = $user[$data['mod']];
            if($data['num']<0){
                $res = Db::name('member')->where('id',$data['uid'])->setDec($data['mod'],abs($data['num']));
                if($res){
                    $after = $before-abs($data['num']);
                    if($data['mod'] == 'money'){
                        $log_income['type'] = 12;
                        Db::name('log_income')->insert($log_income);
                    }else{
                        Db::name('integral_log')->insert($log_integral);
                    }
                }
            }else{
                $res = Db::name('member')->where('id',$data['uid'])->setInc($data['mod'],$data['num']);
                if($res){
                    $after = $before+$data['num'];
                    if($data['mod'] == 'money'){
                        $log_income['type'] = 8;
                        Db::name('log_income')->insert($log_income);
                    }else{
                        Db::name('integral_log')->insert($log_integral);
                    }
                }
            }
            if($res){
                writelog($this->uid,$this->username,$desc,1,$before,$after,$data['uid']);
                return json(['code' => 1, 'msg' => '调整成功']);
            }else{
                return json(['code' => 0, 'msg' => '调整失败']);
            }
        }else{
            return json(['code' => 0, 'msg' => '请填写正确数值']);
        }

    }
    /**
     * 删除会员
     * @author [田建龙] [864491238@qq.com]
     */
    public function del_member()
    {
        $id = input('param.id');
        $member = new MemberModel();
        $flag = $member->delMember($id);
        if($flag['code']==1){
            writelog($this->uid,$this->username,'删除会员',1,'','',$id);
        }
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * 会员状态
     * @author [田建龙] [864491238@qq.com]
     */
    public function member_status()
    {
        $id = input('param.id');
        $status = Db::name('member')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('member')->where('id',$id)->setField(['status'=>0]);
            writelog($this->uid,$this->username,'修改会员状态',1,'正常','禁用',$id);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('member')->where('id',$id)->setField(['status'=>1]);
            writelog($this->uid,$this->username,'修改会员状态',1,'禁用','正常',$id);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    
    }
    //第三方授权会员
	public function authorise(){
		$list = db('oauth_user')->alias('oauth_user')->
		field('think_member.status as member_status,nickname,last_login_ip,create_time,update_time,login_num,head_img,closed,oauth_user.*')->
		join('think_member','oauth_user.uid=think_member.id')->
		where('think_member.status=1 and closed=0')->order('login_num','desc')->paginate(10);
		$this->assign('page',$list->render());
		$this->assign('list', $list);
		return $this->fetch();
	}
	/**
	* 删除第三方用户 
	* @param 
	* @return
	 */
    public function autorise_del($id){
    	if(db('member')->where('id',$id)->update(array('closed'=>1))){
    		$this->success('删除成功');
    	}else{
    		$this->success('删除失败');
    	}
    }
	//会员等级列表
	public function grade(){
	  $kw=input('kw');
	  $list=db('member_grade')->where('name','like',"%{$kw}%")->select();
	  $this->assign('list', $list);
	  $this->assign('kw',$kw);
	  return $this->fetch();
	}
	//等级编辑
	public function grade_eidt($id){
	  $row=db('member_grade')->find($id);
	  $this->assign('row',$row);
	  return $this->fetch();
	}
	/***
	 * 等级更新
	 */
	public function ajax_grade_update(){
		 $model=model('MemberGrade');
		 $data=input('post.');
		 $info='';
		 if(isset($data['id'])){
		 	$info=$model->updataGrade($data);
		 }else{
		 	$info=$model->addGrade($data);
		 }
		 return json($info);
	}
	//等级添加
	public function grade_add(){
	  return $this->fetch();
	}
	//等级删除
	public function grade_del(){
	   $id=input('id');
	   if(db('member_grade')->delete($id)){
	   	  return json(['code'=>1,'msg'=>'删除成功']);
	   }else{
	   	  return json(['code'=>0,'msg'=>'删除失败']);
	   }
	}
    
    public function del_money_details(){
        $param = input('post.');
        if(isset($param['id']) && !empty($param['id'])){
            Db::name('log_income')->where(['id'=>['in',$param['id']]])->delete();
            writelog($this->uid,$this->username,'删除收支记录');
        }
        $this->success('操作成功');
    }
    public function money_details($uid=0){
        $param = input();
        $map = [];
        $id=input('id',0);
        if($uid ==0)    $uid=input('param.uid',0);
        if($id > 0) $uid=0;
        if($uid > 0){
            $user_account = Db::name('member')->where('id',$uid)->value('account');
            $param['uid'] = $uid;
            $map['uid'] = $uid;
        }
        $start_time=input('start_time','');
        $end_time=input('end_time','');
        $key=input('key','');
        $type=input('type',0);
        
        
        if($start_time){
            $map['li.create_time']=['gt',strtotime($start_time)];
            $query['start_time']=$start_time;
        }

        if($end_time){
            $map['li.create_time']=['lt',strtotime($end_time)];
            $query['end_time']=$end_time;
        }

        if($start_time && $end_time){
            $map['li.create_time']=['BETWEEN',[strtotime($start_time),strtotime($end_time)]];
        }
        if($type){
            $map['type']=$type;
        }
        if($key){
            $map['m.account|m.mobile'] = ['like',"%" . $key . "%"];
        }
        if($id > 0){
            $map['m.id'] = $id;
        }
        $list = Db::name('log_income')->alias('li')->join('think_member m','li.uid=m.id','left')->field('li.*,account')->where($map)->order('id desc')->paginate(15,false,['query'=>$param]);
        //echo Db::name('log_income')->getLastSql();exit;
        //查询用户总支出和总收益
        //$total_income = Db::name('log_income')->alias('li')->join('think_member m','li.uid=m.id','left')->where($map)->where(['li.money'=>['>',0]])->sum('li.money');
        //$principal = Db::name('log_income')->alias('li')->join('think_member m','li.uid=m.id','left')->where($map)->where(['type'=>2])->sum('li.principal');
        //$total_cash = Db::name('log_income')->alias('li')->join('think_member m','li.uid=m.id','left')->where($map)->where(['li.money'=>['<',0]])->sum('li.money');
        //获取指定页码是的路径
        $go_url_param = '';
        foreach($param as $k=>$v){
            $go_url_param .= $go_url_param?'&'.$k.'='.$v:$k.'='.$v;
        }
        $go_url = '/admin/member/money_details.html?'.$go_url_param;
        $type_name = config('income_type');
        $this->assign([
            'go_url'            => $go_url,
            'start_time'        => $start_time,
            'end_time'          => $end_time,
            'type'              => $type,
            'user_account'      => $user_account??'',
            'list'  => $list,
            'type_name' => $type_name,
            'total_income'      => 0,
            //'principal'         => $principal,
            'total_cash'        => 0,
            'key'               => $key,
            'uid'               => $uid,
            'id'               => $id
        ]);

        return $this->fetch();
    }
    public function export_money_details($uid=0){
        $param = input();
        $map = [];
        $id=input('id',0);
        if($uid ==0)    $uid=input('param.uid',0);
        if($id > 0) $uid=0;
        if($uid > 0){
            $param['uid'] = $uid;
            $map['uid'] = $uid;
        }
        $start_time=input('start_time','');
        $end_time=input('end_time','');
        $key=input('key','');
        $type=input('type',0);

        if($start_time){
            $map['li.create_time']=['gt',strtotime($start_time)];
            $query['start_time']=$start_time;
        }

        if($end_time){
            $map['li.create_time']=['lt',strtotime($end_time)];
            $query['end_time']=$end_time;
        }

        if($start_time && $end_time){
            $map['li.create_time']=['BETWEEN',[strtotime($start_time),strtotime($end_time)]];
        }
        if($type){
            $map['type']=$type;
        }
        if($key){
            $map['m.account|m.mobile'] = ['like',"%" . $key . "%"];
        }
        if($id > 0){
            $map['m.id'] = $id;
        }
        $list = Db::name('log_income')->alias('li')->join('think_member m','li.uid=m.id','left')->field('li.*,account')->where($map)->order('id desc')->select();
        writelog($this->uid,$this->username,'导出用户账户明细');
        Loader::import('.PHPExcel.PHPExcel');
        Loader::import('.PHPExcel.PHPExcel.IOFactory');
        $phpexcel= new \PHPExcel();
        $filename=str_replace('.xls', '', '用户收支明细').'.xls';
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        //
        $phpexcel->getActiveSheet()->setCellValue('A1','编号');
        $phpexcel->getActiveSheet()->setCellValue('B1','会员编号');
        $phpexcel->getActiveSheet()->setCellValue('C1','会员账户');
        $phpexcel->getActiveSheet()->setCellValue('D1','收支类型');
        $phpexcel->getActiveSheet()->setCellValue('E1','金额');
        $phpexcel->getActiveSheet()->setCellValue('F1','时间');
        $phpexcel->getActiveSheet()->setCellValue('G1','操作备注');

        $objActSheet = $phpexcel->getActiveSheet();
        $objActSheet->getColumnDimension('A')->setWidth(10);
        $objActSheet->getColumnDimension('B')->setWidth(20);
        $objActSheet->getColumnDimension('C')->setWidth(20);
        $objActSheet->getColumnDimension('D')->setWidth(40);
        $objActSheet->getColumnDimension('E')->setWidth(20);
        $objActSheet->getColumnDimension('F')->setWidth(20);
        $objActSheet->getColumnDimension('G')->setWidth(20);

        $i=2;
        $type_arr = config('income_type');
        foreach ($list as $vv){
            //$account_type_name = $vv['account_type']==2?'支付宝':'微信';
            $create_time = $vv['create_time']?date('Y-m-d H:i:s',$vv['create_time']):'--';

            $phpexcel->getActiveSheet()->setCellValue('A' . $i,$vv['id']);
            $phpexcel->getActiveSheet()->setCellValue('B' . $i,$vv['uid']);
            $phpexcel->getActiveSheet()->setCellValue('C' . $i,''.$vv['account']);
            $phpexcel->getActiveSheet()->setCellValue('D' . $i,$type_arr[$vv['type']]);
            $phpexcel->getActiveSheet()->setCellValue('E' . $i,$vv['money']);
            $phpexcel->getActiveSheet()->setCellValue('F' . $i,$create_time);
            $phpexcel->getActiveSheet()->setCellValue('G' . $i,$vv['act_msg']);
            $i++;
        }

        $phpexcel->getActiveSheet()->setTitle('Sheet1');
        $phpexcel->setActiveSheetIndex(0);

        $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$filename");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $aa=new \IOFactory();
        $objwriter = $aa::createWriter($phpexcel, 'Excel5');
        $objwriter->save('php://output');
    }

    public function integral_details($uid=0){
        $param = input();
        $map = [];
        if($uid > 0){
            $user_account = Db::name('member')->where('id',$uid)->value('account');
            $param['uid'] = $uid;
            $map['uid'] = $uid;
        }
        $start_time=input('start_time','');
        $end_time=input('end_time','');
        $key=input('key','');
        $act=input('act',0);
        
        if($start_time){
            $map['create_time']=['gt',strtotime($start_time)];
            $query['start_time']=$start_time;
        }

        if($end_time){
            $map['create_time']=['lt',strtotime($end_time)];
            $query['end_time']=$end_time;
        }

        if($start_time && $end_time){
            $map['create_time']=['BETWEEN',[strtotime($start_time),strtotime($end_time)]];
        }
        if($act){
            $map['act']=$act;
        }
        if($key){
            $map['m.account|m.mobile|m.id'] = ['like',"%" . $key . "%"];
        }
        $list = Db::name('integral_log')->alias('li')->join('think_member m','li.uid=m.id','left')->field('li.*,account')->where($map)->order('create_time desc')->paginate(15,false,['query'=>$param]);
        $this->assign([
            'start_time'        => $start_time,
            'end_time'          => $end_time,
            'act'              => $act,
            'user_account'      => $user_account??'',
            'list'  => $list,
            'key'               => $key,
            'uid'               => $uid
        ]);

        return $this->fetch();
        
        
        
        
        
        
        $map=['status'=>1];
        $start_time=input('start_time','');
        $end_time=input('end_time','');
        $act=input('act',0);

        if($start_time){
            $map['create_time']=['gt',strtotime($start_time)];
            $query['start_time']=$start_time;
        }

        if($end_time){
            $map['create_time']=['lt',strtotime($end_time)];
            $query['end_time']=$end_time;
        }

        if($start_time && $end_time){
            $map['create_time']=['BETWEEN',[strtotime($start_time),strtotime($end_time)]];
        }
        if($act){
            $map['act']=$act;
        }


        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('act', $act);

        $Users=new Users();
        $res = $Users->userIntegralLog($id,$map);

        $this->assign('lists',is_array($res['data'])?$res['data']:[]);
        return $this->fetch();
    }
	//*********************************************站内信推送*********************************************//
	//推送列表
	public function msg_list(){
		$list = Db::name('system_msg')->order('send_time desc')->paginate(10);
		$page = $list->render();
		if($list){
			$list = collection($list->items())->toArray();
			foreach($list as $k=>$v){
				$list[$k]['content'] = unserialize($v['content']);
				$list[$k]['date'] = date('Y-m-d',$v['send_time']);
			}
		}	
		$this->assign('list', $list);
        $this->assign('page', $page);
		return $this->fetch();
	}
	//添加推送
	public function add_msg(){
		if (Request::instance()->isPost()){
			$data = input('post.');
			//在推送表中插入一条推送数据
			$id = Db::name('system_msg')->insertGetId([
												'receive'	=> $data['receive'],
												'title'		=> $data['title'],
												'content'	=> serialize($data['content']),
												'send_time'	=> time()
												]);
			//根据推送方式向用户表中添加信息
			$back = ['code'=>0,'msg'=>'推送失败'];
			$uid = get_uid();
			if($id > 0){
				if($data['receive_type'] == 1){
					//推送给个人
					$res = Db::name('member_msg')->insert([
														'receive_uid'	=> $data['receive_id'],
														'send_uid'		=> $uid,
														'content_id'	=> $id,
														'status'		=> 0
														]);
					if($res > 0){
						$back = ['code'=>1,'msg'=>'推送成功'];
					}
				}elseif($data['receive_type'] == 2){
					//推送给一个群组下所用会员
					$member_list = Db::name('member')->field('id')->where('group_id',$data['receive_id'])->where('closed',0)->select();
					$data_arr = [];
					foreach($member_list as $k=>$v){
						$data_arr[] = ['receive_uid'=>$v['id'],'send_uid'=>$uid,'content_id'=>$id,'status'=> 0];
					}
					$res = Db::name('member_msg')->insertAll($data_arr);
					if($res > 0){
						$back = ['code'=>1,'msg'=>'推送成功'];
					}
				}
			}
			
			echo json_encode($back);
		}else{
			//获取会员分组
			$user_group = Db::name('member_group')->where('status',1)->field('id,group_name')->select();
			$this->assign('user_group',json_encode($user_group));
			return $this->fetch();
		}
	}
	//删除
	public function del_msg(){
		$id = input('post.id');
		echo Db::name('system_msg')->where('id',$id)->delete();
	}

	//搜索会员
	public function search_member(){
		$keywords = input("post.keywords");
		$res = Db::name('member')->field('id,nickname')->where('account|nickname','like','%'.$keywords.'%')->where('closed',0)->limit(10)->select();
		echo json_encode($res);
	}


	public function bill(){
        $id   = input('id',4);
        $act   = input('act',-1);
        $keywords   = input('keywords','');
        $map=[];
        if($act>=0){
            $id = $act;
        }
        if($id != 4){
            $map['think_yang_bill.status']=$id;
        }
        if($keywords != ''){
            $map['think_yang_bill.username']= ['like',"%" . $keywords . "%"];
        }
        $this->assign('act',$id);
        $this->assign('id',$id);
        $this->assign('keywords',$keywords);



        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('yang_bill')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $Withdraw=new YangBill();
        $lists =$Withdraw->field('think_yang_bill.*,think_member.account')->join('think_member','think_member.id=think_yang_bill.uid','left')->where($map)->page($Nowpage, $limits)->order('id desc')->select();

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        if(input('get.page')){
            return json($lists);
        }

        return $this->fetch();
    }

    public function dakuan($id){
       $info = Db::name('yang_bill')->find($id);
       if($info['status'] != 1){
           return ['status'=>0,'msg'=>'该状态不能操作'];
       }
       if($info['exchange_type']==1){
         //给用户加积分  前提是积分是1：1的
           $users  =  new Users();
          if( $users->userIncIntegral($info['uid'],$info['true_money'])){
              //加记录
              IntegralLog::operate($info['uid'],$info['true_money'],31,1,'羊到期兑换',$id);
          }
           if( Db::name('yang_bill')->where(['id'=>$id])->update(['status'=>2,'update_time'=>time()])){
               //改变羊的状态 变成 已完成
               Db::name('yang_yang')->where(['id'=>$info['yang_id']])->update(['status'=>3]);
               return ['status'=>1,'msg'=>'操作成功'];
           }
       }elseif($info['exchange_type']==2){
           //给用户的订单 直接变成付款
           $OrderCommon = new \app\common\model\Order();
           Db::name('order')->where('order_id', $info['order_id'])->update(['pay_status'=>1]);
           $OrderCommon->logOrder($info['order_id'], '管理员操作', '付款成功', 0);

           if( Db::name('yang_bill')->where(['id'=>$id])->update(['status'=>2,'update_time'=>time()])){
               //改变羊的状态 变成 已完成
               Db::name('yang_yang')->where(['id'=>$info['yang_id']])->update(['status'=>3]);
               return ['status'=>1,'msg'=>'操作成功'];
           }
       }
    }
    public function jujue_dakuan($id){
        $info = Db::name('yang_bill')->find($id);
        if($info['status'] != 1){
            return ['status'=>0,'msg'=>'该状态不能操作'];
        }
        //将订单改成 已关闭
        if($info['exchange_type']==2){
            $orderModel = new \app\common\model\Order();
            $orderModel->cancel_order($info['uid'], $info['order_id'], '管理审核不通过');
        }
        if( Db::name('yang_bill')->where(['id'=>$id])->update(['status'=>3,'update_time'=>time()])){
            //改变羊的状态 变成 已完成
            Db::name('yang_yang')->where(['id'=>$info['yang_id']])->update(['is_submit'=>0]);
            return ['status'=>1,'msg'=>'操作成功'];
        }

    }

    public function export()
    {

        $key = input('key');
        $map['closed'] = 0;//0未删除，1已删除
        if($key&&$key!=="")
        {
            $map['account|nickname|mobile'] = ['like',"%" . $key . "%"];
        }
        $member = new MemberModel();

        $lists = $member->field('think_member.*,group_name')->join('think_member_group', 'think_member.group_id = think_member_group.id')
            ->where($map)->order('id desc')->select();;
        Loader::import('.PHPExcel.PHPExcel');
        Loader::import('.PHPExcel.PHPExcel.IOFactory');
        $date = date("Y_m_d",time());
        $phpexcel= new \PHPExcel();
        $filename=str_replace('.xls', '', '用户列表'.$date).'.xls';
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");

        $phpexcel->getActiveSheet()->setCellValue('A1','ID');
        $phpexcel->getActiveSheet()->setCellValue('B1','账号');
        $phpexcel->getActiveSheet()->setCellValue('C1','推荐人id');
        $phpexcel->getActiveSheet()->setCellValue('D1','推荐人');
        $phpexcel->getActiveSheet()->setCellValue('E1','羊币');
        $phpexcel->getActiveSheet()->setCellValue('F1','余额');
        $phpexcel->getActiveSheet()->setCellValue('G1','团队认养');
        $phpexcel->getActiveSheet()->setCellValue('H1','总认养');
        $phpexcel->getActiveSheet()->setCellValue('I1','现认养');
        $phpexcel->getActiveSheet()->setCellValue('J1','登录次数');
        $phpexcel->getActiveSheet()->setCellValue('K1','状态');
        $phpexcel->getActiveSheet()->setCellValue('L1','注册时间');

       /* $objActSheet = $phpexcel->getActiveSheet();
        $objActSheet->getColumnDimension('A')->setWidth(10);
        $objActSheet->getColumnDimension('B')->setWidth(15);
        $objActSheet->getColumnDimension('C')->setWidth(15);
        $objActSheet->getColumnDimension('D')->setWidth(15);
        $objActSheet->getColumnDimension('E')->setWidth(30);
        $objActSheet->getColumnDimension('F')->setWidth(45);

        $objActSheet->getStyle('F')->getAlignment()->setWrapText(true);*/
        $i=2;
        foreach ($lists as $vv){

            $phpexcel->getActiveSheet()->setCellValue('A' . $i,$vv['id']);
            $phpexcel->getActiveSheet()->setCellValue('B' . $i,$vv['account']);
            $phpexcel->getActiveSheet()->setCellValue('C' . $i,$vv['tuid']);
            $phpexcel->getActiveSheet()->setCellValue('D' . $i,$vv['tusername']??'无推荐人');
            $phpexcel->getActiveSheet()->setCellValue('E' . $i,$vv['integral']);
            $phpexcel->getActiveSheet()->setCellValue('F' . $i,$vv['money']);
            $phpexcel->getActiveSheet()->setCellValue('G' . $i,$vv['tyang_count']);
            $phpexcel->getActiveSheet()->setCellValue('H' . $i,$vv['yang_count']);
            $phpexcel->getActiveSheet()->setCellValue('I' . $i,$vv['yang_ing']);
            $phpexcel->getActiveSheet()->setCellValue('J' . $i,$vv['login_num']);
            $phpexcel->getActiveSheet()->setCellValue('K' . $i,$vv['status']?'开启':'禁用');
            $phpexcel->getActiveSheet()->setCellValue('L' . $i,$vv['create_time']);
            $i++;
        }

        $phpexcel->getActiveSheet()->setTitle('Sheet1');
        $phpexcel->setActiveSheetIndex(0);

        $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$filename");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $aa=new \IOFactory();
        $objwriter = $aa::createWriter($phpexcel, 'Excel5');
        $objwriter->save('php://output');
    }
	
	/*************************************************************************************************************/
    /***************************************************提现管理************************************************/
    /*************************************************************************************************************/
	//提现申请列表
    public function cash_list(){
        $data = input('get.');
        //组装筛选条件
        $map = [];
        $map['lc.status'] = ['<>','4'];
        $data['start_time'] = $data['start_time']??'';
        $data['end_time'] = $data['end_time']??'';
        $data['start_time2'] = $data['start_time2']??'';
        $data['end_time2'] = $data['end_time2']??'';
        $data['money'] = $data['money']??'';
        $data['status'] = $data['status']??'all';
        $data['keywords'] = $data['keywords']??'';
        $data['id'] = $data['id']??0;
        if($data['money']){
            $money_limit = explode('-',$data['money']);
            if(count($money_limit) == 2){
                $map['lc.money'] = ['between',[$money_limit[0],$money_limit[1]]];
            }
        }
        if($data['start_time'] && $data['end_time']){
            $map['lc.create_time'] = ['between',[strtotime($data['start_time']),strtotime($data['end_time'])]];
        }else{
            if($data['start_time']){
                $map['lc.create_time'] = ['>',strtotime($data['start_time'])];
            }
            if($data['end_time']){
                $map['lc.create_time'] = ['<',strtotime($data['end_time'])];
            }
        }
        if($data['start_time2'] && $data['end_time2']){
            $map['lc.update_time'] = ['between',[strtotime($data['start_time2']),strtotime($data['end_time2'])]];
        }else{
            if($data['start_time2']){
                $map['lc.update_time'] = ['>',strtotime($data['start_time2'])];
            }
            if($data['end_time2']){
                $map['lc.update_time'] = ['<',strtotime($data['end_time2'])];
            }
        }
        if($data['status'] !='all'){
            $map['lc.status'] = $data['status'];
        }
        if($data['keywords']){
            $map['m.account|m.nickname|m.mobile'] = ['like','%'.$data['keywords'].'%'];
        }
        if($data['id'] > 0){
            $map['m.id'] = $data['id'];
        }
        $list = Db::name('log_cash')->alias('lc')
                ->join('think_member m','lc.uid=m.id','left')
                ->field('lc.*,m.account as user_account,m.mobile,m.nickname')
                ->where($map)
                ->order('create_time desc')
                ->paginate(15, false, ['query' => $data]);
        //获取指定页码是的路径
        $go_url_param = '';
        foreach($data as $k=>$v){
            $go_url_param .= $go_url_param?'&'.$k.'='.$v:$k.'='.$v;
        }
        $go_url = '/admin/member/cash_list.html?'.$go_url_param;

        return view('', ['list' => $list,  'data' => $data,'go_url'=>$go_url]);
    }
    //提现申请删除
    public function delete_cash(){
        $param = input('post.');
        if(isset($param['del_id']) && !empty($param['del_id'])){
            Db::name('log_cash')->where(['id'=>['in',$param['del_id']]])->delete();
            writelog($this->uid,$this->username,'删除提现申请');
        }
        $this->success('操作成功');
    }
    
    //提现数据导出
    public function cash_export(){
        $data = input('get.');
        //组装筛选条件
        $map = [];
        $map['lc.status'] = ['<>','4'];
        $data['start_time'] = $data['start_time']??'';
        $data['end_time'] = $data['end_time']??'';
        $data['start_time2'] = $data['start_time2']??'';
        $data['end_time2'] = $data['end_time2']??'';
        $data['money'] = $data['money']??'';
        $data['status'] = $data['status']??'all';
        $data['keywords'] = $data['keywords']??'';
        $data['id'] = $data['id']??0;
        if($data['money']){
            $money_limit = explode('-',$data['money']);
            if(count($money_limit) == 2){
                $map['lc.money'] = ['between',[$money_limit[0],$money_limit[1]]];
            }
        }
        if($data['start_time2'] && $data['end_time2']){
            $map['lc.update_time'] = ['between',[strtotime($data['start_time2']),strtotime($data['end_time2'])]];
        }else{
            if($data['start_time2']){
                $map['lc.update_time'] = ['>',strtotime($data['start_time2'])];
            }
            if($data['end_time2']){
                $map['lc.update_time'] = ['<',strtotime($data['end_time2'])];
            }
        }
        if($data['start_time'] && $data['end_time']){
            $map['lc.create_time'] = ['between',[strtotime($data['start_time']),strtotime($data['end_time'])]];
        }else{
            if($data['start_time']){
                $map['lc.create_time'] = ['>',strtotime($data['start_time'])];
            }
            if($data['end_time']){
                $map['lc.create_time'] = ['<',strtotime($data['end_time'])];
            }
        }
        if($data['status'] !='all'){
            $map['lc.status'] = $data['status'];
        }
        if($data['keywords']){
            $map['m.account|m.mobile|m.nickname'] = ['like','%'.$data['keywords'].'%'];
        }
        if($data['id'] > 0){
            $map['m.id'] = $data['id'];
        }
        $list = Db::name('log_cash')->alias('lc')
            ->join('think_member m','lc.uid=m.id','left')
            ->field('lc.*,m.account as user_account,m.nickname')
            ->where($map)
            ->order('create_time asc')
            ->select();
        writelog($this->uid,$this->username,'导出提现数据');
        Loader::import('.PHPExcel.PHPExcel');
        Loader::import('.PHPExcel.PHPExcel.IOFactory');
        $phpexcel= new \PHPExcel();
        $filename=str_replace('.xls', '', '提现申请记录').'.xls';
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        // 
        $phpexcel->getActiveSheet()->setCellValue('A1','提现编号');
        $phpexcel->getActiveSheet()->setCellValue('B1','会员编号');
        $phpexcel->getActiveSheet()->setCellValue('C1','会员账户');
        $phpexcel->getActiveSheet()->setCellValue('D1','银行卡号');
        $phpexcel->getActiveSheet()->setCellValue('E1','持卡人');
        $phpexcel->getActiveSheet()->setCellValue('F1','开户银行');
        $phpexcel->getActiveSheet()->setCellValue('G1','开户支行');
        $phpexcel->getActiveSheet()->setCellValue('H1','预留电话');
        $phpexcel->getActiveSheet()->setCellValue('I1','开户行归属地');
        $phpexcel->getActiveSheet()->setCellValue('J1','银行联行号');
        $phpexcel->getActiveSheet()->setCellValue('K1','提现金额');
        $phpexcel->getActiveSheet()->setCellValue('L1','手续费');
        $phpexcel->getActiveSheet()->setCellValue('M1','服务费');
        $phpexcel->getActiveSheet()->setCellValue('N1','实得金额');
        $phpexcel->getActiveSheet()->setCellValue('O1','账户结余');
        $phpexcel->getActiveSheet()->setCellValue('P1','申请日期');
        $phpexcel->getActiveSheet()->setCellValue('Q1','审核日期');
        $phpexcel->getActiveSheet()->setCellValue('R1','审核状态');
        $phpexcel->getActiveSheet()->setCellValue('S1','昵称');
        
        $objActSheet = $phpexcel->getActiveSheet();
        $objActSheet->getColumnDimension('A')->setWidth(10);
        $objActSheet->getColumnDimension('B')->setWidth(20);
        $objActSheet->getColumnDimension('C')->setWidth(20);
        $objActSheet->getColumnDimension('D')->setWidth(20);
        $objActSheet->getColumnDimension('E')->setWidth(20);
        $objActSheet->getColumnDimension('F')->setWidth(20);
        $objActSheet->getColumnDimension('G')->setWidth(15);
        $objActSheet->getColumnDimension('H')->setWidth(45);

        $i=2;
        $status_arr = [
            0   => '待审核',
            1   => '转账中',
            2   => '已提现',
            3   => '已拒绝',
        ];
        foreach ($list as $vv){
            //$account_type_name = $vv['account_type']==2?'支付宝':'微信';
            $create_time = $vv['create_time']?date('Y-m-d H:i:s',$vv['create_time']):'--';
            $update_time = $vv['update_time']?date('Y-m-d H:i:s',$vv['update_time']):'--';

            $phpexcel->getActiveSheet()->setCellValue('A' . $i,$vv['id']);
            $phpexcel->getActiveSheet()->setCellValue('B' . $i,$vv['uid']);
            $phpexcel->getActiveSheet()->setCellValue('C' . $i,$vv['user_account']);
            $phpexcel->getActiveSheet()->setCellValue('D' . $i,' '.$vv['account']);
            $phpexcel->getActiveSheet()->setCellValue('E' . $i,$vv['name']);
            $phpexcel->getActiveSheet()->setCellValue('F' . $i,$vv['bank']);
            $phpexcel->getActiveSheet()->setCellValue('G' . $i,$vv['bank_son']);
            $phpexcel->getActiveSheet()->setCellValue('H' . $i,$vv['mobile']);
            $phpexcel->getActiveSheet()->setCellValue('I' . $i,$vv['bank_local']);
            $phpexcel->getActiveSheet()->setCellValue('J' . $i,$vv['bank_number']);
            $phpexcel->getActiveSheet()->setCellValue('K' . $i,$vv['money']);
            $phpexcel->getActiveSheet()->setCellValue('L' . $i,$vv['shouxu_cost']);
            $phpexcel->getActiveSheet()->setCellValue('M' . $i,$vv['fuwu_cost']);
            $phpexcel->getActiveSheet()->setCellValue('N' . $i,$vv['get_money']);
            $phpexcel->getActiveSheet()->setCellValue('O' . $i,$vv['balance']);
            $phpexcel->getActiveSheet()->setCellValue('P' . $i,$create_time);
            $phpexcel->getActiveSheet()->setCellValue('Q' . $i,$update_time);
            $phpexcel->getActiveSheet()->setCellValue('R' . $i,$status_arr[$vv['status']]);
            $phpexcel->getActiveSheet()->setCellValue('S' . $i,$vv['nickname']);
            $i++;
        }

        $phpexcel->getActiveSheet()->setTitle('Sheet1');
        $phpexcel->setActiveSheetIndex(0);

        $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$filename");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $aa=new \IOFactory();
        $objwriter = $aa::createWriter($phpexcel, 'Excel5');
        $objwriter->save('php://output');
    }
    //提现申请操作
    /* public function cash_act(){
        $data = input('post.');
        if($data['type'] == 1){
            $status = 1;
            if(empty($data['id_arr'])){
                echo json_encode(['status'=>0,'msg'=>'操作失败']);exit;
            }
            $log_cash = Db::name('log_cash')->where(['status'=>0,'id'=>['in',$data['id_arr']]])->select();
            //此处采用逐条修改提现记录的方法，避免某个用户打款出错后，信息混乱
            //print_r($log_cash);exit;
            $payment = new Payment();
            foreach($log_cash as $v){
                Db::startTrans();
                try {
                    //修改提现记录状态
                    DB::name('log_cash')->where('id',$v['id'])->update(['status'=>$status,'msg'=>'转账申请已提交','update_time'=>time(),'admin'=>$this->username]);
                    //减用户余额
                    Db::name('member')->where('id',$v['uid'])->setDec('money',$v['money']);
                    //向收益表中插入记录
                    Db::name('log_income')->insert(['uid'=>$v['uid'],'money'=>-$v['money'],'type'=>10,'create_time'=>time()]);
                    //发起打款程序
                    if($v['account_type'] ==2){
                        $pay_res = $payment->ali_transfer($v['account'],$v['uid'],'','tx'.$v['id'], $v['money'],['method' => 'cash', 'param' => ['id' => $v['id'], 'param' => 2]]);
                        if($pay_res !== true){
                           throw new \Exception($pay_res);
                        }
                    }elseif($v['account_type'] ==3){
                        //微信转账需要获取用户的openid
                        $openid = Db::name('member')->where('id',$v['uid'])->value('openid');
                        $pay_res = $payment->wx_transfer($openid,$v['uid'],'','tx'.$v['id'], $v['money'],['method' => 'cash', 'param' => ['id' => $v['id'], 'param' => 3]]);
                        if($pay_res !== true){
                            throw new \Exception($pay_res);
                        }
                    }

                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    Db::name('log_cash')->where('id',$v['id'])->update(['msg'=>'转账异常：'.$e->getMessage(),'update_time'=>time(),'admin'=>$this->username]);
                    //echo $e->getLine();
                    //echo $e->getFile();
                    //exit;
                }
            }
            $back = ['status' => 1, 'msg' => '操作成功'];
        }else {
            $status = 3;
            if (empty($data['admin_msg'])) {
                echo json_encode(['status' => 0, 'msg' => '请输入不通过原因说明']);
                exit;
            }
            if (DB::name('log_cash')->where(['status' => 0, 'id' => ['in', $data['id_arr']]])->update(['status' => $status,'update_time'=>time(),'admin'=>$this->username,'msg'=>$data['admin_msg']])) {
                $back = ['status' => 1, 'msg' => '操作成功'];
            } else {
                $back = ['status' => 0, 'msg' => '操作失败'];
            }
        }
        echo json_encode($back);
    } */
	//提现申请操作
    public function cash_act(){
        $data = input('post.');
        if($data['type'] == 1){
            $status = 2;
            if(empty($data['id_arr'])){
                echo json_encode(['status'=>0,'msg'=>'操作失败']);exit;
            }
            $log_cash = Db::name('log_cash')->where(['status'=>0,'id'=>['in',$data['id_arr']]])->select();
            //此处采用逐条修改提现记录的方法，避免某个用户打款出错后，信息混乱
            //print_r($log_cash);exit;
            $payment = new Payment();
            foreach($log_cash as $v){
                Db::startTrans();
                try {
                    //修改提现记录状态
                    DB::name('log_cash')->where('id',$v['id'])->update(['status'=>$status,'msg'=>'申请已通过','update_time'=>time(),'admin'=>$this->username]);
                    //向收益表中插入记录
                    //Db::name('log_income')->insert(['uid'=>$v['uid'],'money'=>-$v['money'],'type'=>10,'create_time'=>time()]);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    Db::name('log_cash')->where('id',$v['id'])->update(['msg'=>'操作异常：'.$e->getMessage(),'update_time'=>time(),'admin'=>$this->username]);
                }
            }
            $desc = '提现申请审核通过';
            $back = ['status' => 1, 'msg' => '操作成功'];
        }else {
            $status = 3;
            if (empty($data['admin_msg'])) {
                echo json_encode(['status' => 0, 'msg' => '请输入不通过原因说明']);
                exit;
            }
            if(empty($data['id_arr'])){
                echo json_encode(['status'=>0,'msg'=>'操作失败']);exit;
            }
            $log_cash = Db::name('log_cash')->where(['status'=>0,'id'=>['in',$data['id_arr']]])->select();
            //此处采用逐条修改提现记录的方法，避免某个用户打款出错后，信息混乱
            //print_r($log_cash);exit;
            $payment = new Payment();
            foreach($log_cash as $v){
                Db::startTrans();
                try {
                    //修改提现记录状态
                    DB::name('log_cash')->where('id',$v['id'])->update(['status'=>$status,'msg'=>$data['admin_msg'],'update_time'=>time(),'admin'=>$this->username]);
                    //把提现金额返还给用户
                    Db::name('member')->where('id',$v['uid'])->setInc('money',$v['money']);
                    //向收益表中插入记录
                    Db::name('log_income')->insert(['uid'=>$v['uid'],'money'=>$v['money'],'type'=>14,'create_time'=>time()]);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    Db::name('log_cash')->where('id',$v['id'])->update(['msg'=>'操作异常：'.$e->getMessage(),'update_time'=>time(),'admin'=>$this->username]);
                }
            }
            $desc = '提现申请审核不通过';
            $back = ['status' => 1, 'msg' => '操作成功'];
        }
        writelog($this->uid,$this->username,$desc);
        echo json_encode($back);
    }
    //*********************************************实名审核*********************************************//
    public function real_info(){
        $map=[];
        $keywords = input('param.keywords','');
        if(!empty($keywords)){
            $map['m.account|m.mobile'] = ['like','%'.$keywords.'%'];
        }
        $model = new MemberInfoModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $model->allcount($map);//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = $model->getListByWhere($map, $Nowpage, $limits);

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('keywords', $keywords); //总页数
        if(input('get.page'))
        {
            return json($lists);
        }

        return $this->fetch();
    }
    public function real_act(){
        $data = input('post.');
        if(empty($data['id_arr'])){
            echo json_encode(['status'=>0,'msg'=>'操作失败']);exit;
        }

        if($data['status'] == 1){
            $status = 1;
            $msg = !empty($data['admin_msg'])?$data['admin_msg']:'审核通过';
            foreach($data['id_arr'] as $v){
                $err = '';
                Db::startTrans();
                try {
                    //修改申请记录状态
                    $res1 = DB::name('member_info')->where('uid',$v)->update(['status'=>$status,'msg'=>$msg,'update_time'=>time()]);
                    //修改用户实名状态
                    $res2 = DB::name('member')->where('id',$v)->update(['real_info_ok'=>$status]);
                    if($res1 && $res2!==false){
                        Db::commit();
                    }else{
                        Db::rollback();
                        $err = '操作失败'.$res1.'--'.$res2;
                    }
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $err = $e->getMessage();
                }
                if($err){
                    DB::name('member_info')->where('uid',$v)->update(['msg'=>$err,'update_time'=>time()]);
                }
            }
            $back = ['status' => 1, 'msg' => '操作成功'];
        }else {
            $status = 2;
            if (empty($data['admin_msg'])) {
                echo json_encode(['status' => 0, 'msg' => '请输入不通过原因说明']);
                exit;
            }
            if(empty($data['id_arr'])){
                echo json_encode(['status'=>0,'msg'=>'操作失败']);exit;
            }
            $res = Db::name('member_info')->where(['status'=>0,'uid'=>['in',$data['id_arr']]])->update(['status'=>$status,'msg'=>$data['admin_msg'],'update_time'=>time()]);
            if($res){
                $back = ['status' => 1, 'msg' => '操作成功'];
            }else{
                $back = ['status' => 0, 'msg' => '操作成功'];
            }
        }
        writelog($this->uid,$this->username,'实名审核操作，'.$data['admin_msg']);
        echo json_encode($back);
    }
    //删除提现申请
    public function del_real_act($id){
        Db::startTrans();
        try {
            //删除申请记录
            $res1 = DB::name('member_info')->where('uid',$id)->delete();
            //修改用户实名状态
            $res2 = DB::name('member')->where('id',$id)->update(['real_info_ok'=>0]);
            if(!$res1 || $res2 === false){
                throw new \Exception('操作失败'.$res1.$res2);
            }else{
                Db::commit();
                $back = ['status' => 1, 'msg' => '操作成功'];
            }
            writelog($this->uid,$this->username,'删除实名审核信息',1,'','',$id);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $back = ['status' => 0, 'msg' => $e->getMessage()];
        }
        echo json_encode($back);
    }
	
	
	
	
	
	
	
	
	
	
	
}