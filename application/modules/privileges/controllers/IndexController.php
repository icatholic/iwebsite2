<?php

class Privileges_IndexController extends Zend_Controller_Action
{

    private $_resource;

    public function init ()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_resource = new Privileges_Model_Resource();
    }

    /**
     * @name 获取资源列表
     */
    public function indexAction ()
    {
        try {
            $this->_resource->remove(array());
            $assetsList = new iWebsite_Helper_AssetsList();
            $all = $assetsList->getList();
            foreach ($all as $moduleName => $module) {
                if (! empty($module)) {
                    foreach ($module as $controller => $actions) {
                        if (! empty($actions)) {
                            foreach ($actions as $action) {
                                $object = array();
                                $object['name'] = $action['name'];
                                $object['alias'] = $controller . '::' .
                                         $action['method'];
                                $object['resource'] = $controller;
                                $object['privilege'] = $action['method'];
                                $this->_resource->insertAsync($object);
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function testAction ()
    {
        echo 'ok';
    }
}