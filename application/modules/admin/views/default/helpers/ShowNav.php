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
            
            //category start
            case "admin/category/list":
                $ur_here = '商品分类';
                $action_link = array('text' => '添加商品分类', 'href'=>'category/add');
                break;
            case "admin/category/add":
                $ur_here = '添加商品分类';
                $action_link = array('text' => '商品分类', 'href'=>'category/list');
                break;
            case "admin/category/edit":
                $ur_here = '编辑商品分类';
                $action_link = array('text' => '商品分类', 'href'=>'category/list/uselastfilter/1');
                break;
            case "admin/category/move":
                $ur_here = '转移商品';
                $action_link = array('text' => '商品分类', 'href'=>'category/list');
                break;
            //category end
                	
            //brand start
            case "admin/brand/list":
                $ur_here = '商品品牌';
                $action_link = array('text' => '添加品牌', 'href'=>'brand/add');
                break;
            case "admin/brand/add":
                $ur_here = '添加品牌';
                $action_link = array('text' => '商品品牌', 'href'=>'brand/list');
                break;
            case "admin/brand/edit":
                $ur_here = '编辑品牌';
                $action_link = array('text' => '商品品牌', 'href'=>'brand/list/uselastfilter/1');
                break;
            //brand end
            //goods start
            case "admin/goods/list":
                $ur_here = '商品列表';
                $action_link = array('text' => '添加商品', 'href'=>'goods/add');
                
                break;
            case "admin/goods/trash":
                $ur_here = '商品回收站';
                $action_link = array('text' => '商品列表', 'href'=>'goods/list');
                break;
            case "admin/goods/add":
                $ur_here = '添加商品';
                $action_link = array('text' => '商品列表', 'href'=>'goods/list');               
                break;
            case "admin/goods/edit":
                $ur_here = '编辑商品';
                $action_link = array('text' => '商品列表', 'href'=>'goods/list/uselastfilter/1');
                break;
            case "admin/goods/copy":
                $ur_here = '复制商品';
                $action_link = array('text' => '商品列表', 'href'=>'goods/list/uselastfilter/1');
                break;
            //goods end
            
            //sku start
            case "admin/sku/list":
                $ur_here = 'SKU列表';
                $action_link = array('text' => '添加SKU', 'href'=>'sku/add');
                break;
            case "admin/sku/add":
                $ur_here = '添加SKU';
                $action_link = array('text' => 'SKU列表', 'href'=>'sku/list');
                break;
            case "admin/sku/edit":
                $ur_here = '编辑SKU';
                $action_link = array('text' => 'SKU列表', 'href'=>'sku/list/uselastfilter/1');
                break;
            case "admin/sku/copy":
                $ur_here = '复制SKU';
                $action_link = array('text' => 'SKU列表', 'href'=>'sku/list/uselastfilter/1');
                break;
            //sku end

            //trace start
            case "admin/trace/list":
                $ur_here = '溯源列表';
                $action_link = array('text' => '添加溯源', 'href'=>'trace/add');
                break;
            case "admin/trace/add":
                $ur_here = '添加溯源';
                $action_link = array('text' => '溯源列表', 'href'=>'trace/list');
                break;
            case "admin/trace/edit":
                $ur_here = '编辑溯源';
                $action_link = array('text' => '溯源列表', 'href'=>'trace/list/uselastfilter/1');
                break;
            case "admin/trace/copy":
                $ur_here = '复制溯源';
                $action_link = array('text' => '溯源列表', 'href'=>'trace/list/uselastfilter/1');
                break;
            //trace end
            default:
                break;
        }
        
        /* 模板赋值 */
        $this->view->assign('ur_here', $ur_here);
        $this->view->assign('action_link', $action_link);
        $this->view->assign('action_link2', $action_link2);
    }
}