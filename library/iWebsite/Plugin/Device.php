<?php
/**
 * 判断是否为移动设备
 */

class iWebsite_Plugin_Device extends Zend_Controller_Plugin_Abstract {

    public function preDispatch (Zend_Controller_Request_Abstract $request) {
        $module = strtolower($request->getModuleName());
        
        $detect = new Mobile_Detect();
        if($detect->isMobile()) { //如果是手机设备
            /**
             * 请针对你的项目编写此部分的逻辑
             * 例如重置module等
             * $request->setModuleName('m');
             */

        }
        elseif($detect->isTablet()) { //如果是平板设备
            
        }
        else { //如果是PC设备
            
        }
        
        /**
         if($module==='default') {
        	$detect = new Mobile_Detect();
        	if($detect->isMobile()) {
        		$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        		$redirector->direct('index','index','m');
        	}
    	 }
        */
    }
    
}