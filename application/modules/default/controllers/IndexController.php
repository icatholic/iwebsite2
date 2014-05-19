<?php

class Default_IndexController extends Zend_Controller_Action
{

    private $_resource;

    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
    }

    /**
     *
     * @name 获取资源列表
     */
    public function indexAction()
    {
        echo 'ok';
    }

    public function testAction()
    {}
}