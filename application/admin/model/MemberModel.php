<?php
/**
 * Created by PhpStorm.
 * User: qiyu
 * Date: 2017-09-29
 * Time: 14:06
 */
namespace app\admin\model;
use app\common\model\IntegralLog;
use app\common\model\MoneyLog;
use think\Model;
use think\Db;

class MemberModel extends Model
{
    protected $name = 'member';
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    /**
     * 根据搜索条件获取用户列表信息
     */
    public function getMemberByWhere($map, $Nowpage, $limits)
    {
        return $this->field('think_member.*,m.account as r_account')->join('think_member m', 'think_member.ruid = m.id','left')->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }

    //$type =0默认获取列表，$type=1获取统计信息
    public function getMemberByWhere2($map, $Nowpage, $limits,$type=0)
    {
        if($type == 0){
            return $this->field('think_member.*,m.account as r_account,m.mobile as r_mobile,think_member.create_dl_income as income,think_member.create_dy_income as income_dy,mi.name as real_name,mi.age as real_age')
                ->join('think_member m', 'think_member.ruid = m.id','left')
                ->join('think_member_info mi','think_member.id=mi.uid and mi.status=1','left')
                ->where($map)->page($Nowpage, $limits)->order('id desc')->select();
            
        }else{
            $total = $this->field('think_member.*,m.account as r_account,')
            ->join('think_member m', 'think_member.ruid = m.id','left')
            ->where($map)->count();
            if(isset($map['think_member.dl_time']) || isset($map['think_member.is_center'])){
                $dl = $center = 0;
            }else{
                $dl = $this->field('think_member.*,m.account as r_account')
                    ->join('think_member m', 'think_member.ruid = m.id','left')
                    ->where($map)->where(['think_member.dl_time'=>['>',0],'think_member.is_center'=>0])->count();
                $center =$this->field('think_member.*,m.account as r_account')
                    ->join('think_member m', 'think_member.ruid = m.id','left')
                    ->where($map)->where(['think_member.is_center'=>1])->count();
            }

            return ['total'=>$total,'dl'=>$dl,'center'=>$center];
        }
        
    }
    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }


    /**
     * 插入信息
     */
    public function insertMember($param)
    {
        try{
            $result = $this->validate('MemberValidate')->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                //往 记录表里添加 充值的数量记录啊
                if(isset($param['money']) && $param['money']>0){
                    MoneyLog::operate($param['id'],$param['money'],2,1,'管理员手动处理',session('uid'));
                }
                //添加羊币
                if(isset($param['integral']) && $param['integral']>0){
                    IntegralLog::operate($param['id'],$param['integral'],2,1,'管理员手动处理',session('uid'));
                }
                return ['code' => 1, 'data' => $this->id, 'msg' => '添加成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑信息
     * @param $param
     */
    public function editMember($param)
    {
        try{
            $info=$this->get($param['id']);
            //$money=$param['money']-$info->money;
            //$integral=$param['integral']-$info->integral;
            $money=0;
            $integral=0;
            $result =  $this->validate('MemberValidate')->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                if($money != 0){
                    MoneyLog::operate($param['id'],$money,2,1,'管理员手动处理',session('uid'));
                }
                if($integral != 0){
                    IntegralLog::operate($param['id'],$integral,2,1,'管理员手动处理',session('uid'));
                }

                return ['code' => 1, 'data' => '', 'msg' => '编辑成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据管理员id获取角色信息
     * @param $id
     */
    public function getOneMember($id)
    {
        return $this->where('id', $id)->find();
    }

    public function delMember($id)
    {
        try{
            $map['closed']=1;
            $this->save($map, ['id' => $id]);
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}