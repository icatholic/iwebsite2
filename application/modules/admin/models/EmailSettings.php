<?php

class Admin_Model_EmailSettings extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinPay_EmailSettings';

    protected $dbName = 'weixinshop';

    /**
     * 根据邮件主题获取邮件设置的详细信息
     * 
     * @param string $subject            
     * @return array
     */
    public function getInfoBySubject($subject)
    {
        $query = array();
        $query['subject'] = $subject;
        $info = $this->findOne($query);
        return $info;
    }
}