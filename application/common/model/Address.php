<?php
/**
 * 用户地址管理
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/26
 * Time: 10:17
 */
namespace app\common\model;

use think\Model;
use think\Db;
use think\Cache;

class Address extends Model
{
    /**
     * 区域三级列表
     */
    public function getRegionTree()
    {

        if (!$list = Cache::get('region')) {
            $arr = Db::name('region')->field('id as value,name as text,parent_id')->order('sort asc')->select();
            $list = [];
            foreach ($arr as $k => $v) {
                if ($v['parent_id'] == 0) {
                    foreach ($arr as $k1 => $v1) {
                        if ($v1['parent_id'] == $v['value']) {
                            foreach ($arr as $k2 => $v2) {
                                if ($v2['parent_id'] ==  $v1['value']) {
                                    $v1['children'][] = $v2;
                                }
                            }
                            $v['children'][] = $v1;
                        }
                    }
                    $list[] = $v;
                }

            }
            Cache::set('region',$list,0);
        }
        return $list;
    }

    /**
     * 新增&编辑收货地址
     * @param $data user_id,consignee,email,country,province,city,district,twon,address,zipcode,mobile
     * @return array
     */
    public function editAddress($data)
    {
        $data['user_id'] = get_uid();
        if (!$data['consignee']) {
            return ['status' => 0, 'msg' => '收货人必填'];
        }
        if (!$data['province'] || !$data['city'] || !$data['address']) {
            return ['status' => 0, 'msg' => '收货地址必填'];
        }
        if (!$data['mobile']) {
            return ['status' => 0, 'msg' => '联系电话必填'];
        }else{
            if(!isMobile($data['mobile'])){
                return ['status' => 0, 'msg' => '联系电话必须为手机号'];
            }
        }
        if ($data['is_default'] == 1) {
            Db::name('user_address')->where(['user_id' => $data['user_id']])->update(['is_default' => 0]);//将所有的地址都设为非默认
        } else {
            //查看是否有默认地址，没有的话新增的这个默认为默认地址
            if (!$data['address_id']) {
                if (!Db::name('user_address')->where(['user_id' =>$data['user_id'], 'is_default' => 1])->count()) {
                    $data['is_default'] = 1;
                }
            } else {
                if (Db::name('user_address')->where(['user_id' =>$data['user_id']])->count() == 1) {
                    $data['is_default'] = 1;
                }
            }

        }
        if ($address_id = $data['address_id']) {
            //修改
            unset($data['address_id']);
            Db::name('user_address')->where(['address_id' => $address_id, 'user_id' => $data['user_id']])->update($data);
        } else {
            Db::name('user_address')->insert($data);
        }
        return ['status' => 1, 'msg' => '保存成功'];
    }

    /**
     * 删除收货地址
     */
    public function delAddress($id)
    {
        $user_id = get_uid();
        $address = Db::name('user_address')->where(['address_id' => $id, 'user_id' => $user_id])->find();
        if ($address && $address['is_default']) {
            return ['status' => 0, 'msg' => '默认地址不能删除'];
        }
        Db::name('user_address')->where(['address_id' => $id, 'user_id' => $user_id])->delete();
        return ['status' => 1, 'msg' => '删除成功'];
    }

    /**
     * 设置默认收货地址
     */
    public function setNormal($id)
    {
        $user_id = get_uid();
        Db::name('user_address')->where(['user_id' => $user_id])->update(['is_default' => 0]);
        Db::name('user_address')->where(['address_id' => $id, 'user_id' => $user_id])->update(['is_default' => 1]);
        return ['status' => 1, 'msg' => '设置成功'];
    }

    /**
     * 收货地址列表
     */
    public function addressList()
    {
        $user_id = get_uid();
        $list = Db::name('user_address')->where(['user_id' => $user_id,'is_default'=>1])->order('is_default desc,address_id asc')->select();
        if ($list) {
            if (!$region = Cache::get('regiondata')) {
                $region    = Db::name('region')->column('name','id');
                Cache::set('regiondata', $region, 0);
            }
            foreach ($list as $k => $v) {
                $addr = '';
                isset($region[$v['province']]) && $addr .= $region[$v['province']] . '，';
                isset($region[$v['city']])     && $addr .= $region[$v['city']] . '，';
                isset($region[$v['district']]) && $addr .= $region[$v['district']] . '，';
                //isset($region[$v['twon']])     && $addr .= $region[$v['twon']] . '，';
                $v['address']            && $addr .= $v['address'];
                $list[$k]['address_detail'] = $addr;
            }
            return ['status' => 1, 'msg' => '获取收货地址成功', 'data' => $list];
        }
        return ['status' => 0, 'msg' => '用户未设置收货地址', 'data' => ''];
    }

    public function getNormalAddress($id = 0)
    {
        $user_id = get_uid();
        if ($id == 0) {
            $info = Db::name('user_address')->where(['user_id' => $user_id, 'is_default' => 1])->find();
        } else {
            $info = Db::name('user_address')->where(['user_id' => $user_id, 'address_id' => $id])->find();
        }
        if ($info) {
            if (!$region = Cache::get('regiondata')) {
                $region    = Db::name('region')->column('name','id');
                Cache::set('regiondata', $region, 0);
            }
            $addr = '';
            isset($region[$info['province']]) && $addr .= $region[$info['province']] . '，';
            isset($region[$info['city']])     && $addr .= $region[$info['city']] . '，';
            isset($region[$info['district']]) && $addr .= $region[$info['district']] . '，';
            //isset($region[$info['twon']])     && $addr .= $region[$info['twon']] . '，';
            $info['pcd'] = trim(str_replace('，', ' ', $addr));
            $info['address'] && $addr .= $info['address'];
            $info['address_detail'] = $addr;
            return ['status' => 1, 'msg' => '获取收货地址成功', 'data' => $info];
        }
        return ['status' => 0, 'msg' => '用户未设置收货地址', 'data' => ''];
    }

    public function addrList(){
        return Db::name('region')->column('id,name');
    }

}