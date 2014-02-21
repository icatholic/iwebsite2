<?php
class Lottery_Model_Prize extends iWebsite_Plugin_Mongo
{
    protected $name = 'prize';
    protected $dbName = 'lottery_sample';
    
    //获取大奖的奖品code列表
    public function getBigPrizes()
    {
    	$query=array('is_big_prize'=>1);//是否是大奖
    	$list = $this->distinct('prize_code', $query);
    	return $list;
    } 
    
    public function getPrizeList()
    {
    	$prize_list = array();
    	$prizes = $this->find(array());
    	foreach($prizes['datas'] as $row) {
    		$prize_list[$row['prize_code']] = $row['prize_name'];
    	}
    	return $prize_list;
    }
    
    //获取实物奖的奖品code列表
    public function getRealPrizes()
    {
    	$query=array('is_real'=>1);//是否是实物
    	$list = $this->distinct('prize_code', $query);
    	if(empty($list)) $list= array();
    	return $list;
    }
}