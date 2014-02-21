<?php
class Integral_Model_IntegralRule extends iWebsite_Plugin_Mongo
{
    protected $name = 'integral_rule';
    protected $dbName = 'integral'; 

    public function getRuleInfo($ruleName)
    {
    	$ruleInfo = $this->findOne(array('name'=>$ruleName));
    	if(empty($ruleInfo)){
    		throw new Exception('rule is not set');
    	}
    	return $ruleInfo;
    }
    
    public function getCustomizeRuleInfo($ruleName,$integral=0)
    {    	
    	return array('name'=>$ruleName,'integral'=>$integral);
    }
}