<?php
/**
 * 微信商城--微信支付
 * @author 郭永荣
 *
 */
class Weixinshop_PayController extends iWebsite_Controller_Action {
	private $weixinPay = null;
	private $notify_url = "";
	private $weixin;
	private $errorLog = null;
	public function init() {
		parent::init ();
		
		$this->errorLog = new Weixinshop_Model_PayErrorLog ();
		
		$this->getHelper ( 'viewRenderer' )->setNoRender ( true );
		$config = Zend_Registry::get ( 'config' );
		$this->notify_url = $config ['iWeixin'] ['pay'] ['notify_url'];
		$token = $config ['iWeixin'] ['token'];
		$project_id = $config ['iWeixin'] ['project_id'];
		
		$this->weixin = new Weixin\Client ();
		$tokenServer = new Weixin\Token\Server ( $config ['iWeixin'] ['pay'] ['appId'], $config ['iWeixin'] ['pay'] ['appSecret'] );
		$token = $tokenServer->getAccessToken ();
		
		$iWeixinPay = new Weixin\Pay ();
		$iWeixinPay->setAppId ( $config ['iWeixin'] ['pay'] ['appId'] );
		$iWeixinPay->setAppSecret ( $config ['iWeixin'] ['pay'] ['appSecret'] );
		$iWeixinPay->setPaySignKey ( $config ['iWeixin'] ['pay'] ['paySignKey'] );
		$iWeixinPay->setPartnerId ( $config ['iWeixin'] ['pay'] ['partnerId'] );
		$iWeixinPay->setPartnerKey ( $config ['iWeixin'] ['pay'] ['partnerKey'] );
		$iWeixinPay->setAccessToken ( $token ['access_token'] );
		$this->weixinPay = $iWeixinPay;
	}
	
	/**
	 * 生成订单
	 */
	public function createOrderAction() {
		// http://iwebsite2/weixinshop/pay/create-order?jsonpcallback=?&OpenId=xxx&ProductId=xxxx&gnum=xxxx
		try {
			$OpenId = $this->getRequest ()->getCookie ( "FromUserName", '' );
			// $OpenId = trim ( $this->get ( 'OpenId', '' ) ); // 微信号
			// if (empty ( $OpenId )) {
			// throw new Exception ( "微信号为空" );
			// }
			$ProductId = trim ( $this->get ( 'ProductId', '' ) ); // 商品号
			if (empty ( $ProductId )) {
				throw new Exception ( "商品号为空" );
			}
			$gnum = trim ( $this->get ( 'gnum', '0' ) ); // 购买数量
			if (empty ( $gnum )) {
				throw new Exception ( "购买数量为空" );
			}
			
			$consignee_province = trim ( $this->get ( 'consignee_province', '' ) ); // 收货人省份
			if (empty ( $consignee_province )) {
				throw new Exception ( "收货人省份为空" );
			}
			$consignee_city = trim ( $this->get ( 'consignee_city', '' ) ); // 收货人城市
			if (empty ( $consignee_city )) {
				throw new Exception ( "收货人城市为空" );
			}
			$consignee_area = trim ( $this->get ( 'consignee_area', '' ) ); // 收货人区或县
			if (empty ( $consignee_area )) {
				throw new Exception ( "收货人区或县为空" );
			}
			$consignee_address = trim ( $this->get ( 'consignee_address', '' ) ); // 收货地址
			if (empty ( $consignee_address )) {
				throw new Exception ( "收货地址为空" );
			}
			$consignee_name = trim ( $this->get ( 'consignee_name', '' ) ); // 收货人
			if (empty ( $consignee_name )) {
				throw new Exception ( "收货人为空" );
			}
			$consignee_address = trim ( $this->get ( 'consignee_address', '' ) ); // 收货地址
			if (empty ( $consignee_address )) {
				throw new Exception ( "收货地址为空" );
			}
			$consignee_tel = trim ( $this->get ( 'consignee_tel', '' ) ); // 收货人手机
			if (empty ( $consignee_tel )) {
				throw new Exception ( "收货人手机为空" );
			}
			if (! isValidMobile ( $consignee_tel )) {
				throw new Exception ( "收货人手机格式不正确" );
			}
			
			$consignee_zipcode = trim ( $this->get ( 'consignee_zipcode', '' ) ); // 邮政编码
			if (empty ( $consignee_zipcode )) {
				throw new Exception ( "邮政编码为空" );
			}
			$modelGoods = new Weixinshop_Model_Goods ();
			$goodsInfo = $modelGoods->getInfoByGid ( $ProductId );
			if (empty ( $goodsInfo )) {
				throw new Exception ( "商品号无效" );
			}
			
			// 生成订单
			$modelOrder = new Weixinshop_Model_Order ();
			$orderInfo = $modelOrder->createOrder ( $OpenId, $ProductId, $goodsInfo ['body'], $goodsInfo ['gprize'], $gnum, $this->notify_url, $goodsInfo ['attach'], $goodsInfo ['goods_tag'], $goodsInfo ['transport_fee'], $goodsInfo ['composite_sku_no'], $consignee_province, $consignee_city, $consignee_area, $consignee_name, $consignee_address, $consignee_tel, $consignee_zipcode );
			
			// 记录收货人信息
			$modelConsignee = new Weixinshop_Model_Consignee ();
			$modelConsignee->log ( $orderInfo ['consignee_province'], $orderInfo ['consignee_city'], $orderInfo ['consignee_area'], $orderInfo ['consignee_name'], $orderInfo ['consignee_address'], $orderInfo ['consignee_tel'], $orderInfo ['consignee_zipcode'], $orderInfo ['OpenId'], $orderInfo ['_id'] );
			
			$_SESSION ['orderInfo'] = $orderInfo;
			exit ( $this->result ( "处理结束", $orderInfo ) );
		} catch ( Exception $e ) {
			$this->errorLog->log ( $e );
			exit ( $this->error ( false, $e->getMessage () ) );
		}
	}
	
	/**
	 * Native（原生）支付回调商户后台获取package
	 */
	public function getPackageInfoAction() {
		$RetCode = 0;
		$RetErrMsg = "ok";
		$out_trade_no = "";
		$package = "";
		
		try {
			// $postStr = "<xml>
			// <AppId><![CDATA[wxf8b4f85f3a794e77]]></AppId>
			// <OpenId><![CDATA[111222]]></OpenId>
			// <IsSubscribe>1</IsSubscribe>
			// <ProductId><![CDATA[777111666]]></ProductId>
			// <TimeStamp>1369743908</TimeStamp>
			// <NonceStr><![CDATA[YvMZOX28YQkoU1i4NdOnlXB1]]></NonceStr>
			// <AppSignature><![CDATA[351a6eb8f2c6cbee2a06ab51331a060b7bddf450]]></AppSignature>
			// <SignMethod><![CDATA[sha1]]></SignMethod>
			// </xml>";
			$postStr = file_get_contents ( 'php://input' );
			$postData = ( array ) simplexml_load_string ( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );
			
			// 参与签名的字段为：appid、appkey、productid、timestamp、noncestr、openid、issubscribe
			// 获取paySign
			$paySignPara = array (
					"AppId" => $postData ["AppId"],
					"OpenId" => $postData ["OpenId"],
					"IsSubscribe" => $postData ["IsSubscribe"],
					"ProductId" => $postData ["ProductId"],
					"TimeStamp" => $postData ["TimeStamp"],
					"NonceStr" => $postData ["NonceStr"] 
			);
			// $paySign = $this->weixinPay->getPaySign ( $paySignPara );
			// die($paySign);
			
			// 签名校验
			$checkRet = $this->checkAppSignature ( $paySignPara, $postData ['AppSignature'] );
			$calc_appSignature = $checkRet ['sign'];
			if (empty ( $checkRet ['isvalid'] )) {
				throw new Exception ( "AppSignature签名校验无效:{$checkRet['sign']}", - 1 );
			}
			
			// 判断商品是否有效
			$modelGoods = new Weixinshop_Model_Goods ();
			$goodsInfo = $modelGoods->getInfoByGid ( $postData ['ProductId'] );
			if (empty ( $goodsInfo )) {
				throw new Exception ( "商品号无效", - 2 );
			}
			
			// 生成订单
			$modelOrder = new Weixinshop_Model_Order ();
			$orderInfo = $modelOrder->createOrder ( $postData ['OpenId'], $postData ['ProductId'], $goodsInfo ['body'], $goodsInfo ['gprize'], 1, $this->notify_url, $goodsInfo ['attach'], $goodsInfo ['goods_tag'], $goodsInfo ['transport_fee'], $goodsInfo ['composite_sku_no'] );
			
			// 获取Package
			$package = $goodsInfo ['package'];
			$out_trade_no = $orderInfo ['out_trade_no'];
		} catch ( Exception $e ) {
			$this->errorLog->log ( $e );
			$RetCode = $e->getCode ();
			$RetErrMsg = $e->getMessage ();
		}
		
		// 调用Native（原生）支付回调商户后台获取package
		$ret = $this->weixinPay->getPackageForNativeUrl ( $package, $orderInfo ['nonceStr'], 		// createRandCode(32),//
		$orderInfo ['timeStamp'], 		// time(),//
		$postData ['SignMethod'], $RetCode, $RetErrMsg );
		
		// 记录
		$modelPayNativePackageResult = new Weixinshop_Model_PayNativePackageResult ();
		$modelPayNativePackageResult->log ( $postData ['AppId'], $postData ['OpenId'], $postData ['IsSubscribe'], $postData ['ProductId'], $postData ['TimeStamp'], $postData ['NonceStr'], $postData ['AppSignature'], $postData ['SignMethod'], $postStr, $RetCode, $RetErrMsg, $out_trade_no, $package, $ret, $calc_appSignature );
		
		echo $ret;
		exit ();
	}
	
	/**
	 * 用户在成功完成支付后，微信后台通知（post）商户服务器（notify_url）支付结果。
	 * 商户可以使用notify_url 的通知结果进行个性化页面的展示。
	 * 对后台通知交互时，如果微信收到商户的应答不是success 或超时，微信认为通知失败，
	 * 微信会通过一定的策略（如30 分钟共8 次）定期重新发起通知，尽可能提高通知的成功率,
	 * 但微信不保证通知最终能成功。
	 * 由于存在重新发送后台通知的情况，因此同样的通知可能会多次发送给商户系统。商户
	 * 系统必须能够正确处理重复的通知。
	 * 微信推荐的做法是，当收到通知进行处理时，首先检查对应业务数据的状态，判断该通
	 * 知是否已经处理过，如果没有处理过再进行处理，如果处理过直接返回success。在对业务
	 * 数据进行状态检查和处理之前，要采用数据锁进行并发控制，以避免函数重入造成的数据混
	 * 乱。
	 * 目前补单机制的间隔时间为：8s、10s、10s、30s、30s、60s、120s、360s、1000s。
	 */
	public function notifyAction() {
		/**
		 * 后台通知通过请求中的notify_url 进行，采用post 机制。返回通知中的参数一致，url
		 * 包含如下内容：
		 * 字段名 变量名 必填 类型 说明
		 * 协议参数
		 * 签名方式sign_type否String(8)签名类型，取值：MD5、RSA，默认：MD5
		 * 接口版本service_version否String(8)版本号，默认为1.0
		 * 字符集input_charset否String(8)字符编码,取值：GBK、UTF-8，默认：GBK。
		 * 签名sign是String(32)签名
		 * 密钥序号sign_key_index否Int多密钥支持的密钥序号，默认1
		 *
		 * 业务参数
		 * 交易模式trade_mode是Int1-即时到账其他保留
		 * 交易状态trade_state是Int支付结果：0—成功其他保留
		 * 支付结果信息pay_info否String(64)支付结果信息，支付成功时为空
		 * 商户号partner是String(10)商户号， 也即之前步骤的partnerid, 由微信统一分配的10
		 * 位正整数(120XXXXXXX)号
		 * 付款银行bank_type是String(16)银行类型，在微信中使用WX
		 * 银行订单号bank_billno否String(32)银行订单号
		 * 总金额total_fee是Int支付金额，单位为分，如果discount 有值，通知的total_fee+ discount =
		 * 请求的total_fee
		 * 币种fee_type是Int现金支付币种,目前只支持人民币,默认值是1-人民币
		 * 通知ID notify_id是String(128)支付结果通知id，对于某些特定商户，只返回通知id，要求商户据此查询交易结果
		 * 订单号transaction_id是String(28)交易号，28 位长的数值，其中前10 位为商户号，之后8 位为订单产生的日期，
		 * 如20090415，最后10 位是流水号。
		 * 商户订单号out_trade_no是String(32)商户系统的订单号，与请求一致。
		 * 商家数据包attach否String(127)商家数据包，原样返回
		 * 支付完成时间time_end是String(14) 支付完成时间， 格式为yyyyMMddhhmmss ， 如2009年12 月27 日9
		 * 点10 分10 秒表示为20091227091010。时区为GMT+8 beijing。
		 * 物流费用transport_fee否Int物流费用，单位分，默认0。如果有值， 必须保证transport_fee +
		 * product_fee =total_fee
		 * 物品费用product_fee否Int物品费用，单位分。如果有值，必须保证transport_fee
		 * +product_fee=total_fee折扣价格
		 * discount 否Int折扣价格，单位分，如果有值，通知的total_fee + discount =请求的total_fee
		 * 买家别名buyer_alias否String(64)对应买家账号的一个加密串
		 *
		 * 同时，在postData 中还将包含xml 数据。数据如下：
		 * <xml>
		 * <OpenId><![CDATA[111222]]></OpenId>
		 * <AppId><![CDATA[wxf8b4f85f3a794e77]]></AppId>
		 * <IsSubscribe>1</IsSubscribe>
		 * <TimeStamp> 1369743511</TimeStamp>
		 * <NonceStr><![CDATA[jALldRTHAFd5Tgs5]]></NonceStr>
		 * <AppSignature><![CDATA[bafe07f060f22dcda0bfdb4b5ff756f973aecffa]]></AppSignature>
		 * <SignMethod><![CDATA[sha1]]></ SignMethod >
		 * </xml>
		 * 各字段定义如下：
		 * 参数 必填 说明
		 * AppId 是字段名称：公众号id；字段来源：商户注册具有支付权限的公众号成功后即可获得；传入方式：由商户直接传入。
		 * TimeStamp 是 字段名称：时间戳；字段来源：商户生成从1970 年1 月1日00：00：00
		 * 至今的秒数，即当前的时间；由商户生成后传入。取值范围：32 字符以下
		 * NonceStr 是字段名称：随机字符串；字段来源：商户生成的随机字符串；取值范围：长度为32
		 * 个字符以下。由商户生成后传入。取值范围：32 字符以下
		 * OpenId 是支付该笔订单的用户ID，商户可通过公众号其他接口为付款用户服务。
		 * AppSignature 是字段名称：签名；字段来源：对前面的其他字段与appKey按照字典序排序后，使用SHA1
		 * 算法得到的结果。由商户生成后传入。
		 * IsSubscribe 是用户是否关注了公众号。1 为关注，0 为未关注。
		 *
		 * AppSignature 依然是根据前文paySign 所述的签名方式生成，
		 * 参与签名的字段为：appid、appkey、timestamp、noncestr、openid、issubscribe。
		 * 从以上信息可以看出，url 参数中携带订单相关信息，postData 中携带该次支付的用户
		 * 相关信息，这将便于商家拿到openid，以便后续提供更好的售后服务。
		 */
		$ret = array ();
		$ret ['notify_result'] = "fail";
		$ret ['error'] = "";
		try {
			$data = array ();
			// 协议参数
			// 签名方式sign_type否String(8)签名类型，取值：MD5、RSA，默认：MD5
			$data ['sign_type'] = $sign_type = ($this->get ( 'sign_type' ));
			// 接口版本service_version否String(8)版本号，默认为1.0
			$data ['service_version'] = $service_version = ($this->get ( 'service_version' ));
			// 字符集input_charset否String(8)字符编码,取值：GBK、UTF-8，默认：GBK。
			$data ['input_charset'] = $input_charset = ($this->get ( 'input_charset' ));
			// 签名sign是String(32)签名
			$data ['sign'] = $sign = ($this->get ( 'sign' ));
			if (strlen ( $sign ) < 1) {
				throw new Exception ( '签名sign为空' );
			}
			// 密钥序号sign_key_index否Int多密钥支持的密钥序号，默认1
			$data ['sign_key_index'] = $sign_key_index = ($this->get ( 'sign_key_index' ));
			
			// 业务参数
			// 交易模式trade_mode是Int 1-即时到账其他保留
			$data ['trade_mode'] = $trade_mode = ($this->get ( 'trade_mode' ));
			// 交易状态trade_state是Int支付结果：0—成功其他保留
			$data ['trade_state'] = $trade_state = ($this->get ( 'trade_state' ));
			// 支付结果信息pay_info否String(64)支付结果信息，支付成功时为空
			$data ['pay_info'] = $pay_info = ($this->get ( 'pay_info' ));
			// 商户号partner是String(10)商户号， 也即之前步骤的partnerid, 由微信统一分配的10
			// 位正整数(120XXXXXXX)号
			$data ['partner'] = $partner = ($this->get ( 'partner' ));
			if (strlen ( $partner ) < 1) {
				throw new Exception ( '商户号partner为空' );
			}
			// 付款银行bank_type是String(16)银行类型，在微信中使用WX
			$data ['bank_type'] = $bank_type = ($this->get ( 'bank_type' ));
			// 银行订单号bank_billno否String(32)银行订单号
			$data ['bank_billno'] = $bank_billno = ($this->get ( 'bank_billno' ));
			// 总金额total_fee是Int支付金额，单位为分，如果discount 有值，通知的total_fee+ discount =
			// 请求的total_fee
			$data ['total_fee'] = $total_fee = ($this->get ( 'total_fee' ));
			// 币种fee_type是Int现金支付币种,目前只支持人民币,默认值是1-人民币
			$data ['fee_type'] = $fee_type = ($this->get ( 'fee_type' ));
			// 通知ID notify_id是String(128)支付结果通知id，对于某些特定商户，只返回通知id，要求商户据此查询交易结果
			$data ['notify_id'] = $notify_id = ($this->get ( 'notify_id' ));
			if (strlen ( $notify_id ) < 1) {
				throw new Exception ( '通知ID(notify_id)为空' );
			}
			// 订单号transaction_id是String(28)交易号，28 位长的数值，其中前10 位为商户号，之后8
			// 位为订单产生的日期， 如20090415，最后10 位是流水号。
			$data ['transaction_id'] = $transaction_id = ($this->get ( 'transaction_id' ));
			if (strlen ( $transaction_id ) < 1) {
				throw new Exception ( '订单号transaction_id为空' );
			}
			// 商户订单号out_trade_no是String(32)商户系统的订单号，与请求一致。
			$data ['out_trade_no'] = $out_trade_no = ($this->get ( 'out_trade_no' ));
			if (strlen ( $out_trade_no ) < 1) {
				throw new Exception ( '商户订单号out_trade_no为空' );
			}
			// 商家数据包attach否String(127)商家数据包，原样返回
			$data ['attach'] = $attach = ($this->get ( 'attach' ));
			// 支付完成时间time_end是String(14)支付完成时间， 格式为yyyyMMddhhmmss ， 如2009年12 月27
			// 日9 点10 分10 秒表示为20091227091010。时区为GMT+8 beijing。
			$data ['time_end'] = $time_end = ($this->get ( 'time_end' ));
			if (strlen ( $time_end ) < 1) {
				throw new Exception ( '支付完成时间time_end为空' );
			}
			// 物流费用transport_fee否Int物流费用，单位分，默认0。如果有值， 必须保证transport_fee +
			// product_fee =total_fee
			$data ['transport_fee'] = $transport_fee = ($this->get ( 'transport_fee' ));
			// 物品费用product_fee否Int物品费用，单位分。如果有值，必须保证transport_fee
			// +product_fee=total_fee折扣价格
			$data ['product_fee'] = $product_fee = ($this->get ( 'product_fee' ));
			// discount否Int折扣价格，单位分，如果有值，通知的total_fee + discount =请求的total_fee
			$data ['discount'] = $discount = ($this->get ( 'discount' ));
			// 买家别名buyer_alias否String(64)对应买家账号的一个加密串
			$data ['buyer_alias'] = $buyer_alias = ($this->get ( 'buyer_alias' ));
			
			// postData 中还将包含xml 数据
			// $postStr ="<xml>
			// <AppId><![CDATA[wxf8b4f85f3a794e77]]></AppId>
			// <OpenId><![CDATA[111222]]></OpenId>
			// <IsSubscribe>1</IsSubscribe>
			// <ProductId><![CDATA[777111666]]></ProductId>
			// <TimeStamp>1369743908</TimeStamp>
			// <NonceStr><![CDATA[YvMZOX28YQkoU1i4NdOnlXB1]]></NonceStr>
			// <AppSignature><![CDATA[351a6eb8f2c6cbee2a06ab51331a060b7bddf450]]></AppSignature>
			// <SignMethod><![CDATA[sha1]]></SignMethod>
			// </xml>";
			$postStr = file_get_contents ( 'php://input' );
			$postData = ( array ) simplexml_load_string ( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );
			
			// $sign = $this->weixinPay->getSign ( $data );
			// die($sign);
			
			// 签名校验
			$checkSignRet = $this->checkSign ( $data ); // ??这段还是有问题
			$calc_sign = $checkSignRet ['sign'];
			// $checkSignRet = array('isvalid'=>true);
			if (empty ( $checkSignRet ['isvalid'] )) {
				foreach ( $data as $key => $value ) {
					$strDatas .= "{$key}={$value} || ";
				}
				throw new Exception ( "sign签名校验无效:{$checkSignRet['sign']} 传入参数为{$strDatas}", - 1 );
			}
			
			// 参与签名的字段为：appid、appkey、productid、timestamp、noncestr、openid、issubscribe
			// 获取paySign
			$paySignPara = array (
					"AppId" => $postData ["AppId"],
					"OpenId" => $postData ["OpenId"],
					"IsSubscribe" => $postData ["IsSubscribe"],
					"ProductId" => $postData ["ProductId"],
					"TimeStamp" => $postData ["TimeStamp"],
					"NonceStr" => $postData ["NonceStr"] 
			);
			
			// $paySign = $this->weixinPay->getPaySign ( $paySignPara );
			// die($paySign);
			
			// 签名校验
			$checkRet = $this->checkAppSignature ( $paySignPara, $postData ['AppSignature'] );
			$calc_appSignature = $checkRet ['sign'];
			if (empty ( $checkRet ['isvalid'] )) {
				throw new Exception ( "AppSignature签名校验无效:{$checkRet['sign']}", - 1 );
			}
			
			// 检查订单
			$modelOrder = new Weixinshop_Model_Order ();
			$orderInfo = $modelOrder->getInfoByOutTradeNo ( $out_trade_no );
			if (empty ( $orderInfo )) {
				throw new Exception ( "out_trade_no无效:{$out_trade_no}", - 2 );
			}
			// 如果微信ID为空的话，就更新
			if (empty ( $orderInfo ['OpenId'] )) {
				$modelOrder->updateOpenId ( $out_trade_no, $postData ["OpenId"] );
			}
			// 是否支付成功
			$isOk = $modelOrder->isOK ( $data ['trade_state'], $data ['trade_mode'] );
			if ($isOk) {
				// 处理订单的状态
				$newOrderInfo = $modelOrder->changeStatus ( $orderInfo, $data, 'notify' );
				
				// 是否支付成功
				$isOk = $modelOrder->isOK ( $newOrderInfo ['trade_state'], $newOrderInfo ['trade_mode'] );
				
				// 为了更好地跟踪订单的情况，需要第三方在收到最终支付通知之后，
				// 调用发货通知API告知微信后台该订单的发货状态。
				// 请在收到支付通知发货后，一定调用发货通知接口，否则可能影响商户信誉和资金结算。
				// 调用发货通知接口
				if ($isOk) {
					// $delivernotityResult = $this->weixinPay->delivernotify(
					// $newOrderInfo['OpenId'], $newOrderInfo['transaction_id'],
					// $newOrderInfo['out_trade_no'], time(), 1, "已发货");
					$ret ['notify_result'] = "success";
				}
			}
		} catch ( Exception $e ) {
			$this->errorLog->log ( $e );
			$ret ['notify_result'] = "fail";
			$ret ['error'] = $e->getMessage ();
		}
		
		try {
			// 支付结果的保存
			$modelPayNotifyResult = new Weixinshop_Model_PayNotifyResult ();
			$modelPayNotifyResult->handle ( $sign_type, $service_version, $input_charset, $sign, $sign_key_index, $trade_mode, $trade_state, $pay_info, $partner, $bank_type, $bank_billno, $total_fee, $fee_type, $notify_id, $transaction_id, $out_trade_no, $attach, $time_end, $transport_fee, $product_fee, $discount, $buyer_alias, $postData, $postStr, $ret, $calc_sign, $calc_appSignature );
		} catch ( Exception $e ) {
			$this->errorLog->log ( $e );
			$ret ['notify_result'] = "fail";
			$ret ['error'] = $e->getMessage ();
		}
		
		/**
		 * 后台通知结果返回
		 * 微信后台通过notify_url 通知商户，商户做业务处理后，需要以字符串的形式反馈处理结果，内容如下：
		 * 返回结果 结果说明
		 * success 处理成功，微信系统收到此结果后不再进行后续通知
		 * fail 或其它字符处理不成功，微信收到此结果或者没有收到任何结果，系统通过补单机制再次通知
		 */
		exit ( $ret ['notify_result'] . $ret ['error'] );
	}
	
	/**
	 * 订单查询
	 * 因为某一方技术的原因，可能导致商家在预期时间内都收不到最终支付通知，
	 * 此时商家可以通过该API 来查询订单的详细支付状态。
	 */
	public function orderQueryAction() {
		$ret_code = 0;
		$ret_msg = "ok";
		try {
			$out_trade_no = trim ( $this->get ( 'out_trade_no', '' ) );
			if (strlen ( $out_trade_no ) < 1) {
				throw new Exception ( '商户订单号out_trade_no为空' );
			}
			
			$modelOrder = new Weixinshop_Model_Order ();
			$orderInfo = $modelOrder->getInfoByOutTradeNo ( $out_trade_no );
			if (empty ( $orderInfo )) {
				throw new Exception ( "订单号out_trade_no无效:{$out_trade_no}" );
			}
			
			// 是否支付成功
			$isOk = $modelOrder->isOK ( $orderInfo ['trade_state'], $orderInfo ['trade_mode'] );
			if (! $isOk) {
				// 调用订单查询 orderquery
				$ret = $this->weixinPay->orderquery ( $out_trade_no, time () );
				$order_info = $ret ['order_info'];
				$ret_code = $order_info ['ret_code'];
				$ret_msg = $order_info ['ret_msg'];
				
				// 订单状态的改变
				$newOrderInfo = $modelOrder->changeStatus ( $orderInfo, $order_info, 'orderquery' );
				
				// 是否支付成功
				$isOk = $modelOrder->isOK ( $newOrderInfo ['trade_state'], $newOrderInfo ['trade_mode'] );
			}
			
			$result = array ();
			$result ['isOk'] = empty ( $isOk ) ? 0 : 1;
			exit ( $this->result ( "处理结束", $result ) );
		} catch ( Exception $e ) {
			$this->errorLog->log ( $e );
			exit ( $this->error ( false, $e->getMessage () ) );
		}
	}
	
	/**
	 * 用户在新增投诉单后，
	 * 微信后台通知（post）商户服务器（payfeedback_url）支付结果。
	 * 商户可以使用payfeedback_url的通知结果进行个性化页面的展示。
	 * 注：payfeedback_url请提交至微信相关接口人，微信侧登记后即可用。
	 */
	public function payFeedbackAction() {
		/**
		 * 后台通知通过请求中的payfeedback_url进行，采用post机制。
		 * postData中将包含xml数据。数据如下：
		 *
		 *
		 * (1) 用户新增投诉的xml
		 * <xml>
		 * <OpenId><![CDATA[111222]]></OpenId>
		 * <AppId><![CDATA[wwwwb4f85f3a797777]]></AppId>
		 * <TimeStamp> 1369743511</TimeStamp>
		 * <MsgType><![CDATA[request]]></MsgType>
		 * <FeedBackId><![CDATA[5883726847655944563]]></FeedBackId>
		 * <TransId><![CDATA[10123312412321435345]]></TransId>
		 * <Reason><![CDATA[商品质量有问题]]></Reason>
		 * <Solution><![CDATA[补发货给我]]></Solution>
		 * <ExtInfo><![CDATA[明天六点前联系我18610847266]]></ExtInfo>
		 * <AppSignature><![CDATA[bafe07f060f22dcda0bfdb4b5ff756f973aecffa]]>
		 * </AppSignature>
		 * <SignMethod><![CDATA[sha1]]></ SignMethod >
		 * <PicInfo>
		 * <item><PicUrl><![CDATA[http://mmbiz.qpic.cn/mmbiz/49ogibiahRNtOk37iaztwmdgFbyFS9FUrqfodiaUAmxr4hOP34C6R4nGgebMalKuY3H35riaZ5vtzJh25tp7vBUwWxw/0]]></PicUrl></item>
		 * <item><PicUrl><![CDATA[http://mmbiz.qpic.cn/mmbiz/49ogibiahRNtOk37iaztwmdgFbyFS9FUrqfn3y72eHKRSAwVz1PyIcUSjBrDzXAibTiaAdrTGb4eBFbib9ibFaSeic3OIg/0]]></PicUrl></item>
		 * <item><PicUrl><![CDATA[]]></PicUrl></item>
		 * <item><PicUrl><![CDATA[]]></PicUrl></item>
		 * <item><PicUrl><![CDATA[]]></PicUrl></item>
		 * </PicInfo>
		 * </xml>
		 * (2) 用户确认处理完毕投诉的xml
		 * <xml>
		 * <OpenId><![CDATA[111222]]></OpenId>
		 * <AppId><![CDATA[wwwwb4f85f3a797777]]></AppId>
		 * <TimeStamp> 1369743511</TimeStamp>
		 * <MsgType><![CDATA[confirm/reject]]></MsgType>
		 * <FeedBackId><![CDATA[5883726847655944563]]></FeedBackId>
		 * <Reason><![CDATA[商品质量有问题]]></Reason>
		 * <AppSignature><![CDATA[bafe07f060f22dcda0bfdb4b5ff756f973aecffa]]>
		 * </AppSignature>
		 * <SignMethod><![CDATA[sha1]]></SignMethod>
		 * </xml>
		 *
		 * 各字段定义如下：
		 * 参数 必填 说明
		 * AppId是字段名称：公众号id；字段来源：商户注册具有支付权限的公众号成功后即可获得；传入方式：由商户直接传入。
		 * TimeStamp是字段名称：时间戳；字段来源：商户生成从1970年1月1日00：00：00至今的秒数，即当前的时间；由商户生成后传入。取值范围：32字符以下用户维权系统接口文档
		 * V1.5
		 * OpenId是支付该笔订单的用户ID，商户可通过公众号其他接口为付款用户服务。
		 * AppSignature是字段名称：签名；字段来源：对前面的其他字段不appKey按照字典序排序后，使用SHA1算法得到的结果。由商户生成后传入。
		 * MsgType是通知类型 request 用户提交投诉 confirm 用户确认消除投诉 reject 用户拒绝消除投诉
		 * FeedBackId是投诉单号
		 * TransId否交易订单号
		 * Reason否用户投诉原因
		 * Solution否用户希望解决方案
		 * ExtInfo否备注信息+电话
		 * PicUrl否用户上传的图片凭证，最多五张
		 * AppSignature依然是根据前文paySign所述的签名方式生成，参于签名的字段为：appid、appkey、timestamp、openid。
		 */
		try {
			// $postStr = "<xml>
			// <OpenId><![CDATA[111222]]></OpenId>
			// <AppId><![CDATA[wwwwb4f85f3a797777]]></AppId>
			// <TimeStamp> 1369743511</TimeStamp>
			// <MsgType><![CDATA[request]]></MsgType>
			// <FeedBackId><![CDATA[5883726847655944563]]></FeedBackId>
			// <TransId><![CDATA[10123312412321435345]]></TransId>
			// <Reason><![CDATA[商品质量有问题]]></Reason>
			// <Solution><![CDATA[补发货给我]]></Solution>
			// <ExtInfo><![CDATA[明天六点前联系我18610847266]]></ExtInfo>
			// <PicInfo>
			// <item><PicUrl><![CDATA[http://mmbiz.qpic.cn/mmbiz/49ogibiahRNtOk37iaztwmdgFbyFS9FUrqfodiaUAmxr4hOP34C6R4nGgebMalKuY3H35riaZ5vtzJh25tp7vBUwWxw/0]]></PicUrl></item>
			// <item><PicUrl><![CDATA[http://mmbiz.qpic.cn/mmbiz/49ogibiahRNtOk37iaztwmdgFbyFS9FUrqfn3y72eHKRSAwVz1PyIcUSjBrDzXAibTiaAdrTGb4eBFbib9ibFaSeic3OIg/0]]></PicUrl></item>
			// <item><PicUrl><![CDATA[]]></PicUrl></item>
			// <item><PicUrl><![CDATA[]]></PicUrl></item>
			// <item><PicUrl><![CDATA[]]></PicUrl></item>
			// </PicInfo>
			// <AppSignature><![CDATA[eca1995eb902a4f413339626723cf3e4fcb48346]]>
			// </AppSignature>
			// <SignMethod><![CDATA[sha1]]></SignMethod>
			// </xml>";
			$postStr = file_get_contents ( 'php://input' );
			$postData = simplexml_load_string ( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );
			$postData = object2Array ( $postData );
			$picInfoList = array ();
			foreach ( $postData ['PicInfo'] ['item'] as $picInfo ) {
				if (! empty ( $picInfo ['PicUrl'] )) {
					$picInfoList [] = $picInfo ['PicUrl'];
				}
			}
			$postData ['PicInfo'] = implode ( "\n", $picInfoList );
			
			// 参于签名的字段为：appid、appkey、timestamp、openid
			// 签名校验
			$paySignPara = array ();
			$paySignPara ['appid'] = $postData ['AppId'];
			$paySignPara ['openid'] = $postData ['OpenId'];
			$paySignPara ['timestamp'] = $postData ['TimeStamp'];
			// $paySign = $this->weixinPay->getPaySign ( $paySignPara );
			// die($paySign);
			$checkRet = $this->checkAppSignature ( $paySignPara, $postData ['AppSignature'] );
			$calc_appSignature = $checkRet ['sign'];
			if (empty ( $checkRet ['isvalid'] )) {
				throw new Exception ( "AppSignature签名校验无效:{$checkRet['sign']}", - 1 );
			}
			
			// 处理投诉
			$modelPayFeedback = new Weixinshop_Model_PayFeedback ();
			$feedbackInfo = $modelPayFeedback->handle ( $postData ['AppId'], $postData ['TimeStamp'], $postData ['OpenId'], $postData ['MsgType'], $postData ['FeedBackId'], $postData ['TransId'], $postData ['Reason'], $postData ['Solution'], $postData ['ExtInfo'], $postData ['PicInfo'], $postData ['AppSignature'], $postData ['SignMethod'], $postStr, $calc_appSignature );
			exit ( $this->result ( "处理结束" ) );
		} catch ( Exception $e ) {
			$this->errorLog->log ( $e );
			exit ( $this->error ( false, $e->getMessage () ) );
		}
	}
	
	/**
	 * 获取收货人的信息
	 */
	public function getConsigneeInfoAction() {
		try {
			$OpenId = $this->getRequest ()->getCookie ( "FromUserName", '' );
			$modelConsignee = new Weixinshop_Model_Consignee ();
			$consigneeInfo = $modelConsignee->getLastInfoByOpenid ( $OpenId );
			exit ( $this->result ( "处理结束", $consigneeInfo ) );
		} catch ( Exception $e ) {
			$this->errorLog->log ( $e );
			exit ( $this->error ( false, $e->getMessage () ) );
		}
	}
	
	/**
	 * 签名校验
	 *
	 * @return boolean
	 */
	private function checkAppSignature($paySignData, $signature) {
		// 第三方为了确保是来自于微信公众平台的合法请求，
		// 需要使用同样的方式生成签名，并与AppSignature 的值进行对比。
		// 获取paySign
		$paySign = $this->weixinPay->getPaySign ( $paySignData );
		// print_r($paySignData);
		// die('signature:'.$signature.'||||paySign:'.$paySign);
		return trim ( $paySign ) == trim ( $signature ) ? array (
				'isvalid' => true,
				'sign' => $paySign 
		) : array (
				'isvalid' => false,
				'sign' => $paySign 
		);
	}
	
	/**
	 * 签名校验
	 *
	 * @return boolean
	 */
	private function checkSign($data) {
		// 第三方为了确保是来自于微信公众平台的合法请求，
		// 需要使用同样的方式生成签名，并与AppSignature 的值进行对比。
		$signature = $data ['sign'];
		unset ( $data ['sign'] );
		$sign = $this->weixinPay->getSign ( $data );
		return trim ( $sign ) == trim ( $signature ) ? array (
				'isvalid' => true,
				'sign' => $sign 
		) : array (
				'isvalid' => false,
				'sign' => $sign 
		);
	}
}

