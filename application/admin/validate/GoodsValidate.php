<?php

namespace app\admin\validate;

use think\Validate;
use think\Config;

class GoodsValidate extends Validate
{
    // 验证规则
    protected $rule = [
        ['goods_name','require|unique:goods','商品名称必填|商品名称重复'],
        ['dl_price', 'require|gt:0', '代理价格必填|代理价格必须大于0'],
        ['dy_price', 'require|gt:0', '代营价格必填|代营价格必须大于0'],
        ['zy_price', 'require|gt:0', '自营价格必填|自营价格必须大于0'], 
        ['original_img','require','图片必须上传。'],
    ];


    /**
     * 检查羊币兑换
     * @author dyr
     * @return bool
     */
    protected function checkExchangeIntegral($value, $rule)
    {
        $exchange_integral = $value;
        $shop_price = input('shop_price', 0);
        //羊币换算比例
        $point_rate_value = Config::get('point_rate');
        $point_rate_value = $point_rate_value ? $point_rate_value : 100;
        if ($exchange_integral > ($shop_price * $point_rate_value)) {
            return '羊币抵扣金额不能超过商品总额';
        } else {
            return true;
        }
    }

    /**
     * 检查市场价
     * @param $value
     * @param $rule
     * @param $data
     * @return bool
     */
    protected function  checkMarketPrice($value,$rule,$data){
        if($value < $data['shop_price']){
            return false;
        }else{
            return true;
        }
    }
}