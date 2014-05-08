<?php

class Admin_IndexController extends iWebsite_Controller_Admin_Action
{

    public function init()
    {
        parent::init();
        $this->disableLayout();
        // 获得管理员ID
        $this->view->assign('admin_id', $_SESSION['admin_id']);
    }
    
    /* ------------------------------------------------------ */
    // -- 框架
    /* ------------------------------------------------------ */
    public function indexAction()
    {}
    
    /* ------------------------------------------------------ */
    // -- 顶部框架的内容
    /* ------------------------------------------------------ */
    public function topAction()
    {}
    
    /* ------------------------------------------------------ */
    // -- 左边的框架
    /* ------------------------------------------------------ */
    public function menuAction()
    {}
    
    /* ------------------------------------------------------ */
    // -- 拖动的帧
    /* ------------------------------------------------------ */
    public function dragAction()
    {}
    
    /* ------------------------------------------------------ */
    // -- 主窗口，起始页
    /* ------------------------------------------------------ */
    public function mainAction()
    {
        $modelOrder = new Admin_Model_Order();
        
        /* 已完成的订单 */
        $order['paid'] = $modelOrder->getPaidCount();
        
        /* 待付款的订单： */
        $order['await_pay'] = $modelOrder->getAwaitPayCount();
        
        $this->view->assign('order', $order);
        
        /* 系统信息 */
        $sys_info['os'] = PHP_OS;
        $sys_info['ip'] = $_SERVER['SERVER_ADDR'];
        $sys_info['web_server'] = $_SERVER['SERVER_SOFTWARE'];
        $sys_info['php_ver'] = PHP_VERSION;
        $sys_info['zlib'] = function_exists('gzclose') ? "是" : "否";
        $sys_info['safe_mode'] = (boolean) ini_get('safe_mode') ? "是" : "否";
        $sys_info['safe_mode_gid'] = (boolean) ini_get('safe_mode_gid') ? "是" : "否";
        $sys_info['timezone'] = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "无需设置";
        $sys_info['socket'] = function_exists('fsockopen') ? "是" : "否";
        $sys_info['mysql_ver'] = "5.1";
        $sys_info['gd'] = "2";
        $this->view->assign('sys_info', $sys_info);
        $this->_helper->layout()->enableLayout();
    }
}