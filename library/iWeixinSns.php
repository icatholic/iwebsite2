<?php
//用户管理
class WeixinSnsUserManager
{
	protected $weixin;
	public function getWeixin()
	{
		return $this->weixin;
	}
		
	public function __construct(iWeixinSns $weixin) {
		$this->weixin    = $weixin;	
	}

	/**
	 * 拉取用户信息(需scope为 snsapi_userinfo)
	 * 如果网页授权作用域为snsapi_userinfo，
	 * 则此时开发者可以通过access_token和openid拉取用户信息了。
	 * @author Kan
	 *
	 */
	public function getSnsUserInfo($umaId,$openid,$scope="snsapi_userinfo")
	{
		$projectId=$this->getWeixin()->getProjectId();
		return $this->weixin->get($umaId, "sns/userinfo", array("openid"=>$openid,"scope"=>$scope));
	}
	
}


class iWeixinSns
{
	/**
	 * soap服务的调用地址
	 * @var string
	 */
	private $_wsdl      = 'http://scrm.umaman.com/soa/weixin-sns/soap?wsdl';
	public function getWsdl()
	{
		return $this->_wsdl;
	}
	
	/**
	 * 调用客户端
	 * @var resource
	 */
	private $_client;
	public function getClient()
	{
		return $this->_client;
	}
	
	/**
	 * 是否刷新soap请求的wsdl缓存
	 * @var bool
	 */
	private $_refresh = false;
	
	/**
	 * code
	 * @var string
	 */
	private $code = '';
	public function getCode()
	{
		return $this->code;
	}
	public function setCode($code)
	{
		$this->code = $code;
	}
	/**
	 * 项目编号
	 * @var string
	 */
	private $_project_id = '';
	public function getProjectId()
	{
		return $this->_project_id;
	}
	
    private   $_token;
    public function getToken()
    {
    	return $this->_token;
    }
    
    protected $weixinUserManager;
    public function getWeixinUserManager()
    {
    	return $this->weixinUserManager;
    }
    
    /**
     * 
     * @param string $token UMA后台iweixin项目的token
     * 
     */
    public function __construct($project_id,$token) {
        $this->_project_id    = $project_id;
        $this->_token    = $token;       
        //用户管理        
        $this->weixinUserManager = new WeixinSnsUserManager($this);        
        ////生成client对象 延迟到具体调用的时候
        //$this->connect();
        //获取umaId
        //$this->getUmaId();
    }
    
    /**
     * 进行调用授权身份认证处理
     * @return resource
     */
    public function connect() {
    	$this->_client = $this->callSoap($this->_wsdl,$this->_refresh);
    	return $this->_client;
    }
    
    /**
     * 进行调用授权身份认证处理
     * @return resource
     */
    public function isConnected() {
    	 return !empty($this->_client);
    }
    /**
     * 建立soap链接
     * @param string $wsdl
     * @param bool $refresh
     * @return resource|boolean
     */
    private function callSoap($wsdl,$refresh=false) {
    	$options = array(
    			'soap_version'=>SOAP_1_2,//必须是1.2版本的soap协议，支持soapheader
    			'exceptions'=>true,
    			'trace'=>true,
    			'connection_timeout'=>60, //避免网络延迟导致的链接丢失
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
    
    /**
     *
     * 获取项目的授权地址信息
     *
     * @param string $redirecUri
     * @param string $project_id 项目ID
     * @param string $scope 应用授权作用域
     * @param string $response_type 默认值为code
     * @param string $state 重定向后会带上state参数，开发者可以填写任意参数值
     * @return string 返回认证地址
     *
     */
    public function getAuthorizeURL ($redirectUri,$scope = "snsapi_userinfo", $response_type = 'code', $state = "") {    	
    	if(!$this->isConnected()){
    		$this->connect();
    	}
    	return $this->_client->getAuthorizeURL($redirectUri,$this->_project_id,$scope,$response_type,$state);
    }
    
    /**
     * 发送GET类型的请求到微信API
     * @param string $umaId UMA系统中的唯一授权表示 通过这个标示获取系统中的微信access_token
     * @param string $url   微信API的请求地址  例如：user/info
     * @param array  $parameters 微信API请求地址对应的API参数
     * @return array
     */
    public function get($umaId, $url, $parameters = array()) {
    	if(!$this->isConnected()){
    		$this->connect();
    	}
    	return $this->_client->get($umaId, $url, $parameters);
    }
    
    /**
     * 发送POST类型的请求到微信API
     * @param string $umaId UMA系统中的唯一授权表示 通过这个标示获取系统中的微信access_token
     * @param string $url   微信API的请求地址  例如：menu/create
     * @param array  $parameters 微信API请求地址对应的API参数
     *
     * @return array
     */
    public function post($umaId, $url, $parameters = array()) {
    	if(!$this->isConnected()){
    		$this->connect();
    	}
    	return $this->_client->post($umaId, $url, $parameters);
    }
    
    /**
     *
     *
     * 获取该SnsAccessToken
     *
     * @param string $projectId
     * @param string $code
     * @return array
     */
    public function getSnsAccessToken ($code="")
    {
    	if(!$this->isConnected()){
    		$this->connect();
    	}
    	$token = $this->_client->getSnsAccessToken($this->_project_id,$code);
    	return $token;
    }
    
    public function __destruct() {
        
    }
}
