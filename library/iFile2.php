<?php
/**
 * UMA文件存储的SDK，文件上限不能超过100M
 * @author Young
 * @version 1.0
 * @date 2013-03-15
 * 
 * 使用方法：
 * 
 * $o = new iFile2('51ee42974996198b64deb712', '1234567890abcdefghijklop');
 * 
 * 方式1
 * echo $o->save($_FILES['file']); //返回格式为http://scrm.umaman.com/soa/file/get/id/xxxxxxxx 的文件路径
 * 
 * 方式2
 * echo $o->store('1.jpg','asadasdasdasdasdasd'); //返回格式为http://scrm.umaman.com/soa/file/get/id/xxxxxxxx 的文件路径
 * 
 */
class iFile2 {
	/**
	 * soap服务的调用地址
	 * @var string
	 */
	private $_wsdl      = 'http://scrm.umaman.com/soa/file2/soap?wsdl';
	
	/**
	 * 是否每次加载WSDL 默认为false
	 * @var string 
	 */
	private $_refresh   = false;

	/**
	 * 调用客户端
	 * @var resource
	 */
	private $_client;
	
	/**
	 * 身份认证的命名空间
	 * @var string
	 */
	private $_namespace = 'http://scrm.umaman.com/soa/file2/soap';
	
	/**
	 * 身份认证中的授权方法名
	 * @var string
	 */
	private $_authenticate = 'authenticate';
	
	/**
	 * 项目编号
	 * @var string
	 */
	private $_project_id;
	
	/**
	 * 项目签名密码
	 * @var string
	 */
	private $_password;
	
	/**
	 * 随机字符串
	 * @var string
	 */
	private $_rand;
	
	/**
	 * 是否开启debug功能
	 * @var bool
	 */
	private $_debug = false;
	
	/**
	 * 记录错误信息
	 * @var string
	 */
	private $_error;
	
	/**
	 * 输出类型
	 * @var array
	 */
	private $_outTypes = array('id','url');
	
	/**
	 * 指定UMA图片路径的域名
	 * 
	 * 例如:http://images.xyz.com  必须包含http
	 * 
	 * @var string
	 */
	private $_domain = null;
	
	
	/**
	 * 初始化，建立文件存储链接
	 * @param string $project_id
     * @param string $password 
	 */
    public function __construct($project_id,$password) {
        $this->_client = null;
        $this->_project_id = $project_id;
        $this->_password   = $password;
        $this->_rand       = time();
        $this->connect();
    }
	
	/**
	 * 设定输出路径的域名
	 * @param string $domain
	 */
	public function setDomain($domain) {
	    if(!empty($domain)) {
	        if(filter_var($domain,FILTER_VALIDATE_URL)!==false)
	            $this->_domain = $domain;
	        else {
	            $this->_error = $domain.' Invalid URL';
	            throw new iFile2Exception('Invalid URL');
	        }
	    }
	}
	
	/**
	 * 开启或者关闭debug模式
	 * @param bool $debug
	 */
	public function setDebug($debug=false) {
	    $this->_debug = is_bool($debug) ? $debug : false;
	}
	
	/**
	 * 设定是否刷新wsdl缓存
	 * @param bool $bool
	 */
	public function setRefresh($bool) {
	    $this->_refresh = is_bool($bool) ? $bool : false;
	}

	/**
	 * 建立soap链接
	 * @param string $wsdl
	 * @param bool $refresh
	 * @return resource|boolean
	 */
	private function callSoap($wsdl) {
	    try {
            $options = array(
                    'soap_version'=>SOAP_1_2,//必须是1.2版本的soap协议，支持soapheader
                    'exceptions'=>true,
                    'trace'=>$this->_debug,
                    'connection_timeout'=>300, //避免网络延迟导致的链接丢失
                    'keep_alive'=>false,
                    'compression'=>true
            );
            if($this->_refresh==true)
                $options['cache_wsdl'] = WSDL_CACHE_NONE;
            else
                $options['cache_wsdl'] = WSDL_CACHE_DISK;
            	
            $this->_client = new SoapClient($wsdl,$options);
            return $this->_client;
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iFile2Exception($this->_error);
        }
	}
	
    /**
     * 进行调用授权身份认证处理
     * @return resource
     */
    private function connect() {
        $auth = array();
        $auth['project_id'] = $this->_project_id;
        $auth['rand']       = $this->_rand;
        $auth['sign']       = $this->sign();
        $authenticate       = new SoapHeader($this->_namespace,$this->_authenticate,new SoapVar($auth, SOAP_ENC_OBJECT), false);
    
        $this->_client = $this->callSoap($this->_wsdl);
        $this->_client->__setSoapHeaders(array($authenticate));
        return $this->_client;
    }
    
    /**
     * 签名算法
     * @return string
     */
    private function sign() {
    	return md5($this->_project_id.$this->_rand.$this->_password);
    }

	/**
	 * 存储文件的简化方法
	 * 
	 * @param resource $file    文件变量 $_FILES['file'];
	 * @param int      $expire  过期时间 0表示永久保存 设定过期时间，超过该时间会被清除
	 * @param string   $out     输出类型，默认为图片URL输出，可选参数id 输出id
	 * @return mixed string or false
	 */
	public function save($file,$expire=0,$out='url') {
	    if(array_key_exists('error', $file) && $file['error']==UPLOAD_ERR_OK) {
    	    $fileName = $file['name'];
    	    $file     = base64_encode(file_get_contents($file['tmp_name']));
    	    $expire   = intval($expire) > 0 ? intval($expire) : 0;
    	    $out      = in_array($out,$this->_outTypes) ? $out : 'url';
	    }
	    else {
	        $this->_error = 'UPLOAD FILE ERROR';
	        throw new iFile2Exception($this->_error);
	    }
	    
	    try {
	        $rst = $this->_client->storeFileOnCloud($fileName, $file ,$expire ,$out);
	        if($out=='url' && $this->_domain!=null ) {
	            $rst = preg_replace('/http(s)?:\/\/scrm\.umaman\.com/i', $this->_domain, $rst);
	        }
	        return $rst;
	    }
	    catch (Exception $e) {
	        $this->exceptionMsg($e);
	        throw new iFile2Exception($this->_error);
	    }
	}
	
	/**
	 * 采用原生的soap参数来存储文件
	 * 
	 * @param string $fileName 文件名 如123.jpg
	 * @param string $file     base64编码过的字符串
	 * @param int    $expire   过期时间 0表示永久保存 设定过期时间，超过该时间会被清除
	 * @param string $out      输出类型，默认为图片URL输出，可选参数id 输出id
	 * @return mixed string|false
	 */
	public function store($fileName, $file ,$expire=0 ,$out='url') {
	    if(empty($fileName) || empty($file)) {
	        $this->_error = '$fileName or $file is empty';
	        return false;
	    }
	    
	    $expire   = intval($expire) > 0 ? intval($expire) : 0;
	    $out      = in_array($out,$this->_outTypes) ? $out : 'url';
	    try {
	        $rst = $this->_client->storeFileOnCloud($fileName, $file ,$expire ,$out);
	        if($out=='url' && $this->_domain!=null) {
	            $rst = preg_replace('/http(s)?:\/\/scrm\.umaman\.com/i', $this->_domain, $rst);
	        }
	        return $rst;
	    }
	    catch (Exception $e) {
	        $this->exceptionMsg($e);
	        throw new iFile2Exception($this->_error);
	    }
	}
	
	/**
	 * 获取文件URL的简化方法
	 *
	 * @param string $fileId    文件ID变量;
	 * @param string $domain  设定输出路径的域名
	 * @return mixed string or false
	 */
	public function getUrlById($fileId,$domain=null) {
		try {
			$rst = "http://scrm.umaman.com/soa/image/get/id/{$fileId}";
			if(!empty($domain)) {
				if(filter_var($domain,FILTER_VALIDATE_URL)==false) {
					throw new Exception('Invalid URL');
				}				
			}else{
				$domain = $this->_domain;
			}
			if(!empty($domain)) {
				$rst = preg_replace('/http(s)?:\/\/scrm\.umaman\.com/i', $domain, $rst);
			}
			return $rst;
		}
		catch (Exception $e) {
			$this->exceptionMsg($e);
			throw new iFile2Exception($this->_error);
		}
	}
	
	/**
	 * 将异常信息记录到$this->_error中
	 * 
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
            var_dump(
                $this->_error,
                $this->_client->__getLastRequestHeaders(),
                $this->_client->__getLastRequest(),
                $this->_client->__getLastResponseHeaders(),
                $this->_client->__getLastResponse(),
                $this->_client,
                $this->_client->__getFunctions(),
                $this->_wsdl
            );
        }
    }
	
}

class iFile2Exception extends Exception
{
    
}

