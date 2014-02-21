<?php 
class iWebsite_Plugin_Front extends Zend_Controller_Plugin_Abstract
{
    
    public function preDispatch (Zend_Controller_Request_Abstract $request)
    {
        $config = Zend_Registry::get('config');
        $module = strtolower($request->getModuleName());
        
        // 判断是否为ajax请求(jquery extjs yui etc)，如果是自动关闭view 如果调用服务模块，也关闭layout和view
        if ($request->isXmlHttpRequest()) {
            $front = Zend_Controller_Front::getInstance();
            $front->setParam('noViewRenderer', true);

            $layout = Zend_Layout::getMvcInstance();
            $layout->disableLayout();
        }

    
        //采用扩展URL的方式重定向，用于二级目录
        $baseUrl = isset($config['resources']['frontController']['baseUrl']) ? $config['resources']['frontController']['baseUrl'] : '';
        if($baseUrl!=null && $baseUrl!='/') {
            $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
            $redirector->setPrependBase(false);
        }
    }
    
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $moduleName = strtolower($request->getModuleName());
        //这里可以程序控制显示何种样式
        $theme = 'default';
        
        //控制样式显示的路径
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        if (null === $viewRenderer->view) {
            $viewRenderer->init();
        }
        $view = $viewRenderer->view;
        $view->setBasePath(APPLICATION_PATH . "/modules/{$moduleName}/views/{$theme}");
        // base helpers
        $view->addHelperPath('iWebsite/View/Helper', 'iWebsite_View_Helper');
        
        //设定相应的layout
        $layout = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('layout');
        $layout->setLayoutPath(APPLICATION_PATH . "/modules/{$moduleName}/views/{$theme}/layout");
    }
    
    public function __destruct() {
        fastcgi_finish_request();
        if(rand(0,1000)==1) {
            $cache = Zend_Registry::get('cache');
            $pageCache = Zend_Registry::get('pageCache');
            $cache->clean(Zend_Cache::CLEANING_MODE_OLD);
            $pageCache->clean(Zend_Cache::CLEANING_MODE_OLD);
        }
    }
    
}