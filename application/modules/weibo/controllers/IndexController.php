<?php
class Weibo_IndexController extends Zend_Controller_Action
{
    public function init()
    {
    	$this->getHelper('viewRenderer')->setNoRender(true);
    }
    
    /**
     *
     * 微博授权
     * 
     * @param string callbackUrl  回调URL
     * @return 
     * 前端调用实例
     * window.location.href = "http://iwebsite/weibo/index/authorize?callbackUrl=http://iwebsite/default/weibo/index";
     * */
    public function authorizeAction()
    {
    	//http://iwebsite/weibo/index/authorize?callbackUrl=http://iwebsite/default/index/index
    	$callbackUrl = trim($this->getRequest()->getParam('callbackUrl',''));//回调URL
    	$callbackUrl = urlencode($callbackUrl);
    	$config = Zend_Registry::get("config");
    	$path = $config['global']['path'];
    	$client = new iSina($config['iWeibo']['project_id']);
    	$scheme = $this->getRequest()->getScheme();
    	$host = $this->getRequest()->getHttpHost();
    	$detect = new Mobile_Detect();
    	$m = "";
    	if($detect->isMobile()) { //如果是手机设备
    		$m ="&display=mobile";
    	}
    	elseif($detect->isTablet()) { //如果是平板设备
    	}
    	else { //如果是PC设备
    	}
    	$moduleName = trim($this->getRequest()->getModuleName());
    	$myCallbackUrl = "{$scheme}://{$host}{$path}{$moduleName}/index/callback?callbackUrl={$callbackUrl}";
    	$authorizeURL = $client->getAuthorizeURL($myCallbackUrl);    	
    	$this->_redirect($authorizeURL.$m);

    }
    
    /**
     *
     * 微博回调
     * @param string callbackUrl  回调URL
     * @return 
     * */
    public function callbackAction()
    {
    	//http://iwebsite/weibo/index/callback?callbackUrl=http://iwebsite/default/index/index
    	$callbackUrl = trim($this->getRequest()->getParam('callbackUrl'));//返回URL
    	$callbackUrl = urldecode($callbackUrl);
    	//从uma返回的参数umaId和uid，不要修改
    	$umaId = trim($this->getRequest()->getParam('umaId'));
    	$uid = trim($this->getRequest()->getParam('uid'));
    	//回调处理
    	$redirectUrl = $this->callback($umaId,$uid,$callbackUrl);    	
    	$this->_redirect($redirectUrl);
    }
    
    /**
     * 发送POST类型的请求到微博API
     * @param string $umaId UMA系统中的唯一授权表示 通过这个标示获取系统中的新浪微博access_token
     * @param string $url   新浪微博API的请求地址  例如：statuses/user_timeline表示获取用户发布的微博
     * @param array  $parameters 新浪微博API请求地址对应的API参数
     * @param bool $multi 默认为false。当POST参数中包含上传文件信息时，请将$multi参数设置为true
     *
     * 例如：上传带图片的微博接口
     * URL为statuses/upload
     * 参数为pic表示上传图片，路径为@http://www.filedomain.com/filepath/filename
     * 此时，请将$multi设置为true
     *
     * @return json
     * 前端调用实例
     * 
     * 
     * 		var params = {};
            params['umaId'] = '5189ce69479619635f000a1c';
            params['url'] = 'statuses/update';
            params['multi'] = 'false';
            params['parameters'] = {status:'用户post测试',visible:0};
    		
            $.ajax(
            {
                url:web_path+'weibo/index/post',
                type:'POST',
                data:params,
                dataType: "json",
                success:function(data) {
                    console.info(data);
                    if(data.success) {
                    	alert(data.result);
                    }
                    else {
                        alert(data.message);
                    }
                }
            });
     */
    public function postAction() 
    {
    	try {
    		$umaId = trim($this->getRequest()->getParam('umaId',''));//UMAID
    		$url = trim($this->getRequest()->getParam('url',''));//新浪微博API的请求地址
    		$parameters = $this->getRequest()->getParam('parameters');//新浪微博API请求地址对应的API参数
    		$multi = trim($this->getRequest()->getParam('multi','false'));//新浪微博API请求地址对应的API参数
    		if(empty($umaId)) {
    			exit($this->response(false,'UMA id不能为空'));
    		}
    		if(empty($url)) {
    			exit($this->response(false,'新浪微博API的请求地址不能为空'));
    		}
    		if(empty($parameters)) {
    			exit($this->response(false,'新浪微博API请求地址对应的API参数不能为空'));
    		}
    		if(strtolower($multi) == 'true')
    		{
    			$multi = true;
    		}else{
    			$multi = false;
    		}
    		$config = Zend_Registry::get("config");
    		$client = new iSina($config['iWeibo']['project_id']);
    		
    		$ret= $client->post($umaId, $url, $parameters, $multi);
    		if($ret ==false){
    			exit($this->response(false,"不明错误已发生",$ret));
    		}else{
    			exit($this->response(true,"OK",$ret));
    		}
    	}
    	catch (Exception $e) {
    		exit($this->response(false,$e->getMessage()));
    	}
    }
    
    /**
     * 发送GET类型的请求到微博API
     * @param string $umaId UMA系统中的唯一授权表示 通过这个标示获取系统中的新浪微博access_token
     * @param string $url   新浪微博API的请求地址  例如：statuses/user_timeline表示获取用户发布的微博
     * @param array  $parameters 新浪微博API请求地址对应的API参数
     * @return json
     * 前端调用实例
     * 
     * 		var params = {};
            params['umaId'] = '5189ce69479619635f000a1c';
            params['url'] = 'users/show';
            params['multi'] = 'false';
            params['parameters'] = {uid:'1596822015'};           
            $.ajax(
            {
                url:web_path+'weibo/index/get',
                type:'POST',
                data:params,
                dataType: "json",
                success:function(data) {
                    console.info(data);
                    if(data.success) {
                    	alert(data.result);
                    }
                    else {
                        alert(data.message);
                    }
                }
            });
     */
    public function getAction() 
    {
    	try {
    		$umaId = trim($this->getRequest()->getParam('umaId',''));//UMAID
    		$url = trim($this->getRequest()->getParam('url',''));//新浪微博API的请求地址
    		$parameters = $this->getRequest()->getParam('parameters');//新浪微博API请求地址对应的API参数
    		if(empty($umaId)) {
    			exit($this->response(false,'UMA id不能为空'));
    		}
    		if(empty($url)) {
    			exit($this->response(false,'新浪微博API的请求地址不能为空'));
    		}
    		if(empty($parameters)) {
    			exit($this->response(false,'新浪微博API请求地址对应的API参数不能为空'));
    		}
    		
    		$config = Zend_Registry::get("config");
    		$client = new iSina($config['iWeibo']['project_id']);
    		
    		$ret= $client->get($umaId, $url, $parameters);
    		if($ret ==false){
    			exit($this->response(false,"不明错误已发生",$ret));
    		}else{
    			exit($this->response(true,"OK",$ret));
    		}
    	}
    	catch (Exception $e) {
    		exit($this->response(false,$e->getMessage()));
    	}
    }
    
    /**
     * 微博分享
     * @param string content  分享内容 不能为空 
     * @param string pic_url 分享图片 可空 
     * @param string follow 自动关注的微博UID 可空 
     * @param int friendNum  @朋友数 可空  
     * @param array friends  @朋友 可空  如果有值，参数friendNum被忽略
     * @param string mobile  手机 可空
     * @return json
     * 
     * 前端调用实例
     * 		var content = '微博接口测试';
            var pic_url = '';
            var params = {};
            params['umaId'] = '5189ce69479619635f000a1c';
            params['uid'] = '11258900';
            params['screen_name'] = 'xxxxx';
            params['content'] = content;
            params['pic_url'] = pic_url;
            params['follow'] = '1942631884';//自动关注微博UID 定海东
            //params['friendNum'] = 3;//随机@3个朋友
            //params['friends'] = ['@1','@2','@3'];//如果是指定@朋友的话
            params['mobile'] = '13564100096';
            
            $.ajax(
            {
                url:web_path+'weibo/index/share',
                type:'POST',
                data:params,
                dataType: "json",
                success:function(data) {
                    console.info(data);
                    if(data.success) {
                    	alert(data.result);
                    }
                    else {
                        alert(data.message);
                    }
                }
            });
     * */
    public function shareAction()
    {
    	//http://iwebsite/weibo/index/share?jsonpcallback=?&umaId=1233434&uid=1234&pic_url=&content=32323&follow=微博UID&friendNum=3&friends[]=guo
    	try {    		
    		$umaId = trim($this->getRequest()->getParam('umaId',''));//UMAID
    		$uid = trim($this->getRequest()->getParam('uid',''));//微博UID
    		$screen_name = trim($this->getRequest()->getParam('screen_name',''));//微博昵称
    		$content =trim($this->getRequest()->getParam('content'));//分享内容
    		$pic_url =trim($this->getRequest()->getParam('pic_url'));//分享图片
    		$follow =trim($this->getRequest()->getParam('follow',''));//自动关注的微博UID
    		$friendNum =intval($this->getRequest()->getParam('friendNum','0'));//@朋友数
    		$friends =$this->getRequest()->getParam('friends');//@朋友    		
    		$mobile =$this->getRequest()->getParam('mobile');//手机
    		
    		if(empty($umaId)) {
    			exit($this->response(false,'UMA id不能为空'));
    		}  
    		if(empty($uid)) {
    			exit($this->response(false,'微博UID不能为空'));
    		}  		
    		if(empty($content)) {
    			exit($this->response(false,'分享内容不能为空'));
    		}
    		/*
    		if(empty($pic_url)) {
    			exit($this->response(false,'分享图片不能为空'));
    		}*/
    		if(!empty($mobile) && !isValidMobile($mobile)) {
    			exit($this->response(false,'手机格式不正确'));
    		}
    		//特殊的业务逻辑代码开始
    		//特殊的业务逻辑代码结束
    		
    		//微博分享
    		$result = $this->share($umaId,$uid,$content,$pic_url,$follow,$friendNum,$friends);
    		exit($this->response(true,"微博分享成功",$result));
    	}
    	catch (Exception $e) {
    		exit($this->response(false,$e->getMessage()));
    	}
    }
    
    /**
     * 获取微博用户信息
     * @return json
     *
     * 前端调用实例
	     var params = {};
	     params['umaId'] = '5189ce69479619635f000a1c';
         params['uid'] = '11258900';
	     $.ajax(
	     {
	     	url:web_path+'weibo/index/get-user-info',
	     	type:'POST',
	     	data:params,
	     	dataType: "json",
	     	success:function(data) {
	     		console.info(data);
			     if(data.success) {
			     	alert(data.result);
			     }
			     else {
			     	alert(data.message);
			     }
	     	}
	     });
     * */
    public function getUserInfoAction()
    {
    	//http://iwebsite/weibo/index/get-user-info?jsonpcallback=?&umaId=1233434&uid=1234
    	try {
    		$umaId = trim($this->getRequest()->getParam('umaId',''));//UMAID
    		$uid = trim($this->getRequest()->getParam('uid',''));//微博UID
    		
    		if(empty($umaId)) {
    			exit($this->response(false,'UMA id不能为空'));
    		}
    		if(empty($uid)) {
    			exit($this->response(false,'微博UID不能为空'));
    		}
    		$config = Zend_Registry::get("config");
    		$client = new iSina($config['iWeibo']['project_id']);
    		//获取微博用户信息
    		$result = $this->getUser($client,$umaId,$uid);
    		exit($this->response(true,"获取微博用户信息成功",$result));
    	}
    	catch (Exception $e) {
    		exit($this->response(false,$e->getMessage()));
    	}
    }  
    
    /**
     * 获取微博好友
     * @return json
     *
     * 前端调用实例
		     var params = {};
		     params['umaId'] = '5189ce69479619635f000a1c';
             params['uid'] = '11258900';
		     $.ajax(
		     {
		     	url:web_path+'weibo/index/get-friend-list',
		     	type:'POST',
		     	data:params,
		     	dataType: "json",
		     	success:function(data) {
		     		console.info(data);
				     if(data.success) {
				     	alert(data.result);
				     }
				     else {
				     	alert(data.message);
				     }
		     	}
		     });
     * */
    public function getFriendListAction()
    {
    	//http://iwebsite/weibo/index/get-friend-list?jsonpcallback=?&umaId=1233434&uid=1234
    	try {
    		$umaId = trim($this->getRequest()->getParam('umaId',''));//UMAID
    		$uid = trim($this->getRequest()->getParam('uid',''));//微博UID
    		$isBilateral = intval($this->getRequest()->getParam('isBilateral','0'));//是否互相关注
    		
    		if(empty($umaId)) {
    			exit($this->response(false,'UMA id不能为空'));
    		}
    		if(empty($uid)) {
    			exit($this->response(false,'微博UID不能为空'));
    		}
    		$config = Zend_Registry::get("config");
    		$client = new iSina($config['iWeibo']['project_id']); 
    		$result = $this->getFriendsByCondition($client,$umaId,$uid,$isBilateral);
    		exit($this->response(true,"获取微博好友成功",$result));
    	}
    	catch (Exception $e) {
    		exit($this->response(false,$e->getMessage()));
    	}
    }
    
    //回调处理函数
    private function callback($umaId,$uid,$callbackUrl)
    {
    	$config = Zend_Registry::get("config");
    	$path = $config['global']['path'];
    	$client = new iSina($config['iWeibo']['project_id']);
    	$screen_name = "";
    	try {
    		$userinfo = $this->getUser($client, $umaId, $uid);
    		$uid = $userinfo['idstr'];
    		$screen_name = $userinfo['screen_name'];
    		
    		//特殊的业务逻辑进行处理开始
    		//特殊的业务逻辑进行处理结束
    		
    		//$urlInfo = parse_url($callbackUrl);
    		//$domain = $urlInfo['host'];
    		//setcookie('umaId', $umaId,time()+3600*24*7,$path,$domain);
    		//setcookie('uid', $uid,time()+3600*24*7,$path,$domain);
    		//setcookie('screen_name', $screen_name,time()+3600*24*7,$path,$domain);
    		//setcookie('weibo_user_info', $userinfo,time()+3600*24*7,$path,$domain);
    		
    	} catch (Exception $e) {    		
    	}
    	$redirectUrl = $this->getRedirectUrl($callbackUrl,$umaId,$uid,$screen_name);
    	return $redirectUrl;
    }
    
    //获取回调URl
    private function getRedirectUrl($callbackUrl,$umaId,$uid,$screen_name)
    {
    	$redirectUrl="";
    	$pos = strpos($callbackUrl, '?');//查找是否有？
    	// 注意这里使用的是 ===。简单的 == 不能像我们期待的那样工作，
    	// 因为 '?' 是第 0 位置上的（第一个）字符。
    	if ($pos === false) {//未找到的话
    		$redirectUrl = $callbackUrl.'?';
    	}else{
    		$redirectUrl = $callbackUrl.'&';
    	}
    	$redirectUrl.="umaId={$umaId}&uid={$uid}&screen_name={$screen_name}"; 
    	return $redirectUrl;
    }
    //自动关注处理
    private function follow($client,$umaId,$uid,$follow)
    {
		//是否已关注
		$followed_by = false;
		$friendships= $client->get($umaId, 'friendships/show',array('source_id'=>$uid,'target_id'=>$follow));//关注的微博UID
    	if (isset($friendships['target'])){
			if (isset($friendships['target']['followed_by'])){
				$followed_by = $friendships['target']['followed_by'];
			}
		}
		if(!$followed_by){
			//关注一个用户
			$parameter2 = array();
			$parameter2['uid'] = $follow;//关注的微博UID
			$rst2 = $client->post($umaId, 'friendships/create',$parameter2,false);
			if(isset($rst2['error'])) {
				throw new Exception($rst2['error']);
			}
		}    	
    }
    //微博分享处理
    private function share($umaId,$uid,$content,$pic_url,$follow="",$friendNum=0,$friendNames=array(),$isBilateral=false)
    {
    	$source =1;//微博来源
    	$config = Zend_Registry::get("config");
    	$client = new iSina($config['iWeibo']['project_id']);    	
    	//获取朋友列表
    	if(!empty($friendNum) || !empty($friendNames)){
    		if(!empty($friendNum)){
	    		$friends = $this->getWeiboFriendsByRand($client,$umaId,$uid,$friendNum,$isBilateral);
    		}else if(!empty($friendNames)){
    			$friends = implode("，", $friendNames);
    		}
    		$content .= $friends;//@好友
    	}
    	
    	$parameters =array();     	    	
    	$parameters['status'] = g_substr($content,280);
    	$parameters['visible'] = 0;
    	if(!empty($pic_url)){//有图片
    		$parameters['pic'] = '@' . $pic_url;
    	}    	
    	if(!empty($pic_url)){//有图片
    		//上传图片并发布一条新微博
    		$rst = $client->post($umaId, 'statuses/upload',$parameters,true);
    	}else{
    		//发布一条新微博
    		$rst = $client->post($umaId, 'statuses/update',$parameters);
    	}
    	if(isset($rst['error'])) {
    		throw new Exception($rst['error']);
    	}
    	
    	//自动关注
    	if(!empty($follow)){//关注的微博UID	    	
	    	$this->follow($client, $umaId, $uid, $follow);
    	}
    	
    	//特殊的业务逻辑代码开始    	
    	//特殊的业务逻辑代码结束
    	
    	return $rst;
    }
    
    //获取微博用户信息
    private function getUser($client,$umaId,$uid)
    {
    	$cacheKey = md5("user".$uid);
    	$cache = Zend_Registry::get('cache');
    	$userInfo = $cache->load($cacheKey);
    
    	if (empty($userInfo)) {    		
    		$userInfo = $client->get($umaId,'users/show',array('uid'=>$uid));    		 
    		if(isset($userInfo['error'])) {
    			throw new Exception($userInfo['error']);
    		}else{
    			$cache->save($userInfo, $cacheKey);//利用zend_cache对象缓存查询出来的结果
    		}
    	}
    	return $userInfo;
    }
    
    //获取微博好友列表
    private function getFriends($client,$umaId,$uid)
    {
    	$cacheKey = md5("friendsList".$uid);
    	$cache = Zend_Registry::get('cache');
    	$friends = $cache->load($cacheKey);
    	 
    	if (empty($friends)) {
    		//获取用户的关注列表
    		$rst = $client->get($umaId, 'friendships/friends',array('uid'=>$uid));
    		if(isset($rst['error'])) {
    			throw new Exception($rst['error']);
    		}
    		$friends =array();
    		if(!empty($rst['users'])){
    			foreach ($rst['users'] as $user) {
    				$friends[] = $user;
    			}
    			$cache->save($friends, $cacheKey);//利用zend_cache对象缓存查询出来的结果
    		}
    	}
    	return $friends;
    }
    //获取微博互相关注好友列表
    private function getBilateralFriends($client,$umaId,$uid)
    {
    	$cacheKey = md5("bilateralfriendsList".$uid);
    	$cache = Zend_Registry::get('cache');
    	$friends = $cache->load($cacheKey);
    
    	if (empty($friends)) {
    		//获取用户的关注列表
    		$rst = $client->get($umaId, 'friendships/friends/bilateral',array('uid'=>$uid,'count'=>200));
    		if(isset($rst['error'])) {
    			throw new Exception($rst['error']);
    		}
    		$friends =array();
    		if(!empty($rst['users'])){
    			foreach ($rst['users'] as $user) {
    				$friends[] = $user;//'@'.$user['screen_name'];
    			}
    			$cache->save($friends, $cacheKey);//利用zend_cache对象缓存查询出来的结果
    		}
    	}
    	return $friends;
    }
    //获取随机@好友
    private function getWeiboFriendsByRand($client,$umaId,$uid,$friendNum,$isBilateral=false)
    {
    	//获取微博好友列表
    	$friends = $this->getFriendsByCondition($client,$umaId,$uid,$isBilateral);    	
    	$comma_separated = "";
    	if(!empty($friends)){
    		srand((float) microtime() * 10000000);
    		$rand_keys = array_rand($friends, $friendNum);
    		$rand_friends =array();
    		foreach ($rand_keys as $key) {
    			$rand_friends[] = '@'.$friends[$key]['screen_name'];
    		}
    		$comma_separated = implode("，", $rand_friends);
    	}
    	return $comma_separated;
    }
    
    private function getFriendsByCondition($client,$umaId,$uid,$isBilateral=false)
    {
    	if(!$isBilateral){
    		$result = $this->getFriends($client,$umaId,$uid);
    	}else{
    		$result = $this->getBilateralFriends($client,$umaId,$uid);
    	}
    	return $result;
    }
    private function response($stat,$msg='',$result='') 
    {
    	$jsonpcallback = trim($this->getRequest()->getParam('jsonpcallback'));
    	if(!empty($jsonpcallback)){
    		return $jsonpcallback . '(' . json_encode(array('success'=>$stat,'message'=>$msg,'result'=>$result)) . ')';
    	}else{
    		return json_encode(array('success'=>$stat,'message'=>$msg,'result'=>$result));
    	}
    }
}

