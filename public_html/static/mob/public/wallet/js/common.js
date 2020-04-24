/*
 * @Author: 王世文
 * @Date: 2018-10-24 17:33:15
 * @Last Modified by: mikey.11
 * @Last Modified time: 2018-12-21 16:10:08
 */

// var baseUrl='http://midu.ruanduo.com/';
var baseUrl='';




function $get(url,data,func){
	var surl=url.replace(/^(\/)|(\/)$/g,'');	
	$.ajax({
		type:"get",
		data:data,
		url:baseUrl+url,
		success:function(data){
			
			func(data);
		}
	});
};

function $post(url,data,func){
	var surl=url.replace(/^(\/)|(\/)$/g,'');
	$.ajax({
		type:"post",
		data:data,
		url:baseUrl+url,
		success:function(data){
			func(data);
		}
	});
};



	// function openW(name,url,)




	

// 时间转换格式一
function timetrans_1(date){
	var date = new Date(date*1000);//如果date为13位不需要乘1000
	var Y = date.getFullYear() + '-';
	var M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1) + '-';
	var D = (date.getDate() < 10 ? '0' + (date.getDate()) : date.getDate()) + ' ';
	var h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':';
	var m = (date.getMinutes() <10 ? '0' + date.getMinutes() : date.getMinutes()) + ':';
	var s = (date.getSeconds() <10 ? '0' + date.getSeconds() : date.getSeconds());
	return Y+M+D;
}
// 时间转换格式二
function timetrans_2(date){
	var date = new Date(date*1000);//如果date为13位不需要乘1000
	var Y = date.getFullYear() + '-';
	var M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1) + '-';
	var D = (date.getDate() < 10 ? '0' + (date.getDate()) : date.getDate()) + ' ';
	var h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':';
	var m = (date.getMinutes() <10 ? '0' + date.getMinutes() : date.getMinutes()) + ':';
	var s = (date.getSeconds() <10 ? '0' + date.getSeconds() : date.getSeconds());
	return M+D;
}
// 时间转换格式三
function timetrans_3(date){
	var date = new Date(date*1000);//如果date为13位不需要乘1000
	var Y = date.getFullYear() + '-';
	var M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1) + '-';
	var D = (date.getDate() < 10 ? '0' + (date.getDate()) : date.getDate()) + ' ';
	var h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':';
	var m = (date.getMinutes() <10 ? '0' + date.getMinutes() : date.getMinutes()) + ':';
	var s = (date.getSeconds() <10 ? '0' + date.getSeconds() : date.getSeconds());
	return Y+M+D+h+m+s;
}
// 时间转换格式4
function timetrans_4(date){
	var date = new Date(date*1000);//如果date为13位不需要乘1000
	var Y = date.getFullYear() + '-';
	var M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1) + '-';
	var D = (date.getDate() < 10 ? '0' + (date.getDate()) : date.getDate()) + ' ';
	var h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':';
	var m = (date.getMinutes() <10 ? '0' + date.getMinutes() : date.getMinutes()) ;
	var s =  ':'+(date.getSeconds() <10 ? '0' + date.getSeconds() : date.getSeconds());
	return Y+M+D+h+m;
}
// 手机判断
/* if(mui.os.ios==true){
	//...操作
	// alert('ios')
	// $('document,body').css('margin-top','20px')
}
if(mui.os.android==true){
	//...操作
	// alert('安卓')
}  */
