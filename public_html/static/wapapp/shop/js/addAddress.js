/**
 * 添加地址
 */
define(function(require, exports, module) {
    var common = require('common'),
        layer = require('layer'),
        template = require('template');

    //定义省市区邮政编码
    var province, city, district = 0;

    sessionStorage.setItem("need-refresh", true);
    //省市区三级联动
    mui.init();
    mui.ready(function() {
        //三级联
        var cityPicker = new mui.PopPicker({
            layer: 3
        });
        $get('/api2/address/get_region_tree', {}, function(data) {
            if (data.status) {
                
                cityPicker.setData(data.data);
            } else {
                common.toast(data.msg);
            }
        })
        var showCityPicker = document.getElementById('showCityPicker');
        showCityPicker.addEventListener('click', function(event) {
            console.log(event);
            cityPicker.show(function(items) {
                console.log(items);
                var provinceText, cityText, districtText;
                province = items[0].value;
                provinceText = items[0].text;
                city = items[1].value;
                cityText = items[1].text;
                if (typeof(items[2].value) == 'undefined') {
                    district = 0;
                    districtText = '';
                } else {
                    district = items[2].value;
                    districtText = items[2].text;
                }
                showCityPicker.value = provinceText + " " + cityText + " " + districtText;
                // 返回 false 可以阻止选择框的关闭
                // return false;
            });
        }, false);
        //修改操作
        if (opr == 'mod') {
            $('.mui-title').html('编辑收货地址');
            $('.save-address button').html('确认修改');
            $get('/api2/address/get_address', { id: address_id }, function(data) {
                if (data.status) {
                    $("input[name='name']").val(data.data.consignee);
                    $("input[name='phone']").val(data.data.mobile);
                    $("input[name='address']").val(data.data.address);
                    $("input[name='area']").val(data.data.pcd);
                    province = data.data.province;
                    city = data.data.city;
                    district = data.data.district;
                    cityPicker.pickers[0].setSelectedValue(province);
                    cityPicker.pickers[1].setSelectedValue(city);
                    if (district) {
                        cityPicker.pickers[2].setSelectedValue(district);
                    }
                    if (data.data.is_default) {
                        $("input[name='set-default']").attr('checked', 'checked');
                    }
                } else {
                    common.toast(data.msg);
                }
            })
        }
    });
    //添加地址
    $('.save-address').on('click', 'button', function() {
        var name = $("input[name='name']").val();
        var phone = $("input[name='phone']").val();
        var address = $("input[name='address']").val();
        var isSet = $("input[name='set-default']").is(':checked');
        if (!name) {
            common.open('收货人不能为空', 1);
        } else if (!phone) {
            common.open('手机号码不能为空', 1);
        } else if (phone.search(/^\d{11}$/) == -1) {
            mui.toast('手机号码格式不正确');
        } else if (!province || !city) {
            common.open('省市区不能为空', 1);
        } else if (!address) {
            common.open('详细地址不能为空', 1);
        } else {
            if (isSet) {
                isSet = 1;
            } else {
                isSet = 0;
            }
            var par = {
                consignee: name,
                province: province,
                city: city,
                district: district,
                address: address,
                mobile: phone,
                is_default: isSet
            };
            if (opr == 'mod') {
                //修改收货地址多个id参数
                par.address_id = address_id;
            }
            //修改||增加地址
            $post('/api2/address/edit_address', par, function(data) {
                if (data.status) {
                    // layer.open({
                    //     content: data.msg,
                    //     time: 1,
                    //     end: function() {
                    //         if (address_id == 'new') {
                    //             location.href = '/wapapp/order/firm_order';
                    //         } else {
                    //             mui.back(true);
                    //         }
                    //     }
                    // });
                    // location.href = "/wapapp/usercenter/address_list.html";
                    mui.back();
                } else {
                    common.toast(data.msg);
                }
            });
        }
    });
});