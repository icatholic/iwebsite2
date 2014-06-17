<?php
class Question_Model_Rand  extends iWebsite_Plugin_Mongo
{
    protected $name = 'iQuestionnaire_rand';
    protected $dbName = 'question';
    
    
    //获取已经生产的随机题目
    public function get($naireCode,$user_id,$bFinish = false)
    {
    	$result = $this->findOne(array('naire_id'=>$naireCode,'user_id'=>$user_id,'is_finish'=>$bFinish));
    	if($result)
    	{
    		return $result;
    	}
    	else
    		return false;
    }
    
    //插入随机题目
    public function add($naireCode,$user_id,$arrayQuestion)
    {
    	$arrayData = array();
    	$arrayData['naire_id'] = $naireCode;
    	$arrayData['user_id'] = $user_id;
    	$arrayData['question'] = json_encode($arrayQuestion);
    	$arrayData['is_finish'] = false;
    	$arrayData = $this->insertRef($arrayData);
    	return $arrayData['_id']->__toString();
    }
}