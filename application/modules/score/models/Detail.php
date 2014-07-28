<?php
class Score_Model_Detail  extends iWebsite_Plugin_Mongo
{
    protected $name = 'iScore_detail';
    protected $dbName = 'score';
    
    public function addOne($strUserId,$nScore,$strReason)
    {
    	$arrayData = array();
    	$arrayData['user_id'] = $strUserId;
    	$arrayData['score'] = $nScore;
    	$arrayData['reason'] = $strReason;
    	$this->insertRef($arrayData);
    }
}