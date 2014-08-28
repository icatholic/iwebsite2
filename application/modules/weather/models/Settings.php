<?php

class Weather_Model_Settings extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeather_settings';

    protected $dbName = 'weather';
    
    /*
     * 记录明细
     */
    public function log($weather_code, $weather)
    {
        $data = array();
        $data['weather_code'] = $weather_code;
        $data['weather'] = $weather;
        $info = $this->insert($data);
        return $info;
    }
    
    public function getSettings()
    {
        $ret = $this->findAll(array());
        $list = array();
        foreach ($ret as $setting) {
            array_unset_recursive($setting, array("_id","__CREATE_TIME__","__MODIFY_TIME__","__REMOVED__"),true);            
        	$list[$setting['weather']] = $setting;
        }
        return $list;
    }
    
}