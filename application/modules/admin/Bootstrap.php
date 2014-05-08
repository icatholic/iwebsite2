<?php

class Admin_Bootstrap extends Zend_Application_Module_Bootstrap
{
    // 控制前段显示模板的插件
    protected function _initPlugin()
    {
        $_front = Zend_Controller_Front::getInstance();
        // 注册前端控制插件
        $_front->registerPlugin(new iWebsite_Plugin_Admin());
    }
    
    protected function _initView()
    {
        // 支持不同域名多模板操作
    }
}

