seajs.use(['common','template','echo'],function(c,tem,echo){
	//图片延迟加载
	echo.init({
		offset: 0,
		throttle: 0
	});
	var allData = [];

	var par={
		    page:1,
		    num:10
		};
	var $miaoSha = $('#miaoSha');
	var $dropDown = $('.dropDown');
	$get("/api/goods/flash_sale",par,function(data){
		if(data.status){
            console.log(data.data)
			var str = '';
			for(var i=0; i<data.data.length; i++){
				allData.push(data.data[i]);
				var startTime = data.data[i].start_time*1000;
				var endTime = data.data[i].end_time*1000;
				data.data[i].start_time = startTime;
				data.data[i].end_time = endTime;
				var now = new Date();
				var nowTime = now.getTime();
				data.data[i].now_time = nowTime;
				var runm = data.data[i].goods_num - data.data[i].buy_num;
                data.data[i].runm = runm;
				var time = countDown(startTime,endTime,runm);
				data.data[i].time = time;
				var bai = parseInt(100*(data.data[i].goods_num-data.data[i].buy_num)/data.data[i].goods_num);
                data.data[i].bai = bai;
				str += tem('miaoShaList',data.data[i]);
			}
			timer;
			$miaoSha.html(str);
			 //防止页面上加载的列表不够一页，无法实现上拉加载
            if($(".mui-content").innerHeight() < $(window).height()){
            	$(".mui-content").css("minHeight",$(window).height()+1);
            }
		}else{
			$dropDown.hide();
            $('.noContent').show();
        }
	});
	//下拉加载
	c.upLoad(
		$(document),
		$dropDown,
		function(suc,err){
			par.page++;
			$get("/api/goods/flash_sale",par,function(data){
				if(data.status){
                    console.log(data)
					var str = '';
					for(var i=0; i<data.data.length; i++){
						allData.push(data.data[i]);
						var startTime = data.data[i].start_time*1000;
						var endTime = data.data[i].end_time*1000;
						data.data[i].start_time = startTime;
						data.data[i].end_time = endTime;
						var now = new Date();
						var nowTime = now.getTime();
						data.data[i].now_time = nowTime;
						var runm = data.data[i].goods_num - data.data[i].buy_num;
						var time = countDown(startTime,endTime,runm);
						data.data[i].time = time;
						var bai = parseInt(100*(data.data[i].goods_num-data.data[i].buy_num)/data.data[i].goods_num);
						data.data[i].bai = bai;
						str += tem('miaoShaList',data.data[i]);
					}
					$miaoSha.append(str);
					echo.init({
						offset: 0,
						throttle: 0
					});
					suc();
				}else{
					err();
				}
			})
		}
	);
	//定时器函数
    var timer = setInterval(function(){
		if(allData){
			for(var i=0;i<allData.length;i++){
				var id = allData[i].id;
				var now = new Date();
				var nowTime = now.getTime();
				var startTime = allData[i].start_time;
				var endTime = allData[i].end_time;
				var runm = allData[i].goods_num - allData[i].buy_num;
				var time = countDown(startTime,endTime,runm);
				if(time == 0){
				//数量结束
					$('#goods_'+id).find('.countdown').html('<i class="iconfont icon-xianshi"></i>' +
						' 活动已结束 <span style="font-size: 12px;">'+allData[i].buy_num+'件已秒杀完</span>');
					$('#goods_'+id).find('.progressa,#bar').hide();
					$('#goods_'+id).find('.swapping-div').html('<span class="sale-price mui-pull-right color-black mui-block swapping" style="background:#aaa;color:#fff !important;">已结束</span>');
				}else if(time == 1){
				//截止时间结束
					$('#goods_'+id).find('.countdown').html('<i class="iconfont icon-xianshi"></i>' +
						' 活动已结束');
					$('#goods_'+id).find('.progressa,#bar').hide();
					$('#goods_'+id).find('.swapping-div').html('<span class="sale-price mui-pull-right color-black mui-block swapping" style="background:#aaa;color:#fff !important;">已结束</span>');
				}else{
				//进行中
					if(startTime > nowTime){
					//即将开始
						$('#goods_'+id).find('.countdown').html('<i class="iconfont icon-xianshi"></i> 距离活动开始还剩:<span class="hei">'+time[0]+'</span>天<span class="hei">'+time[1]+'</span>时<span class="hei">'+time[2]+'</span>分<span class="hei">'+time[3]+'</span>秒');
						$('#goods_'+id).find('.progressa,#bar').show();
						$('#goods_'+id).find('.swapping-div').html('<span class="sale-price mui-pull-right color-black mui-block swapping" style="background:#aaa;color:#fff !important;">即将开始</span>');
					}else{
					//去秒杀
						$('#goods_'+id).find('.countdown').html('<i class="iconfont icon-xianshi"></i> 距离活动结束还剩:<span class="hei">'+time[0]+'</span>天<span class="hei">'+time[1]+'</span>时<span class="hei">'+time[2]+'</span>分<span class="hei">'+time[3]+'</span>秒');
						$('#goods_'+id).find('.progressa,#bar').show();
						$('#goods_'+id).find('.swapping-div').html('<span class="sale-price mui-pull-right color-black mui-block swapping" style="color:#fff !important;background-color: #ff3b01;margin-top: 2px;">去秒杀</span>');
					}
				}
			}
		}
	},1000);

	/*
	* 根据两个时间差返回时间数组
	* sTime开始时间
	* eTime结束时间
	* rnum 余下秒杀数量
	* */
	function countDown(sTime, eTime, rnum) {
		var now = new Date();
		var nowTime = now.getTime();
		var time;
		if (sTime > nowTime) {
			//距离开始还有多少时间
			time = parseInt((sTime - nowTime) / 1000);
		} else if (((sTime <= nowTime) && (nowTime < eTime)) && (rnum != 0 )) {
			//距离结束还有多久
			time = parseInt((eTime - nowTime) / 1000);
		} else {
			//到时间截止或者秒杀完--已结束
			if (rnum == 0) {//如果数量=0，结束
				return 0;
			} else {
			//如果截止时间到了，结束
				return 1;
			}$(document).on("click",".endbtn",function(){
                mui.toast("活动已结束")
            })
		}
		var d = parseInt(time / 86400);
		time = time % 86400;  //分别取余数
		var h = parseInt(time / 3600);
		time %= 3600;
		var m = parseInt(time / 60);
		var s = time % 60;
		/*if(((d==0)&&(h==0)&&(m==0)&&(s==0))||(rnum == 0 )){
		 window.location.reload();
		 }*/
		return [d, h, m, s];
	}
    $(document).on("click",".endbtn",function(){
        mui.toast("活动已结束")
    })
});
