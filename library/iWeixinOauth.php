<?php

class iWeixinOauth
{

    private $_appid;

    private $_secret;

    private $_redirect_uri;

    private $_scope = 'snsapi_base';

    private $_state;

    public function __construct ($appid, $secret, $redirect_uri, 
            $scope = 'snsapi_base', $state = '')
    {
        $this->_appid = $appid;
        $this->_secret = $secret;
        $this->_redirect_uri = $redirect_uri;
        $this->_scope = $scope;
        $this->_state = $state;
    }

    public function getCode ()
    {
        header("location:https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->_appid}&redirect_uri={$this->_redirect_uri}&response_type=code&scope={$this->_scope}&state={$this->_state}#wechat_redirect");
        exit();
    }

    public function getAccessToken ()
    {
        session_unset();
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        if($code=='') {
            throw new Exception('code不能为空');
        }
        $response = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->_appid}&secret={$this->_secret}&code={$code}&grant_type=authorization_code");
        $response = json_decode($response,true);
        if(!isset($response['errcode'])) {
            $_SESSION['weixin_access_token'] = $response;
        }
        else {
            var_dump($response);
        }
    }

    public function getUserInfo ()
    {  
        if(isset($_SESSION['weixin_access_token'])) {
            $accessToken = $_SESSION['weixin_access_token']['access_token'];
            $openId = $_SESSION['weixin_access_token']['openid'];
            $userInfo = file_get_contents("https://api.weixin.qq.com/sns/userinfo?access_token={$accessToken}&openid={$openId}&lang=zh_CN");
            return json_decode($userInfo,true);
        }
        return false;
        
    }

    public function getRefreshToken ()
    {
        
    }

    public function __destruct ()
    {}
}