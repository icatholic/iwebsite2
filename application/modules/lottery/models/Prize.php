<?php

class Lottery_Model_Prize extends iWebsite_Plugin_Mongo
{

    protected $name = 'iLottery_prize';

    protected $dbName = 'lottery';

    public function getPrizeInfo($prize_id)
    {
        return $this->findOne(array(
            '_id' => myMongoId($prize_id)
        ));
    }
}