<?php
class Weixinshop_Model_PayNotifyResult extends iWebsite_Plugin_Mongo
{
	protected $name = 'iWeixinpay_PayNotifyResult';
	protected $dbName = 'weixinshop';
	/*
	 * 默认排序
	*/
	public function getDefaultSort()
	{
		$sort = array('_id'=>-1);
		return $sort;
	}
	/*
	 * 默认查询条件
	*/
	public function getQuery()
	{
		$query = array();
		return $query;
	}
	
	/**
	 * 根据ID获取信息
	 */
	public function getInfoById($id)
	{
		$query = array('_id'=>myMongoId($id));
		$info = $this->findOne($query);
		return $info;
	}
	
	/**
	 * 根据商户订单号获取信息
	 */
	public function getInfoByOutTradeNo($out_trade_no)
	{
		$query = array('out_trade_no'=>$out_trade_no);
		$info = $this->findOne($query);
		return $info;
	}
	
	
	/**
	 * 生成或更新支付通知
	 * @param string $sign_type
	 * @param string $service_version
	 * @param string $input_charset
	 * @param string $sign
	 * @param int $sign_key_index
	 * @param int $trade_mode
	 * @param int $trade_state
	 * @param string $pay_info
	 * @param string $partner
	 * @param string $bank_type
	 * @param string $bank_billno
	 * @param int $total_fee
	 * @param int $fee_type
	 * @param string $notify_id
	 * @param string $transaction_id
	 * @param string $out_trade_no
	 * @param string $attach
	 * @param string $time_end
	 * @param int $transport_fee
	 * @param int $product_fee
	 * @param int $discount
	 * @param string $buyer_alias
	 * @param array $PostData
	 * @param array $notify_result
	 */
	public function handle($sign_type,$service_version,$input_charset,$sign,$sign_key_index,
			$trade_mode,$trade_state,$pay_info,$partner,$bank_type,$bank_billno,
			$total_fee,$fee_type,$notify_id,$transaction_id,$out_trade_no,$attach,$time_end,
			$transport_fee,$product_fee,$discount,$buyer_alias,array $PostData,$postStr,
			array $notify_result,$calc_sign="",$calc_appSignature="")
	{		
		$data =array();
		//协议参数
		//签名方式sign_type否String(8)签名类型，取值：MD5、RSA，默认：MD5
		$data['sign_type'] = $sign_type;
		//接口版本service_version否String(8)版本号，默认为1.0
		$data['service_version'] = $service_version;
		//字符集input_charset否String(8)字符编码,取值：GBK、UTF-8，默认：GBK。
		$data['input_charset'] = $input_charset;
		//签名sign是String(32)签名
		$data['sign'] = $sign;
		//密钥序号sign_key_index否Int多密钥支持的密钥序号，默认1
		$data['sign_key_index'] = $sign_key_index;
		
		//业务参数
		//交易模式trade_mode是Int1-即时到账其他保留
		$data['trade_mode'] = $trade_mode;
		//交易状态trade_state是Int支付结果：0—成功其他保留
		$data['trade_state'] = $trade_state;
		//支付结果信息pay_info否String(64)支付结果信息，支付成功时为空
		$data['pay_info'] = $pay_info;
		//商户号partner是String(10)商户号， 也即之前步骤的partnerid, 由微信统一分配的10 位正整数(120XXXXXXX)号
		$data['partner'] = $partner;
		//付款银行bank_type是String(16)银行类型，在微信中使用WX
		$data['bank_type'] = $bank_type;
		//银行订单号bank_billno否String(32)银行订单号
		$data['bank_billno'] = $bank_billno;
		//总金额total_fee是Int支付金额，单位为分，如果discount 有值，通知的total_fee+ discount = 请求的total_fee
		$data['total_fee'] = $total_fee;
		//币种fee_type是Int现金支付币种,目前只支持人民币,默认值是1-人民币
		$data['fee_type'] = $fee_type;
		//通知ID notify_id是String(128)支付结果通知id，对于某些特定商户，只返回通知id，要求商户据此查询交易结果
		$data['notify_id'] = $notify_id;
		//订单号transaction_id是String(28)交易号，28 位长的数值，其中前10 位为商户号，之后8 位为订单产生的日期， 如20090415，最后10 位是流水号。
		$data['transaction_id'] = $transaction_id;
		//商户订单号out_trade_no是String(32)商户系统的订单号，与请求一致。
		$data['out_trade_no'] = $out_trade_no;
		//商家数据包attach否String(127)商家数据包，原样返回
		$data['attach'] = $attach;
		//支付完成时间time_end是String(14)支付完成时间， 格式为yyyyMMddhhmmss ， 如2009年12 月27 日9 点10 分10 秒表示为20091227091010。时区为GMT+8 beijing。
		$data['time_end'] = $time_end;
		//物流费用transport_fee否Int物流费用，单位分，默认0。如果有值， 必须保证transport_fee + product_fee =total_fee
		$data['transport_fee'] = $transport_fee;
		//物品费用product_fee否Int物品费用，单位分。如果有值，必须保证transport_fee +product_fee=total_fee折扣价格
		$data['product_fee'] = $product_fee;
		//discount否Int折扣价格，单位分，如果有值，通知的total_fee + discount =请求的total_fee
		$data['discount'] = $discount;
		//买家别名buyer_alias否String(64)对应买家账号的一个加密串
		$data['buyer_alias'] = $buyer_alias;
		//微信号
		$data['OpenId'] = $PostData['OpenId'];
		//标记用户是否订阅该公众帐号，1 为关注，0 为未关注
		$data['IsSubscribe'] = $PostData['IsSubscribe'];
		//时间戳
		$data['TimeStamp'] = $PostData['TimeStamp'];
		//随机串
		$data['NonceStr'] = $PostData['NonceStr'];
		//签名方式
		$data['SignMethod'] = $PostData['SignMethod'];
		//参数的加密签名
		$data['AppSignature'] = $PostData['AppSignature'];
		//推送xml 格式的postStr
		$data['PostData'] = $postStr;
		//通知结果		
		$data['notify_result'] = $notify_result['notify_result'];
		//通知结果错误说明
		$data['error'] = $notify_result['error'];
		//通知时间		
		$data['notify_time'] = date('Y-m-d H:i:s');
		
		//计算所得签名
		$data['calc_sign'] = $calc_sign;
		$data['calc_appSignature'] = $calc_appSignature;
		
		//判断数据是否存在
		$info = $this->getInfoByOutTradeNo($data['out_trade_no']);
		if(empty($info)){
			//通知次数
			$data['notify_times'] = 1;
			$result = $this->insert($data);
		}else{
			$options = array(
					"query"=>array("_id"=>$info['_id'],'notify_result'=>'fail'),
					"update"=>array(
							'$set'=>$data,
							'$inc'=>array('notify_times'=>1)),
					"new"=>true
			);
			$return_result = $this->findAndModify($options);
			$result = $return_result["value"];
		}
		
		return $result;
	}
}