<?php

class Zend_View_Helper_ShowOrderStatusSelect extends Zend_View_Helper_Abstract
{    
    public function showOrderStatusSelect($name,
    									$value = "0",
										$attribs = null,
										$listsep = "<br />\n")
	{
		// 订单状态		
		$list = array();
		$list["0"] = "请选择";
		$list["1"] = '取消';
		$list["2"] = '无效';
		$list["3"] = '退货';
		return $this->view->formSelect($name,$value,$attribs,$list,$listsep);
    }  
}