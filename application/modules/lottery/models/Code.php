<?php

class Lottery_Model_Code extends iWebsite_Plugin_Mongo
{

    protected $name = 'iLottery_code';

    protected $dbName = 'lottery';

    public function getCode($activity_id, $prize_id)
    {
        $now = new MongoDate();
        $query = array(
//             'activity_id' => $activity_id,
            'prize_id' => $prize_id,
            'is_used' => array(
                '$ne' => true
            ),
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
                $options = array();
                $options['query'] = array(
                    '_id' => $row['_id'],
//                     'activity_id' => $activity_id,
                    'prize_id' => $prize_id,
                    'is_used' => array(
                        '$ne' => true
                    ),
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
                if (! empty($rst['value']))
                    return $rst['value'];
                else
                    continue;
            }
        }
        return false;
    }
}