<?php
class Lottery_Model_LotteryResult extends iWebsite_Plugin_Mongo
{
    protected $name = 'lottery_result';
    protected $dbName = 'lottery_sample';
    
    public function getLotteryResultMsg($lottery_result)
    {
    	$ruleResult =$this->findOne(array("lottery_result_value"=>$lottery_result));
    	return $ruleResult['lottery_result_msg'];
    }
}