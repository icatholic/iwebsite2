<?php
class Weixin_CampaignController extends Zend_Controller_Action
{
    public function init()
    {
    	$this->getHelper('viewRenderer')->setNoRender(true);
    }
    
    /**
     *
     * 微信授权
     * 
     * @param string callbackUrl  回调URL
     * @param string scope  应用授权作用域，snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid），
     * snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
     * @return 
     * 前端调用实例
     * window.location.href = "http://iwebsite/weixin/campaign/authorize?callbackUrl=http://iwebsite/default/weixin/campaign&scope=snsapi_userinfo";
     * */
    public function authorizeAction()
    {
    	//http://iwebsite/weixin/campaign/authorize?scope=snsapi_userinfo&callbackUrl=http://iwebsite/default/index/index
    	$callbackUrl = trim($this->getRequest()->getParam('callbackUrl',''));//回调URL
    	$scope = trim($this->getRequest()->getParam('scope','snsapi_userinfo'));//scope
    	$state = trim($this->getRequest()->getParam('state',''));//$state
    	$callbackUrl = urlencode($callbackUrl);
    	$config = Zend_Registry::get("config");
    	$path = $config['global']['path'];
    	$client = new iWeixinSns($config['iWeixin']['project_id'],$config['iWeixin']['token']);
    	$scheme = $this->getRequest()->getScheme();
    	$host = $this->getRequest()->getHttpHost();
    	$moduleName = trim($this->getRequest()->getModuleName());
    	$myCallbackUrl = "{$scheme}://{$host}{$path}{$moduleName}/campaign/callback?callbackUrl={$callbackUrl}";
    	//try {
    		$authorizeURL = $client->getAuthorizeURL($myCallbackUrl,$scope,'code',$state);
    	//} catch (Exception $e) {
    	//	die($e->getMessage());
    	//}
    	$this->_redirect($authorizeURL);

    }
    
    /**
     *
     * 微信回调
     * @param string callbackUrl  回调URL
     * @return 
     * */
    public function callbackAction()
    {
    	//http://iwebsite/weixin/campaign/callback?callbackUrl=http://iwebsite/default/index/index
    	$callbackUrl = trim($this->getRequest()->getParam('callbackUrl'));//返回URL
    	$callbackUrl = urldecode($callbackUrl);
    	//从uma返回的参数umaId和openid，不要修改
    	$umaId = trim($this->getRequest()->getParam('umaId'));
    	$openid = trim($this->getRequest()->getParam('openid'));
    	$scope = trim($this->getRequest()->getParam('scope','snsapi_userinfo'));
    	$code = trim($this->getRequest()->getParam('code'));
    	//回调处理
    	$redirectUrl = $this->callback($umaId,$openid,$callbackUrl,$scope);
    	$this->_redirect($redirectUrl);
    }
    
    /**
     * 发送POST类型的请求到微信API
     * @param string $umaId UMA系统中的唯一授权表示 通过这个标示获取系统中的微信access_token
     * @param string $url   微信API的请求地址  例如：menu/create
     * @param array  $parameters 微信API请求地址对应的API参数
     *
     * @return json
     * 前端调用实例
     * 
     * 
     * 		var params = {};
            params['umaId'] = '5189ce69479619635f000a1c';
            params['url'] = 'menu/create';
            params['parameters'] = {status:'用户post测试',visible:0};
    		
            $.ajax(
            {
                url:web_path+'weixin/campaign/post',
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
    		$url = trim($this->getRequest()->getParam('url',''));//微信API的请求地址
    		$parameters = $this->getRequest()->getParam('parameters');//微信API请求地址对应的API参数
    		if(empty($umaId)) {
    			exit($this->response(false,'UMA id不能为空'));
    		}
    		if(empty($url)) {
    			exit($this->response(false,'微信API的请求地址不能为空'));
    		}
    		if(empty($parameters)) {
    			exit($this->response(false,'微信API请求地址对应的API参数不能为空'));
    		}
    		$config = Zend_Registry::get("config");
    		$client = new iWeixinSns($config['iWeixin']['project_id'],$config['iWeixin']['token']);
    		
    		$ret= $client->post($umaId, $url, $parameters);
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
     * 发送GET类型的请求到微信API
     * @param string $umaId UMA系统中的唯一授权表示 通过这个标示获取系统中的微信access_token
     * @param string $url   微信API的请求地址  例如：user/info
     * @param array  $parameters 微信API请求地址对应的API参数
     * @return json
     * 前端调用实例
     * 
     * 		var params = {};
            params['umaId'] = '5189ce69479619635f000a1c';
            params['url'] = 'user/info';
            params['parameters'] = {openid:'1596822015'};           
            $.ajax(
            {
                url:web_path+'weixin/campaign/get',
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
    		$url = trim($this->getRequest()->getParam('url',''));//微信API的请求地址
    		$parameters = $this->getRequest()->getParam('parameters');//微信API请求地址对应的API参数
    		if(empty($umaId)) {
    			exit($this->response(false,'UMA id不能为空'));
    		}
    		if(empty($url)) {
    			exit($this->response(false,'微信API的请求地址不能为空'));
    		}
    		if(empty($parameters)) {
    			exit($this->response(false,'微信API请求地址对应的API参数不能为空'));
    		}
    		
    		$config = Zend_Registry::get("config");
    		$client = new iWeixinSns($config['iWeixin']['project_id'],$config['iWeixin']['token']);
    		
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
     * 获取微信用户信息
     * @return json
     *
     * 前端调用实例
	     var params = {};
	     params['umaId'] = '5189ce69479619635f000a1c';
         params['openid'] = '11258900';
         params['scope'] = 'snsapi_userinfo';
	     $.ajax(
	     {
	     	url:web_path+'weixin/campaign/get-sns-user-info',
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
    public function getSnsUserInfoAction()
    {
    	//http://iwebsite/weixin/campaign/get-sns-user-info?jsonpcallback=?&umaId=1233434&openid=1234&scope=snsapi_userinfo
    	try {
    		$umaId = trim($this->getRequest()->getParam('umaId',''));//UMAID
    		$openid = trim($this->getRequest()->getParam('openid',''));//微信openid
    		$scope = trim($this->getRequest()->getParam('scope','snsapi_userinfo'));
    		
    		if(empty($umaId)) {
    			exit($this->response(false,'UMA id不能为空'));
    		}
    		if(empty($openid)) {
    			exit($this->response(false,'openid不能为空'));
    		}
    		$config = Zend_Registry::get("config");
    		$client = new iWeixinSns($config['iWeixin']['project_id'],$config['iWeixin']['token']);
    		//获取微信用户信息
    		$result = $this->getSnsUser($client,$umaId,$openid,$scope);
    		exit($this->response(true,"获取微信用户信息成功",$result));
    	}
    	catch (Exception $e) {
    		exit($this->response(false,$e->getMessage()));
    	}
    }  
    
    //回调处理函数
    private function callback($umaId,$openid,$callbackUrl,$scope='snsapi_userinfo')
    {
    	$config = Zend_Registry::get("config");
    	$path = $config['global']['path'];
    	$client = new iWeixinSns($config['iWeixin']['project_id'],$config['iWeixin']['token']);
    	$nickname = "";
    	try {
    		$userinfo = $this->getSnsUser($client, $umaId, $openid,$scope);
    		$openid= $userinfo['openid'];
    		$nickname = $userinfo['nickname'];
    		
    		//特殊的业务逻辑进行处理开始
    		//特殊的业务逻辑进行处理结束
    		
    		//$urlInfo = parse_url($callbackUrl);
    		//$domain = $urlInfo['host'];
    		//setcookie('umaId', $umaId,time()+3600*24*7,$path,$domain);
    		//setcookie('openid', $openid,time()+3600*24*7,$path,$domain);
    		//setcookie('nickname', $nickname,time()+3600*24*7,$path,$domain);
    		//setcookie('weixin_user_info', $userinfo,time()+3600*24*7,$path,$domain);
    		
    	} catch (Exception $e) {    		
    	}
    	$redirectUrl = $this->getRedirectUrl($callbackUrl,$umaId,$openid,$nickname);
    	return $redirectUrl;
    }
    
    //获取回调URl
    private function getRedirectUrl($callbackUrl,$umaId,$openid,$nickname)
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
    	$redirectUrl.="umaId={$umaId}&openid={$openid}&nickname={$nickname}"; 
    	return $redirectUrl;
    }
	
    //获取微信用户信息
    private function getSnsUser(iWeixinSns $client,$umaId,$openid,$scope='snsapi_userinfo')
    {
    	$cacheKey = md5("weixinuser".$openid);
    	$cache = Zend_Registry::get('cache');
    	$userInfo = false;//$cache->load($cacheKey);
    	
    	if (empty($userInfo)) {
    		$userInfo = $client->get($umaId,'sns/userinfo',array('openid'=>$openid,'scope'=>$scope));
    		if(!empty($userInfo['error'])) {
    			throw new Exception($userInfo['msg']);
    		}else{
    			$cache->save($userInfo, $cacheKey);//利用zend_cache对象缓存查询出来的结果
    		}
    	}
    	return $userInfo;
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

