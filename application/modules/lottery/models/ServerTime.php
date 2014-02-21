<?php
class Lottery_Model_ServerTime extends iWebsite_Plugin_Mongo
{
    protected $name = 'server_time';
    protected $dbName = 'lottery_sample';
    
    public function getTime()
    {
    	$currentTime=date("Y-m-d H:i:s");
    	//return $currentTime;    	
    	$query=array();
    	$query['nowtime'] = array('$gt'=>$currentTime);
    	$info = $this->findOne($query);
    	if($info){
    		return $info['nowtime'];
    	}else{
    		$data = array();
    		$data['nowtime'] = $currentTime;
    		$this->update(array(), array('$set'=>$data));
    		return $currentTime;
    	}
    }
}