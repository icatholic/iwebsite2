<?php

class Cronjob_IndexController extends Zend_Controller_Action
{

    public function init()
    {
        //$this->_helper->viewRenderer->setNoRender(true);
        echo 'INIT';
    }

    public function indexAction()
    {
        echo 'OK';
    }
    
    public function dbAction() {
        $model = new Cronjob_Model_DbTest();
        var_dump($model->find(array()));
    }
    
}

