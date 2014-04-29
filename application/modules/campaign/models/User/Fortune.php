<?php

class Campaign_Model_User_Fortune extends iWebsite_Plugin_Mongo
{

    protected $name = 'user_fortune';

    protected $dbName = 'default';

    public function getFortune($openid)
    {
        $startToday = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        $endToday = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
        $check = $this->findOne(array(
            'openid' => $openid,
            '__CREATE_TIME__' => array(
                '$gte' => new MongoDate($startToday),
                '$lte' => new MongoDate($endToday)
            )
        ));
        
        if ($check == null) {
            //随机生成数据
            $modelFortune = new Campaign_Model_Fortune();
            $data = array();
            $data['openid'] = $openid;
            $data['color'] = $modelFortune->getRandomContentByType(1);
            $data['constellations'] = $modelFortune->getRandomContentByType(2);
            $data['gift'] = $modelFortune->getRandomContentByType(3);
            $data['today'] = $modelFortune->getRandomContentByType(4);
            $data['mood'] = $modelFortune->getRandomContentByType(5);
            $data['mood_number'] = rand(2,4);
            $data['charm'] = $modelFortune->getRandomContentByType(6);
            $data['charm_number'] = rand(2,4);
            $data['slimming'] = $modelFortune->getRandomContentByType(7);
            $data['slimming_number'] = rand(2,4);
            $this->insert($data);
            return $data;
        } else {
            return $check;
        }
    }
}