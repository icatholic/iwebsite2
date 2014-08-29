<?php

class Default_IndexController extends Zend_Controller_Action
{

    private $_test;

    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_test = new Default_Model_Test();
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
    {
        try {
            echo $this->_test->count(array());
            var_dump($this->_test->insert(array('textfield'=>'123')));
            echo $this->_test->count(array());
            var_dump($this->_test);
        } catch (Exception $e) {
            var_dump($e);
        }
    }
}