<?php

class Zend_View_Helper_ShowOrderStatusSelect extends Zend_View_Helper_Abstract
{    
    public function showOrderStatusSelect($name,
    									$value = "-1",
										$attribs = null,
										$listsep = "<br />\n")
	{
		// 订单状态		
		$list = array();
		$list["-1"] = "请选择";
		$list["0"] = '待确认';
		$list["1"] = '待付款';
		$list["2"] = '待发货';
		$list["3"] = '已完成';
		$list["4"] = '付款中';
		$list["5"] = '取消';
		$list["6"] = '无效';
		$list["7"] = '退货';
		return $this->view->formSelect($name,$value,$attribs,$status_list,$listsep);
    }  
}