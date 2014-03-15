<?php

/**
 * 记录微信二维码扫描状况
 * 
 * @author young
 *
 */
class Weixin_Model_Qrcode extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixin_qrcode';

    protected $dbName = 'weixin';

    public function record($openid, $event, $eventKey, $ticket)
    {
        try {
            if ($event === 'subscribe') {
                $sence_id = str_ireplace('qrscene_', '', $eventKey);
            } else 
                if ($event === 'SCAN') {
                    $sence_id = $eventKey;
                } else {
                    throw new Exception("无效的事件类型");
                }
            
            $datas = array(
                'sence_id' => $sence_id,
                'openid' => $openid,
                'Event' => $event,
                'EventKey' => $eventKey,
                'Ticket' => $ticket
            );
            return $this->insert($datas);
        } catch (Exception $e) {
            return exceptionMsg($e);
        }
    }
}