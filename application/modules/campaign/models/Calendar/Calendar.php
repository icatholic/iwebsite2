<?php

class Campaign_Model_Calendar_Calendar extends iWebsite_Plugin_Mongo
{

    protected $name = 'user_menstruation_record';

    protected $dbName = 'default';

    private $_userInfo = null;

    public function getUserInfo($openid)
    {
        if ($this->_userInfo == null) {
            $modelUser = new Campaign_Model_User_Info();
            $this->_userInfo = $modelUser->getUserInfo($openid);
        }
        return $this->_userInfo;
    }

    /**
     * 获取用户在指定年月，前后偏移$offset月的实际经期记录
     *
     * @param string $openid            
     * @param int $year            
     * @param int $month            
     * @param int $offset            
     * @throws Exception
     * @return array
     */
    public function getActualRecordByMonth($openid, $year, $month, $offset = 2)
    {
        $userInfo = $this->getUserInfo($openid);
        if ($userInfo == null) {
            throw new Exception("无效的用户信息，请检查用户是否存在");
        }
        
        $records = array();
        $birthday = date("Y-m-d", $userInfo['birthday']->sec);
        $lastDay = date_create(date("Y-m-d", $userInfo['last_time']->sec));
        $menstruation = $userInfo['menstruation'];
        $period = $userInfo['period'];
        
        $currentMonthFirstDay = date_create(date("Y-m-d", mktime(0, 0, 0, $month, 1, $year)));
        $currentMonthLastDay = date_create(date("Y-m-d", mktime(23, 59, 59, $month, cal_days_in_month(1, $month, $year), $year)));
        $startDay = date_sub($currentMonthFirstDay, date_interval_create_from_date_string("{$offset} months"));
        $endDay = date_add($currentMonthLastDay, date_interval_create_from_date_string("{$offset} months"));
        
        $records = $this->findAll(array(
            'openid' => $openid,
            'date' => array(
                '$gte' => new MongoDate($startDay->format("U")),
                '$lte' => new MongoDate($endDay->format("U"))
            )
        ), array(
            'date' => - 1
        ));
        
        $rst = array();
        if (! empty($records)) {
            foreach ($records as $record) {
                $rst[] = array(
                    'date' => date_create(date("Y-m-d", $record['date']->sec)),
                    'state' => $record['state']
                );
            }
        }
        $rst[] = array(
            'date' => $lastDay,
            'state' => 1
        );
        
        usort($rst, function ($a, $b)
        {
            if ($a['date'] == $b['date']) {
                return 0;
            }
            return ($a['date'] > $b['date']) ? 1 : - 1;
        });
        
        return $rst;
    }

    /**
     * 获取用户的经期与偏移量
     *
     * @param string $openid
     *            微信用户id
     * @param int $year            
     * @param int $month            
     * @param int $offset            
     * @return boolean number multitype:unknown
     */
    public function getCalendarByMonth($openid, $year, $month, $offset = 2)
    {
        $userInfo = $this->getUserInfo($openid);
        if ($userInfo == null) {
            throw new Exception("无效的用户信息，请检查用户是否存在");
        }
        
        $records = array();
        $birthday = date("Y-m-d", $userInfo['birthday']->sec);
        $lastDay = date_create(date("Y-m-d", $userInfo['last_time']->sec));
        $menstruation = $userInfo['menstruation'];
        $period = $userInfo['period'];
        
        $currentMonthFirstDay = date_create(date("Y-m-d", mktime(0, 0, 0, $month, 1, $year)));
        $currentMonthLastDay = date_create(date("Y-m-d", mktime(23, 59, 59, $month, cal_days_in_month(1, $month, $year), $year)));
        $startDay = date_sub($currentMonthFirstDay, date_interval_create_from_date_string("{$offset} months"));
        $endDay = date_add($currentMonthLastDay, date_interval_create_from_date_string("{$offset} months"));
        
        $records = $this->findAll(array(
            'openid' => $openid,
            'state' => 1
        ), array(
            'date' => - 1
        ));
        
        $firstRecordDay = '';
        $allDays = array();
        if (empty($records)) {
            $firstRecordDay = $lastDay;
            $allDays[] = $lastDay;
        } else {
            $firstRecordDay = date_create(date("Y-m-d", $records[0]['date']->sec));
            foreach ($records as $row) {
                $allDays[] = date_create(date("Y-m-d", $row['date']->sec));
            }
            $allDays[] = $lastDay;
        }
        $userMenstruationDays = array();
        if ($firstRecordDay <= $startDay) {
            $n = 1;
            $date = $firstRecordDay;
            do {
                date_add($date, date_interval_create_from_date_string("{$period} days"));
                if ($date >= $startDay && $date <= $endDay) {
                    array_push($userMenstruationDays, clone $date);
                }
                $n += 1;
            } while ($date <= $endDay);
        } elseif ($firstRecordDay > $startDay && $firstRecordDay <= $endDay) {
            $n = 1;
            $date = $firstRecordDay;
            do {
                date_add($date, date_interval_create_from_date_string("{$period} days"));
                if ($date >= $startDay && $date <= $endDay) {
                    array_push($userMenstruationDays, clone $date);
                }
                $n += 1;
            } while ($date <= $endDay);
            
            foreach ($allDays as $baseDate) {
                $date = $baseDate;
                $n = 0;
                do {
                    if ($n ++ > 1)
                        date_sub($date, date_interval_create_from_date_string("{$period} days"));
                    if ($date >= $startDay && $date <= $endDay) {
                        $userMenstruationDays = array_filter($userMenstruationDays, function ($var) use($baseDate)
                        {
                            return $var > $baseDate;
                        });
                        array_push($userMenstruationDays, clone $date);
                    }
                } while ($date >= $startDay);
            }
        } else {
            foreach ($allDays as $baseDate) {
                $date = $baseDate;
                $n = 0;
                do {
                    if ($n ++ > 1)
                        date_sub($date, date_interval_create_from_date_string("{$period} days"));
                    if ($date >= $startDay && $date <= $endDay) {
                        $userMenstruationDays = array_filter($userMenstruationDays, function ($var) use($baseDate)
                        {
                            return $var > $baseDate;
                        });
                        array_push($userMenstruationDays, clone $date);
                    }
                } while ($date >= $startDay);
            }
        }
        
        $userMenstruationDays = array_filter($userMenstruationDays, function ($date)
        {
            static $idList = array();
            if (! array_key_exists('date', $date)) {
                if (in_array($date->date, $idList, true)) {
                    return false;
                }
                $idList[] = $date->date;
            }
            return true;
        });
        
        usort($userMenstruationDays, function ($a, $b)
        {
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? 1 : - 1;
        });
        
        return $userMenstruationDays;
    }

    /**
     * 获取某一天的用户与关系
     * 计算当前是什么时期
     * 处于经期第几天
     * 或者距离经期还有几天
     */
    public function getCalendarByDay($date)
    {
        // getCalendarByMonth
    }

    /**
     * 检查用户是否设定了经期的开始
     * 规则，检测设定结束日期的14日内是否设定经期
     *
     * @param string $openid            
     * @param string $date            
     * @param int $limit            
     * @return boolean
     */
    public function checkUserSetMenstruationStart($openid, $date, $limit = 14)
    {
        $endTime = strtotime($date);
        $startTime = $endTime - $limit * 24 * 3600;
        $date = strtotime($date);
        $userInfo = $this->getUserInfo($openid);
        
        $lastOne = $this->find(array(
            'openid' => $openid,
            'date' => array(
                '$lte' => new MongoDate($date)
            )
        ), array(
            'date' => - 1
        ), 0, 1);
        
        if (isset($lastOne['total']) && $lastOne['total'] == 0) {
            if (isset($userInfo['last_time']) && $userInfo['last_time'] instanceof MongoDate) {
                $regDate = $userInfo['last_time']->sec;
                if ($startTime > $regDate || $regDate > $endTime) {
                    return false;
                }
            }
        } else {
            if (isset($lastOne['datas'][0]['state']) && $lastOne['datas'][0]['state'] == 2) {
                return false;
            }
        }
        
        $rst = $this->count(array(
            'openid' => $openid,
            'state' => 1,
            'date' => array(
                '$gte' => new MongoDate($startTime),
                '$lte' => new MongoDate($endTime)
            )
        ));
        
        if (isset($userInfo['last_time']) && $userInfo['last_time'] instanceof MongoDate) {
            $regDate = $userInfo['last_time']->sec;
            if ($startTime <= $regDate && $regDate <= $endTime) {
                $rst += 1;
            }
        }
        return $rst > 0 ? true : false;
    }

    /**
     * 记录用户的行经记录
     *
     * @param string $openid            
     * @param string $date            
     * @param int $state            
     * @return array
     */
    public function record($openid, $date, $state)
    {
        try {
            $date = new MongoDate(strtotime($date));
            $state = (int) $state;
            
            if ($state == 3 || $state == 0) {
                return $this->remove(array(
                    'openid' => $openid,
                    'date' => $date
                ));
            } else {
                return $this->update(array(
                    'openid' => $openid,
                    'date' => $date
                ), array(
                    '$set' => array(
                        'openid' => $openid,
                        'date' => $date,
                        'state' => $state
                    )
                ), array(
                    'upsert' => true
                ));
                
                /*
                 * $check = $this->count(array( 'openid' => $openid, 'date' => $date )); if ($check > 0) { return $this->update(array( 'openid' => $openid, 'date' => $date ), array( '$set' => array( 'openid' => $openid, 'date' => $date, 'state' => $state ) )); } else { return $this->insert(array( 'openid' => $openid, 'date' => $date, 'state' => $state )); }
                 */
            }
        } catch (Exception $e) {
            var_dump($e);
        }
    }
}