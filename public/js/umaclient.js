/**
 * 对象转化为json字符串的方法 使用方法
 * var thing = {plugin: 'jquery-json', version: 2.3}; var encoded = $.toJSON( thing ); 
 * //'{"plugin":"jquery-json","version":2.3}'
 *
 */
(function($){var escapeable=/["\\\x00-\x1f\x7f-\x9f]/g,meta={'\b':'\\b','\t':'\\t','\n':'\\n','\f':'\\f','\r':'\\r','"':'\\"','\\':'\\\\'};$.toJSON=typeof JSON==='object'&&JSON.stringify?JSON.stringify:function(o){if(o===null){return'null';}
var type=typeof o;if(type==='undefined'){return undefined;}
if(type==='number'||type==='boolean'){return''+o;}
if(type==='string'){return $.quoteString(o);}
if(type==='object'){if(typeof o.toJSON==='function'){return $.toJSON(o.toJSON());}
if(o.constructor===Date){var month=o.getUTCMonth()+1,day=o.getUTCDate(),year=o.getUTCFullYear(),hours=o.getUTCHours(),minutes=o.getUTCMinutes(),seconds=o.getUTCSeconds(),milli=o.getUTCMilliseconds();if(month<10){month='0'+month;}
if(day<10){day='0'+day;}
if(hours<10){hours='0'+hours;}
if(minutes<10){minutes='0'+minutes;}
if(seconds<10){seconds='0'+seconds;}
if(milli<100){milli='0'+milli;}
if(milli<10){milli='0'+milli;}
return'"'+year+'-'+month+'-'+day+'T'+
hours+':'+minutes+':'+seconds+'.'+milli+'Z"';}
if(o.constructor===Array){var ret=[];for(var i=0;i<o.length;i++){ret.push($.toJSON(o[i])||'null');}
return'['+ret.join(',')+']';}
var name,val,pairs=[];for(var k in o){type=typeof k;if(type==='number'){name='"'+k+'"';}else if(type==='string'){name=$.quoteString(k);}else{continue;}
type=typeof o[k];if(type==='function'||type==='undefined'){continue;}
val=$.toJSON(o[k]);pairs.push(name+':'+val);}
return'{'+pairs.join(',')+'}';}};$.evalJSON=typeof JSON==='object'&&JSON.parse?JSON.parse:function(src){return eval('('+src+')');};$.secureEvalJSON=typeof JSON==='object'&&JSON.parse?JSON.parse:function(src){var filtered=src.replace(/\\["\\\/bfnrtu]/g,'@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,']').replace(/(?:^|:|,)(?:\s*\[)+/g,'');if(/^[\],:{}\s]*$/.test(filtered)){return eval('('+src+')');}else{throw new SyntaxError('Error parsing JSON, source is not valid.');}};$.quoteString=function(string){if(string.match(escapeable)){return'"'+string.replace(escapeable,function(a){var c=meta[a];if(typeof c==='string'){return c;}
c=a.charCodeAt();return'\\u00'+Math.floor(c/16).toString(16)+(c%16).toString(16);})+'"';}
return'"'+string+'"';};})(jQuery);



/**
 * 请在调用前加载jquery 微博调用接口 $(function(){
 * 
 * //获取认证链接并跳转 $("#getAuthorizeURL").click(function(){
 * umaWeibo.getAuthorizeURL('http://192.168.5.40/fg0344UMA/test.html','4f0e3d3c7c999dbb55000000');
 * });
 * 
 * //4f0e75e07c999dbb55000001 为返回的get形式返回的umaId
 * 
 * //上传带图片的微博，注意中文微博内容请编码 $("#upload").click(function(){ var params = {
 * status:encodeURIComponent('#质同道合#
 * 
 * @茶缸杨明 内容信息等等'),
 *       pic:'@http://u.umaman.com/service/image/get/id/4f0ea89a7c999dad41000001' };
 *       umaWeibo.post('4f0e75e07c999dbb55000001','statuses/upload',params,1);
 *       }); //注意对于包含中文的部分，进行转码encodeURIComponent
 * 
 * //发表微博 
 * $("#update").click(function(){ 
 *     var params = {
 *         status:encodeURIComponent('#质同道合#@茶缸杨明 内容信息等等') 
 *     };
 *     
 *     umaWeibo.post('4f0e75e07c999dbb55000001','statuses/update',params);
 * });
 * 
 * //发表带图片的微博 
 * $("#update").click(function(){ 
 *     var params = {
 *         status:encodeURIComponent('#质同道合#@茶缸杨明 内容信息等等'),
 *         pic:'@http://www.example.com/images/imagename.jpg'
 *     };
 *     
 *     umaWeibo.post('4f0e75e07c999dbb55000001','statuses/upload',params,1);
 * }); 
 * 
 * //记录数据 
 * $("#record").click(function(){ 
 *     var datas = { 
 *         'key1':'value1',
 *         'key2':'value2', 'key3':'value3' 
 *     };
 * 	   umaWeibo.record('4f0e75e07c999dbb55000001',datas); }); 
 * });
 * 
 * 获取记录方法
 * umaWeibo.getRecord(umaId,{},{},0,10);
 * 
 */
if (typeof console == "undefined" || typeof console.log == "undefined") {
	var console = {
		log : function() {
			return false;
		},
		info : function() {
			return false;
		}
	};
}

function umaParams(object) {
	var paramStr = '';
	if (typeof (object) == 'object') {
		for ( var key in object) {
			paramStr += key + "::" + object[key] + "||";
		}
	}
	return paramStr;
}

function getUmaCallback(arguments) {
	if(arguments.length>0) {
		var callback = arguments[arguments.length-1];
		if(typeof callback=='function') {
			return callback;
		}
	}
	return false;
}

var umaWeibo = {
	callback : 'console.info',
	serviceURL : 'http://scrm.umaman.com/soa/sina/jsonp',
	getAuthorizeURL : function() {
		var redirectUri = $.trim(arguments[0]);
		var poject = $.trim(arguments[1]);
		if (redirectUri == '' || poject == '') {
			alert('Params Missing:redirectUri and poject is required');
		}
		$.ajax({
			url : umaWeibo.serviceURL + '?method=getAuthorizeURL&argument1=' + redirectUri + '&argument2=' + poject,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				var ua = navigator.userAgent.toLowerCase();
				var isMobile = '';
				if(ua.indexOf('ipad') != -1 || ua.indexOf('iphone os') != -1 || ua.indexOf('android') != -1) {
					isMobile = '&display=mobile';
				}
				window.location.href = data+isMobile;
			}
		});
	},
	getToken : function(umaSid) {
		var callback = getUmaCallback(arguments);
		if(callback==false) {
			callback = this.callback;
		}
		
		$.ajax({
			url : umaWeibo.serviceURL + '?method=getToken&argument1=' + umaSid,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				if (typeof callback == 'string') {
					eval(callback + '(data);');
				}
				else {
					callback(data);
				}
			}
		});
	},
	addToken : function(projectId,token) {
		var callback = getUmaCallback(arguments);
		if(callback==false) {
			callback = this.callback;
		}
		
		token = encodeURIComponent($.toJSON(token)); 
		$.ajax({
			url : umaWeibo.serviceURL + '?method=addToken&argument1=' + projectId+'&argument2='+token,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				if (typeof callback == 'string') {
					eval(callback + '(data);');
				}
				else {
					callback(data);
				}
			}
		});
	},
	getAccessTokenList : function(number) {
		var callback = getUmaCallback(arguments);
		if(callback==false) {
			callback = this.callback;
		}
		$.ajax({
			url : umaWeibo.serviceURL + '?method=getAccessTokenList&argument1=' + number,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				if (typeof callback == 'string') {
					eval(callback + '(data);');
				}
				else {
					callback(data);
				}
			}
		});
	},
	get : function() {
		var umaSid = $.trim(arguments[0]);
		var apiURL = $.trim(arguments[1]);
		var params = umaWeibo.params(arguments[2]);
		if (umaSid == '' || apiURL == '') {
			alert('Params Missing:umaSid and apiURL is required');
		}
		var callback = getUmaCallback(arguments);
		if(callback==false) {
			callback = this.callback;
		}

		$.ajax({
			url : umaWeibo.serviceURL + '?method=get&argument1=' + umaSid + '&argument2=' + apiURL + '&argument3=' + params,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				if (typeof callback == 'string') {
					eval(callback + '(data);');
				}
				else {
					callback(data);
				}
			}
		});
	},
	post : function() {
		var umaSid = $.trim(arguments[0]);
		var apiURL = $.trim(arguments[1]);
		var params = umaWeibo.params(arguments[2]);
		var muti   = (arguments[3] == undefined || arguments[3] == 0 || arguments[3] == '') ? 0 : 1;
		var callback = getUmaCallback(arguments);
		if(callback==false) {
			callback = this.callback;
		}

		if (umaSid == '' || apiURL == '') {
			alert('Params Missing:umaSid and apiURL is required');
		}
		$.ajax({
			url : umaWeibo.serviceURL + '?method=post&argument1=' + umaSid + '&argument2=' + apiURL + '&argument3=' + params + '&argument4=' + muti,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				if (typeof callback == 'string') {
					eval(callback + '(data);');
				}
				else {
					callback(data);
				}
			}
		});
	},
	record : function() {
		var umaSid   = $.trim(arguments[0]);
		var datas    = encodeURIComponent($.toJSON(arguments[1]));
		var callback = getUmaCallback(arguments);
		if(callback==false) {
			callback = this.callback;
		}

		if (umaSid == '') {
			alert('Params Missing:umaSid is required');
		}
		$.ajax({
			url : umaWeibo.serviceURL + '?method=record&argument1=' + umaSid + '&argument2=' + datas,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				if (typeof callback == 'string') {
					eval(callback + '(data);');
				}
				else {
					callback(data);
				}
			}
		});
	},
	getRecord : function() {
		var umaSid    = $.trim(arguments[0]);
		var condition = encodeURIComponent($.toJSON( arguments[1] )); 
		var order     = encodeURIComponent($.toJSON( arguments[2] )); 
		var offset    = arguments[3]==undefined ? 0 : arguments[3];
		var limit     = arguments[4]==undefined ? 0 : arguments[4];
		var callback = getUmaCallback(arguments);
		if(callback==false) {
			callback = this.callback;
		}

		if (umaSid == '') {
			alert('Params Missing:umaSid is required');
		}
		$.ajax({
			url : umaWeibo.serviceURL + '?method=getRecord&argument1=' + umaSid + '&argument2=' + condition + '&argument3=' + order + '&argument4=' + offset +"&argument5="+limit,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				if (typeof callback == 'string') {
					eval(callback + '(data);');
				}
				else {
					callback(data);
				}
			}
		});
	},
	params : umaParams

};

/**
 * 调用方法 $(function(){ umaImage.callback = function(data) {
 * $("img[id=abc]").attr('src',data); }
 * umaImage.qrcode(encodeURIComponent('测试内容'));
 * 
 * umaImage.callback = function(data) {
 * $("img[id=abc]").attr('src','data:image/png;base64,'.data); }
 * 
 * var bgImage = 'http://www.google.com/images/srpr/logo3w.png'; var params = [{
 * x:10, y:10,
 * images:'http://www.gravatar.com/avatar/69a38b2f586c9efbf64d0198c2a16eb7.png'
 * },{ x:50, y:10,
 * images:'http://www.gravatar.com/avatar/69a38b2f586c9efbf64d0198c2a16eb7.png'
 * }]; var outType = 'url';//urluri base64
 * umaImage.mergeImage(bgImage,params,outType);
 * 
 * });
 * 
 */
var umaImage = {
	callback : 'console.info',
	serviceURL : 'http://scrm.umaman.com/soa/image/jsonp',
	params : umaParams,
	alphaPng : function(text, imgWidth, imgHeight, fontFamily, fontSize, fontColor) {
		var callback = getUmaCallback(arguments);
		if(callback==false) {
			callback = this.callback;
		}
		$.ajax({
			url : umaImage.serviceURL + '?method=alphaPngUrl&argument1=' + encodeURIComponent(text) + '&argument2=' + imgWidth + '&argument3=' + imgHeight + '&argument4=' + fontFamily + '&argument5='
					+ fontSize + '&argument6=' + fontColor,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				if (typeof callback == 'string') {
					eval(callback + '(data);');
				}
				else {
					callback(data);
				}
			}
		});
	},
	mergeImage : function() {
		var bgImage = arguments[0] == undefined ? console.log("BgImage is Null") : encodeURIComponent($.trim(arguments[0]));
		var params = arguments[1] == undefined ? console.log("Params is Null") : encodeURIComponent($.toJSON(arguments[1]));
		var type = arguments[2] == undefined ? 'url' : $.trim(arguments[2]);
		var callback = getUmaCallback(arguments);
		if(callback==false) {
			callback = this.callback;
		}

		$.ajax({
			url : umaImage.serviceURL + '?method=mergeImage&argument1=' + bgImage + '&argument2=' + params + '&argument2=' + type,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				if (typeof callback == 'string') {
					eval(callback + '(data);');
				}
				else {
					callback(data);
				}
			}
		});
	},
	qrcode : function() {
		var text = $.trim(arguments[0]);
		var bcolor = arguments[1] == undefined ? 'ffffff' : $.trim(arguments[1]);
		var fcolor = arguments[2] == undefined ? '000000' : $.trim(arguments[2]);
		var type = arguments[3] == undefined ? 'url' : $.trim(arguments[3]);
		var callback = getUmaCallback(arguments);
		if(callback==false) {
			callback = this.callback;
		}

		$.ajax({
			url : umaImage.serviceURL + '?method=qrcode&argument1=' + text + '&argument2=' + bcolor + '&argument3=' + fcolor + '&argument4=' + type,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				if (typeof callback == 'string') {
					eval(callback + '(data);');
				}
				else {
					callback(data);
				}
			}
		});
	}
};

/**
 * 调用方法 function callbackfunctionname(data) { 业务逻辑代码 }
 * 
 * $(function(){ umaPdf.callback = 'callbackfunctionname'; //获取页面的列表
 * umaPdf.getPageList('69a38b2f586c9efbf64d0198c2a16eb7');
 * 
 * umaPdf.callback = 'callbackfunctionname';
 * umaPdf.search('69a38b2f586c9efbf64d0198c2a16eb7','搜索关键词');
 * 
 * umaPdf.callback = 'callbackfunctionname'; var pageIds = {
 * "0"=>'69a38b2f586c9efbf64d0198c2a16eb7',
 * "1"=>'69a38b2f586c9efbf64d0198c2a16eb7',
 * "2"=>'69a38b2f586c9efbf64d0198c2a16eb7' }; umaPdf.merge(pageIds); });
 * 
 */
var umaPdf = {
	callback : 'console.log',
	serviceURL : 'http://scrm.umaman.com/soa/pdf/jsonp',
	params : umaParams,
	getPageList : function(bookId) {
		var callback = getUmaCallback(arguments);
		if(callback==false) {
			callback = this.callback;
		}
		$.ajax({
			url : umaPdf.serviceURL + '?method=getPageList&argument1=' + bookId,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				if (typeof callback == 'string') {
					eval(callback + '(data);');
				}
				else {
					callback(data);
				}
			}
		});
	},
	search : function(bookId, keyword) {
		var callback = getUmaCallback(arguments);
		if(callback==false) {
			callback = this.callback;
		}
		keyword = encodeURIComponent(keyword);

		$.ajax({
			url : umaPdf.serviceURL + '?method=search&argument1=' + bookId + '&argument2=' + keyword,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				if (typeof callback == 'string') {
					eval(callback + '(data);');
				}
				else {
					callback(data);
				}
			}
		});
	},
	merge : function(pageIds) {
		pageIds = $.toJSON(pageIds);
		var callback = getUmaCallback(arguments);
		if(callback==false) {
			callback = this.callback;
		}
		$.ajax({
			url : umaPdf.serviceURL + '?method=merge&argument1=' + pageIds,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				if (typeof callback == 'string') {
					eval(callback + '(data);');
				}
				else {
					callback(data);
				}
			}
		});
	}
};

var umaAlipay = {
	callback : 'console.log',
	serviceURL : 'http://scrm.umaman.com/commerce/order/jsonp',
	setProduct : function(order, product, price, amount) {
		var callback = this.callback;
		product = encodeURIComponent(product);
		$.ajax({
			url : umaAlipay.serviceURL + '?method=setProduct&argument1=' + order + '&argument2=' + product + '&argument3=' + price + '&argument4=' + amount,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				callback(data);
			}
		});
	},
	setAddress : function(order, name, mobile, province, city, area, address, postcode) {
		var callback = this.callback;
		name = encodeURIComponent(name);
		mobile = encodeURIComponent(mobile);
		province = encodeURIComponent(province);
		city = encodeURIComponent(city);
		area = encodeURIComponent(area);
		address = encodeURIComponent(address);
		postcode = encodeURIComponent(postcode);
		$.ajax({
			url : umaAlipay.serviceURL + '?method=setAddress&argument1=' + order + '&argument2=' + name + '&argument3=' + mobile + '&argument4=' + province + '&argument5= ' + city
					+ '&argument6=' + area + '&argument7=' + address + '&argument8=' + postcode,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				callback(data);
			}
		});
	},

	findOrder : function(offset, limit) {
		var callback = this.callback;
		$.ajax({
			url : umaAlipay.serviceURL + '?method=findOrder&argument1=' + offset + '&argument2=' + limit,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				callback(data);
			}
		});
	},
	alipay : function(order) {
		window.location.href = 'http://scrm.umaman.com/commerce/order/alipay?_id=' + order;
	}
};

var umaForm = {
	serviceURL : 'http://scrm.umaman.com/soa/form/jsonp',
	add : function(form, data, callback) {
		data = $.toJSON(data);
		data = encodeURIComponent(data);
		$.ajax({
			url : this.serviceURL + '?method=add&argument1=' + form + '&argument2=' + data,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				callback(data);
			}
		});
	},

	find : function(form, query, sort, skip, limit, callback) {
		if (query == null) {
			query = '{}';
		}
		else {
			query = $.toJSON(query);
		}
		query = encodeURIComponent(query);
		if (sort == null) {
			sort = '{}';
		}
		else {
			sort = $.toJSON(sort);
		}
		sort = encodeURIComponent(sort);
		if (skip == null) {
			skip = 0;
		}
		if (limit == null) {
			limit = 20;
		}
		$.ajax({
			url : this.serviceURL + '?method=find&argument1=' + form + '&argument2=' + query + '&argument3=' + sort + '&argument4=' + skip + '&argument5=' + limit,
			dataType : 'jsonp',
			jsonp : 'jsonpcallback',
			success : function(data) {
				callback(data);
			}
		});
	}
};
