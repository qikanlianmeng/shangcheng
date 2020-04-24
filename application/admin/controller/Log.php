<?php

namespace app\admin\controller;
use app\admin\model\LogModel;
use think\Db;
use think\Loader;
//use com\IpLocation;
 
class Log extends Base
{

    /**
     * [operate_log 操作日志]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function operate_log()
    {

        $admin_id = input('param.admin_id',0);
        $start = input('param.start','');
        $end = input('param.end','');
        $user = input('param.user','');

        $map = [];
        if($admin_id > 0){
            $map['admin_id'] =  $admin_id;
        }
        if($start && $end){
            $map['add_time'] = ['between',[strtotime($start),strtotime($end)]];
        }else{
            if($start){
                $map['add_time'] = ['>',strtotime($start)];
            }
            if($end){
                $map['add_time'] = ['<',strtotime($end)];
            }
        }
        //print_r($map);exit;
        if($user){
            $map['m.id|mobile|nickname'] = ['like',"%" . $user . "%"];
        }
        //$page = input('get.page');var_dump($page);exit;
        $arr=Db::name("admin")->select(); //获取用户列表
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('log')->alias('l')->join('think_member m','l.user_id=m.id','left')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('log')->alias('l')
            ->join('think_member m','l.user_id=m.id','left')
            ->join('think_admin a','l.admin_id=a.id','left')
            ->field('l.*,m.mobile,nickname,groupid')->where($map)->page($Nowpage, $limits)->order('add_time desc')->select();
        //$Ip = new IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
        $group_info = Db::name('auth_group')->column('title','id');
        foreach($lists as $k=>$v){
            $lists[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            //$lists[$k]['ipaddr'] = $Ip->getlocation($lists[$k]['ip']);
            $lists[$k]['group'] = $group_info[$v['groupid']];
        }  
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('count', $count);
        $this->assign("search_user",$arr);
        $this->assign('admin_id', $admin_id);
        $this->assign('start', $start);
        $this->assign('end', $end);
        $this->assign('user', $user);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }
    //前端会员信息操作记录
    public function front_log()
    {
        $start = input('param.start','');
        $end = input('param.end','');
        $user = input('param.user','');
        $map = [];
        if($start && $end){
            $map['add_time'] = ['between',[strtotime($start),strtotime($end)]];
        }else{
            if($start){
                $map['add_time'] = ['>',strtotime($start)];
            }
            if($end){
                $map['add_time'] = ['<',strtotime($end)];
            }
        }
        //print_r($map);exit;
        if($user){
            $map['m.id|mobile|nickname'] = ['like',"%" . $user . "%"];
        }

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('log_front')->alias('f')->join('think_member m','f.uid=m.id','left')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('log_front')->alias('f')
            ->join('think_member m','f.uid=m.id','left')
            ->field('f.*,m.mobile,nickname')->where($map)->page($Nowpage, $limits)->order('add_time desc')->select();
        //$Ip = new IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
        foreach($lists as $k=>$v){
            $lists[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            //$lists[$k]['ipaddr'] = $Ip->getlocation($lists[$k]['ip']);
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);

        $this->assign('start', $start);
        $this->assign('end', $end);
        $this->assign('user', $user);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }
    public function export_admin_log()
    {

        $admin_id = input('param.admin_id',0);
        $start = input('param.start','');
        $end = input('param.end','');
        $user = input('param.user','');

        $map = [];
        if($admin_id > 0){
            $map['admin_id'] =  $admin_id;
        }
        if($start && $end){
            $map['add_time'] = ['between',[strtotime($start),strtotime($end)]];
        }else{
            if($start){
                $map['add_time'] = ['>',strtotime($start)];
            }
            if($end){
                $map['add_time'] = ['<',strtotime($end)];
            }
        }
        //print_r($map);exit;
        if($user){
            $map['m.id|mobile'] = ['like',"%" . $user . "%"];
        }

        $list = Db::name('log')->alias('l')
            ->join('think_member m','l.user_id=m.id','left')
            ->join('think_admin a','l.admin_id=a.id','left')
            ->field('l.*,m.mobile,groupid')->where($map)->order('add_time desc')->select();
        $group_info = Db::name('auth_group')->column('title','id');
        writelog($this->uid,$this->username,'导出管理员行为日志');
        Loader::import('.PHPExcel.PHPExcel');
        Loader::import('.PHPExcel.PHPExcel.IOFactory');
        $phpexcel= new \PHPExcel();
        $filename=str_replace('.xls', '', '管理员操作记录').'.xls';
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        //
        $phpexcel->getActiveSheet()->setCellValue('A1','操作编号');
        $phpexcel->getActiveSheet()->setCellValue('B1','管理员名称');
        $phpexcel->getActiveSheet()->setCellValue('C1','操作描述');
        $phpexcel->getActiveSheet()->setCellValue('D1','操作前数据');
        $phpexcel->getActiveSheet()->setCellValue('E1','操作后数据');
        $phpexcel->getActiveSheet()->setCellValue('F1','被操作会员编号');
        $phpexcel->getActiveSheet()->setCellValue('G1','被操作会员手机号');
        $phpexcel->getActiveSheet()->setCellValue('H1','被操作会员昵称');
        $phpexcel->getActiveSheet()->setCellValue('I1','操作时间');
        $phpexcel->getActiveSheet()->setCellValue('J1','操作ip');
        $phpexcel->getActiveSheet()->setCellValue('K1','管理员角色');
        $objActSheet = $phpexcel->getActiveSheet();

        $objActSheet->getColumnDimension('C')->setWidth(30);
        $objActSheet->getColumnDimension('D')->setWidth(30);
        $objActSheet->getColumnDimension('E')->setWidth(30);
        $objActSheet->getColumnDimension('G')->setWidth(20);
        $objActSheet->getColumnDimension('H')->setWidth(20);
        $objActSheet->getColumnDimension('I')->setWidth(20);

        $i=2;
        foreach ($list as $vv){
            $user_mobile=$user_id=$nickname='';
            if($vv['user_id'] > 0){
                $user_mobile = $vv['mobile'];
                $user_id = $vv['user_id'];
                $nickname = $vv['nickname'];
            }

            $phpexcel->getActiveSheet()->setCellValue('A' . $i,$vv['log_id']);
            $phpexcel->getActiveSheet()->setCellValue('B' . $i,$vv['admin_name']);
            $phpexcel->getActiveSheet()->setCellValue('C' . $i,$vv['description']);
            $phpexcel->getActiveSheet()->setCellValue('D' . $i,$vv['before']);
            $phpexcel->getActiveSheet()->setCellValue('E' . $i,$vv['after']);
            $phpexcel->getActiveSheet()->setCellValue('F' . $i,$user_id);
            $phpexcel->getActiveSheet()->setCellValue('G' . $i,''.$user_mobile);
            $phpexcel->getActiveSheet()->setCellValue('H' . $i,$nickname);
            $phpexcel->getActiveSheet()->setCellValue('I' . $i,date('Y-m-d H:i:s',$vv['add_time']));
            $phpexcel->getActiveSheet()->setCellValue('J' . $i,$vv['ip']);
            $phpexcel->getActiveSheet()->setCellValue('K' . $i,$group_info[$vv['groupid']]);
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
    public function export_front_log()
    {

        $start = input('param.start','');
        $end = input('param.end','');
        $user = input('param.user','');
        $map = [];
        if($start && $end){
            $map['add_time'] = ['between',[strtotime($start),strtotime($end)]];
        }else{
            if($start){
                $map['add_time'] = ['>',strtotime($start)];
            }
            if($end){
                $map['add_time'] = ['<',strtotime($end)];
            }
        }
        //print_r($map);exit;
        if($user){
            $map['m.id|mobile|nickname'] = ['like',"%" . $user . "%"];
        }

        $list = Db::name('log_front')->alias('f')
            ->join('think_member m','f.uid=m.id','left')
            ->field('f.*,m.mobile,nickname')->where($map)->order('add_time desc')->select();
        writelog($this->uid,$this->username,'导出会员行为日志');
        Loader::import('.PHPExcel.PHPExcel');
        Loader::import('.PHPExcel.PHPExcel.IOFactory');
        $phpexcel= new \PHPExcel();
        $filename=str_replace('.xls', '', '会员操作记录').'.xls';
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        //
        $phpexcel->getActiveSheet()->setCellValue('A1','操作编号');
        $phpexcel->getActiveSheet()->setCellValue('B1','会员编号');
        $phpexcel->getActiveSheet()->setCellValue('C1','会员手机号');
        $phpexcel->getActiveSheet()->setCellValue('D1','会员昵称');
        $phpexcel->getActiveSheet()->setCellValue('E1','操作描述');
        $phpexcel->getActiveSheet()->setCellValue('F1','操作前数据');
        $phpexcel->getActiveSheet()->setCellValue('G1','操作后数据');
        $phpexcel->getActiveSheet()->setCellValue('H1','操作时间');
        $phpexcel->getActiveSheet()->setCellValue('I1','操作ip');

        $objActSheet = $phpexcel->getActiveSheet();

        $objActSheet->getColumnDimension('C')->setWidth(30);
        $objActSheet->getColumnDimension('D')->setWidth(30);
        $objActSheet->getColumnDimension('E')->setWidth(30);
        $objActSheet->getColumnDimension('F')->setWidth(30);
        $objActSheet->getColumnDimension('G')->setWidth(30);
        $objActSheet->getColumnDimension('H')->setWidth(20);

        $i=2;
        foreach ($list as $vv){

            $phpexcel->getActiveSheet()->setCellValue('A' . $i,$vv['id']);
            $phpexcel->getActiveSheet()->setCellValue('B' . $i,$vv['uid']);
            $phpexcel->getActiveSheet()->setCellValue('C' . $i,''.$vv['mobile']);
            $phpexcel->getActiveSheet()->setCellValue('D' . $i,$vv['nickname']);
            $phpexcel->getActiveSheet()->setCellValue('E' . $i,$vv['description']);
            $phpexcel->getActiveSheet()->setCellValue('F' . $i,$vv['before']);
            $phpexcel->getActiveSheet()->setCellValue('G' . $i,$vv['after']);
            $phpexcel->getActiveSheet()->setCellValue('H' . $i,date('Y-m-d H:i:s',$vv['add_time']));
            $phpexcel->getActiveSheet()->setCellValue('I' . $i,$vv['ip']);
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
     * [del_log 删除日志]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del_log()
    {
        $id = input('param.id');
        $log = new LogModel();
        $flag = $log->delLog($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
    public function delete_operate(){
        $param = input('post.');
        if(isset($param['id']) && !empty($param['id'])){
            Db::name('log')->where(['log_id'=>['in',$param['id']]])->delete();
        }
        $this->success('操作成功');
    }
    public function delete_front(){
        $param = input('post.');
        if(isset($param['id']) && !empty($param['id'])){
            Db::name('log_front')->where(['id'=>['in',$param['id']]])->delete();
        }
        $this->success('操作成功');
    }
 
}