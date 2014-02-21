<?php

class Cronjob_ErrorController extends Zend_Controller_Action
{

    public function init()
    {
        
    }

    public function indexAction()
    {
        var_dump($this->_request->getParams());
        var_dump($_GET);
    }
    
}

