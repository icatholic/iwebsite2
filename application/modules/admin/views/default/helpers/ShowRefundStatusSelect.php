<?php

class Zend_View_Helper_ShowRefundStatusSelect extends Zend_View_Helper_Abstract
{    
    public function showRefundStatusSelect($name,
    									$value = "0",
										$attribs = null,
										$listsep = "<br />\n")
	{
		/* 退款状态 */
		$list["0"] = "请选择";
		$list["1"] = '未退款';
		$list["2"] = '已退款';		
		return $this->view->formSelect($name,$value,$attribs,$list,$listsep);
    }  
}