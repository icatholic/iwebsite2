<?php

class Weibo_SnsController extends iWebsite_Controller_Action
{

    private $_user;

    private $_app;

    private $_config;

    private $_tracking;

    private $_model;

    private $_oauth;

    private $_appid;

    private $_appConfig;

    public function init()
    {
        parent::init();
        try {
            $this->getHelper('viewRenderer')->setNoRender(true);
            $this->_config = Zend_Registry::get('config');
            
            $this->_tracking = new Weibo_Model_ScriptTracking();
            $this->_user = new Weibo_Model_User();
            $this->_model = new Weibo_Model_OauthInfo();
            $this->_app = new Weibo_Model_Application();
            $this->_key = new Weibo_Model_AppKey();
            
            $this->_appid = $this->get('appid');
            if (empty($this->_appid)) {
                exit("appid不能为空");
            }
            
            // 获取设置
            $this->_appConfig = $this->_app->getInfoById($this->_appid);
            if (empty($this->_appConfig)) {
                exit("appid不正确");
            }
            
            if (empty($this->_appConfig['appKeyId'])) {
                exit("appKey未设置");
            }
            
            // 初始化应用密钥
            $this->_appKey = $this->_key->getInfoById($this->_appConfig['appKeyId']);
            
            // 初始化新浪微博适配器
            $this->_oauth = new SaeTOAuthV2($this->_appKey['akey'], $this->_appKey['skey'], NULL);
        } catch (Exception $e) {
            print_r($e->getFile());
            print_r($e->getLine());
            print_r($e->getMessage());
        }
    }

    /**
     * http://www.example.com/weibo/sns/index?appid=xxxxxx&redirect=回调地址
     * 引导用户去往登录授权
     */
    public function indexAction()
    {
        $_SESSION['oauth_start_time'] = microtime(true);
        try {
            if (isset($_SESSION['iWeibo']['accessToken'])) {
                $redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '';
                $arrAccessToken = $_SESSION['iWeibo']['accessToken'];
                if (isset($arrAccessToken['uid'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'uid' => $arrAccessToken['uid']
                    ));
                }
                
                if (isset($arrAccessToken['umaId'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'umaId' => $arrAccessToken['umaId']
                    ));
                }
                
                if (isset($arrAccessToken['screen_name'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'screen_name' => $arrAccessToken['screen_name']
                    ));
                }
                
                if (isset($arrAccessToken['profile_image_url'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'profile_image_url' => $arrAccessToken['profile_image_url']
                    ));
                }
                
                // 计算signkey
                $timestamp = time();
                $signkey = $this->getSignKey($arrAccessToken['uid'], $timestamp);
                $redirect = $this->addUrlParameter($redirect, array(
                    'signkey' => $signkey
                ));
                $redirect = $this->addUrlParameter($redirect, array(
                    'timestamp' => $timestamp
                ));
                
                $this->_tracking->record("授权session存在", $_SESSION['oauth_start_time'], microtime(true), $arrAccessToken['uid']);
                header("location:{$redirect}");
                exit();
            } elseif (! empty($_COOKIE['weibo[uid]'])) {
                $redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '';
                if (isset($_COOKIE['weibo[uid]'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'uid' => $_COOKIE['weibo[uid]']
                    ));
                }
                if (isset($_COOKIE['weibo[umaId]'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'umaId' => $_COOKIE['weibo[umaId]']
                    ));
                }
                
                if (isset($_COOKIE['weibo[screen_name]'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'screen_name' => $_COOKIE['weibo[screen_name]']
                    ));
                }
                
                if (isset($_COOKIE['weibo[profile_image_url]'])) {
                    $redirect = $this->addUrlParameter($redirect, array(
                        'profile_image_url' => $_COOKIE['weibo[profile_image_url]']
                    ));
                }
                
                // 计算signkey
                $timestamp = time();
                $signkey = $this->getSignKey($_COOKIE['weibo[uid]'], $timestamp);
                $redirect = $this->addUrlParameter($redirect, array(
                    'signkey' => $signkey
                ));
                $redirect = $this->addUrlParameter($redirect, array(
                    'timestamp' => $timestamp
                ));
                
                $this->_tracking->record("授权cookie存在", $_SESSION['oauth_start_time'], microtime(true), $_COOKIE['weibo[uid]']);
                header("location:{$redirect}");
                exit();
            } else {
                $redirect = isset($_GET['redirect']) ? urlencode(trim($_GET['redirect'])) : ''; // 附加参数存储跳转地址
                if (empty($redirect)) {
                    exit("回调地址未定义");
                }
                
                $path = $this->_config['global']['path'];
                $scheme = $this->getRequest()->getScheme();
                $host = $this->getRequest()->getHttpHost();
                $moduleName = $this->getRequest()->getModuleName();
                
                $callbackUrl = urlencode("{$scheme}://{$host}{$path}{$moduleName}/sns/callback?appid={$this->_appid}&callbackUrl={$redirect}");
                $redirect_uri = "http://scrm.umaman.com/soa/sina/icc-callback?redirect={$callbackUrl}";
                
                $_SESSION['iWeibo']['redirect_uri'] = $redirect_uri;
                
                $detect = new Mobile_Detect();
                $m = "";
                if ($detect->isTablet()) { // 如果是平板设备
                } else {
                    if ($detect->isMobile()) { // 如果是手机设备
                        $m = "&display=mobile";
                    } else { // 如果是PC设备
                        ;
                    }
                }
                
                $authorizeURL = $this->_oauth->getAuthorizeURL($redirect_uri) . $m;
                header("location:{$authorizeURL}");
                exit();
            }
        } catch (Exception $e) {
            print_r($e->getFile());
            print_r($e->getLine());
            print_r($e->getMessage());
        }
    }

    /**
     * 处理微博的回调数据
     *
     * @return boolean
     */
    public function callbackAction()
    {
        try {
            if (isset($_GET['code'])) {
                
                $redirect = isset($_GET['callbackUrl']) ? urldecode($_GET['callbackUrl']) : '';
                if (empty($redirect)) {
                    throw new Exception("回调地址未定义");
                }
                
                // 获取accessToken
                $keys = array();
                $keys['code'] = $_GET['code'];
                $keys['redirect_uri'] = $_SESSION['iWeibo']['redirect_uri'];
                $arrAccessToken = $this->_oauth->getAccessToken('code', $keys);
                
                // 记录授权ID
                $umaId = $this->_model->record(myMongoId($this->_appConfig['_id']), $arrAccessToken);
                
                if (isset($arrAccessToken['uid'])) {
                    // 授权成功后，记录该微博用户的基本信息
                    $userInfo = $this->_oauth->get('users/show', array(
                        'uid' => $arrAccessToken['uid']
                    ));
                    if (! isset($userInfo['error'])) {
                        $userInfo['access_token'] = $arrAccessToken;
                        $this->_user->updateUserInfo($arrAccessToken['uid'], $userInfo);
                        
                        // 记录SESSION
                        $arrAccessToken['umaId'] = $umaId;
                        $arrAccessToken['screen_name'] = $userInfo['screen_name'];
                        $arrAccessToken['profile_image_url'] = urlencode($userInfo['profile_image_url']);
                        
                        $_SESSION['iWeibo']['accessToken'] = $arrAccessToken;
                        
                        $path = $this->_config['global']['path'];
                        setcookie('weibo[uid]', $arrAccessToken['uid'], time() + 365 * 24 * 3600, $path);
                        setcookie('weibo[umaId]', $arrAccessToken['umaId'], time() + 365 * 24 * 3600, $path);
                        setcookie('weibo[screen_name]', $arrAccessToken['screen_name'], time() + 365 * 24 * 3600, $path);
                        setcookie('weibo[profile_image_url]', $arrAccessToken['profile_image_url'], time() + 365 * 24 * 3600, $path);
                    } else {
                        throw new Exception("获取用户信息失败，原因:" . json_encode($userInfo, JSON_UNESCAPED_UNICODE));
                    }
                    
                    $redirect = $this->addUrlParameter($redirect, array(
                        'uid' => $arrAccessToken['uid']
                    ));
                    
                    $redirect = $this->addUrlParameter($redirect, array(
                        'umaId' => $arrAccessToken['umaId']
                    ));
                    
                    $redirect = $this->addUrlParameter($redirect, array(
                        'screen_name' => $arrAccessToken['screen_name']
                    ));
                    
                    $redirect = $this->addUrlParameter($redirect, array(
                        'profile_image_url' => $arrAccessToken['profile_image_url']
                    ));
                    
                    // 计算signkey
                    $timestamp = time();
                    $signkey = $this->getSignKey($arrAccessToken['uid'], $timestamp);
                    $redirect = $this->addUrlParameter($redirect, array(
                        'signkey' => $signkey
                    ));
                    $redirect = $this->addUrlParameter($redirect, array(
                        'timestamp' => $timestamp
                    ));
                }
                
                $this->_tracking->record("微博授权", $_SESSION['oauth_start_time'], microtime(true), $arrAccessToken['uid']);
                header("location:{$redirect}");
                exit();
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

    private function getSignKey($uid, $timestamp = 0)
    {
        return $this->_app->getSignKey($uid, $this->_appConfig['secretKey'], $timestamp);
    }
}

