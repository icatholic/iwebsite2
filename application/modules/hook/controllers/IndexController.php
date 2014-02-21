<?php

class Hook_IndexController extends Zend_Controller_Action
{

    private $_resource;

    public function init ()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_resource = new Privileges_Model_Resource();
    }

    /**
     * @name 获取资源列表
     */
    public function indexAction ()
    {

    }

    public function testAction ()
    {
        echo 'ok';
    }
}