<?php
class Zend_View_Helper_GetControllerUrl extends Zend_View_Helper_Abstract
{
    /**
     * Retrieve the base url
     *
     * @return string
     */
    public function getControllerUrl()
    {
        $moduleName = Zend_Controller_Front::getInstance()->getRequest()-> getModuleName();
        $controllerName = Zend_Controller_Front::getInstance()->getRequest()-> getControllerName();
        $path = "/".$moduleName."/".$controllerName;
        return $this->view->serverUrl($path);
    }
}