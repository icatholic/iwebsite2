<?php

class Lottery_Model_Code extends iWebsite_Plugin_Mongo
{

    protected $name = 'iLottery_code';

    protected $dbName = 'lottery';

    public function getCode($activity_id, $prize_id)
    {
        $query = array(
            'activity_id' => $activity_id,
            'prize_id' => $prize_id,
            'is_used' => false,
            'start_time' => array(
                '$lt' => $now
            ),
            'end_time' => array(
                '$gt' => $now
            )
        );
        $codes = $this->find($query);
        
        if (! empty($codes['datas'])) {
            foreach ($codes['datas'] as $row) {
                $now = new MongoDate();
                $options = array();
                $options['query'] = array(
                    '_id' => $row['_id'],
                    'activity_id' => $activity_id,
                    'prize_id' => $prize_id,
                    'is_used' => false,
                    'start_time' => array(
                        '$lt' => $now
                    ),
                    'end_time' => array(
                        '$gt' => $now
                    )
                );
                $options['update'] = array(
                    '$set' => array(
                        'is_used' => true
                    )
                );
                $rst = $this->findAndModify($options);
                if(!empty($rst['result']))
                    return $rst['result'];
                else 
                    continue;
            }
        }
        return false;
    }
}