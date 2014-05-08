<?php

class Admin_Model_EmailLog extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinPay_EmailLog';

    protected $dbName = 'weixinshop';
    
    /**
     * 发送库存预警邮件，并记录邮件信息
     * @param array $stockAlarmInfo
     */
    public function sendEmail4StockAlarm($stockAlarmInfo)
    {
        $subject = "库存预警";
        $modelEmailSettings = new Admin_Model_EmailSettings();
        $emailSettings = $modelEmailSettings->getInfoBySubject($subject);
        $to = explode(",", $emailSettings['to']);
        $type = ! empty($emailSettings['is_html']) ? 'html' : 'text';
        $content = $emailSettings['content_template'];
        $content = str_ireplace('{$productId}', $stockAlarmInfo['productId'], $content);
        $content = str_ireplace('{$stock_day}', $stockAlarmInfo['stock_day'], $content);
        $content = str_ireplace('{$happen_time}', date('Y年m月d日', strtotime($stockAlarmInfo['happen_time'])), $content);
        $ret = 0;
        $data = array();
        $data['subject'] = $subject;
        $data['to'] = $emailSettings['to'];
        $now = new MongoDate();
        $data['send_time'] = $now;
        $data['type'] = $type;
        $data['content'] = $content;
        $data['title'] = $emailSettings['title'];
        $data['is_send_success'] = empty($ret) ? 0 : 1;
        $this->insert($data);
    }

    public function insertDemo()
    {
        $data = array();
        $data['subject'] = 'aaaa';
        $data['to'] = 'handsomegyr@126.com,handsomegyr@hotmail.com';
        $now = new MongoDate();
        $data['send_time'] = $now;
        $data['type'] = 'html';
        $data['content'] = 'aaaaaaaaaaaaaa';
        $data['is_send_success'] = 1;
        print_r($data);
        $this->insert($data);
    }
}