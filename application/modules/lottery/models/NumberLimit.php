<?php
class Lottery_Model_NumberLimit extends iWebsite_Plugin_Mongo
{
    protected $name = 'number_limit';
    protected $dbName = 'lottery_sample';
    
    public function getLimit($condition)
    {
    	$info = $this->findOne(array('condition'=>$condition));
    	if(!empty($info)){
    		return $info['number_limit'];
    	}else{
    		return 0; //无限制
    	}
    }
}