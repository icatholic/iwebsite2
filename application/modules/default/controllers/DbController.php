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

    public function indexAction()
    {
        try {
//             $this->model = new Default_Model_Test();
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    public function findAction()
    {
        try {
            var_dump($this->model->find(array()));
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    public function findAllAction()
    {
        echo 'default index index';
    }

    public function insertAction()
    {}

    public function __destruct()
    {}
}

