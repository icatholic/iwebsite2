<?php

class Weixinsign_Model_Details extends iWebsite_Plugin_Mongo
{

    protected $name = 'iSign_details';

    protected $dbName = 'weixinsign';
    
    /*
     * 记录明细
     */
    public function log($OpenId, $sign_time)
    {
        $data = array();
        $data['OpenId'] = $OpenId;
        $data['ip'] = getIp();
        $data['sign_time'] = $sign_time;
        $info = $this->insert($data);
        return $info;
    }
}