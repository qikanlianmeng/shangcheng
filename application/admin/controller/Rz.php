<?php

namespace app\admin\controller;

use app\admin\model\GoodsSpecItem;
use think\Db;
use think\Request;
use think\Loader;

class Rz extends Base
{
    protected $request;
    protected $backUrl;

    public function __construct(Request $request)
    {
        parent::__construct();
        header("Content-type:text/html;charset=utf-8");
        $this->request = $request;
        $this->backUrl = isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
        $this->assign("backUrl", $this->backUrl);
    }
    
    #列表
    public function rz()
    {
        $list = Db::name('rz')->order('start_time asc')->select();
        return view('', ['list' => $list]);
    }
    #添加
    public function addrz()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            if(Db::name('rz')->insert($data)){
                return json(['code' => 1, 'msg' => '添加成功']);
            }else{
                return json(['code' => 0, 'msg' => '添加失败']);
            }
        }
        return view('');
    }
    #编辑
    public function editrz($id=0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            if(Db::name('rz')->where('id',$data['id'])->update($data)){
                return json(['code' => 1, 'msg' => '编辑成功']);
            }else{
                return json(['code' => 0, 'msg' => '编辑失败']);
            }
        }
        $info = Db::name('rz')->where('id',$id)->find();
        if (!$info) {
            $this->error('查无此信息');
        }
        
        return view('', ['info' => $info]);
    }
    #删除
    public function delrz($id)
    {
        if (Db::name('rz_log')->where('rid',$id)->count()) {
            return json(['code' => 0, 'msg' => '该认购项目下有记录，不能删除']);
        }
        Db::name('rz')->where('id',$id)->delete();
        return json(['code' => 1, 'msg' => '删除成功']);
    }
    /*************************************************************************************************************/
    /***************************************************申请管理************************************************/
    /*************************************************************************************************************/
	//提现申请列表
    public function log_list(){
        $data = input('get.');
        //组装筛选条件
        $map = [];
        $data['start_time'] = $data['start_time']??'';
        $data['end_time'] = $data['end_time']??'';
        $data['status'] = $data['status']??'all';
        $data['keywords'] = $data['keywords']??'';
        $data['rid'] = $data['rid']??0;

        if($data['start_time'] && $data['end_time']){
            $map['rl.create_time'] = ['between',[strtotime($data['start_time']),strtotime($data['end_time'])]];
        }else{
            if($data['start_time']){
                $map['rl.create_time'] = ['>',strtotime($data['start_time'])];
            }
            if($data['end_time']){
                $map['rl.create_time'] = ['<',strtotime($data['end_time'])];
            }
        }
        if($data['rid']){
            $map['rl.rid'] = $data['rid'];
        }
        if($data['status'] !='all'){
            $map['rl.status'] = $data['status'];
        }
        if($data['keywords']){
            $map['m.account|m.nickname|m.mobile'] = ['like','%'.$data['keywords'].'%'];
        }

        $list = Db::name('rz_log')->alias('rl')
                ->join('think_member m','rl.uid=m.id','left')
                ->join('think_rz r','r.id=rl.rid','left')
                ->field('rl.*,m.account as user_account,m.mobile,m.nickname,r.title')
                ->where($map)
                ->order('rl.status asc,rl.create_time desc')
                ->paginate(15, false, ['query' => $data]);
        //获取指定页码是的路径
        $go_url_param = '';
        foreach($data as $k=>$v){
            $go_url_param .= $go_url_param?'&'.$k.'='.$v:$k.'='.$v;
        }
        $go_url = '/admin/rz/log_list.html?'.$go_url_param;
        //获得认购项目列表
        $rz = Db::name('rz')->select();

        return view('', ['list' => $list,  'data' => $data,'go_url'=>$go_url,'rz'=>$rz]);
    }
    //提现申请删除
    public function delete_log(){
        $param = input('post.');
        if(isset($param['del_id']) && !empty($param['del_id'])){
            Db::name('rz_log')->where(['id'=>['in',$param['del_id']]])->delete();
            writelog($this->uid,$this->username,'删除提现申请');
        }
        $this->success('操作成功');
    }
    
    //提现数据导出
    public function log_export(){
        $data = input('get.');
        //组装筛选条件
        $map = [];
        $data['start_time'] = $data['start_time']??'';
        $data['end_time'] = $data['end_time']??'';
        $data['status'] = $data['status']??'all';
        $data['keywords'] = $data['keywords']??'';
        $data['rid'] = $data['rid']??0;

        if($data['start_time'] && $data['end_time']){
            $map['rl.create_time'] = ['between',[strtotime($data['start_time']),strtotime($data['end_time'])]];
        }else{
            if($data['start_time']){
                $map['rl.create_time'] = ['>',strtotime($data['start_time'])];
            }
            if($data['end_time']){
                $map['rl.create_time'] = ['<',strtotime($data['end_time'])];
            }
        }
        if($data['rid']){
            $map['rl.rid'] = $data['rid'];
        }
        if($data['status'] !='all'){
            $map['rl.status'] = $data['status'];
        }
        if($data['keywords']){
            $map['m.account|m.nickname|m.mobile'] = ['like','%'.$data['keywords'].'%'];
        }

        $list = Db::name('rz_log')->alias('rl')
                ->join('think_member m','rl.uid=m.id','left')
                ->join('think_rz r','r.id=rl.rid','left')
                ->field('rl.*,m.account as user_account,m.mobile,m.nickname,r.title')
                ->where($map)
                ->order('rl.status asc,rl.create_time desc')
                ->select();
        writelog($this->uid,$this->username,'导出认购数据');
        Loader::import('.PHPExcel.PHPExcel');
        Loader::import('.PHPExcel.PHPExcel.IOFactory');
        $phpexcel= new \PHPExcel();
        $filename=str_replace('.xls', '', '认购申请记录').'.xls';
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        // 
        $phpexcel->getActiveSheet()->setCellValue('A1','申请编号');
        $phpexcel->getActiveSheet()->setCellValue('B1','会员编号');
        $phpexcel->getActiveSheet()->setCellValue('C1','会员账号');
        $phpexcel->getActiveSheet()->setCellValue('D1','会员昵称');
        $phpexcel->getActiveSheet()->setCellValue('E1','会员手机号');
        $phpexcel->getActiveSheet()->setCellValue('F1','认购项目');
        $phpexcel->getActiveSheet()->setCellValue('G1','认购单价');
        $phpexcel->getActiveSheet()->setCellValue('H1','认购数量');
        $phpexcel->getActiveSheet()->setCellValue('I1','认购总价');
        $phpexcel->getActiveSheet()->setCellValue('J1','审核状态');
        $phpexcel->getActiveSheet()->setCellValue('K1','申请时间');
        $phpexcel->getActiveSheet()->setCellValue('L1','审核备注');
        

        $i=2;
        $status_arr = [
            0   => '待审核',
            1   => '已审核',
            2   => '已拒绝',
        ];
        foreach ($list as $vv){
            //$account_type_name = $vv['account_type']==2?'支付宝':'微信';
            $create_time = $vv['create_time']?date('Y-m-d H:i:s',$vv['create_time']):'--';

            $phpexcel->getActiveSheet()->setCellValue('A' . $i,$vv['id']);
            $phpexcel->getActiveSheet()->setCellValue('B' . $i,$vv['uid']);
            $phpexcel->getActiveSheet()->setCellValue('C' . $i,' '.$vv['user_account']);
            $phpexcel->getActiveSheet()->setCellValue('D' . $i,$vv['nickname']);
            $phpexcel->getActiveSheet()->setCellValue('E' . $i,' '.$vv['mobile']);
            $phpexcel->getActiveSheet()->setCellValue('F' . $i,$vv['title']);
            $phpexcel->getActiveSheet()->setCellValue('G' . $i,$vv['price']);
            $phpexcel->getActiveSheet()->setCellValue('H' . $i,$vv['num']);
            $phpexcel->getActiveSheet()->setCellValue('I' . $i,$vv['total_price']);
            $phpexcel->getActiveSheet()->setCellValue('J' . $i,$status_arr[$vv['status']]);
            $phpexcel->getActiveSheet()->setCellValue('K' . $i,$create_time);
            $phpexcel->getActiveSheet()->setCellValue('L' . $i,$vv['msg']);
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
    public function log_act(){
        $data = input('post.');
        if($data['type'] == 1){
            $status = 1;
            if(empty($data['id_arr'])){
                echo json_encode(['status'=>0,'msg'=>'操作失败']);exit;
            }
            $log_cash = Db::name('rz_log')->where(['status'=>0,'id'=>['in',$data['id_arr']]])->select();
            
                Db::startTrans();
                try {
                    foreach($log_cash as $v){
                    //修改提现记录状态
                        DB::name('rz_log')->where('id',$v['id'])->update(['status'=>$status,'msg'=>'申请已通过']);
                    }
                    
                    // 提交事务
                    Db::commit();
                    $desc = '认购申请审核通过';
                    $back = ['status' => 1, 'msg' => '操作成功'];
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $desc = '认购申请审核异常';
                    $back = ['status' => 0, 'msg' => '操作成功'.$e->getMessage()];
                }
        }else {
            $status = 2;
            if (empty($data['admin_msg'])) {
                echo json_encode(['status' => 0, 'msg' => '请输入不通过原因说明']);
                exit;
            }
            if(empty($data['id_arr'])){
                echo json_encode(['status'=>0,'msg'=>'操作失败']);exit;
            }
            $log_cash = Db::name('rz_log')->where(['status'=>0,'id'=>['in',$data['id_arr']]])->select();
            //此处采用逐条修改提现记录的方法，避免某个用户打款出错后，信息混乱
            //print_r($log_cash);exit;

            foreach($log_cash as $v){
                Db::startTrans();
                try {
                    //修改提现记录状态
                    DB::name('rz_log')->where('id',$v['id'])->update(['status'=>$status,'msg'=>$data['admin_msg']]);
                    //把提现金额返还给用户
                    Db::name('member')->where('id',$v['uid'])->setInc('money',$v['total_price']);
                    //向收益表中插入记录
                    Db::name('log_income')->insert(['uid'=>$v['uid'],'money'=>$v['total_price'],'type'=>24,'create_time'=>time()]);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    Db::name('rz_log')->where('id',$v['id'])->update(['msg'=>'操作异常：'.$e->getMessage()]);
                }
            }
            $desc = '提现申请审核不通过';
            $back = ['status' => 1, 'msg' => '操作成功'];
        }
        writelog($this->uid,$this->username,$desc);
        echo json_encode($back);
    }
}
