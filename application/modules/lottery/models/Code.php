<?php
class Lottery_Model_Code extends iWebsite_Plugin_Mongo
{
    protected $name = 'code';
    protected $dbName = 'lottery_sample';
    
    public function handlePrize($prize_name,$prize_source=1) 
    {
    	$now = date("Y-m-d H:i:s");
    	$query = array('start_time'=>array('$lt'=>$now),
    							'end_time'=>array('$gt'=>$now),
    							'prize_name'=>$prize_name,
    							'is_used'=>0);
    	
    	$rst = $this->find($query,array('prize_name'=>1),0,1000);
    	if($rst['total']>0) {
    		foreach ($rst['datas'] as $row) {
    			$options  = array();
    			$options['query']  = array('_id'=>$row['_id'],'prize_name'=>$prize_name,'is_used'=>0);
    			$options['update'] = array('$set'=>array('is_used'=>1,'prize_source'=>$prize_source));
    			$modify = $this->findAndModify($options);
    			if($modify['value']!=null) {
    				return $row;
    			}
    		}
    	}
    	return null;
    }
    
    //用于测试数据
    public function initCode()
    {
    	$data = array(
    			'start_time'=>'2013-07-01 00:00:00',
    			'end_time'=>'2014-07-31 23:59:59',
    			'is_used'=>0);
    	$this->update(array(), array('$set'=>$data));
    }
}