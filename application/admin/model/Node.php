<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class Node extends Model
{

    protected $name = "auth_rule";


    /**
     * [getNodeInfo 获取节点数据]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getNodeInfo($id)
    {
        //$result = $this->field('id,title,pid')->select();
        
        //修改为只显示正常状态的权限
        $noshow_id = $this->where(['status'=>0])->column('id');//获取所有不显示的菜单id
        $all_result = $this->field('id,title,pid')->select();
        $all_id = $this->column('pid','id');
        $all_result = collection($all_result)->toArray();
        $result = [];
        foreach($all_result as $k=>$v){
            if($v['pid'] > 0){
                //$ppid = Db::name('auth_rule')->where('id',$v['pid'])->value('pid');
                $ppid = $all_id[$v['pid']];
                $all_result[$k]['ppid'] = $ppid;
            }else{
                $all_result[$k]['ppid'] = 0;
            }
        }
        foreach($all_result as $v){
            if(in_array($v['id'],$noshow_id) || in_array($v['pid'],$noshow_id) || in_array($v['ppid'],$noshow_id)){
                continue;
            }else{
                $result[] = $v;
            }
        }
        
        
        
        $str = "";
        $role = new UserType();
        $rule = $role->getRuleById($id);

        if(!empty($rule)){
            $rule = explode(',', $rule);
        }
        foreach($result as $key=>$vo){
            $str .= '{ "id": "' . $vo['id'] . '", "pId":"' . $vo['pid'] . '", "name":"' . $vo['title'].'"';

            if(!empty($rule) && in_array($vo['id'], $rule)){
                $str .= ' ,"checked":1';
            }

            $str .= '},';
        }

        return "[" . substr($str, 0, -1) . "]";
    }


    /**
     * [getMenu 根据节点数据获取对应的菜单]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getMenu($nodeStr = '')
    {
        //超级管理员没有节点数组
        $where = empty($nodeStr) ? 'status = 1' : 'status = 1 and id in('.$nodeStr.')';
        $result = Db::name('auth_rule')->where($where)->order('sort')->select();
        $menu = prepareMenu($result);
        return $menu;
    }
}