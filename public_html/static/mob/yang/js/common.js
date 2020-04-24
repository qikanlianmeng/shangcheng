/*
 * @Author: 王世文
 * @Date: 2018-10-24 17:33:15
 * @Last Modified by: 王世文
 * @Last Modified time: 2018-12-20 10:52:17
 */

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


// 自定义时间戳

// new Date(time*1000).Format("yyyy-MM-dd hh:mm:ss");    调用格式
Date.prototype.Format = function (fmt) { //author: meizz
	var o = {
	"M+": this.getMonth() + 1, //月份
	"d+": this.getDate(), //日
	"h+": this.getHours(), //小时
	"m+": this.getMinutes(), //分
	"s+": this.getSeconds(), //秒
	"q+": Math.floor((this.getMonth() + 3) / 3), //季度
	"S": this.getMilliseconds() //毫秒
	};
	if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
	for (var k in o)
	if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
	return fmt;
	};
	Date.Diff = function (start, end, fmt) {
	var time = end.getTime() - start.getTime();
	var day = time / (1000 * 3600 * 24);
	var hour = (time % (1000 * 3600 * 24)) / (3600 * 1000);
	var min = (time % (1000 * 3600)) / (60 * 1000);
	var sec = (time % (1000 * 60)) / 1000;
	var o = {
	"d+": parseInt(day), //日
	"h+": parseInt(hour), //小时
	"m+": parseInt(min), //分
	"s+": parseInt(sec), //秒
	};
	
	for (var k in o)
	if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
	return fmt;
	};


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

