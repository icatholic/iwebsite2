<?php
class Question_Model_Option  extends iWebsite_Plugin_Mongo
{
    protected $name = 'iQuestionnaire_options';
    protected $dbName = 'question';
    
    public function addTimes($key,$questionId)
    {
    	$arrayOption = $this->findOne(array('key'=>$key,'question_id'=>$questionId));
    	
    	$options = array();
    	$options['query'] = array(
    			'_id' => $arrayOption['_id']
    	);
    	$options['update'] = array('$set'=>array('key'=>$key,'question_id'=>$questionId),'$inc'=>array('used_times'=>1));
    	$options['upsert'] = true;
    	$this->findAndModify($options);
    }
    
}