<?php

namespace app\common\model;
use think\Model;
use think\Db;

class Member extends Model
{
    protected $name = 'member';
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    /**
     * 根据搜索条件获取用户列表信息
     */
    public function getMemberByWhere($map, $Nowpage, $limits)
    {
        return $this->field('think_member.*,group_name')->join('think_member_group', 'think_member.group_id = think_member_group.id')
            ->where($map)->page($Nowpage, $limits)->order('id desc')->select();
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
                return ['code' => 1, 'data' => '', 'msg' => '添加成功'];
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
            $money=$param['money']-$info->money;
            $integral=$param['integral']-$info->integral;
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

    /**
     * 给用户加钱
     * @param $uid
     * @param $money
     * @return array
     */
    public function incMoney($uid,$money){
        if($this->where(['id'=>$uid])->setInc('money',$money)){
            return ['code'=>1,'msg'=>'设置成功'];
        }else{
            return ['code'=>0,'msg'=>$this->getLastSql()];
        }
    }

    /**
     * 给用户减钱
     * @param $uid
     * @param $money
     * @return array
     */
    public function decMoney($uid,$money){
        if($this->where(['id'=>$uid])->setDec('money',$money)){
            return ['code'=>1,'msg'=>'设置成功'];
        }else{
            return ['code'=>0,'msg'=>$this->getLastSql()];
        }
    }


    public function incIntegral($uid, $integral)
    {
        if ($this->where(['id'=>$uid])->setInc('integral', $integral))  {
            return ['code'=>1,'msg'=>'设置成功'];
        } else {
            return ['code'=>0,'msg'=>$this->getLastSql()];
        }
    }

    public function decIntegral($uid, $integral)
    {
        if ($this->where(['id'=>$uid])->setDec('integral', $integral))  {
            return ['code'=>1,'msg'=>'设置成功'];
        } else {
            return ['code'=>0,'msg'=>$this->getLastSql()];
        }
    }

}