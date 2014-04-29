<?php

class Campaign_PrototypeController extends iWebsite_Controller_Action
{
    private $_user;
    
    private $_point;
    
    private $_weixin_user;
    
    private $_config;
    
    public function init()
    {
        $this->_weixin_user = new Weixin_Model_User();
        $this->_user = new Campaign_Model_User_Info();
        $this->_point = new Campaign_Model_User_Point();
        $this->_config = $this->getConfig();
    }
}