<?php

class Zend_View_Helper_ShowOrderShippingStatusSelect extends Zend_View_Helper_Abstract
{    
    public function showOrderShippingStatusSelect($name,
    									$value = "0",
										$attribs = null,
										$listsep = "<br />\n")
	{
		/* 发货状态 */
		$list["0"] = "请选择";
		$list["1"] = '未发货';
		$list["2"] = '已发货';
		return $this->view->formSelect($name,$value,$attribs,$list,$listsep);
    }  
}