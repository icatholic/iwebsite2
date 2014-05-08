<?php
class Zend_View_Helper_RenderPrice extends Zend_View_Helper_Abstract
{
    public function renderPrice($price, $change_price = false)
    {
    	if(empty($price)) $price = 0;
    	$price = number_format($price/100, 2, '.', '');
        return $price;
    }
}