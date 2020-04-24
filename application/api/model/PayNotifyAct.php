<?php

namespace app\api\model;

use app\common\model\MoneyLog;
use app\common\model\Order;
use app\common\model\YangDrugLogModel;
use app\common\model\YangTenderModel;
use app\common\model\YangYangModel;
use app\common\model\YangYenderReceiveModel;
use app\common\service\Users;
use think\Db;


/**
 * Created by PhpStorm.
 * User: qiyu
 * Date: 2017-09-27
 * Time: 15:49
 */
class PayNotifyAct
{
    /**
     *  充值
     * @param $order_number
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function chongzhi($order_number, $param)
    {
        $MoneyLog = new MoneyLog();
        $info = $MoneyLog->get($order_number);
        //判断订单状态 然后改变
        if (!empty($info) && $info->status == 0) {
            //给用户加钱
            $Users = new Users();
            $tag = $Users->userIncMoney($info->uid, $info->money);
            if ($tag['code'] == 0) {
                Log::error($tag['msg']);
                return false;
            } else {
                //改变订单状态
                if (!$info->save(['status' => 1])) {
                    Log::error($MoneyLog->getLastSql());
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 订单处理
     * @param $order_number
     * @param $param
     * @return bool
     */
    public function shoporder($order_number, $param)
    {
        $orderModel = new Order();
        //file_put_contents('./uploads/test.txt', $order_number.'-----'.serialize($param));
        $tag = $orderModel->update_pay_status($order_number, ['pay_code' => $param['param']]);
        if ($tag['code'] != 1) {
            Log::error($order_number . '_' . $tag['msg']);
            return false;
        }
        return true;
    }

    /**
     * 药品购买回调
     */
    public function buy_drug($order_number, $param)
    {
        //$param;  Array ( [uid] => 212126 [id] => 1 [num] => 3 )
        //给用户增加药品
        Db::startTrans();
        try {
            $YangDrugLogModel = new YangDrugLogModel();
            if ($YangDrugLogModel::log($param['uid'], $param['num'], 1)) {
                //给用户添加 药品
                Db::name('member')->where(['id' => $param['uid']])->setInc('drug_count', $param['num']);
                Db::commit();
                return true;
            }else{
                Db::rollback();
                return false;
            }
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }

        return false;
    }

    /**
     * 小羊购买回调
     */
    public function buy_yang($order_number, $param)
    {

        $config = cache('db_config_data');
        if (!$config) {
            $config = api('Config/lists');
            cache('db_config_data', $config);
        }
        config($config);

        //$param; Array ( [uid] => 212126 [tender_id] => 8 [receive_num] => 2 [receive_price] => 330.00 )

        Db::startTrans();
        try {


            //给用户增加购买记录
            $YangYenderReceiveModel = new YangYenderReceiveModel();
            $YangYenderReceiveModel->save([
                'uid' => $param['uid'],
                'tender_id' => $param['tender_id'],
                'receive_num' => $param['receive_num'],
                'receive_price' => $param['receive_price'],
                'status' => 1,
            ]);
            $receive_id = $YangYenderReceiveModel->getLastInsID();
            //给用户增加小羊
            $YangYangModel = new YangYangModel();
            $data = [];
            $single_rebate = 0;
            $zhouqi = 180;//TODO 180天的周期
            $start_date = strtotime(date('Y-m-d', strtotime('+1 day')));
            $end_date = $start_date + $zhouqi * 60 * 60 * 24;
            for ($i = 0; $i < $param['receive_num']; $i++) {
                $data[] = [
                    'uid' => $param['uid'],
                    'tender_id' => $param['tender_id'],
                    'receive_id' => $receive_id,
                    'single_rebate' => $single_rebate,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'status' => 1
                ];
            }
            $YangYangModel->saveAll($data);
            $YangTenderModel = new YangTenderModel();
            //给标增加认购数量
            $tenderInfo = $YangTenderModel->get($param['tender_id']);
            $tenderInfo->receive_num = $tenderInfo->receive_num + $param['receive_num'];
            //认购数量大于设定值 的时候就关闭这个标
            if ($tenderInfo->receive_num >= $tenderInfo->num) {
                $tenderInfo->status = 0;
            }
            $tenderInfo->save();


            //给用户增加认养数量
            Db::name('member')->where(['id' => $param['uid']])->setInc('yang_count', $param['receive_num']);
            Db::name('member')->where(['id' => $param['uid']])->setInc('yang_ing', $param['receive_num']);

            //奖励+增加会员伞下认养总数+更新会员等级
            $Users = new Users();
            $Users->renyang_jiangli($param['uid'], $param['receive_num']);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }


    }

}