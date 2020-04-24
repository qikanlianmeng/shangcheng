<?php

namespace app\common\model;
use think\Model;
use think\Db;

class YangCameraModel extends Model
{
    protected $name = 'yang_camera';
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    public function setStartTimeAttr($value){
        return strtotime($value);
    }
    public function getStartTimeAttr($value){
        return date('Y-m-d',$value);
    }

    public function getMemberByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }
    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }
    /**
     * 插入信息
     */
    public function insertOne($param)
    {
        try{
            $result = $this->validate('YangTenderValidate')->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
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
    public function editOne($param)
    {
        try{
            $result =  $this->validate('YangTenderValidate')->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    public function delOne($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    public static function getLists(){
        return self::where(['status'=>1])->field('id,title,photo')->order('create_time desc')->select();
    }

    public static function getAll($page,$num){
        return self::where(['start_time'=>['<',time()]])->order('create_time desc')->page($page, $num)->select();
    }



}