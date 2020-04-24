define(function(require,exports,module){
    require("jquery");
    var common = require("common");
    var template = require("template");
    var page = 1;
    getData();

    function getData(){
        $get('/api/goods/comment_list',{'goods_id':id,page:page,num:10},function(data){
            if(!data.status){
                $(".noAttend").show();
            }else{
                var str = '';
                var data = data.data;
                for (var i = 0; i< data.length; i++) {
					var comment_content = data[i].comment_content;
					for(var j in comment_content){
						// 时间转换
						var c_time = new  Date(parseInt(comment_content[j].c_time)*1000);
	            		data[i].comment_content[j].c_time = common.getLocalTime(c_time);
	            		if (comment_content[j].reply_time) {
	            			var reply_time = new  Date(parseInt(comment_content[j].reply_time)*1000);
	            			data[i].comment_content[j].reply_time = common.getLocalTime(reply_time);
	            		}
	            		// 获取评论人的头像用户名
	            		if (comment_content[j].type == 0) {
	            			data[i].user_avatar = comment_content[j].user_avatar;
	            			data[i].user_nickname = comment_content[j].user_nickname;
	            		}
					}
					str += template('commentList',data[i]);
				}
                $('#comment').prepend(str);
            }
        })
    }
    //上拉加载
    $(window).scroll(function() {
      if ($(document).scrollTop() >= $(document).height() - $(window).height()) {
          page++;
          $get('/api/goods/comment_list',{'goods_id':id,page:page,num:10},function(data){
              if(!data.status){
                   $(".dropDown p").show();
              }else{
                  var str = '';
                  var data = data.data;
                  for (var i = 0; i< data.length; i++) {
  					var comment_content = data[i].comment_content;
  					for(var j in comment_content){
  						// 时间转换
  						var c_time = new  Date(parseInt(comment_content[j].c_time)*1000);
  	            		data[i].comment_content[j].c_time = common.getLocalTime(c_time);
  	            		if (comment_content[j].reply_time) {
  	            			var reply_time = new  Date(parseInt(comment_content[j].reply_time)*1000);
  	            			data[i].comment_content[j].reply_time = common.getLocalTime(reply_time);
  	            		}
  	            		// 获取评论人的头像用户名
  	            		if (comment_content[j].type == 0) {
  	            			data[i].user_avatar = comment_content[j].user_avatar;
  	            			data[i].user_nickname = comment_content[j].user_nickname;
  	            		}
  					}
  					str += template('commentList',data[i]);
  				}
                  $('#comment').prepend(str);
              }
          })
      }
    })
})
