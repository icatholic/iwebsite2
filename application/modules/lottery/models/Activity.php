<?php

class Lottery_Model_Activity extends iWebsite_Plugin_Mongo
{

    protected $name = 'iLottery_activity';

    protected $dbName = 'lottery';

    private $_activityInfo = null;

    /**
     * 获取活动信息
     *
     * @param string $activity_id            
     */
    public function getActivityInfo($activity_id)
    {
        if ($this->_activityInfo == null) {
            $this->_activityInfo = $this->findOne(array(
                '_id' => $activity_id
            ));
        }
        return $this->_activityInfo;
    }

    /**
     * 检测活动是否开始
     *
     * @param string $activity_id            
     */
    public function checkActivityActive($activity_id)
    {
        $activityInfo = $this->getActivityInfo($activity_id);
        if (! empty($activityInfo['is_actived'])) {
            $now = time();
            if (! empty($activityInfo['start_time']) && ! empty($activityInfo['end_time'])) {
                if ($activityInfo['start_time']->sec <= $now && $now <= $activityInfo['end_time']->sec) {
                    return true;
                } else {
                    fb(date("Y-m-d H:i:s", $now),'LOG');
                    fb(date("Y-m-d H:i:s", $activityInfo['start_time']->sec),'LOG');
                    fb(date("Y-m-d H:i:s", $activityInfo['end_time']->sec),'LOG');
                    return false;
                }
            } else {
                throw new Exception("请设定完整的活动起止时间");
            }
        } else {
            return false;
        }
    }
}