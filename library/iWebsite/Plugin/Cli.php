<?php
class iWebsite_Plugin_Cli extends Zend_Controller_Plugin_Abstract {
    
    public function preDispatch (Zend_Controller_Request_Abstract $request) {
        
        //在CLI模式下关闭视图和布局
        $front = Zend_Controller_Front::getInstance();
        $front->setParam('noViewRenderer', true);
        
        $layout = Zend_Layout::getMvcInstance();
        if($layout!=null) {
            $layout->disableLayout();
        }
    }

}