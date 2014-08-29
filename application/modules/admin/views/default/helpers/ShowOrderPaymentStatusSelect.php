<?php

class Zend_View_Helper_ShowOrderPaymentStatusSelect extends Zend_View_Helper_Abstract
{    
    public function showOrderPaymentStatusSelect($name,
    									$value = "2",
										$attribs = null,
										$listsep = "<br />\n")
	{
		/* 付款状态 */	    
		$list["0"] = "请选择";		
		$list["1"] = '未付款';
		$list["2"] = '已付款';
		return $this->view->formSelect($name,$value,$attribs,$list,$listsep);
    }  
}