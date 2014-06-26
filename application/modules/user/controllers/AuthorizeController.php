<?php

class User_AuthorizeController extends iWebsite_Controller_Action
{
    private $_weixin;
    public function init()
    {
        $this->_weixin = new Weixin\Client();
    }
    
    public function indexAction()
    {
        $oBind = new User_Model_Bind();
        $x = $oBind->getSchema();
        var_dump($x);
        exit;
    }
    
    
    /**
     * http://www.example.com/weixin/sns/index?redirect=回调地址&scope=[snsapi_userinfo(default)|snsapi_base]
     * 引导用户去往登录授权
     */
    public function weixinAction()
    {
        try {
        $oTrack = new Weixin_Model_ScriptTracking();
        $oApp = new Weixin_Model_Application();
        $appConfig = $oApp->getToken();
    	$_SESSION['oauth_start_time'] = microtime(true);
    	
    		$redirect = isset($_GET['redirect']) ? urlencode(trim($_GET['redirect'])) : ''; // 附加参数存储跳转地址
    		$scope = isset($_GET['scope']) ? trim($_GET['scope']) : 'snsapi_userinfo';
    
    		$appid = $appConfig['appid'];
    		$secret = $appConfig['secret'];
    
    		$moduleName = $this->getRequest()->getModuleName();
    		$controllerName = $this->getRequest()->getControllerName();
    		$actionName = $this->getRequest()->getActionName();
    
    		$redirectUri = 'http://';
    		$redirectUri .= $_SERVER["HTTP_HOST"];
    		$redirectUri .= '/' . $moduleName;
    		$redirectUri .= '/' . $controllerName;
    		$redirectUri .= '/callback';
    		$redirectUri .= '?redirect=' . $redirect;
    		if (isset($_SESSION['iWeixin']['accessToken'])&&0) {
    			$redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '';
    			$arrAccessToken = $_SESSION['iWeixin']['accessToken'];
    			if (isset($arrAccessToken['openid'])) {
    				if (strpos($redirect, 'FromUserName') === false) {
    					if (strpos($redirect, '?') === false)
    						$redirect .= '?FromUserName=' . $arrAccessToken['openid'];
    					else
    						$redirect .= '&FromUserName=' . $arrAccessToken['openid'];
    				}
    			}
    			$oTrack->record("授权session存在", $_SESSION['oauth_start_time'], microtime(true), $arrAccessToken['openid']);
    			header("location:{$redirect}");
    			exit();
    		} elseif (! empty($_COOKIE['openid']) && $scope == 'snsapi_base') {
    		    exit('b');
    			$redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '';
    			$openid = $_COOKIE['openid'];
    			if (strpos($redirect, 'FromUserName') === false) {
    				if (strpos($redirect, '?') === false)
    					$redirect .= '?FromUserName=' . $openid;
    				else
    					$redirect .= '&FromUserName=' . $openid;
    			}
    			$oTrack->record("授权cookie存在", $_SESSION['oauth_start_time'], microtime(true), $openid);
    			header("location:{$redirect}");
    			exit();
    		} else {
    		    
    			$sns = new \Weixin\Token\Sns($appid, $secret);
    			$sns->setRedirectUri($redirectUri);
    			$sns->setScope($scope);
    			$sns->getAuthorizeUrl();
    		}
    	} catch (Exception $e) {
    		print_r($e->getFile());
    		print_r($e->getLine());
    		print_r($e->getMessage());
    	}
    }
    
    /**
     * 处理微信的回调数据
     *
     * @return boolean
     */
    public function callbackAction()
    {
    	try {
    	    $oApp = new Weixin_Model_Application();
    	    $oTrack = new Weixin_Model_ScriptTracking();
    	    $appConfig = $oApp->getToken();
    		$appid = $appConfig['appid'];
    		$secret = $appConfig['secret'];
    
    		$sns = new \Weixin\Token\Sns($appid, $secret);
    		$arrAccessToken = $sns->getAccessToken();
    
    		if (! isset($arrAccessToken['errcode'])) {
    			$_SESSION['iWeixin']['accessToken'] = $arrAccessToken;
    			$redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '';
    			if (empty($redirect)) {
    				throw new Exception("回调地址未定义");
    			}
    			// 授权成功后，记录该微信用户的基本信息
    			if ($arrAccessToken['scope'] === 'snsapi_userinfo') {
    				$this->_weixin->setSnsAccessToken($arrAccessToken['access_token']);
    				$userInfo = $this->_weixin->getSnsManager()->getSnsUserInfo($arrAccessToken['openid']);
    				if (! isset($userInfo['errcode'])) {
    					$userInfo['access_token'] = $arrAccessToken;
//     					$this->_user->updateUserInfoBySns($arrAccessToken['openid'], $userInfo);
                        $oUserWeixin = new User_Model_Weixin();
                        $oUserWeixin->add($userInfo);
                        
    				} else {
    					throw new Exception("获取用户信息失败，原因:" . json_encode($userInfo, JSON_UNESCAPED_UNICODE));
    				}
    			}
    
    			if (isset($arrAccessToken['openid'])) {
    				setcookie('openid', $arrAccessToken['openid'], time() + 365 * 24 * 3600, '/');
    				if (strpos($redirect, 'FromUserName') === false) {
    					if (strpos($redirect, '?') === false)
    						$redirect .= '?FromUserName=' . $arrAccessToken['openid'];
    					else
    						$redirect .= '&FromUserName=' . $arrAccessToken['openid'];
    				}
    			}
    
    			$oTrack->record("SNS授权", $_SESSION['oauth_start_time'], microtime(true), $arrAccessToken['openid']);
    			header("location:{$redirect}");
    			exit();
    		} else {
    			// 如果用户未授权登录，点击取消，自行设定取消的业务逻辑
    			throw new Exception("获取token失败,原因:" . json_encode($arrAccessToken, JSON_UNESCAPED_UNICODE));
    		}
    	} catch (Exception $e) {
    		print_r($e->getFile());
    		print_r($e->getLine());
    		print_r($e->getMessage());
    	}
    }
    
}