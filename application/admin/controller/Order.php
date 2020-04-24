<?php
/**
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/15
 * Time: 9:07
 */

namespace app\admin\controller;

use think\Db;
use think\Request;
use app\admin\model\Order as OrderModel;
use think\Config;
use app\admin\model\RegionModel;
use app\admin\model\GoodsBrand;
use app\common\model\Goods;
use app\common\model\Order as CommonOrderModel;
use app\common\model\Member;
use think\Loader;
class Order extends Base
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
        Config::load(APP_PATH.'common/config.php');
        $ORDER_STATUS    = Config::get('ORDER_STATUS');
        $PAY_STATUS      = Config::get('PAY_STATUS');
        $SHIPPING_STATUS = Config::get('SHIPPING_STATUS');
        $this->assign(['ORDER_STATUS' => $ORDER_STATUS, 'PAY_STATUS' => $PAY_STATUS, 'SHIPPING_STATUS' => $SHIPPING_STATUS]);
    }
    public function order_dl(){
        return $this->order(1);
    }
    public function order_dy(){
        return $this->order(2);
    }
    public function order_zy(){
        return $this->order(3);
    }
    //已发货订单一键收货
    public function shouhuo(){
        $group = input('group');
        //echo $group;exit;
        $where = [
            'order_group'   => $group,
            'pay_status'    => 1,
            'order_status'  => 1,
            'shipping_status'=>1,
        ];
        $data = [
            'order_status'  => 2,
            'shipping_status'=>2,
            'confirm_time'  => time()
        ];
        $data2 = [
            'order_status'  => 2,
            'shipping_status'=>2,
            'pay_status'    => 1,
            'log_time'  => time(),
            'status_desc' => '收货成功',
            'action_note'   => '管理员收货',
            'action_user'   => $this->uid,
        ];
        $list = Db::name('order')->where($where)->select();
        foreach($list as $v){
            $data2['order_id'] = $v['order_id'];
            Db::name('order')->where('order_id',$v['order_id'])->update($data);
            Db::name('order_action')->insert($data2);
        }
        writelog($this->uid,$this->username,'已发货订单一键收货');
        return json(['code'=>'1','msg'=>'操作成功']);
    }
    public function delete_all_order(){
        $param = input('param.');
        //print_r($param);exit;
        if(isset($param['id']) && !empty($param['id'])){
            Db::name('order')->where(['order_id'=>['in',$param['id']]])->update(['deleted'=>$param['deleted']]);
            writelog($this->uid,$this->username,'删除订单');
        }
        $this->success('操作成功');
    }
    //订单列表
    //$group 订单类型：1代理，2代售，3自营
    public function order($order_group=0)
    {
        
        $data = input('param.');
        if(!in_array($order_group,[1,2,3])){
            $this->error('类型参数错误');
        }
        //组装筛选条件
        $map = ['order_group'=>$order_group];
        $map['deleted'] = ['in','0,1'];
        //排序条件
        //$order = 'order_id-0';//默认
        $where2 = '';

        if (count($data) > 1) {
            // !empty($data['start_time'])        && $data['start_time'] = strtotime($data['start_time']);
            // !empty($data['end_time'])          && $data['end_time'] = strtotime($data['end_time']);
            //!empty($data['start_time'])        && $map['add_time'] = ['>=', $data['start_time']];
            !empty($data['start_time']) && $where2 = 'add_time >= '.strtotime($data['start_time']);
            if(!empty($data['end_time'])){
                if(!empty($where2)){
                    $where2 .= ' and add_time <='.strtotime($data['end_time']);
                }else{
                    $where2 = 'add_time <='.strtotime($data['end_time']);
                }
            }
            if(!empty($data['start_time2'])){
                $where2 .= $where2?' and pay_time >='.strtotime($data['start_time2']):'pay_time >='.strtotime($data['start_time2']);
            }
            if(!empty($data['end_time2'])){
                $where2 .= $where2?' and pay_time <='.strtotime($data['end_time2']):'pay_time <='.strtotime($data['end_time2']);
            }
            
            //!empty($data['end_time'])          && $map['add_time'] = ['<=', $data['end_time']];
            //if(isset($data['order_group']) && $data['order_group']>0)   $map['order_group']=$data['order_group'];
            isset($data['pay_status']) && $data['pay_status'] !== '' && $map['pay_status'] = $data['pay_status'];

            !empty($data['pay_code'])  && $map['pay_code'] = $data['pay_code'];

            isset($data['shipping_status']) && $data['shipping_status'] !== ''  && $map['shipping_status'] = $data['shipping_status'];

            isset($data['order_status'])  && $data['order_status'] !== ''    && $map['order_status'] = $data['order_status'];

            !empty($data['keywords'])          && $map['order_sn|account|m.mobile|consignee|think_order.mobile|out_trade_no'] = ['like', '%' . $data['keywords'] . '%'];
            //排序条件
            if (isset($data['order']) && !empty($data['order'])) {
                $order = $data['order'];
            }
            if(isset($data['id']) && $data['id']>0){
                $map['think_order.user_id'] = $data['id'];
            }
        }else{
            $data['start_time'] = '';
            $data['end_time'] = '';
            $data['start_time2'] = '';
            $data['end_time2'] = '';
        }
        //$data['order_group'] = $group;
        //排序条件
        //$order = explode('-', $order);
        //$orderStr = $order[1] == 1 ? "$order[0] asc" : "$order[0] desc";
        //print_r($map);exit;
        $pay_log_sql = Db::name('pay_log')->where('status',1)->buildSql();
        /*$list = OrderModel::order('order_id desc')
            ->join('think_member m','think_order.user_id=m.id','left')
            ->join([$pay_log_sql=>'pl'],'think_order.order_sn=pl.order_number','left')
            ->field('think_order.*,m.account,m.mobile as user_mobile,out_trade_no')
            ->where($map)->where($where2)->paginate(10, false, ['query' => $data]);*/
        $list = OrderModel::order('order_id desc')
            ->join('think_member m','think_order.user_id=m.id','left')
            ->join('think_pay_log pl','think_order.order_sn=pl.order_number and pl.status=1','left')
            ->field('think_order.*,m.account,m.mobile as user_mobile,out_trade_no')
            ->where($map)->where($where2)->paginate(10, false, ['query' => $data]);
        
        //支付方式
        $pay_name = Db::name('pay_config')->where('status', 1)->order('id asc')->select();
        //print_r($pay_name);exit;
        return view('order', ['list' => $list, 'order_name' => '', 'order_sort' => '', 'data' => $data, 'pay_name' => $pay_name,'group'=>$order_group]);
        
        //return view('', ['list' => $list, 'order_name' => $order[0], 'order_sort' => $order[1], 'data' => $data, 'pay_name' => $pay_name]);
    }
    //代售订单一键发货
    public function dy_shipping(){
        $where = [
            'order_group'   => 2,
            'order_status'  => 1,
            'pay_status'    => 1,
            'shipping_status'=> 0
        ];
        $data = [
            'shipping_status'   => 1,
            'shipping_time'     => time()
        ];
        //插入订单操作记录
        $data2 = [
            'order_status'  => 1,
            'shipping_status'=>1,
            'pay_status'    => 1,
            'log_time'  => time(),
            'status_desc' => '发货成功',
            'action_note'   => '管理员发货',
            'action_user'   => $this->uid,
        ];
        $list = Db::name('order')->where($where)->select();
        foreach($list as $v){
            $data2['order_id'] = $v['order_id'];
            Db::name('order_action')->insert($data2);
        }
        if(Db::name('order')->where($where)->update($data)){
            $back = ['code'=>1,'msg'=>'操作成功'];
            writelog($this->uid,$this->username,'代售订单一键发货');
        }else{
            $back = ['code'=>0,'msg'=>'没有需要发货的订单'];
        }
        return json($back);
    }

    public function export()
    {
        $data = input('get.');
        //组装筛选条件
        $map = [];
        $map['deleted'] = ['in','0,1'];
        //排序条件
        $order = 'order_id-0';//默认
        $where2 = '';
        if ($data) {
            // !empty($data['start_time'])        && $data['start_time'] = strtotime($data['start_time']);

            //!empty($data['start_time'])        && $map['add_time'] = ['>=', $data['start_time']];
            !empty($data['start_time']) && $where2 = 'add_time >= '.strtotime($data['start_time']);
            // !empty($data['end_time'])          && $data['end_time'] = strtotime($data['end_time']);
            if(!empty($data['end_time'])){
                if(!empty($where2)){
                    $where2 .= ' and add_time <='.strtotime($data['end_time']);
                }else{
                    $where2 = 'add_time <='.strtotime($data['end_time']);
                }
            }
            if(!empty($data['start_time2'])){
                $where2 .= $where2?' and pay_time >='.strtotime($data['start_time2']):'pay_time >='.strtotime($data['start_time2']);
            }
            if(!empty($data['end_time2'])){
                $where2 .= $where2?' and pay_time <='.strtotime($data['end_time2']):'pay_time <='.strtotime($data['end_time2']);
            }
            //!empty($data['end_time'])          && $map['add_time'] = ['<=', $data['end_time']];
            if(!isset($data['order_group']))    $this->error('参数错误');
            $map['order_group']=$data['order_group'];
            isset($data['pay_status']) && $data['pay_status'] !== '' && $map['pay_status'] = $data['pay_status'];

            !empty($data['pay_code'])  && $map['pay_code'] = $data['pay_code'];

            isset($data['shipping_status']) && $data['shipping_status'] !== ''  && $map['shipping_status'] = $data['shipping_status'];

            isset($data['order_status'])  && $data['order_status'] !== ''    && $map['order_status'] = $data['order_status'];

            !empty($data['keywords']) && $map['order_sn|account|m.mobile|consignee|think_order.mobile|out_trade_no'] = ['like', '%' . $data['keywords'] . '%'];
            //排序条件
            if (isset($data['order']) && !empty($data['order'])) {
                $order = $data['order'];
            }
            if(isset($data['id']) && $data['id']>0){
                $map['think_order.user_id'] = $data['id'];
            }
        }
        
        
      /* $uiddata=db::name('weihui')->field('uid')->where('id','between',[19001,19136])->select();
        $arr=array();
        foreach($uiddata as $k=>$v){
          $arr[]=$v['uid'];
        }
      $uiddata=implode(',',$arr);

      $map['think_order.user_id'] = array('in',$uiddata);*/
        
        
        
        //排序条件
        $order = explode('-', $order);
        $orderStr = $order[1] == 1 ? "$order[0] asc" : "$order[0] desc";
        $pay_log_sql = Db::name('pay_log')->where('status',1)->buildSql();
        $list = OrderModel::order($orderStr)
            ->join('think_member m','think_order.user_id=m.id','left')
           // ->join([$pay_log_sql=>'pl'],'think_order.order_sn=pl.order_number','left')
            ->join('think_pay_log pl','think_order.order_sn=pl.order_number and pl.status=1','left')
            ->field('think_order.*,m.account,out_trade_no')->where($map)->where($where2)->select();
        //支付方式
        //$pay_name = Db::name('pay_config')->where('status', 1)->order('id asc')->select();


        $Address_model =new \app\common\model\Address();
        $addr_lists = $Address_model->addrList();
        Loader::import('.PHPExcel.PHPExcel');
        Loader::import('.PHPExcel.PHPExcel.IOFactory');
        $date = date("Y_m_d",time());
        $phpexcel= new \PHPExcel();
        switch($data['order_group']){
            case 1:
                $group_name = '代理';
                break;
            case 2:
                $group_name = '代售';
                break;
            case 3:
                $group_name = '自营';
                break;

        }
        writelog($this->uid,$this->username,'导出'.$group_name.'订单');
        $filename=str_replace('.xls', '', $group_name.'订单汇总表'.$date).'.xls';
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        // 序号	应聘科室	面试排序	录用排序	姓名	性别	出生年月	籍贯	专业	学历	学位	学制	毕业学校	毕业时间	联系手机	电子邮箱	国家地区	身份证号	目前所在地	身高	外语等级	计算机等级	通讯地址	邮政编码	是否应届	是否取得执业资格	参加工作时间	现工作单位	聘任时间	职称	职务	备注
        $phpexcel->getActiveSheet()->setCellValue('A1','会员编号');
        $phpexcel->getActiveSheet()->setCellValue('B1','会员账号');
        $phpexcel->getActiveSheet()->setCellValue('C1','订单编号');
        $phpexcel->getActiveSheet()->setCellValue('D1','收货人');
        $phpexcel->getActiveSheet()->setCellValue('E1','收货人电话');
        $phpexcel->getActiveSheet()->setCellValue('F1','收货地址');
        $phpexcel->getActiveSheet()->setCellValue('G1','订单详情');
        $phpexcel->getActiveSheet()->setCellValue('H1','总金额	');
        $phpexcel->getActiveSheet()->setCellValue('I1','应付金额');
        $phpexcel->getActiveSheet()->setCellValue('J1','订单状态');
        $phpexcel->getActiveSheet()->setCellValue('K1','支付状态');
        $phpexcel->getActiveSheet()->setCellValue('L1','发货状态');
        $phpexcel->getActiveSheet()->setCellValue('M1','支付方式');
        $phpexcel->getActiveSheet()->setCellValue('N1','下单时间');
        $phpexcel->getActiveSheet()->setCellValue('O1','支付时间');
        $phpexcel->getActiveSheet()->setCellValue('P1','支付交易号');
        $phpexcel->getActiveSheet()->setCellValue('Q1','发货时间');
        $phpexcel->getActiveSheet()->setCellValue('R1','用户备注');

        $objActSheet = $phpexcel->getActiveSheet();
          $objActSheet->getColumnDimension('A')->setWidth(10);
          $objActSheet->getColumnDimension('B')->setWidth(15);
          $objActSheet->getColumnDimension('C')->setWidth(15);
          $objActSheet->getColumnDimension('D')->setWidth(15);
          $objActSheet->getColumnDimension('E')->setWidth(30);
          $objActSheet->getColumnDimension('F')->setWidth(45);

        $objActSheet->getStyle('F')->getAlignment()->setWrapText(true);
        $i=2;

        $ORDER_STATUS    = Config::get('ORDER_STATUS');
        $PAY_STATUS      = Config::get('PAY_STATUS');
        $SHIPPING_STATUS = Config::get('SHIPPING_STATUS');
        foreach ($list as $vv){
            //$nice =Db::name('member')->where(['id'=>$vv['user_id']])->value('account');
            //获取订单详情
            $order_goods = Db::name('order_goods')->where(['order_id'=>$vv['order_id']])->select();
            $good_str='';
            foreach ($order_goods as $k=>$g){
                if($k != 0){
                    $good_str.="\n";
                }
                $good_str.=$g['goods_name'].' '.$g['spec_key_name'].' x '.$g['goods_num'];
            }
            $phpexcel->getActiveSheet()->setCellValue('A' . $i,$vv['user_id']);
            $phpexcel->getActiveSheet()->setCellValue('B' . $i,$vv['account']);
            $phpexcel->getActiveSheet()->setCellValue('C' . $i,' '.$vv['order_sn']);
            $phpexcel->getActiveSheet()->setCellValue('D' . $i,$vv['consignee']);
            $phpexcel->getActiveSheet()->setCellValue('E' . $i,$vv['mobile']);
            $province=$addr_lists[$vv['province']]??'';
            $city=$addr_lists[$vv['city']]??'';
            $district=$addr_lists[$vv['district']]??'';
            $phpexcel->getActiveSheet()->setCellValue('F' . $i,$province.$city. $district.$vv['address']);
            $phpexcel->getActiveSheet()->setCellValue('G' . $i,$good_str);
            $phpexcel->getActiveSheet()->setCellValue('H' . $i,$vv['total_amount']);
            $phpexcel->getActiveSheet()->setCellValue('I' . $i,$vv['order_amount']);
            $phpexcel->getActiveSheet()->setCellValue('J' . $i,$ORDER_STATUS[$vv['order_status']]);
            $phpexcel->getActiveSheet()->setCellValue('K' . $i,$PAY_STATUS[$vv['pay_status']]);
            $phpexcel->getActiveSheet()->setCellValue('L' . $i,$SHIPPING_STATUS[$vv['shipping_status']]);
            $phpexcel->getActiveSheet()->setCellValue('M' . $i,$vv['pay_name']);
            $phpexcel->getActiveSheet()->setCellValue('N' . $i, date('Y-m-d H:i:s',$vv['add_time']));
            $phpexcel->getActiveSheet()->setCellValue('O' . $i, $vv['pay_time']?date('Y-m-d H:i:s',$vv['pay_time']):'--');
            $phpexcel->getActiveSheet()->setCellValue('P' . $i, ' '.$vv['out_trade_no']);
            $phpexcel->getActiveSheet()->setCellValue('Q' . $i, $vv['shipping_time']?date('Y-m-d H:i:s',$vv['shipping_time']):'--');
            $phpexcel->getActiveSheet()->setCellValue('R' . $i,$vv['user_note']);

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
    //导入订单物流
    public function import_shipping(){
        $file = request()->file('file');
        $info =$file->getInfo();
        if(is_file($info['tmp_name'])){
            Loader::import('.PHPExcel.PHPExcel.IOFactory');
            $objReader =  new \PHPExcel_Reader_Excel2007();
            if( ! $objReader->canRead($info['tmp_name'])) {
                $objReader = new \PHPExcel_Reader_Excel5();
                if(!$objReader->canRead($info['tmp_name'])) {
                    return json(['code' => 0, 'data' => '', 'msg' => '仅支持 .xls 类型的文件']);
                }
            }
            $objPHPExcel = $objReader->load($info['tmp_name'],$encode='utf-8');//加载文件

            $sheet = $objPHPExcel->getSheet(0);//取得sheet(0)表
            $highestRow = $sheet->getHighestRow(); // 取得总行数

            for($i=2;$i<=$highestRow;$i++)
            {
                $order_sn = $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
                $shipping_name =  $objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue();
                $shipping_code =  $objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue();
                $invoice_no    =  $objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
                DB::name('order')->where('order_sn',$order_sn)
                    ->update([
                        'shipping_name'     => $shipping_name,
                        'shipping_code'     => $shipping_code,
                        'invoice_no'        => $invoice_no,
                        'shipping_status'   => 1,
                        'shipping_time'     => time()
                    ]);
                if($order_sn){
                    //插入订单操作记录
                    $data2 = [
                        'order_status'  => 1,
                        'shipping_status'=>1,
                        'pay_status'    => 1,
                        'log_time'  => time(),
                        'status_desc' => '发货成功',
                        'action_note'   => '管理员批量导入物流单号',
                        'action_user'   => $this->uid,
                    ];
                    $data2['order_id'] = Db::name('order')->where('order_sn',$order_sn)->value('order_id');
                    Db::name('order_action')->insert($data2);
                }
            }
            writelog($this->uid,$this->username,'批量导入物流单号发货');
            return json(['code' => 1, 'data' => '', 'msg' => '导入成功']);
        }else{
            return json(['code' => 0, 'msg' => '文件不存在']);
        }
    }
    //订单详情
    public function detail(OrderModel $order, $id)
    {
        $orderInfo = $order::get($id);
        if (!$orderInfo) {
            $this->error('查无此订单');
        }
        $goodsList = Db::name('order_goods')->where('order_id', $id)->select();
        //收货地址信息读取
        $region    = Db::name('region')->column('name','id');
        $orderInfo->addr = '';
        isset($region[$orderInfo->province]) && $orderInfo->addr .= $region[$orderInfo->province] . '，';
        isset($region[$orderInfo->city])     && $orderInfo->addr .= $region[$orderInfo->city] . '，';
        isset($region[$orderInfo->district]) && $orderInfo->addr .= $region[$orderInfo->district] . '，';
        isset($region[$orderInfo->twon])     && $orderInfo->addr .= $region[$orderInfo->twon] . '，';
        $orderInfo->address                  && $orderInfo->addr .= $orderInfo->address;
        //订单操作记录
        $orderAction = Db::name('order_action')->where('order_id', $id)->order('action_id desc')->select();
        /*$member = new Member();
        foreach ($orderAction as $k => $v) {
            $v['action_user'] && $orderAction[$k]['user_name'] = $member->where('id', $v['action_user'])->value('nickname');
        }*/
        //管理员可操作订单的按钮
        $orderManagerBtn = $this->orderManagerBtn($orderInfo->order_status, $orderInfo->pay_status, $orderInfo->shipping_status);
        return view('', ['orderInfo' => $orderInfo, 'goodsList' => $goodsList, 'orderAction' => $orderAction, 'orderManagerBtn' => $orderManagerBtn]);
    }
    //发货
    public function delivery(OrderModel $order, $id)
    {
        $orderInfo = $order::get($id);
        if (!$orderInfo) {
            $this->error('查无此订单');
        }
        if ($this->request->isPost()) {
            $data = input('post.');
            if (!$data['invoice_no']) {
                return json(['code' => 0, 'msg' => '发货单号不能为空']);
            }
            if (!$data['shipping_id']) {
                return json(['code' => 0, 'msg' => '物流方式不能为空']);
            }
            if ($orderInfo->pay_status != 1) {
                return json(['code' => 0, 'msg' => '未支付订单不能发货']);
            }
            if ($orderInfo->order_status != 1) {
                return json(['code' => 0, 'msg' => '未确认订单不能发货']);
            }
            //进行发货处理
            $update['shipping_status'] = 1;
            $update['invoice_no']      = $data['invoice_no'];
            $update['shipping_code']   = $data['shipping_id'];
            $update['shipping_name']   = Db::name('shipping')->where('code', $data['shipping_id'])->value('shipping_name');
            $update['shipping_time']   = time();
            Db::name('order')->where('order_id', $id)->update($update);
            //管理员操作日志记录
            $note = $data['note'] ? $data['note'] : '发货操作成功';
            $OrderCommon = new \app\common\model\Order();
            $OrderCommon->logOrder($id, $note, '发货成功', 0);
            //推送消息
            if ($orderInfo['user_id']) {
                $member = new Member();
                $name = $member->where('id', $orderInfo['user_id'])->value('nickname');
                \app\common\service\Msg::send(4, $orderInfo['user_id'], ['name' => $name, 'order_id' => $orderInfo['order_sn']]);
            }
            writelog($this->uid,$this->username,'订单发货'.$orderInfo['order_sn'],1,'','',$orderInfo['user_id']);
            return json(['code' => 1, 'msg' => '发货成功']);
        }
        $goodsList = Db::name('order_goods')->where('order_id', $id)->select();
        //收货地址信息读取
        $region    = Db::name('region')->column('name','id');
        $orderInfo->addr = '';
        isset($region[$orderInfo->province]) && $orderInfo->addr .= $region[$orderInfo->province] . '，';
        isset($region[$orderInfo->city])     && $orderInfo->addr .= $region[$orderInfo->city] . '，';
        isset($region[$orderInfo->district]) && $orderInfo->addr .= $region[$orderInfo->district] . '，';
        isset($region[$orderInfo->twon])     && $orderInfo->addr .= $region[$orderInfo->twon] . '，';
        $orderInfo->address                  && $orderInfo->addr .= $orderInfo->address;
        //物流方式获取
        $shipping = Db::name('shipping')->where('enabled', 1)->order('id asc')->select();
        return view('', ['orderInfo' => $orderInfo,'orderInfo' => $orderInfo, 'goodsList' => $goodsList, 'shipping' => $shipping]);
    }

    //编辑订单
    public function editorder(OrderModel $order, $id)
    {
        $orderInfo = $order::get($id);
        if (!$orderInfo) {
            $this->error('查无此订单');
        }
        if ($orderInfo->order_status >= 2) {
            $this->error('该订单不允许修改');
        }
        if ($this->request->isPost()) {
            $data = input('post.');
            $validate = \think\Loader::validate('OrderValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
            //调加订单方法
            $model = new CommonOrderModel();
            $address = ['consignee' => $data['consignee'],
                'province'  => $data['province'],
                'city'      => $data['city'],
                'district'  => $data['district'],
                'address'   => $data['address'],
                'mobile'    => $data['mobile']
            ];
            $res = $model->adminUpdateOrder($id, $address, $data['pay_code'], $data['goods_list'], $data['invoice_title'], $data['admin_note']);
            return json(['code' => $res['status'], 'msg' => $res['msg']]);
        }
        //地址
        $model = new RegionModel();
        $province = $model->getsubList(0);
        //配送方式
        $shipping_name = Db::name('shipping')->where('enabled', 1)->order('id asc')->select();
        //支付方式
        $pay_name = Db::name('pay_config')->where('status', 1)->order('id asc')->select();
        //分类
        $categoryList = model('GoodsCategory')->getCategorySon();
        $brandList = GoodsBrand::order('order asc')->select();
        //构造前台商品数组json
        $goods_list = Db::name('order_goods')->where('order_id', $id)->select();
        $_goods_list = [];
        foreach ($goods_list as $k => $v) {
            $key =  $v['spec_key'] ? $v['goods_id'] . ':' . $v['spec_key'] : $v['goods_id'];
            //查询库存
            if ($v['spec_key']) {
                $store_count = Db::name('spec_goods')->where(['goods_id' => $v['goods_id'], 'key' => $v['spec_key']])->value('store_count');
            } else {
                $store_count = Db::name('goods')->where('id', $v['goods_id'])->value('store_count');
            }
            $val = ['goods_id'    => $v['goods_id'],
                    'key'         => $v['spec_key'],
                    'goods_name'  => $v['goods_name'],
                    'key_name'    => $v['spec_key_name'],
                    'shop_price'  => $v['member_goods_price'],
                    'store_count' => $store_count,
                    'goods_num'   => $v['goods_num'],
                    ];
            $_goods_list[$key] = $val;
        }
        return view('', ['province' => $province, 'pay_name' => $pay_name, 'shipping_name' => $shipping_name, 'categoryList' => $categoryList, 'brandList' => $brandList, 'orderInfo' => $orderInfo, 'goods_list' => json_encode($_goods_list)]);
    }
    //添加订单
    public function addorder()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $validate = \think\Loader::validate('OrderValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
            //调加订单方法
            $model = new CommonOrderModel();
            $address = ['consignee' => $data['consignee'],
                        'province'  => $data['province'],
                        'city'      => $data['city'],
                        'district'  => $data['district'],
                        'address'   => $data['address'],
                        'mobile'    => $data['mobile']
                        ];
            $res = $model->adminAddOrder($address, $data['pay_code'], $data['goods_list'], $data['invoice_title'], $data['admin_note'], $data['user_id']);
            return json(['code' => $res['status'], 'msg' => $res['msg']]);
        }
        //地址
        $model = new RegionModel();
        $province = $model->getsubList(0);
        //配送方式
        $shipping_name = Db::name('shipping')->where('enabled', 1)->order('id asc')->select();
        //支付方式
        $pay_name = Db::name('pay_config')->where('status', 1)->order('id asc')->select();
        //分类
        $categoryList = model('GoodsCategory')->getCategorySon();
        $brandList = GoodsBrand::order('order asc')->select();
        return view('', ['province' => $province, 'pay_name' => $pay_name, 'shipping_name' => $shipping_name, 'categoryList' => $categoryList, 'brandList' => $brandList]);
    }
    //修改费用
    public function editprice(OrderModel $order, $id)
    {
        $orderInfo = $order::get($id);
        if (!$orderInfo) {
            $this->error('查无此订单');
        }
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['discount'] = (int) $data['discount'];
            if ($data['shipping_price'] < 0) {
                return json(['code' => 0, 'msg' => '物流价格不能为负数']);
            }
            $shipping_price_change = $data['shipping_price'] - $orderInfo->shipping_price;//物流价格变化
            $total_amount          = $orderInfo->total_amount + $shipping_price_change; //计算因运费变化导致订单总额的变化
            $order_amount          = $orderInfo->order_amount + $shipping_price_change; //计算因运费变化导致订单应付金额的变化
            $discount              = $data['discount'] - $orderInfo->discount;
            if ($discount + $order_amount < 0) {
                return json(['code' => 0, 'msg' => '价格下调不能使订单应付金额变为负数']);
            }
            $order_amount          += $discount;//价格微调
            $update = [
                'total_amount'   => $total_amount,
                'order_amount'   => $order_amount,
                'shipping_price' => $data['shipping_price'],
                'discount'       => $data['discount']
            ];
            Db::name('order')->where('order_id', $id)->update($update);
            //操作日志
            $OrderCommon = new \app\common\model\Order();
            $OrderCommon->logOrder($id, '管理员修改订单费用', '修改费用', 0);
            return json(['code' => 1, 'msg' => '修改成功']);
        }
        return view('', ['orderInfo' => $orderInfo]);
    }

    /**
     * 退货管理
     * 订单的单个商品
     * @param $rec_id            order_goods自增主键 用来标识哪个商品退货
     * @param $refund_type       退款类型  0仅退款  1退货退款
     * @param $imgs              图片
     * @param $reason            退款原因
     * @param $reason            问题描述
     */
    public function returngoods()
    {
        $list = DB::name('return_goods')->order('addtime desc')->paginate(10);
        return view('', ['list' => $list]);
    }
    public function shenhe($id)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $status = DB::name('return_goods')->where('id', $id)->value('status');
            if ($status != 0) {
                return json(['code' => 0, 'msg' => '该退款申请已被处理，不能修改']);
            }
            //更新下退款详情
            $update = [
                'refund_money'    => $data['refund_money'],
                'refund_deposit'  => $data['refund_deposit'],
                'refund_integral' => $data['refund_integral'],
                'remark'          => $data['note']
            ];
            DB::name('return_goods')->where('id', $id)->update($update);
            //再进行状态更新
            $OrderCommon = new \app\common\model\Order();
            $res = $OrderCommon->returnGoodsStatus($id, $data['status']);
            return json($res);
        }
        $info = DB::name('return_goods')->where('id', $id)->find();
        return view('', ['info' => $info]);
    }
    public function test()
    {
        echo 111;
        $point_rate = Config::get('point_rate');
        $point_rate = $point_rate ? $point_rate : 100;
    }
    /**
     * 根据btnkey进行订单的操作
     */
    public function manageorder($id, $btn_key, $note = '')
    {
        $btnConfig = Config::get('MANAGE_ORDER_BTN');
        $OrderCommon = new \app\common\model\Order();
        $order_info    = Db::name('order')->where('order_id', $id)->find();
        $order_sn = $order_info['order_sn'];
        switch ($btn_key) {
            case 'pay' :
                //调用订单的方法
                $OrderCommon->update_pay_status($order_sn, ['is_admin' => 1, 'note' => $note]);
                writelog($this->uid,$this->username,'支付订单'.$order_sn,1,'','',$order_info['user_id']);
                return json(['code' => 1, 'msg' => $btnConfig[$btn_key] . '操作成功']);
            case 'pay_cancel' :
                //取消付款
                $OrderCommon->update_paycancel_status($order_sn, ['is_admin' => 1, 'note' => $note]);
                writelog($this->uid,$this->username,'设为未付款订单'.$order_sn,1,'','',$order_info['user_id']);
                return json(['code' => 1, 'msg' => $btnConfig[$btn_key] . '操作成功']);
            case 'confirm' :
                //确认
                $desc = '确认';
                Db::name('order')->where('order_id', $id)->update(['order_status' => 1]);
                break;
            case 'cancel' :
                //取消确认
                $desc = '取消确认';
                Db::name('order')->where('order_id', $id)->update(['order_status' => 0]);
                break;
            case 'invalid' :
                //已作废
                $desc = '作废';
                Db::name('order')->where('order_id', $id)->update(['order_status' => 5]);
                break;
            case 'delivery_confirm' :
                //确认收货
                $desc = '确认收货';
                $OrderCommon->confirm_order($order_sn, ['is_admin' => 1, 'note' => $note]);
                return json(['code' => 1, 'msg' => $btnConfig[$btn_key] . '操作成功']);
            case 'remove' :
                //移除订单
                $OrderCommon->delOrder($id);
                break;
            case 'front_remove':
                $desc = '前端移除';
                Db::name('order')->where('order_id',$id)->update(['deleted'=>1]);
                break;
            case 'back_remove':
                $desc = '后端移除';
                Db::name('order')->where('order_id',$id)->update(['deleted'=>2]);
                break;
            case 'all_remove':
                $desc = '前后端移除';
                Db::name('order')->where('order_id',$id)->update(['deleted'=>3]);
                break;
            default :
                return json(['code' => 0, 'msg' => '系统错误']);
        }
        writelog($this->uid,$this->username,$desc.'订单'.$order_sn,1,'','',$order_info['user_id']);
        //管理员操作日志记录
        $note = $note ? $note : $btnConfig[$btn_key] . '操作成功';
        $OrderCommon->logOrder($id, $note, $btnConfig[$btn_key], 0);
        return json(['code' => 1, 'msg' => $btnConfig[$btn_key] . '操作成功']);
    }

    //该订单可用操作生成按钮
    public function ordermanagerbtn($order_status, $pay_status, $shipping_status) {
        //操作按钮汇总
        $btnConfig    = Config::get('MANAGE_ORDER_BTN');
        $btn = [];
        if ($order_status == 0 && $pay_status == 0) {
            //未确认+未付款 --->支付
            $btn['pay'] = $btnConfig['pay'];
        } elseif ($order_status == 0 && ($pay_status == 1 || $pay_status == 4)) {
            //未确认+已支付 --->设为未付款 确认
            $btn['pay_cancel'] = $btnConfig['pay_cancel'];
            $btn['confirm']    = $btnConfig['confirm'];
        } elseif ($order_status == 1 && ($pay_status == 1 || $pay_status == 4) && $shipping_status == 0) {
            //未发货
            $btn['cancel']     = $btnConfig['cancel'];
            $btn['delivery']   = $btnConfig['delivery'];
        }
        if ($shipping_status == 1 && $order_status == 1 && ($pay_status == 1 || $pay_status == 4)) {
            //已发货+已付款+已支付 --->确认收货 申请退货
        	$btn['delivery_confirm'] = $btnConfig['delivery_confirm'];
            //$btn['refund']           = $btnConfig['refund'];
        } elseif ($order_status == 2 || $order_status == 4) {
            //已收货 或 已完成    --->申请退货
            //$btn['refund'] = $btnConfig['refund'];
        } elseif ($order_status == 3 || $order_status == 5) {
            //已取消 已废除     ---> 移除
            //$btn['remove'] = $btnConfig['remove'];
        }
        //所有订单都可移除
        $btn['front_remove'] = $btnConfig['front_remove'];
        $btn['back_remove'] = $btnConfig['back_remove'];
        $btn['all_remove'] = $btnConfig['all_remove'];
        
        if ($order_status != 5) {
            //所有不是作废订单  都加一个无效
            $btn['invalid'] = $btnConfig['invalid'];
        }
        return $btn;
    }



    public function finduser($search)
    {
        $member = new Member();
        $map['account|nickname']  = ['like', '%' . $search . '%'];
        $list = $member->where($map)->select();
        return json(['code' => 1, 'msg' => $list]);
    }

    public function getregionsublist($id)
    {
        $model = new RegionModel();
        $list = $model->getsubList($id);
        return json(['code' => 1, 'msg' => $list]);
    }

    public function getgoodslist()
    {
        $data  = input('get.');
        $model = new Goods();
        $res = $model->getGoodsList($data['cat_id'], $data['brand_id'], $data['recom_type'], $data['keywords'], 1, 1);
        if ($res['code']) {
            return json(['code' => 1, 'msg' => $res['data']]);
        }
        return json(['code' => 0, 'msg' => '没有数据']);
    }

}