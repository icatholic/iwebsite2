<?php

class Weixin_SnsController extends Zend_Controller_Action
{

    private $_weixin;

    private $_user;

    private $_app;

    private $_config;

    public function init()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $this->_config = Zend_Registry::get('config');
        $this->_user = new Weixin_Model_User();
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
        $redirect = urlencode($this->getRequest()->getParam('redirect', '')); // 附加参数存储跳转地址
        $scope = $this->getRequest()->getParam('scope', 'snsapi_userinfo');
        
        $appid = $this->_appConfig['appid'];
        $secret = $this->_appConfig['secret'];
        
        $moduleName = $this->getRequest()->getModuleName();
        $controllerName = $this->getRequest()->getControllerName();
        $actionName = $this->getRequest()->getActionName();
        
        $redirectUri = $_SERVER["HTTP_HOST"];
        $redirectUri .= '/' . $moduleName;
        $redirectUri .= '/' . $controllerName;
        $redirectUri .= '/callback';
        $redirectUri .= '?redirect=' . $redirect;
        
        $sns = new \Weixin\Token\Sns($appid, $secret);
        $sns->setRedirectUri($redirectUri);
        $sns->setScope($scope);
        $sns->getAuthorizeUrl();
    }

    /**
     * 处理微信的回调数据
     *
     * @return boolean
     */
    public function callbackAction()
    {
        $appid = $this->_appConfig['appid'];
        $secret = $this->_appConfig['secret'];
        
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
                    $this->_user->updateUserInfoBySns($arrAccessToken['openid'], $userInfo);
                } else {
                    throw new Exception("获取用户信息失败，原因:" . json_encode($userInfo, JSON_UNESCAPED_UNICODE));
                }
            }
            header("location:{$redirect}");
            exit();
        } else {
            // 如果用户未授权登录，点击取消，自行设定取消的业务逻辑
            throw new Exception("获取token失败,原因:" . json_encode($arrAccessToken, JSON_UNESCAPED_UNICODE));
        }
    }
}

