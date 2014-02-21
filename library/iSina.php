<?php
/**
 * 新浪微博接口API
 * @author Young
 * @version 1.0
 * @date 2013-04-05
 * 
 * 使用方法：
 * $o = new iSina('微博项目ID');
 * $o->getAuthorizeURL('回调URL');
 * 进行新浪微博授权，然后会自动跳转到回调URL，并GET传递两个参数umaId和sina的uid，请记录umaId
 * $o->get($umaId,'statuses/user_timeline',array('对应新浪接口的参数'));
 * 同理处理POST 
 * 注意最后一个参数：$multi 默认为false。当POST参数中包含上传文件信息时，请将$multi参数设置为true
 * 例如：上传带图片的微博接口  
     * URL为statuses/upload 
     * 参数为pic表示上传图片，路径为@http://www.filedomain.com/filepath/filename
     * 此时，请将$multi设置为true
 */

class iSina
{
	/**
	 * soap服务的调用地址
	 * @var string
	 */
	private $_wsdl      = 'http://scrm.umaman.com/soa/sina/soap?wsdl';
	
	/**
	 * 项目编号
	 * @var string
	 */
	private $_project_id;
    
    /**
     * 调用客户端
     * @var resource
     */
    private $_client;
    

    /**
     * 是否刷新soap请求的wsdl缓存
     * @var bool
     */
    private $_refresh = false;
    
    /**
     *
     * @var bool 是否开启调试模式 默认为关闭
     */
    private $_debug = false;
    
    /**
     * 记录错误信息
     * @var string
     */
    private $_error;
    
    /**
     * 初始化需要传递项目编号，具体参数值请查阅UMA后台
     *
     * @param string $project_id 
     * 
     */
    public function __construct($project_id) {
        $this->_project_id = $project_id;
        $this->connect();
    }
    
    /**
     * 开启或者关闭debug模式
     * @param bool $debug
     */
    public function setDebug($debug=false) {
        $this->_debug = is_bool($debug) ? $debug : false;
    }
    
    /**
     * 开启或者关闭soap客户端的wsdl缓存
     * @param bool $refresh
     */
    public function setRefresh($refresh=false) {
        $this->_refresh = is_bool($refresh) ? $refresh : false;
    }
    
    /**
     * 建立soap链接
     * @param string $wsdl
     * @param bool $refresh
     * @return resource|boolean
     */
    private function callSoap($wsdl,$refresh=false) {
        try {
            $options = array(
                    'soap_version'=>SOAP_1_2,//必须是1.2版本的soap协议，支持soapheader
                    'exceptions'=>true,
                    'trace'=>true,
                    'connection_timeout'=>30, //避免网络延迟导致的链接丢失
                    'keep_alive'=>false,
                    'compression'=>true
            );
            if($refresh==true)
                $options['cache_wsdl'] = WSDL_CACHE_NONE;
            else
                $options['cache_wsdl'] = WSDL_CACHE_DISK;
            	
            $this->_client = new SoapClient($wsdl,$options);
            return $this->_client;
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iSinaException($this->_error);
        }
    }
    
    /**
     * 进行调用授权身份认证处理
     * @return resource
     */
    private function connect() {
        $this->_client = $this->callSoap($this->_wsdl,$this->_refresh);
        return $this->_client;
    }
    
    /**
     *
     * 获取项目的授权地址信息
     *
     * @param string $redirecUri            
     * @return string 返回认证地址
     * 
     */
    public function getAuthorizeURL ($redirectUri) {
        try {
            return $this->_client->getAuthorizeURL($redirectUri,$this->_project_id);
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iSinaException($this->_error);
        }
    }
    
    /**
     * 读取指定UMA ID对应的真正的新浪微博token信息
     * 
     * @param string $umaId
     * @return array
     * 
     */
    public function getToken($umaId) {
        try {
            return $this->_client->getToken($umaId);
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iSinaException($this->_error);
        } 
    }
    
    /**
     * 发送GET类型的请求到微博API
     * @param string $umaId UMA系统中的唯一授权表示 通过这个标示获取系统中的新浪微博access_token
     * @param string $url   新浪微博API的请求地址  例如：statuses/user_timeline表示获取用户发布的微博 
     * @param array  $parameters 新浪微博API请求地址对应的API参数
     * @return array
     */
    public function get($umaId, $url, $parameters = array()) {
        try {
            return $this->_client->get($umaId, $url, $parameters); 
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iSinaException($this->_error);
        }
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
     * @return array
     */
    public function post($umaId, $url, $parameters = array(), $multi = false) {
        try {
            return $this->_client->post($umaId, $url, $parameters, $multi);
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iSinaException($this->_error);
        }
    }
    
    /**
     * UMA系统的独有方法，获取授权信息列表，返回一个包含指定$number数量的umaid的数组
     * @param int $number
     * @return array
     */
    public function getAccessTokenList($number=10) {
        $number = is_int($number) && $number>0 ? $number : 10;
        try {
            return $this->_client->getAccessTokenList($number);
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iSinaException($this->_error);
        }
    }
    
    /**
     * 获取用户的个人信息
     * @param string $umaId
     * @param string $screenName
     * @return mixed
     */
    public function getUserInfo($umaId,$screenName) {
        try {
            return $this->_client->getUserInfo($umaId,$screenName);
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iSinaException($this->_error);
        }
    }
    
    /**
     * 将异常信息记录到$this->_error中
     * @param object $e
     * @return null
     */
    private function exceptionMsg($e) {
        $this->_error = $e->getMessage().$e->getFile().$e->getLine().$e->getTraceAsString();
    }
    
    /**
     * 析构函数
     */
    public function __destruct() {
    	if($this->_debug) {
			var_dump($this->_error,
			         $this->_client->__getLastRequestHeaders(),
			         $this->_client->__getLastRequest(),
			         $this->_client->__getLastResponseHeaders(),
			         $this->_client->__getLastResponse(),
			         $this->_client->__getFunctions(),
			         $this->_wsdl);
		}
    } 
    
}
/**
 * iDatabase异常处理函数
 * @author young
 *
 */
class iSinaException extends Exception {

}