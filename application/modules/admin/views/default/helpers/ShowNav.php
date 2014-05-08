<?php

class Zend_View_Helper_ShowNav extends Zend_View_Helper_Abstract
{

    public function showNav()
    {
        $this->processNav();
        return $this->view->render('partials/nav.phtml');
    }

    private function processNav()
    {
        $font = Zend_Controller_Front::getInstance();
        $request = $font->getRequest();
        $moduleName = $request->getModuleName();
        $controllerName = $request->getControllerName();
        $ActionName = $request->getActionName();
        
        $ur_here = "";
        $action_link = array();
        $action_link2 = array();
        $key = $moduleName . "/" . $controllerName . "/" . $ActionName;
        
        switch ($key) {
            // order start
            case "admin/order/list":
                $ur_here = '订单列表';
                $action_link = array(
                    'text' => '订单查询',
                    'href' => 'order/orderquery'
                );
                break;
            case "admin/order/orderquery":
                $ur_here = '订单查询';
                $action_link = array(
                    'text' => '订单列表',
                    'href' => 'order/list'
                );
                break;
            case "admin/order/add":
                $ur_here = '添加订单';
                break;
            case "admin/order/edit":
                $ur_here = '编辑订单';
                $action_link = array(
                    'text' => '订单列表',
                    'href' => 'order/list/uselastfilter/1'
                );
                break;
            case "admin/order/info":
                $ur_here = '订单信息';
                $action_link = array(
                    'text' => '订单列表',
                    'href' => 'order/list/uselastfilter/1'
                );
                break;
            case "admin/order/templates":
                $ur_here = '编辑订单打印模板';
                $action_link = array(
                    'text' => '订单列表',
                    'href' => 'order/list'
                );
                break;
            case "admin/order/operate":
                $action = $this->view->action;
                $ur_here = '订单操作：' . $action;
                break;
            case "admin/order/batch":
                $action_link = array(
                    'text' => '订单列表',
                    'href' => 'order/list'
                );
                break;
            // order end
            default:
                break;
        }
        
        /* 模板赋值 */
        $this->view->assign('ur_here', $ur_here);
        $this->view->assign('action_link', $action_link);
        $this->view->assign('action_link2', $action_link2);
    }
}