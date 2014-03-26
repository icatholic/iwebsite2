<?php
class Default_DbController extends Zend_Controller_Action
{
    public $model;
    public $unique;
    
    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->model = new Default_Model_Test();
    }

    public function findAction()
    {
        echo 'default index index';
    }
    
    public function findAllAction()
    {
        echo 'default index index';
    }
    
    public function insertAction() {
        
    }
        
    public function __destruct() {
        
    }


}

