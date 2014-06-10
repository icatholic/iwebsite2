<?php

abstract class iWebsite_Controller_Action extends Zend_Controller_Action
{

    public function getVersion()
    {
        return date("YmdHis");
    }

    public function disableLayout()
    {
        $this->_helper->layout()->disableLayout();
    }

    public function enableLayout()
    {
        $this->_helper->layout()->enableLayout();
    }

    public function setLayout($layoutName)
    {
        $this->_helper->layout()->setLayout($layoutName);
    }

    /**
     * 获取网站配置文件数组
     *
     * @return mixed
     */
    public function getConfig()
    {
        return Zend_Registry::get('config');
    }

    /**
     * 初始化
     * 
     * @see Zend_Controller_Action::init()
     */
    public function init()
    {
        $module = $this->getRequest()->getModuleName();
        $controller = $this->getRequest()->getControllerName();
        $action = $this->getRequest()->getActionName();
        $config = $this->getConfig();
        
        $this->assign('config', $config);
        $this->assign('module', $module);
        $this->assign('controller', $controller);
        $this->assign('action', $action);
        
        $path = $config['global']['path'];
        $scheme = $this->getRequest()->getScheme();
        $host = $this->getRequest()->getHttpHost();
        $this->assign('http_host_name', "{$scheme}://{$host}{$path}");
        
        // 微信支付
        // appId 公众号身份标识。
        // appSecret 公众平台API(参考文档API 接口部分)的权限获取所需密钥Key，在使用所有公众平台API 时，都需要先用它去换取access_token，然后再进行调用。
        // paySignKey公众号支付请求中用于加密的密钥Key，可验证商户唯一身份，PaySignKey对应于支付场景中的appKey 值。
        // partnerId 财付通商户身份标识。
        // partnerKey 财付通商户权限密钥Key。
        $this->assign('appId', $config['iWeixin']['pay']['appId']);
        $this->assign('appSecret', $config['iWeixin']['pay']['appSecret']);
        $this->assign('appKey', $config['iWeixin']['pay']['paySignKey']);
        $this->assign('partnerId', $config['iWeixin']['pay']['partnerId']);
        $this->assign('partnerKey', $config['iWeixin']['pay']['partnerKey']);
        $this->assign('notify_url', $config['iWeixin']['pay']['notify_url']);
    }

    /**
     * 获取并分析$_GET数组某参数值
     *
     * 获取$_GET的全局超级变量数组的某参数值,并进行转义化处理，提升代码安全.注:参数支持数组
     *
     * @access public
     * @param string $string
     *            所要获取$_GET的参数
     * @param string $defaultParam
     *            默认参数, 注:只有$string不为数组时有效
     * @return string $_GET数组某参数值
     */
    public function get($string, $defaultParam = null)
    {
        return $this->getRequest()->getParam($string, $defaultParam);
    }

    /**
     * 视图变量赋值操作
     *
     * @access public
     * @param mixted $keys
     *            视图变量名
     * @param string $value
     *            视图变量值
     * @return mixted
     */
    public function assign($keys, $value = null)
    {
        if ($this->view)
            $this->view->assign($keys, $value);
    }

    /**
     * 获取当前运行的Action的URL
     *
     * 获取当前Action的URL. 注:该网址由当前的控制器(Controller)及动作(Action)组成,不含有其它参数信息
     * 如:/index.php/index/list，而非/index.php/index/list/page/5 或 /index.php/index/list/?page=5
     *
     * @access public
     * @return string URL
     */
    public function getSelfUrl()
    {
        return $this->_helper->url($this->getRequest()
            ->getActionName());
    }

    /**
     * 获取某cookie变量的值
     *
     * 获取的数值是进过64decode解密的,注:参数支持数组
     *
     * @access public
     * @param string $cookieName
     *            cookie变量名
     * @return string
     */
    public static function getCookie($cookieName)
    {
        if (! $cookieName) {
            return false;
        }
        return isset($_COOKIE[$cookieName]) ? unserialize(base64_decode($_COOKIE[$cookieName])) : false;
    }

    /**
     * 设置某cookie变量的值
     *
     * 注:这里设置的cookie值是经过64code加密过的,要想获取需要解密.参数支持数组
     *
     * @access public
     * @param string $name
     *            cookie的变量名
     * @param string $value
     *            cookie值
     * @param intger $expire
     *            cookie所持续的有效时间,默认为一小时.(这个参数是时间段不是时间点,参数为一小时就是指从现在开始一小时内有效)
     * @param string $path
     *            cookie所存放的目录,默认为网站根目录
     * @param string $domain
     *            cookie所支持的域名,默认为空
     * @return void
     */
    public static function setCookie($name, $value, $expire = null, $path = null, $domain = null)
    {
        // 参数分析.
        $expire = is_null($expire) ? time() + 3600 : time() + $expire;
        if (is_null($path)) {
            $path = '/';
        }
        
        // 数据加密处理.
        $value = base64_encode(serialize($value));
        setcookie($name, $value, $expire, $path, $domain);
        $_COOKIE[$name] = $value;
    }

    public static function getAdminInfo()
    {
        $admin_info = new Zend_Session_Namespace('admin_info');
        return $admin_info;
    }

    public static function getClientInfo()
    {
        $client_info = new Zend_Session_Namespace('client_info');
        return $client_info;
    }

    public function hasViewRenderer()
    {
        $front = Zend_Controller_Front::getInstance();
        $noViewRenderer = $front->getParam('noViewRenderer');
        return ! $noViewRenderer;
    }

    public function result($msg = '', $result = '')
    {
        $jsonpcallback = trim($this->get('jsonpcallback'));
        if (! empty($jsonpcallback)) {
            return $jsonpcallback . '(' . json_encode(array(
                'success' => true,
                'message' => $msg,
                'result' => $result
            )) . ')';
        } else {
            return json_encode(array(
                'success' => true,
                'message' => $msg,
                'result' => $result
            ));
        }
    }

    public function error($code, $msg)
    {
        $jsonpcallback = trim($this->get('jsonpcallback'));
        if (! empty($jsonpcallback)) {
            return $jsonpcallback . '(' . json_encode(array(
                'success' => false,
                'message' => $msg,
                'result' => $result
            )) . ')';
        } else {
            return json_encode(array(
                'success' => false,
                'error_code' => $code,
                'error_msg' => $msg,
                'errorCode' => $code,
                'errorMsg' => $msg
            ));
        }
    }
}