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
        try {
            var_dump($this->model->find(array()));
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    public function findAllAction()
    {
        try {
            var_dump($this->model->findAll(array()));
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    public function insertAction()
    {
        try {
            var_dump($this->model->insert(array(
                'textfield' => time()
            )));
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    public function __destruct()
    {}
}

