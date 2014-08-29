<?php

class Weixin_SnsController extends Zend_Controller_Action
{

    private $_weixin;

    private $_user;

    private $_app;

    private $_config;

    private $_tracking;

    private $_appConfig;

    public function init()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $this->_config = Zend_Registry::get('config');
        
        $this->_user = new Weixin_Model_User();
        $this->_app = new Weixin_Model_Application();
        $this->_tracking = new Weixin_Model_ScriptTracking();
        $this->_appConfig = $this->_app->getToken();
        $this->_weixin = new Weixin\Client();
        if (! empty($this->_appConfig['access_token'])) {
            $this->_weixin->setAccessToken($this->_appConfig['access_token']);
        }
    }

    /**
     * http://www.example.com/weixin/sns/index?redirect=回调地址&scope=[snsapi_userinfo(default)|snsapi_base]
     * 引导用户去往登录授权
     */
    public function indexAction()
    {
        $_SESSION['oauth_start_time'] = microtime(true);
        try {
            $scope = isset($_GET['scope']) ? trim($_GET['scope']) : 'snsapi_userinfo';
            
            if (isset($_SESSION['iWeixin']['accessToken'])) {
                $redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '';
                $arrAccessToken = $_SESSION['iWeixin']['accessToken'];
                
                if (isset($arrAccessToken['openid'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'FromUserName' => $arrAccessToken['openid']
                    ));
                }
                
                // 计算signkey
                $timestamp = time();
                $signkey = $this->getSignKey($arrAccessToken['openid'], $timestamp);
                $redirect = $this->addUrlParameter($redirect, array(
                    'signkey' => $signkey
                ));
                $redirect = $this->addUrlParameter($redirect, array(
                    'timestamp' => $timestamp
                ));
                
                if (isset($arrAccessToken['nickname'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'nickname' => $arrAccessToken['nickname']
                    ));
                }
                
                if (isset($arrAccessToken['headimgurl'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'headimgurl' => $arrAccessToken['headimgurl']
                    ));
                }
                
                $this->_tracking->record("授权session存在", $_SESSION['oauth_start_time'], microtime(true), $arrAccessToken['openid']);
                header("location:{$redirect}");
                exit();
            } elseif (! empty($_COOKIE['openid']) && $scope == 'snsapi_base') {
                $redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '';
                if (isset($_COOKIE['openid'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'FromUserName' => $_COOKIE['openid']
                    ));
                }
                
                // 计算signkey
                $timestamp = time();
                $signkey = $this->getSignKey($_COOKIE['openid'], $timestamp);
                $redirect = $this->addUrlParameter($redirect, array(
                    'signkey' => $signkey
                ));
                $redirect = $this->addUrlParameter($redirect, array(
                    'timestamp' => $timestamp
                ));
                
                if (isset($_COOKIE['nickname'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'nickname' => $_COOKIE['nickname']
                    ));
                }
                
                if (isset($_COOKIE['headimgurl'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'headimgurl' => $_COOKIE['headimgurl']
                    ));
                }
                
                $this->_tracking->record("授权cookie存在", $_SESSION['oauth_start_time'], microtime(true), $_COOKIE['openid']);
                header("location:{$redirect}");
                exit();
            } else {
                $redirect = isset($_GET['redirect']) ? urlencode(trim($_GET['redirect'])) : ''; // 附加参数存储跳转地址
                
                $moduleName = $this->getRequest()->getModuleName();
                $controllerName = $this->getRequest()->getControllerName();
                $actionName = $this->getRequest()->getActionName();
                
                $redirectUri = 'http://';
                $redirectUri .= $_SERVER["HTTP_HOST"];
                $redirectUri .= '/' . $moduleName;
                $redirectUri .= '/' . $controllerName;
                $redirectUri .= '/callback';
                $redirectUri .= '?redirect=' . urlencode($redirect);
                
                $appid = $this->_appConfig['appid'];
                $secret = $this->_appConfig['secret'];
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
            $redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '';
            if (empty($redirect)) {
                throw new Exception("回调地址未定义");
            }
            
            $appid = $this->_appConfig['appid'];
            $secret = $this->_appConfig['secret'];
            $sns = new \Weixin\Token\Sns($appid, $secret);
            $arrAccessToken = $sns->getAccessToken();
            
            if (! isset($arrAccessToken['errcode'])) {
                // 授权成功后，记录该微信用户的基本信息
                if ($arrAccessToken['scope'] === 'snsapi_userinfo') {
                    $this->_weixin->setSnsAccessToken($arrAccessToken['access_token']);
                    $userInfo = $this->_weixin->getSnsManager()->getSnsUserInfo($arrAccessToken['openid']);
                    if (! isset($userInfo['errcode'])) {
                        $userInfo['access_token'] = $arrAccessToken;
                        $this->_user->updateUserInfoBySns($arrAccessToken['openid'], $userInfo);
                    } else {
                        throw new Exception("获取用户信息失败，原因:" . json_encode($userInfo, JSON_UNESCAPED_UNICODE));
                    }
                }
                
                if (isset($arrAccessToken['openid'])) {
                    
                    if (! empty($userInfo)) {
                        if (! empty($userInfo['nickname'])) {
                            $arrAccessToken['nickname'] = $userInfo['nickname'];
                        }
                        
                        if (! empty($userInfo['headimgurl'])) {
                            $arrAccessToken['headimgurl'] = urlencode($userInfo['headimgurl']);
                        }
                    }
                    $_SESSION['iWeixin']['accessToken'] = $arrAccessToken;
                    
                    $path = $this->_config['global']['path'];
                    setcookie('openid', $arrAccessToken['openid'], time() + 365 * 24 * 3600, $path);
                    if (! empty($arrAccessToken['nickname'])) {
                        setcookie('nickname', $arrAccessToken['nickname'], time() + 365 * 24 * 3600, $path);
                    }
                    if (! empty($arrAccessToken['headimgurl'])) {
                        setcookie('headimgurl', $arrAccessToken['headimgurl'], time() + 365 * 24 * 3600, $path);
                    }
                    
                    $redirect = $this->addUrlParameter($redirect, array(
                        'FromUserName' => $arrAccessToken['openid']
                    ));
                    
                    // 计算signkey
                    $timestamp = time();
                    $signkey = $this->getSignKey($arrAccessToken['openid'], $timestamp);
                    $redirect = $this->addUrlParameter($redirect, array(
                        'signkey' => $signkey
                    ));
                    $redirect = $this->addUrlParameter($redirect, array(
                        'timestamp' => $timestamp
                    ));
                    
                    if (! empty($arrAccessToken['nickname'])) {
                        $redirect = $this->addUrlParameter($redirect, array(
                            'nickname' => $arrAccessToken['nickname']
                        ));
                    }
                    
                    if (! empty($arrAccessToken['headimgurl'])) {
                        $redirect = $this->addUrlParameter($redirect, array(
                            'headimgurl' => $arrAccessToken['headimgurl']
                        ));
                    }
                }
                
                $this->_tracking->record("SNS授权", $_SESSION['oauth_start_time'], microtime(true), $arrAccessToken['openid']);
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

    private function addUrlParameter($url, array $params)
    {
        if (! empty($params)) {
            foreach ($params as $key => $value) {
                if (strpos($url, $key) === false) {
                    if (strpos($url, '?') === false)
                        $url .= "?{$key}=" . $value;
                    else
                        $url .= "&{$key}=" . $value;
                }
            }
        }
        return $url;
    }

    private function getSignKey($openid, $timestamp = 0)
    {
        return $this->_app->getSignKey($openid, $this->_appConfig['secretKey'], $timestamp);
    }
}

