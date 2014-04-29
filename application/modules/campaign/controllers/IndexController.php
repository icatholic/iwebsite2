<?php

class Campaign_IndexController extends Zend_Controller_Action
{

    private $_config;

    public function init()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $this->_config = Zend_Registry::get('config');
    }

    public function indexAction()
    {
        $redirect = $this->_config['global']['path'] . 'campaign/index/result';
        header("location:{$this->_config['global']['path']}weixin/sns/index?redirect={$redirect}&scope=snsapi_userinfo");
        exit();
    }
    
    public function testAction() {
        $obj = new Campaign_Model_User_Invite();
        var_dump($obj->record('o8NOajuFB07kWd4eHbKhY24OXPFE', 'o8NOajkJSKrDLriSSqo0AYGykMrg'));
    }

    public function resultAction()
    {
        var_dump($_SESSION);
    }
}

