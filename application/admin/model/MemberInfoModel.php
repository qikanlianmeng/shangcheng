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

class MemberInfoModel extends Model
{
    protected $name = 'member_info';

    /**
     * 根据搜索条件获取用户列表信息
     */
    public function getListByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->join('think_member m','m.id=think_member_info.uid','left')->field('think_member_info.*,m.account as user_account')->page($Nowpage, $limits)->order('think_member_info.status asc,think_member_info.create_time desc')->select();
    }
    public function allcount($map)
    {
        return $this->where($map)->join('think_member m','m.id=think_member_info.uid','left')->field('think_member_info.*,m.account as user_account')->count();
    }

}