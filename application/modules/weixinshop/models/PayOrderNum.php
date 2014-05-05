<?php
class Weixinshop_Model_PayOrderNum extends iWebsite_Plugin_Mongo
{
	protected $name = 'iWeixinpay_PayOrderNum';
	protected $dbName = 'weixinshop';
	
	public function getRecordNum()
	{
    	$options = array(
    			"query"=>array("_id"=>"533531cb4a9619d6038b4578"),
    			"update"=>array(
    					'$inc'=>array('record_num'=>1)),
    			"new"=>true
    	);
    	$return_result = $this->findAndModify($options);
    	if(!empty($return_result["value"])){
    		return $return_result["value"]['record_num'];
    	}else{
    		throw new Exception('获取计数发生错误');
    	}
    	
	}
	
}