<?php
/**
 * 收货地址管理
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/26
 * Time: 13:34
 */

namespace app\api\controller;

use think\console\command\make\Model;
use think\Db;
use think\Config;
use app\common\model\Address as AddressModel;

class Address extends Model
{
    protected $attrModel = null;

    public function __construct()
    {
        parent::__construct();
        if (!get_uid()) {
            echo json_encode(['status' => 0, 'msg' => '未登录，请先登录']);die;
        }
        $this->attrModel = new AddressModel();
    }

    /**
     * 保存新增收货地址
     * @return \think\response\Json
     */
    public function edit_address()
    {
        $data = input('');
        $arr               = [];
        $arr['consignee']  =  isset($data['consignee']) && !empty($data['consignee']) ? $data['consignee'] : '';
        $arr['email']      =  isset($data['email']) && !empty($data['email']) ? $data['email'] : '';
        $arr['country']    =  isset($data['country']) && !empty($data['country']) ? $data['country'] : 0;
        $arr['province']   =  isset($data['province']) && !empty($data['province']) ? $data['province'] : 0;
        $arr['district']   =  isset($data['district']) && !empty($data['district']) ? $data['district'] : 0;
        $arr['city']       =  isset($data['city']) && !empty($data['city']) ? $data['city'] : 0;
        $arr['twon']       =  isset($data['twon']) && !empty($data['twon']) ? $data['twon'] : 0;
        $arr['address']    =  isset($data['address']) && !empty($data['address']) ? $data['address'] : '';
        $arr['zipcode']    =  isset($data['zipcode']) && !empty($data['zipcode']) ? $data['zipcode'] : '';
        $arr['mobile']     =  isset($data['mobile']) && !empty($data['mobile']) ? $data['mobile'] : '';
        $arr['address_id'] =  isset($data['address_id']) && !empty($data['address_id']) ? $data['address_id'] : '';
        $arr['is_default'] =  isset($data['is_default']) && $data['is_default'] == 1 ? 1 : 0;
        $res = $this->attrModel->editAddress($arr);
        return json($res);
    }

    /**
     * 设置默认收货地址
     */
    public function set_normal($id)
    {
        $res = $this->attrModel->setNormal($id);
        return json($res);
    }

    /**
     * 删除收货地址
     * @param $id
     * @return \think\response\Json
     */
    public function del_address($id)
    {
        $res = $this->attrModel->delAddress($id);
        return json($res);
    }

    /**
     * 获取指定收货地址信息
     * @param $id
     * @return \think\response\Json
     */
    public function get_address($id)
    {
        $res = $this->attrModel->getNormalAddress($id);
        return json($res);
    }

    public function get_normal_address()
    {
        $res = $this->attrModel->getNormalAddress();
        return json($res);
    }
    /**
     * 获取收货地址列表
     * @return \think\response\Json
     */
    public function get_addressList()
    {
        $res = $this->attrModel->addressList();
        return json($res);
    }

    public function get_region_tree()
    {
        $res = $this->attrModel->getRegionTree();
        //var_dump($res);
        return json(['status' => 1, 'msg' => '获取成功', 'data' => $res]);
    }
}
