<?php

class Weixinsign_Model_Statistics extends iWebsite_Plugin_Mongo
{

    protected $name = 'iSign_statistics';

    protected $dbName = 'weixinsign';
    
    /*
     * 根据OpenId获取信息
     */
    public function getInfoByOpenId($OpenId)
    {
        $query = array(
            'OpenId' => $OpenId,
            'is_do' => array(
                '$ne' => true
            )
        );
        $info = $this->findOne($query);
        return $info;
    }
    
    /*
     * 判断签到时间
     */
    public function judgeSignTime($info, $sign_time)
    {
        // 如果最终签到日+1*24*60*60==$sign_time
        $last_sign_time = strtotime(date("Y-m-d", $info['last_sign_time']->sec) . " 00:00:00");
        $current_sign_time = strtotime(date("Y-m-d", $sign_time->sec) . " 00:00:00");
        
        if ($last_sign_time + 1 * 24 * 60 * 60 == $current_sign_time) {
            return 1; // 连续签到
        } else 
            if ($last_sign_time + 1 * 24 * 60 * 60 > $current_sign_time) {
                return - 1; // 同天签到
            } else 
                if ($last_sign_time + 1 * 24 * 60 * 60 < $current_sign_time) {
                    return 0; // 非连续签到
                }
    }
    
    /*
     * 连续签到次数+1
     */
    public function incContinueSignCount($OpenId)
    {
        // 记录签到明细
        $sign_time = new MongoDate();
        $modelDetails = new Weixinsign_Model_Details();
        $modelDetails->log($OpenId, $sign_time);
        
        // 根据OpenId获取签到信息
        $info = $this->getInfoByOpenId($OpenId);
        if ($info) { // 存在
            $judgeResult = $this->judgeSignTime($info, $sign_time); // 检查是否是连续签到
            if ($judgeResult === 1) { // 连续的时候
                $options = array(
                    "query" => array(
                        "_id" => $info['_id']
                    ),
                    "update" => array(
                        '$set' => array(
                            "lastip" => getIp(),
                            "last_sign_time" => $sign_time
                        ),
                        '$inc' => array(
                            'continue_sign_count' => 1, // 签到计数+1
                            'total_sign_count' => 1
                        )
                    ),
                    "new" => true
                );
                $return_result = $this->findAndModify($options);
                $info = $return_result["value"];
                return $info;
            } else 
                if ($judgeResult === 0) { // 非连续的时候
                    $options = array(
                        "query" => array(
                            "_id" => $info['_id']
                        ),
                        "update" => array(
                            '$set' => array(
                                "lastip" => getIp(),
                                "continue_sign_count" => 1, // 重新计数
                                "restart_sign_time" => $sign_time, // 重新设置签到日期
                                "last_sign_time" => $sign_time
                            ),
                            '$inc' => array(
                                'total_sign_count' => 1
                            )
                        ),
                        "new" => true
                    );
                    $return_result = $this->findAndModify($options);
                    $info = $return_result["value"];
                    return $info;
                } else 
                    if ($judgeResult === - 1) { // 同天签到
                        $this->update(array(
                            '_id' => $info['_id']
                        ), array(
                            '$set' => array(
                                'last_sign_time' => $sign_time
                            ),
                            '$inc' => array(
                                'total_sign_count' => 1
                            )
                        ));
                        return $info;
                    }
        } else { // 不存在
            $info = $this->getFinishedSignInfoByOpenId($OpenId); // 获取上次结束的连续签到的信息
            if (! empty($info)) {
                // 检查上次结束的连续签到的最终时间和今次签到的时间是在同一天的话
                $judgeResult = $this->judgeSignTime($info, $sign_time);
                if ($judgeResult === - 1) { // 同天签到
                    $this->update(array(
                        '_id' => $info['_id']
                    ), array(
                        '$set' => array(
                            'last_sign_time' => $sign_time
                        ),
                        '$inc' => array(
                            'total_sign_count' => 1
                        )
                    ));
                    return $info;
                }
            }
            
            $data = array();
            $data['OpenId'] = $OpenId;
            $data['continue_sign_count'] = 1; // 连续签到数量
            $data['total_sign_count'] = 1; // 总签到数量
            $data['is_do'] = false; // 是否完成
            $data['lastip'] = getIp(); // 最终IP
            $data['first_sign_time'] = $sign_time; // 首次签到时间
            $data['restart_sign_time'] = $sign_time; // 重新开始签到时间
            $data['last_sign_time'] = $sign_time; // 最终签到时间
            return $this->insert($data);
        }
    }
    
    /*
     * 完成一次连续签到
     */
    public function finishContinueSign($info)
    {
        // 关闭
        $data = array();
        $data['is_do'] = 1;
        $this->update(array(
            '_id' => $info['_id']
        ), array(
            '$set' => $data
        ));
    }
    
    /*
     * 根据OpenId获取上一次结束的连续签到信息
     */
    public function getFinishedSignInfoByOpenId($OpenId)
    {
        $query = array(
            'OpenId' => $OpenId,
            'is_do' => true
        );
        $info = $this->find($query, array(
            'last_sign_time' => - 1
        ), 0, 1);
        if (! empty($info['datas'])) {
            return $info['datas'][0];
        }
        return null;
    }
    
    /*
     * 根据OpenId获取上一次的签到信息
     */
    public function getLastInfoByOpenId($OpenId)
    {
        $query = array(
            'OpenId' => $OpenId
        );
        $info = $this->find($query, array(
            'last_sign_time' => - 1
        ), 0, 1);
        if (! empty($info['datas'])) {
            return $info['datas'][0];
        }
        return null;
    }
}