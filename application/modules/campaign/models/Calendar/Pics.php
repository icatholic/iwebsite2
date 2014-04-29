<?php

class Campaign_Model_Calendar_Pics extends iWebsite_Plugin_Mongo
{

    protected $name = 'calendar_pics';

    protected $dbName = 'weixin';

    /**
     * 记录用户上传的蜜约图片
     *
     * @param string $openid            
     * @param string $picture            
     */
    public function record($openid, $picture)
    {
        return $this->insert(array(
            'openid' => $openid,
            'picture' => $picture
        ));
    }

    /**
     * 获取今日的最后一张用户上传照片
     * 
     * @param string $openid            
     * @return array
     */
    public function getTodayPics($openid)
    {
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $todayStart = new MongoDate(mktime(0, 0, 0, $month, $day, $year));
        $todayEnd = new MongoDate(mktime(23, 59, 59, $month, $day, $year));
        $picture = $this->find(array(
            'openid' => $openid,
            '__CREATE_TIME__' => array(
                '$gte' => $todayStart,
                '$lte' => $todayEnd
            )
        ), array(
            '_id' => - 1
        ), 0, 1);
        return $picture['datas'];
    }

    /**
     * 获取用户某一天的图片
     *
     * @param string $openid            
     * @param int $year            
     * @param int $month            
     * @param int $day            
     * @return array
     */
    public function getSomeDayPics($openid, $year, $month, $day)
    {
        $somedayStart = new MongoDate(mktime(0, 0, 0, $month, $day, $year));
        $somedayEnd = new MongoDate(mktime(23, 59, 59, $month, $day, $year));
        $picture = $this->find(array(
            'openid' => $openid,
            '__CREATE_TIME__' => array(
                '$gte' => $somedayStart,
                '$lte' => $somedayEnd
            )
        ), array(
            '_id' => - 1
        ), 0, 1);
        return $picture['datas'];
    }
}