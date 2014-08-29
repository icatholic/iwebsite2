<?php

class Admin_Bootstrap extends Zend_Application_Module_Bootstrap
{

    protected function _initConst()
    {
        /* 订单状态 */
        defined('OS_UNCONFIRMED') || define('OS_UNCONFIRMED', 0); // 未确认
        defined('OS_CONFIRMED') || define('OS_CONFIRMED', 1); // 已确认
        defined('OS_CANCELED') || define('OS_CANCELED', 2); // 已取消
        defined('OS_INVALID') || define('OS_INVALID', 3); // 无效
        defined('OS_RETURNED') || define('OS_RETURNED', 4); // 退货
        defined('OS_SPLITED') || define('OS_SPLITED', 5); // 已分单
        defined('OS_SPLITING_PART') || define('OS_SPLITING_PART', 6); // 部分分单
        defined('OS_SHIPPED_PART') || define('OS_SHIPPED_PART', 6); // 已发货(部分商品)
        
        /* 配送状态 */
        defined('SS_UNSHIPPED') || define('SS_UNSHIPPED', 0); // 未发货
        defined('SS_SHIPPED') || define('SS_SHIPPED', 1); // 已发货
        defined('SS_RECEIVED') || define('SS_RECEIVED', 2); // 已收货
        defined('SS_PREPARING') || define('SS_PREPARING', 3); // 备货中
        defined('SS_SHIPPED_PART') || define('SS_SHIPPED_PART', 4); // 已发货(部分商品)
        defined('SS_SHIPPED_ING') || define('SS_SHIPPED_ING', 5); // 发货中(处理分单)
        
        /* 支付状态 */
        defined('PS_UNPAYED') || define('PS_UNPAYED', 0); // 未付款
        defined('PS_PAYING') || define('PS_PAYING', 1); // 付款中
        defined('PS_PAYED') || define('PS_PAYED', 2); // 已付款
        
        /* 维权状态 */
        defined('FDS_NONE') || define('FDS_NONE', 0); // 未维权
        defined('FDS_REQUEST') || define('FDS_REQUEST', 1); // 维权中
        defined('FDS_WAIT') || define('FDS_WAIT', 2); // 等待用户确认
        defined('FDS_FINISHED') || define('FDS_FINISHED', 3); // 已确认
        
        
        /* 综合状态 */
        defined('CS_AWAIT_PAY') || define('CS_AWAIT_PAY', 100); // 待付款：货到付款且已发货且未付款，非货到付款且未付款
        defined('CS_AWAIT_SHIP') || define('CS_AWAIT_SHIP', 101); // 待发货：货到付款且未发货，非货到付款且已付款且未发货
        defined('CS_FINISHED') || define('CS_FINISHED', 102); // 已完成：已确认、已付款、已发货
        
        /* 订单状态 */
        $list['os'][OS_UNCONFIRMED] = '未确认';
        $list['os'][OS_CONFIRMED] = '已确认';
        $list['os'][OS_CANCELED] = '<font color="red">取消</font>';
        $list['os'][OS_INVALID] = '<font color="red">无效</font>';
        $list['os'][OS_RETURNED] = '<font color="red">退货</font>';
        $list['os'][OS_SPLITED] = '已分单';
        $list['os'][OS_SPLITING_PART] = '部分分单';
        /* 配送状态 */
        $list['ss'][SS_UNSHIPPED] = '未发货';
        $list['ss'][SS_PREPARING] = '配货中';
        $list['ss'][SS_SHIPPED] = '已发货';
        $list['ss'][SS_RECEIVED] = '收货确认';
        $list['ss'][SS_SHIPPED_PART] = '已发货(部分商品)';
        $list['ss'][SS_SHIPPED_ING] = '发货中';
        /* 支付状态 */
        $list['ps'][PS_UNPAYED] = '未付款';
        $list['ps'][PS_PAYING] = '付款中';
        $list['ps'][PS_PAYED] = '已付款';
        
        /* 维权状态 */
        $list['fds'][FDS_NONE] = '未维权';
        $list['fds'][FDS_REQUEST] = '维权中';
        $list['fds'][FDS_FINISHED] = '已确认';
        
        Zend_Registry::set('INFO_LIST',$list);
    }
    
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

