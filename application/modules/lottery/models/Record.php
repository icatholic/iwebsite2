<?php

class Lottery_Model_Record extends iWebsite_Plugin_Mongo
{

    protected $name = 'iLottery_record';

    protected $dbName = 'lottery';

    public function record($activity_id, $identity_id, $result_id, $source)
    {
        return $this->insert(array(
            'activity_id' => $activity_id,
            'identity_id' => $identity_id,
            'result_id' => $result_id,
            'source' => $source
        ));
    }

    public function getTotal($activity_id, $identity_id, $success = false)
    {
        if ($success == true) {
            return $this->count(array(
                'activity_id' => $activity_id,
                'identity_id' => $identity_id,
                'result_id' => 1
            ));
        } else {
            return $this->count(array(
                'activity_id' => $activity_id,
                'identity_id' => $identity_id
            ));
        }
    }
}