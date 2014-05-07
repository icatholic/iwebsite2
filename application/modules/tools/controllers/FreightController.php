<?php

class Tools_FreightController extends iWebsite_Controller_Action
{

    public function init()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $this->_config = Zend_Registry::get('config');
    }
    
    /**
     * 计算运价接口
     */
    public function calculateAction() {
        
    }
   
}