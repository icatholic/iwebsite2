<?php

class Zend_View_Helper_ShowOrderShippingStatusSelect extends Zend_View_Helper_Abstract
{    
    public function showOrderShippingStatusSelect($name,
    									$value = "-1",
										$attribs = null,
										$listsep = "<br />\n")
	{
		/* 发货状态 */
		$list["-1"] = "请选择";
		$list["0"] = '未发货';
		$list["1"] = '配货中';
		$list["2"] = '已发货';
		$list["3"] = '收货确认';
		$list["4"] = '已发货(部分商品)';
		$list["5"] = '发货中';
		return $this->view->formSelect($name,$value,$attribs,$ss_list,$listsep);
    }  
}