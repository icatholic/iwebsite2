<?php
class Integral_Model_IntegralInfo extends iWebsite_Plugin_Mongo
{
    protected $name = 'integral_info';
    protected $dbName = 'integral';
    
    //处理
    public function handle($integralIdentity,array $ruleInfo)
    {
    	$get_by = $ruleInfo['name'];
    	$integral = $ruleInfo['integral'];
    	
    	$info = $this->getInfoByIdentity($integralIdentity);
    	if(!empty($info))
    	{
    		$setData =array();
    		$setData["max_integral"] = max($info['max_integral'],$integral);
    		$options = array(
    				"query"=>array("_id"=>$info['_id']),
    				"update"=>array(
    						'$set'=>$setData,
    						'$inc'=>array('accumulate_integral'=>$integral)),
    				"new"=>true
    		);
    		$return_result = $this->findAndModify($options);
    		$userData = $return_result["value"];
    	}else{
    		$userData=array();
    		$userData['integral_identity_id']=$integralIdentity['_id'];
    		$userData['max_integral']=$integral;
    		$userData['accumulate_integral']=$integral;
    		$userData=$this->insert($userData);
    	}
    	//记录积分明细追踪表
    	if(!empty($integral)){
    		$modelIntegralDetailTrack = new Integral_Model_IntegralDetailTrack();
    		$modelIntegralDetailTrack->handle($integralIdentity, $get_by, $integral);
    	}
    	return $userData;
    }
    
    //根据积分凭证ID
    public function getInfoByIdentity($integralIdentity)
    {
    	$query=array();
    	$query['integral_identity_id']=$integralIdentity['_id'];    	
    	$myInfo = $this->findOne($query);
    	return $myInfo;
    }

}