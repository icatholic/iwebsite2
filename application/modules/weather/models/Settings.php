<?php

class Weather_Model_Settings extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeather_settings';

    protected $dbName = 'weather';

    public function getSettings()
    {
        $now = date('H:i:s');
        $query = array(
            'start_time' => array(
                '$lte' => $now
            ),
            'end_time' => array(
                '$gte' => $now
            )
        );
        $ret = $this->findAll($query);
        $list = array();
        foreach ($ret as $setting) {
            array_unset_recursive($setting, array(
                "_id",
                "__CREATE_TIME__",
                "__MODIFY_TIME__",
                "__REMOVED__"
            ), true);
            $list[$setting['scene']] = $setting;
        }
        return $list;
    }
}