<?php
class Lottery_Model_PrizeSource extends iWebsite_Plugin_Mongo
{
    protected $name = 'prize_source';
    protected $dbName = 'lottery_sample';
    
    public function getLottery()
    {
    	return 1;
    }
    
    public function getSeckill()
    {
    	return 2;
    }
    
    public function getExchange()
    {
    	return 3;
    }
}