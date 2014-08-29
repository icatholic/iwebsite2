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
        $now = date("Ymd");
        $year = intval(substr($now, 0, 4));
        $month = intval(substr($now, 4, 2));
        $day = intval(substr($now, 6, 2));
        //die(date('Y-m-d H:i:s',$start)."|".date('Y-m-d H:i:s',$end));
        // 今日订单
        $start = mktime(0, 0, 0, $month, $day, $year);
        $end = mktime(0, 0, 0, $month, $day + 1, $year) - 1;        
        $start = new MongoDate($start);
        $end = new MongoDate($end);
        /* 未支付订单数(今日) */
        $order['daily_unpay'] = $modelOrder->getUnPayCount($start, $end);
        /* 已支付,待发货订单数(今日) */
        $order['daily_paid_unship'] = $modelOrder->getPaidUnshipCount($start, $end);
        /* 已发货订单数(今日) */
        $order['daily_shipped'] = $modelOrder->getShippedCount($start, $end);
        /* 已取消订单数(今日) */
        $order['daily_cancel'] = $modelOrder->getCanceledCount($start, $end);
        
        // 本周订单
        $week = date('N'); // 1（表示星期一）到 7（表示星期天）
        $start = mktime(0, 0, 0, $month, $day - $week, $year);
        $end = mktime(0, 0, 0, $month, $day + 7 - $week, $year) - 1;
        $start = new MongoDate($start);
        $end = new MongoDate($end);
        /* 未支付订单数(本周) */
        $order['weekly_unpay'] = $modelOrder->getUnPayCount($start, $end);
        /* 已支付,待发货订单数(本周) */
        $order['weekly_paid_unship'] = $modelOrder->getPaidUnshipCount($start, $end);
        /* 已发货订单数(本周) */
        $order['weekly_shipped'] = $modelOrder->getShippedCount($start, $end);
        /* 已取消订单数(本周) */
        $order['weekly_cancel'] = $modelOrder->getCanceledCount($start, $end);
        
        // 本月订单
        $start = mktime(0, 0, 0, $month, 1, $year);
        $end = mktime(0, 0, 0, $month + 1, 1, $year) - 1;
        
        $start = new MongoDate($start);
        $end = new MongoDate($end);
        /* 未支付订单数(本月) */
        $order['monthly_unpay'] = $modelOrder->getUnPayCount($start, $end);
        /* 已支付,待发货订单数(本月) */
        $order['monthly_paid_unship'] = $modelOrder->getPaidUnshipCount($start, $end);
        /* 已发货订单数(本月) */
        $order['monthly_shipped'] = $modelOrder->getShippedCount($start, $end);
        /* 已取消订单数(本月) */
        $order['monthly_cancel'] = $modelOrder->getCanceledCount($start, $end);
        
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
        $this->enableLayout();
    }
}