<?php
class Weixinshop_Model_PayNativePackageResult extends iWebsite_Plugin_Mongo
{
	protected $name = 'iWeixinPay_PayNativePackageResult';
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
	 * 记录
	 */
	public function log($AppId,$OpenId,$IsSubscribe,$ProductId,$TimeStamp,$NonceStr,$AppSignature,$SignMethod,$PostData,
			$RetCode = 0,$RetErrMsg = "ok",$out_trade_no="",$Package="",$result="", $calc_appSignature="")
	{		
		$data =array();
		//公众帐号的appid
		$data['AppId'] = $AppId;
		//商品号
		$data['ProductId'] = $ProductId;
		//微信号
		$data['OpenId'] = $OpenId;
		//标记用户是否订阅该公众帐号，1 为关注，0 为未关注
		$data['IsSubscribe'] = $IsSubscribe;
		//时间戳
		$data['TimeStamp'] = $TimeStamp;
		//随机串
		$data['NonceStr'] = $NonceStr;
		//签名方式
		$data['SignMethod'] = $SignMethod;
		//参数的加密签名
		$data['AppSignature'] = $AppSignature;
		//推送xml 格式的PostData
		$data['PostData'] = $PostData;
		//RetCode
		$data['RetCode'] = $RetCode;
		//RetErrMsg
		$data['RetErrMsg'] = $RetErrMsg;
		
		//商户系统内部的订单号
		$data['out_trade_no'] = $out_trade_no;
		//Package
		$data['Package'] = $Package;
		//返回的xml 格式的数据
		$data['result'] = $result;
		
		//计算所得签名
		$data['calc_appSignature'] = $calc_appSignature;
		
		return $this->insert($data);
	}
}