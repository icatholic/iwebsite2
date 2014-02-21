<?php
/**
 *  
 * 客户端调用UMA iPay服务的php版本SDK
 * 
 * @version 1.0
 * @author Young
 *
 */

class iPay
{
	/**
	 * soap服务的调用地址
	 * @var string
	 */
	private $_wsdl;
	
	/**
	 * 是否每次加载WSDL 默认为false
	 * @var string 
	 */
	public  $_refresh   = false;
	
	/**
	 * 身份认证的命名空间
	 * @var string
	 */
	private $_namespace;
	
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
	 * 外部订单号
	 * @var string
	 */
	private $_out_trade_no;
	
	/**
	 * 项目签名密码
	 * @var string
	 */
	private $_password;
	
	/**
	 * 调用客户端
	 * @var resource
	 */
	private $_client;
	
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
	 * 买的
	 * @param string $project_id
	 * @param string $out_trade_no
	 * @param string $password
	 */
	public function __construct($project_id,$out_trade_no,$password) {
		$this->_project_id   = $project_id;
		$this->_out_trade_no = $out_trade_no;
		$this->_password     = $password;
		
		if($this->_project_id=='')
		    throw new iPayException('$project_id is empty');
		
		if($this->_out_trade_no=='')
		    throw new iPayException('$out_trade_no is empty');
		
		if($this->_password=='')
		    throw new iPayException('$password is empty');
	}

	/**
	 * 
	 * @param bool $bool true表示开始调试模式
	 */
	public function setDebug($bool) {
	    $this->_debug = $bool;
	}
	
	/**
	 * 设定支付类型
	 * @param string $type 参数说明：bank网银支付  escow担保交易  wap手机wap支付
	 * 
	 */
	private function setPayType($type) {
	    switch($type) {
	    	case 'bank':
	    		$this->_wsdl = 'http://scrm.umaman.com/soa/alipay-bank/soap?wsdl';
	    		$this->_namespace = 'http://scrm.umaman.com/soa/alipay-bank/soap?wsdl';
	    	    break;
	    	case 'escow':
	    	    $this->_wsdl = 'http://scrm.umaman.com/soa/alipay-escow/soap?wsdl';
	    	    $this->_namespace = 'http://scrm.umaman.com/soa/alipay-escow/soap?wsdl';
	    	    break;
	    	case 'wap':
	    	    $this->_wsdl = 'http://scrm.umaman.com/soa/alipay-wap/soap?wsdl';
	    	    $this->_namespace = 'http://scrm.umaman.com/soa/alipay-wap/soap?wsdl';
	    	    break;
	    	case 'tenpay':
	    		$this->_wsdl = 'http://scrm.umaman.com/soa/tenpay/soap?wsdl';
	    		$this->_namespace = 'http://scrm.umaman.com/soa/tenpay/soap?wsdl';
	    		break;
    		case 'chinapay':
    			$this->_wsdl = 'http://scrm.umaman.com/soa/chinapay/soap?wsdl';
    			$this->_namespace = 'http://scrm.umaman.com/soa/chinapay/soap?wsdl';
    			break;
	    	case 'bill99':
	    		$this->_wsdl = 'http://scrm.umaman.com/soa/bill995/soap?wsdl';
	    		$this->_namespace = 'http://scrm.umaman.com/soa/bill995/soap?wsdl';
	    		break;
	    	default:
	    	    //默认是直接支付
	    	    break;    
	    }
	    
	    $this->connect();
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
				'connection_timeout'=>300 //避免网络延迟导致的链接丢失
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
			throw new iPayException($this->_error);
		}
	}
	
	/**
	 * 进行调用授权身份认证处理
	 * @return resource
	 */
	private function connect() {
		$auth = array();
		$auth['project_id']   = $this->_project_id;
		$auth['out_trade_no'] = $this->_out_trade_no;
		$auth['sign']         = $this->sign();

		$authenticate  = new SoapHeader($this->_namespace,$this->_authenticate,new SoapVar($auth, SOAP_ENC_OBJECT), false);
		$this->_client = $this->callSoap($this->_wsdl,$this->_refresh);
		$this->_client->__setSoapHeaders(array($authenticate));
		return $this->_client;
	}
	/**
	 * 
	 * @param string $project_id
	 * @param string $order_id
	 * @param string $sign
	 */
	public function auth($project_id,$order_id){
		$this->setPayType('bill99');
		$sign = $this->sign();
		try {
			$rst = $this->_client->authenticate($project_id,$order_id,$sign);
			return $rst;
		}
		catch (Exception $e) {
		    $this->exceptionMsg($e);
		    throw new iPayException($this->_error);
		}
		
	}
	/**
	 * 签名算法
	 * @return string
	 */
	private function sign() {
		return md5($this->_project_id.$this->_out_trade_no.$this->_password);
	}
	
	/**
	 * 格式化返回结果
	 * @param  string $rst
	 * @return array
	 */
	private function rst($rst) {
		$rst = json_decode($rst,true);
		if($rst['err'])
			return $rst;
		else
			return $rst['result'];
	} 
	
	/**
	 * 支付宝即时到帐
	 * 
	 * @param string $out_trade_no
	 * @param string $subject
	 * @param double $total_fee
	 * @param string $body
	 * @param string $show_url
	 * @param string $extra_common_param
	 * @return array
	 */
	public function directPay($out_trade_no,$subject,$total_fee,$body='',$show_url='') {
	    $this->setPayType('direct');
	    try {
			$rst = $this->_client->pay($out_trade_no,$subject,$total_fee,$body='',$show_url='');
			return $this->rst($rst);
	    }
		catch (Exception $e) {
		    $this->exceptionMsg($e);
		    throw new iPayException($this->_error);
		}  
	}
	
	/**
	 * 网银直接支付
	 * 
	 * @param string $out_trade_no
	 * @param string $subject
	 * @param double $total_fee
	 * @param string $body
	 * @param string $show_url
	 * @return string
	 */
	public function bankPay($out_trade_no,$subject,$total_fee,$body='',$show_url='') {
	    $this->setPayType('bank');
	     
	    try {
	        $rst = $this->_client->pay($out_trade_no,$subject,$total_fee,$body='',$show_url='');
	    	return $this->rst($rst);
	    }
		catch (Exception $e) {
		    $this->exceptionMsg($e);
		    throw new iPayException($this->_error);
		}
	}
	
	/**
	 * 手机wap支付
	 * 
	 * @param string $out_trade_no
	 * @param string $subject
	 * @param double $total_fee
	 * @param string $out_user	商户系统用户唯一标识
	 * @param $merchant_url 付款中返回页面
	 * @return string
	 */
	public function wapPay($out_trade_no,$subject,$total_fee,$out_user,$merchant_url) {
	    $this->setPayType('wap');

	    try {
 	        $rst = $this->_client->pay($out_trade_no,$subject,$total_fee,$out_user,$merchant_url);
	    	return $this->rst($rst);
	    }
		catch (Exception $e) {
		    $this->exceptionMsg($e);
		    throw new iPayException($this->_error);
		}

	}
	
	public function chinaPay($out_trade_no,$subject,$total_fee,$out_user,$merchant_url) {
		$this->setPayType('chinapay');
		try {
			$rst = $this->_client->pay($out_trade_no,$subject,$total_fee,$out_user,$merchant_url);
			return $this->rst($rst);
	    }
		catch (Exception $e) {
		    $this->exceptionMsg($e);
		    throw new iPayException($this->_error);
		}
	}
	
	/**
	 * 担保交易
	 * 
	 * @param string $out_trade_no
	 * @param string $subject
	 * @param string $price
	 * @param int $quantity
	 * @param double $logistics_fee
	 * @param string $logistics_type
	 * @param string $logistics_payment
	 * @param string $body
	 * @param string $show_url
	 * @param string $receive_name
	 * @param string $receive_address
	 * @param string $receive_zip
	 * @param string $receive_phone
	 * @param string $receive_mobile
	 * @return string 
	 */
	public function escowPay($out_trade_no,$subject,$price,$quantity,$logistics_fee,$logistics_type,$logistics_payment,$body='',$show_url='',$receive_name='',$receive_address='',$receive_zip='',$receive_phone='',$receive_mobile='') {
	    $this->setPayType('escow');
	     
	    try {
	        $rst = $this->_client->pay($out_trade_no,$subject,$price,$quantity,$logistics_fee,$logistics_type,$logistics_payment,$body='',$show_url='',$receive_name='',$receive_address='',$receive_zip='',$receive_phone='',$receive_mobile='');
	    	return $this->rst($rst);
	    }
		catch (Exception $e) {
		    $this->exceptionMsg($e);
		    throw new iPayException($this->_error);
		}
	}
	
	/**
	 * 财付通
	 *
	 * @param array $arrayParameter
	 * @return string
	 */
	public function tenPay($arrayParameter) {
		$this->setPayType('tenpay');
		try {
			$rst = $this->_client->payurl($arrayParameter);
			return $rst;
	    }
		catch (Exception $e) {
		    $this->exceptionMsg($e);
		    throw new iPayException($this->_error);
		}
	}
	
	/**
	 * 快钱支付
	 * @param string $orderId 订单号
	 * @param sring $orderName  订单名称
	 * @param string $orderAmount   订单金额
	 * @param string $businessUser  商户名称
	 * @return array
	 */
	public function bill99Pay($orderId,$orderName,$orderAmount,$businessUser){
		$this->setPayType('bill99');
		
		try {
			$rst = $this->_client->pay($this->_project_id,$this->sign(),$orderId,$orderAmount,$orderName,$businessUser);
			return $rst;
	    }
		catch (Exception $e) {
		    $this->exceptionMsg($e);
		    throw new iPayException($this->_error);
		}
	}
	
	/**
	 * 快钱支付  银行快捷通道
	 * @param string $orderId 订单号
	 * @param sring $orderName  订单名称
	 * @param string $orderAmount   订单金额
	 * @param string $businessUser  商户名称
	 * @param string $bank  		银行编码
	 * @return array
	 */
	public function bill99PayBank($orderId,$orderName,$orderAmount,$businessUser,$bank){
		$this->setPayType('bill99');
	
		try {
			$rst = $this->_client->payBank($this->_project_id,$this->sign(),$orderId,$orderAmount,$orderName,$businessUser,$bank);
			return $rst;
	    }
		catch (Exception $e) {
		    $this->exceptionMsg($e);
		    throw new iPayException($this->_error);
		}
	}
	
	/**
	 * 快钱支付后验证订单是否成功
	 * @param string $orderId 订单号
	 * @return array
	 */
	public function bill99CheckOrder($orderId){
		$this->setPayType('bill99');
		
		try {
			$rst = $this->_client->back($orderId,$this->sign(),$this->_project_id);
			return $rst;
	    }
		catch (Exception $e) {
		    $this->exceptionMsg($e);
		    throw new iPayException($this->_error);
		}
	}
	/**
	 * 快钱支付后验证订单是否成功
	 * @param string $orderId 订单号
	 * @return array
	 */
	public function bill99Test($orderId){
		$this->setPayType('bill99');
	
		try {
		    $rst = $this->_client->test($orderId);
			return $rst;
	    }
		catch (Exception $e) {
		    $this->exceptionMsg($e);
		    throw new iPayException($this->_error);
		}
	}
	
	/**
	 * 校验检测
	 * @return boolean
	 */
	public function verifyReturn() {
	    $sign = isset($_REQUEST['sign']) ? $_REQUEST['sign'] : '';
	    if($sign==$this->sign()) return true;
	    return false;
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

class iPayException extends Exception {
    
}