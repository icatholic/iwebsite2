<?php

class Campaign_Model_User_Info extends iWebsite_Plugin_Mongo
{

    protected $name = 'user';

    protected $dbName = 'default';

    public function getUserInfo($openid)
    {
        return $this->findOne(array(
            'openid' => $openid
        ));
    }

    /**
     * 记录或者而更新用户信息
     *
     * @param string $openid            
     * @param string $birthday            
     * @param int $menstruation            
     * @param int $period            
     * @param string $lastTime            
     */
    public function record($openid, $birthday, $menstruation, $period, $lastTime)
    {
        return $this->update(array(
            'openid' => $openid
        ), array(
            '$set' => array(
                'openid' => $openid,
                'birthday' => new MongoDate(strtotime($birthday)),
                'menstruation' => (int) $menstruation,
                'period' => (int) $period,
                'last_time' => new MongoDate(strtotime($lastTime))
            )
        ), array(
            'upsert' => true
        ));
    }
}