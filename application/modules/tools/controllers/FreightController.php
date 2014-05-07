<?php

class Tools_FreightController extends iWebsite_Controller_Action
{

    private $_price;

    public function init()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $this->_config = Zend_Registry::get('config');
        $this->_price = new Tools_Model_Freight_Price();
    }

    /**
     * 计算运价接口
     */
    public function calculateAction()
    {
        $products = trim($this->get('products', null));
        if (empty($products)) {
            echo $this->error(500, '产品信息不能为空');
            return false;
        }
        
        if (! isJson($products)) {
            echo $this->error(501, '$products信息必须是json格式');
            return false;
        }
        
        $products = json_decode($products, true);
        if (! is_array($products)) {
            echo $this->error(501, '$products信息必须是数组');
            return false;
        }
        
        foreach ($products as $product) {
            $this->_price->getPrice($template, $campany, $warehouse, $unit, $area, $number);
        }
    }

    /**
     * 格式化不符合要求的地理位置信息
     */
    public function formatAction()
    {
        try {
            $area = new Tools_Model_Freight_Area();
            $area->formatCode();
            echo "OK";
        } catch (Exception $e) {
            var_dump(exceptionMsg($e));
        }
    }
}