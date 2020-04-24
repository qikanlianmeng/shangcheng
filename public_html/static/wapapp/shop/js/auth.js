/**
 * 添加地址
 */
define(function (require, exports, module) {
    var common = require('common'),
        layer = require('layer'),
        template = require('template');

    //定义省市区邮政编码
    var province, city, district = 0;

    sessionStorage.setItem("need-refresh", true);
    //省市区三级联动
    mui.init();
    mui.ready(function () {
        //三级联
        var cityPicker = new mui.PopPicker({
            layer: 3
        });
        $get('/api2/address/get_region_tree', {}, function (data) {
            if (data.status==1) {
               
                cityPicker.setData(data.data);
            	setTimeout(() => {
    				$get('/api2/user/update_real_info', {}, function (data) {
			            if (data.data) {
			                $("input[name='name']").val(data.name);
			                $("input[name='id']").val(data.id);
			                $("input[name='age']").val(data.age);
			                $('.idcarda').attr('src', data.front_img?data.front_img:'/static/wapapp/shop/images/card_upload.png');
			                $('.idcardb').attr('src', data.back_img?data.back_img:'/static/wapapp/shop/images/card_upload2.png');
			                //设置地址初始值
			                cityPicker.pickers[0].setSelectedIndex(data.province);
			                cityPicker.pickers[1].setItems(cityPicker.pickers[0].getSelectedItem().children);
			                cityPicker.pickers[1].setSelectedValue(data.city);
			                cityPicker.pickers[2].setItems(cityPicker.pickers[1].getSelectedItem().children);
			                cityPicker.pickers[2].setSelectedValue(data.district);
			                var provinceText, cityText, districtText;
			                provinceText=cityPicker.pickers[0].getSelectedItem().text;
			                cityText=cityPicker.pickers[1].getSelectedItem().text;
			                districtText=cityPicker.pickers[2].getSelectedItem().text;
			                showCityPicker.value = provinceText + " " + cityText + " " + districtText;
			                if(data.status==0){
			                    $('#status_sh').html('<span style="color:orange;">(审核中)</span>');
			                }else if(data.status==1){
			                    $('#status_sh').html('<span style="color:green;">(审核通过)</span>')
			                    $('.need_dis').addClass('mui-disabled');
			                    $('.save-address button').addClass('mui-disabled');
			                    $('.save-address button').html('联系管理员修改');
			                    $("input[name='set-default']").attr('checked',true);
			                }else if(data.status==2){
			                    $('#status_sh').html('<span style="color:red;">(审核失败)</span>');
			                    data.msg?(
			                        $('#label_sh').append('<p>备注：'+data.msg+'</p>'),
			                        $('#status_sh').html('<span style="color:oranged;">('+data.msg+')</span>')
			                    ):'';
			                }
			            } else {
			
			            }
			        });
    			}, 1000);
            } else {
                common.toast(data.msg);
            }
        });
        var showCityPicker = document.getElementById('showCityPicker');
        showCityPicker.addEventListener('click', function (event) {
            console.log(event);
            cityPicker.show(function (items) {
                var provinceText, cityText, districtText;
                province = items[0].value;
                provinceText = items[0].text;
                city = items[1].value;
                cityText = items[1].text;
                if (typeof (items[2].value) == 'undefined') {
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
        //添加地址
        $('.save-address').on('click', 'button', function () {
            var name = $("input[name='name']").val();
            var id = $("input[name='id']").val();
            var age = $("input[name='age']").val();
            var isSet = $("input[name='set-default']").is(':checked');
            var imgshow1 = $('.idcarda').attr('data-src');
            var imgshow2 = $('.idcardb').attr('data-src');
            if (!name) {
                common.open('姓名不能为空', 1);
            } else if (!id) {
                common.open('身份证号不能为空', 1);
            } else if (!province || !city) {
                common.open('省市区不能为空', 1);
            } else if (!age) {
                common.open('年龄不能为空', 1);
            } else if (!imgshow1) {
                common.open('请上传身份证正面照');
            } else if (!imgshow2) {
                common.open('请上传身份证国徽面照');
            } else if (!isSet) {
                common.open('请勾选确认信息', 1);
            } else {
                if (isSet) {
                    isSet = 1;
                } else {
                    isSet = 0;
                }
                var par = {
                    name: name,
                    province: province,
                    city: city,
                    district: district,
                    id: id,
                    age: age,
                    front_img: imgshow1,
                    back_img: imgshow2
                };
                // 提交
                $post('/api2/user/update_real_info', par, function (data) {
                    common.toast(data.msg);
                    if (data.status == 1) {
                        setTimeout(() => {
                            mui.back();
                        }, 1000);

                    } else {

                    }
                });
            }
        });
    });
    function get_data() {
        $get('/api2/user/update_real_info', {}, function (data) {
            if (data) {
                $("input[name='name']").val(data.name);
                $("input[name='id']").val(data.id);
                $("input[name='age']").val(data.age);
                $('.idcarda').attr('src', data.front_img?data.front_img:'/static/wapapp/shop/images/card_upload.png');
                $('.idcardb').attr('src', data.back_img?data.back_img:'/static/wapapp/shop/images/card_upload2.png');
                //设置地址初始值
                cityPicker.pickers[0].setSelectedIndex(data.province);
                cityPicker.pickers[1].setItems(cityPicker.pickers[0].getSelectedItem().children);
                cityPicker.pickers[1].setSelectedValue(data.city);
                cityPicker.pickers[2].setItems(cityPicker.pickers[1].getSelectedItem().children);
                cityPicker.pickers[2].setSelectedValue(data.district);
                var provinceText, cityText, districtText;
                provinceText=cityPicker.pickers[0].getSelectedItem().text;
                cityText=cityPicker.pickers[1].getSelectedItem().text;
                districtText=cityPicker.pickers[2].getSelectedItem().text;
                showCityPicker.value = provinceText + " " + cityText + " " + districtText;
                if(data.status==0){
                    $('#status_sh').html('<span style="color:orange;">(审核中)</span>');
                }else if(data.status==1){
                    $('#status_sh').html('<span style="color:green;">(审核通过)</span>')
                    $('.need_dis').addClass('mui-disabled');
                    $('.save-address button').addClass('mui-disabled');
                    $('.save-address button').html('联系管理员修改');
                    $("input[name='set-default']").attr('checked',true);
                }else if(data.status==2){
                    $('#status_sh').html('<span style="color:red;">(审核失败)</span>');
                    data.msg?(
                        $('#label_sh').append('<p>备注：'+data.msg+'</p>'),
                        $('#status_sh').html('<span style="color:oranged;">('+data.msg+')</span>')
                    ):'';
                }
            } else {

            }
        });
    }
});