<?php
class Weixinshop_Model_PayFeedback extends iWebsite_Plugin_Mongo
{
	protected $name = 'iWeixinpay_PayFeedback';
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
	public function getInfoByFeedBackId($FeedBackId)
	{
		$query = array('FeedBackId'=>$FeedBackId);
		$info = $this->findOne($query);
		return $info;
	}
		
	/**
	 * 处理投诉
	 * @param string $AppId
	 * @param int $TimeStamp
	 * @param string $OpenId
	 * @param string $MsgType
	 * @param string $FeedBackId
	 * @param string $TransId
	 * @param string $Reason
	 * @param string $Solution
	 * @param string $ExtInfo
	 * @param string $AppSignature
	 * @param string $SignMethod
	 * @param string $postStr
	 * @return unknown
	 */
	public function handle($AppId,$TimeStamp,$OpenId,$MsgType,$FeedBackId,
			$TransId,$Reason,$Solution,$ExtInfo,$PicInfo,$AppSignature,$SignMethod,$postStr,$calc_appSignature="")
	{		
		$data =array();		
		//AppId是字段名称：公众号id；字段来源：商户注册具有支付权限的公众号成功后即可获得；传入方式：由商户直接传入。
		$data['AppId'] = $AppId;
		//TimeStamp是字段名称：时间戳；字段来源：商户生成从1970年1月1日00：00：00至今的秒数，即当前的时间；由商户生成后传入。取值范围：32字符以下用户维权系统接口文档 V1.5
		$data['TimeStamp'] = $TimeStamp;
		//OpenId是支付该笔订单的用户ID，商户可通过公众号其他接口为付款用户服务。
		$data['OpenId'] = $OpenId;
		//AppSignature是字段名称：签名；字段来源：对前面的其他字段不appKey按照字典序排序后，使用SHA1算法得到的结果。由商户生成后传入。
		$data['AppId'] = $AppId;
		//MsgType是通知类型 request 用户提交投诉 confirm 用户确认消除投诉 reject 用户拒绝消除投诉
		$data['MsgType'] = $MsgType;
		//FeedBackId是投诉单号
		$data['FeedBackId'] = $FeedBackId;
		//TransId否交易订单号
		$data['TransId'] = $TransId;
		//Reason否用户投诉原因
		$data['Reason'] = $Reason;
		//Solution否用户希望解决方案
		$data['Solution'] = $Solution;
		//ExtInfo否备注信息+电话
		$data['ExtInfo'] = $ExtInfo;
		//PicUrl否用户上传的图片凭证，最多五张
		$data['PicInfo'] = $PicInfo;
		//AppSignature依然是根据前文paySign所述的签名方式生成，参不签名的字段为：appid、appkey、timestamp、openid。
		$data['AppSignature'] = $AppSignature;
		//签名方式
		$data['SignMethod'] = $SignMethod;
		$data['PostData'] = $postStr;
		//投诉时间
		$data['feedback_time'] = date('Y-m-d H:i:s');
		//计算所得签名
		$data['calc_appSignature'] = $calc_appSignature;
		
		//判断数据是否存在
		$info = $this->getInfoByFeedBackId($data['FeedBackId']);
		if(empty($info)){
			//投诉次数
			$data['feedback_times'] = 1;
			$result = $this->insert($data);
		}else{
			$options = array(
					"query"=>array("_id"=>$info['_id']),
					"update"=>array(
							'$set'=>$data,
							'$inc'=>array('feedback_times'=>1)),
					"new"=>true
			);
			$return_result = $this->findAndModify($options);
			$result = $return_result["value"];
		}
		
		return $result;
	}
}