<?php
class Integral_Model_RefereeInfo extends iWebsite_Plugin_Mongo
{
    protected $name = 'referee_info';
    protected $dbName = 'integral';
    
    //处理
    private function handleReferee($integralIdentity,$fromMobile,$ruleInfo)
    {
    	$info = $this->getInfoByIdentity($integralIdentity);
    	if(!empty($info))
    	{
    		$setData =array();
    		$options = array(
    				"query"=>array("_id"=>$info['_id']),
    				"update"=>array(
    						'$inc'=>array('referee_num'=>1)),//增加推荐次数
    				"new"=>true
    		);
    		$return_result = $this->findAndModify($options);
    		$userData = $return_result["value"];
    	}else{
    		$userData=array();
    		$userData['integral_identity_id']=$integralIdentity['_id'];
    		$userData['referee_num']=1;
    		$userData=$this->insert($userData);
    	}
    	//记录推荐明细追踪表
    	$modelRefereeDetailTrack = new Integral_Model_RefereeDetailTrack();
    	$modelRefereeDetailTrack->handle($integralIdentity,$fromMobile,$ruleInfo);
    	
    	return $userData;
    }
    
    //处理推荐
    public function handle($integralIdentity,$fromMobile)
    {
    	//获取当月推荐次数
    	$modelRefereeDetailTrack = new Integral_Model_RefereeDetailTrack();
    	$monthlyNum = $modelRefereeDetailTrack->getMonthlyNum($integralIdentity);
    	//判断如果当月次数
    	if($monthlyNum<5)//未满
    	{	    	
	    	//获取积分数量
	    	$modelIntegralRule = new Integral_Model_IntegralRule();
	    	$ruleInfo = $modelIntegralRule->getRuleInfo("邀请好友");
	    	
	    	//增加推荐人的推荐（邀请好友）积分
	    	$modelIntegralInfo = new Integral_Model_IntegralInfo();
	    	$modelIntegralInfo->handle($integralIdentity, $ruleInfo);
    	}else{
    		//获取积分数量
    		$modelIntegralRule = new Integral_Model_IntegralRule();
    		$ruleInfo = $modelIntegralRule->getCustomizeRuleInfo("邀请好友");
    	}
    	
    	//处理推荐
    	$this->handleReferee($integralIdentity,$fromMobile,$ruleInfo);
    	
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