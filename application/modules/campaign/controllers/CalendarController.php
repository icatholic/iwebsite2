<?php

/**
 * 今日蜜历
 * @author Young
 *
 */
class Campaign_CalendarController extends iWebsite_Controller_Action
{

    private $_weixin_user;

    private $_user;

    private $_pic;

    private $_calendar;

    private $_shake;

    private $_config;

    public function init()
    {
        $this->_user = new Campaign_Model_User_Info();
        $this->_pic = new Campaign_Model_Calendar_Pics();
        $this->_calendar = new Campaign_Model_Calendar_Calendar();
        $this->_shake = new Campaign_Model_User_Fortune();
        $this->_weixin_user = new Weixin_Model_User();
        $this->_config = $this->getConfig();
    }

    /**
     * 介绍活动流程
     */
    public function indexAction()
    {
        try {
            $this->_forward('user-info');
            // 检测用户是否提交个人资料
            // $openid = isset($_REQUEST['FromUserName']) ? $_REQUEST['FromUserName'] : '';
            // if (empty($openid)) {
            // throw new Exception("微信编号为空");
            // }
            
            // $check = $this->_user->findOne(array(
            // 'openid' => $openid
            // ));
            // if ($check != null) {
            // $this->_forward('photo');
            // }
            
            // if (! $this->_weixin_user->checkOpenId($openid)) {
            // throw new Exception("无效的微信ID");
            // }
            
            // $this->assign('openid', $openid);
            // $config = $this->getConfig();
            // $this->assign('rootPath', $config['global']['path']);
        } catch (Exception $e) {
            echo exceptionMsg($e);
        }
    }

    /**
     * 补充个人信息
     */
    public function userInfoAction()
    {
        // 显示填写用户信息页面
        $openid = isset($_REQUEST['FromUserName']) ? $_REQUEST['FromUserName'] : '';
        $redirect = isset($_REQUEST['redirect']) ? $_REQUEST['redirect'] : $this->getParam('redirect', 'photo');
        $openid = $this->getParam('openid', $openid);
        $config = $this->getConfig();
        $this->assign('rootPath', $config['global']['path']);
        
        // 获取用户的微信信息
        $app = new Weixin_Model_Application();
        $appConfig = $app->getToken();
        $weixin = new Weixin\Client();
        if (! empty($appConfig['access_token'])) {
            $weixin->setAccessToken($appConfig['access_token']);
            $user = new Weixin_Model_User();
            $user->setWeixinInstance($weixin);
            $user->updateUserInfoByAction($openid);
        }
        
        $redirect = urldecode($redirect);
        if (strpos($redirect, '?') === false) {
            $redirect .= '?FromUserName=' . $openid;
        } else {
            $redirect .= '&FromUserName=' . $openid;
        }
        $this->assign('redirect', urldecode($redirect));
        $this->assign('version', $this->getVersion());
        
        $userInfo = $this->_user->getUserInfo($openid);
        $this->assign('userInfo', $userInfo);
    }

    /**
     * 处理个人信息
     *
     * @return boolean
     */
    public function submitUserInfoAction()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $openid = isset($_REQUEST['FromUserName']) ? $_REQUEST['FromUserName'] : '';
        $birthday = isset($_REQUEST['birthday']) ? $_REQUEST['birthday'] : '';
        $menstruation = isset($_REQUEST['menstruation']) ? $_REQUEST['menstruation'] : 5;
        $period = isset($_REQUEST['period']) ? $_REQUEST['period'] : 28;
        $lastTime = isset($_REQUEST['last_time']) ? $_REQUEST['last_time'] : '';
        
        if (empty($openid)) {
            echo $this->error(500, '微信ID错误');
            return false;
        }
        
        if (! $this->_weixin_user->checkOpenId($openid)) {
            echo $this->error(500, '无效的微信ID');
            return false;
        }
        
        if (empty($birthday)) {
            echo $this->error(501, '生日错误');
            return false;
        }
        if (empty($menstruation)) {
            echo $this->error(502, '行经周期错误');
            return false;
        }
        if (empty($period)) {
            echo $this->error(503, '月经周期错误');
            return false;
        }
        
        if ($menstruation > $period) {
            echo $this->error(503, '月经周期错误,必须大于行经周期');
            return false;
        }
        
        if (empty($lastTime)) {
            echo $this->error(504, '最后经期时间错误');
            return false;
        }
        try {
            $this->_user->record($openid, $birthday, $menstruation, $period, $lastTime);
            echo $this->result(true, '注册信息成功');
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    /**
     * 无忧日历分享页面
     */
    public function shareAction()
    {
        $openid = isset($_GET['FromUserName']) ? $_GET['FromUserName'] : '';
        $date = isset($_GET['date']) ? strtotime($_GET['date']) : time();
        $tip_id = isset($_GET['tip_id']) ? $_GET['tip_id'] : '';
        $return = isset($_GET['return']) ? $_GET['return'] : '';
        
        $year = date("Y", $date);
        $month = date("m", $date);
        $day = date("d", $date);
        $pic = $this->_pic->getSomeDayPics($openid, $year, $month, $day);
        $modelToday = new Campaign_Model_Today_Info();
        $tips = $modelToday->getInfoById($tip_id);
        if (empty($pic)) {
            $this->view->assign('photo', false);
        } else {
            $this->view->assign('photo', $pic[0]['picture']);
        }
        $config = $this->getConfig();
        $this->assign('rootPath', $config['global']['path']);
        $this->assign('openid', $openid);
        
        $this->view->assign('picture', $tips['picture']);
        $this->view->assign('tip_id', $tips['_id']->__toString());
        
        $this->assign('shareLink', "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '&return=1');
        $this->assign('version', $this->getVersion());
        
        if (! empty($return)) {
            $this->renderScript('calendar/share-back.phtml');
        } else {
            $this->renderScript("calendar/share.phtml");
        }
    }

    /**
     */
    public function careAction()
    {
        $config = $this->getConfig();
        $this->assign('rootPath', $config['global']['path']);
        $this->assign('version', $this->getVersion());
    }

    /**
     * 显示今日秘历
     */
    public function photoOldAction()
    {
        $openid = isset($_GET['FromUserName']) ? $_GET['FromUserName'] : '';
        $date = isset($_GET['date']) ? strtotime($_GET['date']) : time();
        
        $year = date("Y", $date);
        $month = date("m", $date);
        $day = date("d", $date);
        
        $today = date_create(date("Y-m-d", $date));
        $preDay = date("Y-m-d", $date - 24 * 3600);
        $postDay = date("Y-m-d", $date + 24 * 3600);
        
        $this->assign('year', $year);
        $this->assign('month', $month);
        $this->assign('day', $day);
        
        $this->assign('preDay', $preDay);
        $this->assign('postDay', $postDay);
        
        $this->assign('version', $this->getVersion());
        
        // 显示特定图片给好友
        try {
            $pic = $this->_pic->getSomeDayPics($openid, $year, $month, $day);
            // $pic = $this->_pic->getTodayPics($openid);
            if (empty($pic)) {
                $this->view->assign('photo', false);
            } else {
                $this->view->assign('photo', $pic[0]['picture']);
            }
            
            // 计算今天是用户的什么时间
            $userInfo = $this->_calendar->getUserInfo($openid);
            if ($userInfo == null) {
                $this->_redirect($this->_config['global']['path'] . 'campaign/calendar/user-info?FromUserName=' . $openid);
            }
            
            $calendar = $this->_calendar->getCalendarByMonth($openid, date("Y"), date("m"), 2);
            // fb($calendar,'LOG');
            $dayInfo = $this->calDayInfo($calendar, $today);
            // fb($dayInfo,'LOG');
            $modelToday = new Campaign_Model_Today_Info();
            
            $this->view->assign('from', $dayInfo['from']);
            $this->view->assign('to', $dayInfo['to']);
            
            if ($dayInfo['from'] < $userInfo['menstruation']) {
                // 距离大姨妈结束还有多少天
                $tips = $modelToday->getInfoByType('from', $dayInfo['from']);
                $leave = $userInfo['menstruation'] - $dayInfo['from'];
                $this->view->assign('title', "距离月经结束还有{$leave}天");
                $this->view->assign('content', $tips['content']);
                $this->view->assign('picture', $tips['picture']);
                $this->view->assign('tip_id', $tips['_id']->__toString());
            } elseif ($dayInfo['to'] > 5) {
                // 预计下次月经在X月X日到访
                $nextCalendar = array_filter($calendar, function ($date) use($today)
                {
                    return $date > $today;
                });
                $next = array_pop($nextCalendar);
                
                $this->view->assign('title', "预计下次月经将于{$next->format("m")}月{$next->format("d")}日到访");
                
                $tips = $modelToday->getInfoByType('to', $dayInfo['to']);
                $this->view->assign('content', $tips['content']);
                $this->view->assign('picture', $tips['picture']);
                $this->view->assign('tip_id', $tips['_id']->__toString());
            } else {
                // 距离下次月经还有多少天
                $tips = $modelToday->getInfoByType('last', $dayInfo['to']);
                $this->view->assign('title', "距离月经来临还剩{$dayInfo['to']}天");
                $this->view->assign('content', $tips['content']);
                $this->view->assign('picture', $tips['picture']);
                $this->view->assign('tip_id', $tips['_id']->__toString());
            }
            
            $config = $this->getConfig();
            $this->assign('rootPath', $config['global']['path']);
            $this->assign('openid', $openid);
        } catch (Exception $e) {
            echo exceptionMsg($e);
            // 非法操作，跳转到指定页面
        }
    }

    /**
     * 显示今日秘历
     */
    public function photoAction()
    {
        // $this->_helper->viewRenderer->setNoRender(true);
        $openid = isset($_GET['FromUserName']) ? $_GET['FromUserName'] : '';
        $date = isset($_GET['date']) ? strtotime($_GET['date']) : time();
        
        $year = date("Y", $date);
        $month = date("m", $date);
        $day = date("d", $date);
        
        $today = date_create(date("Y-m-d", $date));
        $preDay = date("Y-m-d", $date - 24 * 3600);
        $postDay = date("Y-m-d", $date + 24 * 3600);
        
        $this->assign('year', $year);
        $this->assign('month', $month);
        $this->assign('day', $day);
        
        $this->assign('preDay', $preDay);
        $this->assign('postDay', $postDay);
        
        $this->assign('version', $this->getVersion());
        
        // 显示特定图片给好友
        try {
            $pic = $this->_pic->getSomeDayPics($openid, $year, $month, $day);
            // $pic = $this->_pic->getTodayPics($openid);
            if (empty($pic)) {
                $this->view->assign('photo', false);
            } else {
                $this->view->assign('photo', $pic[0]['picture']);
            }
            
            // 计算今天是用户的什么时间
            $userInfo = $this->_calendar->getUserInfo($openid);
            if ($userInfo == null) {
                $this->_redirect($this->_config['global']['path'] . 'campaign/calendar/user-info?FromUserName=' . $openid);
            }
            
            $calendar = $this->_calendar->getCalendarByMonth($openid, $year, $month, 2);
            $dayInfo = $this->calDayInfo($calendar, $today);
            $modelToday = new Campaign_Model_Today_Info();
            $calendarClone = array_copy($calendar);
            $arrPreNext = $this->calPreNextDay($calendarClone);
            $records = $this->_calendar->getActualRecordByMonth($openid, $year, $month, 2);
            $today = date("Y-m-d", $date);
            $result = $this->calendarResultByDay($calendar, $records, $userInfo['menstruation'], $today);
            // $st = $this->findToday($result);
            
            /**
             * st状态说明：
             * jq经期
             * ycjq预测经期
             * plr排卵日
             * ysy易受孕
             * lpq卵泡期 不定长度
             * htq黄体期
             */
            // fb($result,'LOG');
            if (isset($result['jq']) && in_array($today, $result['jq'])) {
                fb("jq", 'LOG');
                if ($today == current($result['jq'])) {
                    // 经期第一天
                    $tips = $modelToday->getInfoBySt("月经第一天");
                } elseif ($today == end($result['jq'])) {
                    // 经期最后一天
                    $tips = $modelToday->getInfoBySt("月经最后一天");
                } else {
                    // 经期其他日期
                    $day = array_search($today, $result['jq']);
                    $tips = $modelToday->getInfoBySt("月经期间", $day);
                }
                fb($tips, 'LOG');
                $jqEnd = end($result['jq']);
                $jqEnd = date_create($jqEnd);
                $diff = date_diff($jqEnd, date_create($today), true);
                $leave = $diff->days + 1;
                $this->view->assign('title', "距离月经结束还有{$leave}天");
            } elseif (! isset($result['jq']) && isset($result['ycjq']) && in_array($today, $result['ycjq'])) {
                fb("ycjq", 'LOG');
                if ($today == current($result['ycjq'])) {
                    // 经期第一天
                    $tips = $modelToday->getInfoBySt("月经第一天");
                } elseif ($today == end($result['ycjq'])) {
                    // 经期最后一天
                    $tips = $modelToday->getInfoBySt("月经最后一天");
                } else {
                    // 经期其他日期
                    $day = array_search($today, $result['ycjq']);
                    $tips = $modelToday->getInfoBySt("月经期间", $day);
                }
                $jqEnd = end($result['ycjq']);
                $jqEnd = date_create($jqEnd);
                $diff = date_diff($jqEnd, date_create($today), true);
                $leave = $diff->days + 1;
                $this->view->assign('title', "距离月经结束还有{$leave}天");
            } elseif (isset($result['plr']) && in_array($today, $result['plr'])) {
                // 排卵日
                fb("plr", 'LOG');
                $tips = $modelToday->getInfoBySt("排卵日");
                $this->view->assign('title', "预计下次月经将于{$arrPreNext[1]->format("m")}月{$arrPreNext[1]->format("d")}日到访");
            } elseif (isset($result['ysy']) && in_array($today, $result['ysy'])) {
                // 易受孕
                // 排卵期第一天
                fb("ysy", 'LOG');
                $day = array_search($today, $result['ysy']);
                $tips = $modelToday->getInfoBySt("易受孕", $day + 1);
                $this->view->assign('title', "预计下次月经将于{$arrPreNext[1]->format("m")}月{$arrPreNext[1]->format("d")}日到访");
            } elseif (isset($result['plr']) && $date > strtotime($result['plr'][0]) && $dayInfo['to'] > 5) {
                // 黄体期
                fb("htq", 'LOG');
                $diff = date_diff(date_create($today), date_create($result['plr'][0]), true);
                $day = $diff->days - 4;
                $tips = $modelToday->getInfoBySt("黄体期", $day);
                $this->view->assign('title', "预计下次月经将于{$arrPreNext[1]->format("m")}月{$arrPreNext[1]->format("d")}日到访");
            } elseif (isset($result['plr']) && $date < strtotime($result['plr'][0])) {
                // 卵泡期
                fb("lpq", 'LOG');
                $jq = isset($result['jq']) ? $result['jq'] : $result['ycjq'];
                $jqEnd = end($jq);
                $diff = date_diff(date_create($today), date_create($jqEnd), true);
                $day = $diff->days;
                $tips = $modelToday->getInfoBySt("卵泡期", $day);
                $this->view->assign('title', "预计下次月经将于{$arrPreNext[1]->format("m")}月{$arrPreNext[1]->format("d")}日到访");
            } else {
                fb("others", 'LOG');
                // 距离月经来临还剩下X天
                $tips = $modelToday->getInfoBySt("距离经期倒计时五天", $dayInfo['to']);
                $this->view->assign('title', "距离月经来临还剩{$dayInfo['to']}天");
            }
            
            $this->view->assign('content', isset($tips['content']) ? $tips['content'] : '');
            $this->view->assign('picture', isset($tips['picture']) ? $tips['picture'] : '');
            $this->view->assign('tip_id', (isset($tips['_id']) && $tips['_id'] instanceof \MongoId) ? $tips['_id']->__toString() : '');
            
            $config = $this->getConfig();
            $this->assign('rootPath', $config['global']['path']);
            $this->assign('openid', $openid);
            $this->renderScript('calendar/photo.phtml');
        } catch (Exception $e) {
            echo exceptionMsg($e);
            // 非法操作，跳转到指定页面
        }
    }

    /**
     * 获取当前日期的上一次月经来潮日期和下一次即将来临日期
     *
     * @param array $calendar            
     * @return array
     */
    private function calPreNextDay($calendar, $date = null)
    {
        if ($date == null) {
            $date = date_create(date("Y-m-d"));
        } elseif (is_int($date)) {
            $date = date_create(date("Y-m-d", $date));
        } elseif (is_string($date)) {
            $date = date_create(date("Y-m-d", strtotime($date)));
        } elseif ($date instanceof DateTime) {
            $date = $date;
        } else {
            throw new Exception("无效的数据类型");
        }
        
        $pre = array_filter($calendar, function ($d) use($date)
        {
            return $d <= $date;
        });
        
        $post = array_filter($calendar, function ($d) use($date)
        {
            return $d > $date;
        });
        
        $preDay = array_shift($pre);
        $postDay = array_pop($post);
        return array(
            $preDay,
            $postDay
        );
    }

    /**
     * 计算当日信息
     *
     * @param array $calendar            
     * @param date $date            
     */
    private function calDayInfo($calendar, $date)
    {
        if (! empty($calendar)) {
            $arrPreNext = $this->calPreNextDay($calendar, $date);
            $preDay = $arrPreNext[0];
            $postDay = $arrPreNext[1];
            
            $from = date_diff($preDay, $date, true);
            $to = date_diff($postDay, $date, true);
            
            $from = $from->days;
            $to = $to->days;
            
            return array(
                'from' => $from,
                'to' => $to
            );
        }
    }

    /**
     * 显示今日秘历
     */
    public function calendarAction()
    {
        // 显示页面
        $openid = isset($_GET['FromUserName']) ? $_GET['FromUserName'] : '';
        
        $userInfo = $this->_calendar->getUserInfo($openid);
        if ($userInfo == null) {
            $redirect = urlencode("{$this->_config['global']['path']}campaign/calendar/calendar");
            $url = "{$this->_config['global']['path']}campaign/calendar/user-info?FromUserName={$openid}&redirect={$redirect}";
            header("location:{$url}");
            // echo $url;
            exit();
        }
        
        if (! $this->_weixin_user->checkOpenId($openid)) {
            echo $this->error(500, '无效的微信ID');
            return false;
        }
        
        $calendar = $this->_calendar->getCalendarByMonth($openid, date("Y"), date("m"), 10);
        $records = $this->_calendar->getActualRecordByMonth($openid, date("Y"), date("m"), 10);
        $menstruation = $userInfo['menstruation'];
        
        $this->assign('datas', json_encode($this->calendarResult($calendar, $records, $menstruation)));
        
        $config = $this->getConfig();
        $this->assign('rootPath', $config['global']['path']);
        $this->assign('openid', $openid);
        $this->assign('version', $this->getVersion());
    }

    /**
     * 用户自主修改日历上的时间范围
     *
     * @return boolean
     */
    public function addMenstruactionAction()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $openid = isset($_REQUEST['FromUserName']) ? $_REQUEST['FromUserName'] : '';
        $date = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
        $state = isset($_REQUEST['state']) ? intval($_REQUEST['state']) : 0;
        
        if (empty($openid)) {
            echo $this->error(500, '微信ID错误');
            return false;
        }
        
        if (! $this->_weixin_user->checkOpenId($openid)) {
            echo $this->error(500, '无效的微信ID');
            return false;
        }
        
        if (strtotime($date) == 0) {
            echo $this->error(505, '日期格式错误');
            return false;
        }
        
        // 判断当前日期与当前时间
        if (strtotime($date) > mktime(23, 59, 59, date("m"), date("d"), date("Y"))) {
            echo $this->error(509, '未来的经期只能预测，无法人工设定');
            return false;
        }
        
        if ($state == 0) {
            echo $this->error(506, '经期状态错误，只能为1(开始)或者2(结束)');
            return false;
        }
        
        // 如果14天以内没有设定经期结束的，不允许设定经期结束
        if ($state == 2 && ! $this->_calendar->checkUserSetMenstruationStart($openid, $date, 30)) {
            echo $this->error(507, '尚未设定经期开始时间，请检查');
            return false;
        }
        
        $userInfo = $this->_calendar->getUserInfo($openid);
        if (isset($userInfo['last_time']) && $userInfo['last_time'] instanceof MongoDate) {
            if (date("Y-m-d", $userInfo['last_time']->sec) == date("Y-m-d", strtotime($date))) {
                echo $this->error(510, '您取消的日期为您注册时填写的最后一次经期来潮日期，请在修改个人资料中进行修改。');
                return false;
            }
        }
        
        try {
            $this->_calendar->record($openid, $date, $state);
            
            $year = date("Y", strtotime($date));
            $month = date("m", strtotime($date));
            $calendar = $this->_calendar->getCalendarByMonth($openid, $year, $month, 10);
            $records = $this->_calendar->getActualRecordByMonth($openid, $year, $month, 10);
            $userInfo = $this->_calendar->getUserInfo($openid);
            $menstruation = $userInfo['menstruation'];
            $result = $this->calendarResult($calendar, $records, $menstruation);
            
            echo $this->result(true, $result);
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    /**
     * 计算获取日历的数据信息
     */
    public function calCalendarAction()
    {
        try {
            $this->getHelper('viewRenderer')->setNoRender(true);
            $openid = isset($_REQUEST['FromUserName']) ? $_REQUEST['FromUserName'] : '';
            $year = isset($_REQUEST['year']) ? $_REQUEST['year'] : date("Y");
            $month = isset($_REQUEST['month']) ? $_REQUEST['month'] : date("m");
            $offset = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 2;
            $debug = isset($_REQUEST['debug']) ? $_REQUEST['debug'] : false;
            $calendar = $this->_calendar->getCalendarByMonth($openid, $year, $month, $offset);
            if ($debug) {
                echo "<pre>";
                var_dump($calendar);
            }
            $records = $this->_calendar->getActualRecordByMonth($openid, $year, $month, $offset);
            $userInfo = $this->_calendar->getUserInfo($openid);
            $menstruation = $userInfo['menstruation'];
            $result = $this->calendarResult($calendar, $records, $menstruation);
            if ($debug) {
                print_r($result);
                return false;
            }
            echo $this->result("ok", $result);
            return true;
        } catch (Exception $e) {
            print_r($e);
        }
    }

    /**
     * 生成满足前段需求的json数组
     */
    private function calendarResult($datas, $records = array(), $menstruation = 5)
    {
        $result = array();
        // 以下标记实际经期
        if (! empty($records)) {
            foreach ($records as $index => $row) {
                if ($row['state'] == 1) {
                    if (isset($records[$index + 1]['date']) && $records[$index + 1]['state'] == 2) {
                        $first = $row['date'];
                        $last = $records[$index + 1]['date'];
                        $diff = date_diff($first, $last, true);
                        $loop = (int) $diff->days + 1; // 输出天数
                    } else {
                        $loop = (int) $menstruation;
                    }
                    
                    $jq = $row['date'];
                    $jqStart = clone $row['date'];
                    for ($i = 0; $i < $loop; $i ++) {
                        if ($i > 0)
                            date_add($jq, date_interval_create_from_date_string("1 days"));
                        $result[$jq->format("Y")][$jq->format("m")]['jq'][] = $jq->format("d");
                    }
                    
                    $result[$jqStart->format("Y")][$jqStart->format("m")]['jq_start'][] = $jqStart->format("d");
                } else 
                    if ($row['state'] == 2) {
                        $jqEnd = $row['date'];
                        $result[$jqEnd->format("Y")][$jqEnd->format("m")]['jq_end'][] = $jqEnd->format("d");
                    }
            }
        }
        
        foreach ($datas as $index => $row) {
            $ycjq = clone $row;
            
            // 如果预测经期在已知经期里面，不进行预测
            if (isset($result[$ycjq->format("Y")][$ycjq->format("m")]['jq'])) {
                if (in_array($ycjq->format("d"), $result[$ycjq->format("Y")][$ycjq->format("m")]['jq'])) {
                    sort($result[$ycjq->format("Y")][$ycjq->format("m")]['jq']);
                    if ($ycjq->format("d") != $result[$ycjq->format("Y")][$ycjq->format("m")]['jq'][0])
                        continue;
                }
            }
            
            for ($i = 0; $i < $menstruation; $i ++) {
                if ($i > 0)
                    $ycjq = date_add($ycjq, date_interval_create_from_date_string("1 days"));
                $result[$ycjq->format("Y")][$ycjq->format("m")]['ycjq'][] = $ycjq->format("d");
            }
            
            if (isset($datas[$index + 1])) {
                $next = $datas[$index + 1];
                $dateIterval = date_diff($row, $next, true);
                if ($dateIterval->days < 19 + $menstruation) {
                    continue;
                }
            }
            
            $plr = date_sub($row, date_interval_create_from_date_string("14 days"));
            $result[$plr->format("Y")][$plr->format("m")]['plr'][] = $plr->format("d");
            $ysy1 = clone $plr;
            $ysy2 = clone $plr;
            for ($i = 0; $i < 5; $i ++) {
                $ysy1 = date_sub($ysy1, date_interval_create_from_date_string("1 days"));
                $result[$ysy1->format("Y")][$ysy1->format("m")]['ysy'][] = $ysy1->format("d");
            }
            for ($i = 0; $i < 5; $i ++) {
                if ($i > 0)
                    $ysy2 = date_add($ysy2, date_interval_create_from_date_string("1 days"));
                $result[$ysy2->format("Y")][$ysy2->format("m")]['ysy'][] = $ysy2->format("d");
            }
        }
        
        return $result;
    }

    /**
     * 计算日期与状态之间的关系
     */
    private function calendarResultByDay($datas, $records = array(), $menstruation = 5, $date = null)
    {
        $arrPreNext = $this->calPreNextDay($datas, $date);
        $preDay = clone $arrPreNext[0];
        $postDay = clone $arrPreNext[1];
        
        $result = array();
        // 以下标记实际经期
        if (! empty($records)) {
            foreach ($records as $index => $row) {
                if ($row['state'] == 1) {
                    if (isset($records[$index + 1]['date']) && $records[$index + 1]['state'] == 2) {
                        $first = $row['date'];
                        $last = $records[$index + 1]['date'];
                        $diff = date_diff($first, $last, true);
                        $loop = (int) $diff->days + 1; // 输出天数
                    } else {
                        $loop = (int) $menstruation;
                    }
                    
                    $jq = $row['date'];
                    $jqStart = clone $row['date'];
                    for ($i = 0; $i < $loop; $i ++) {
                        if ($i > 0)
                            date_add($jq, date_interval_create_from_date_string("1 days"));
                        $result['jq'][] = $jq->format("Y-m-d");
                    }
                    
                    $result['jq_start'][] = $jqStart->format("Y-m-d");
                } else 
                    if ($row['state'] == 2) {
                        $jqEnd = $row['date'];
                        $result['jq_end'][] = $jqEnd->format("Y-m-d");
                    }
            }
        }
        
        foreach ($datas as $index => $row) {
            if (isset($datas[$index + 1])) {
                $next = $datas[$index + 1];
                $dateIterval = date_diff($row, $next, true);
                if ($dateIterval->days < 19 + $menstruation) {
                    continue;
                }
            }
            
            $ycjq = clone $row;
            
            // 如果预测经期在已知经期里面，不进行预测
            if (isset($result[$ycjq->format("Y")][$ycjq->format("m")]['jq'])) {
                if (in_array($ycjq->format("d"), $result[$ycjq->format("Y")][$ycjq->format("m")]['jq'])) {
                    sort($result[$ycjq->format("Y")][$ycjq->format("m")]['jq']);
                    if ($ycjq->format("d") != $result[$ycjq->format("Y")][$ycjq->format("m")]['jq'][0])
                        continue;
                }
            }
            
            for ($i = 0; $i < $menstruation; $i ++) {
                if ($i > 0)
                    $ycjq = date_add($ycjq, date_interval_create_from_date_string("1 days"));
                $result['ycjq'][] = $ycjq->format("Y-m-d");
            }
            $plr = date_sub($row, date_interval_create_from_date_string("14 days"));
            $result['plr'][] = $plr->format("Y-m-d");
            $ysy1 = clone $plr;
            $ysy2 = clone $plr;
            for ($i = 0; $i < 5; $i ++) {
                $ysy1 = date_sub($ysy1, date_interval_create_from_date_string("1 days"));
                $result['ysy'][] = $ysy1->format("Y-m-d");
            }
            for ($i = 0; $i < 5; $i ++) {
                if ($i > 0)
                    $ysy2 = date_add($ysy2, date_interval_create_from_date_string("1 days"));
                $result['ysy'][] = $ysy2->format("Y-m-d");
            }
        }
        // fb($result,'LOG');
        
        // 过滤无效的日期数据，只体现一个月经周期内的数据
        $newResult = array();
        foreach ($result as $period => $days) {
            if (is_array($days)) {
                $days = array_filter($days, function ($day) use($preDay, $postDay)
                {
                    $day = date_create($day);
                    if ($day < $preDay || $day >= $postDay) {
                        return false;
                    }
                    return true;
                });
                if (! empty($days)) {
                    sort($days, SORT_STRING);
                    $newResult[$period] = $days;
                }
            } else {
                $days = date_create($days);
                if ($days >= $preDay && $days < $postDay) {
                    $newResult[$period] = $days;
                }
            }
        }
        return $newResult;
    }

    /**
     * 查找今天处于月经期的什么状态
     *
     * @param array $newResult            
     * @return string boolean
     */
    private function findToday($newResult)
    {
        $today = date("Y-m-d");
        foreach ($newResult as $st => $days) {
            if (is_array($days)) {
                if (in_array($today, $days)) {
                    return $st;
                }
            } else {
                if ($today == $days)
                    return $st;
            }
        }
        if (isset($newResult['plr'])) {
            if (strtotime($newResult['plr']) > $today) {
                return 'lpq';
            } else {
                return 'htq';
            }
        }
        return false;
    }

    /**
     * 摇一摇结果页面
     */
    public function shakeAction()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $openid = isset($_REQUEST['FromUserName']) ? $_REQUEST['FromUserName'] : '';
        if (empty($openid)) {
            echo $this->error(500, '微信ID错误');
            return false;
        }
        
        if (! $this->_weixin_user->checkOpenId($openid)) {
            echo $this->error(500, '无效的微信ID');
            return false;
        }
        
        echo $this->result('成功', $this->_shake->getFortune($openid));
        return true;
    }

    /**
     * 摇奖结果页面
     */
    public function shakeResultAction()
    {
        $openid = isset($_REQUEST['FromUserName']) ? $_REQUEST['FromUserName'] : '';
        $config = $this->getConfig();
        $this->assign('rootPath', $config['global']['path']);
        $this->assign('version', $this->getVersion());
        $this->assign('result', json_encode($this->_shake->getFortune($openid)));
    }
}

