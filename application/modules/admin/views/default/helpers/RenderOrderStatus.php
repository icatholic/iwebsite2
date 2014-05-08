<?php
class Zend_View_Helper_RenderOrderStatus extends Zend_View_Helper_Abstract
{
    public function renderOrderStatus($order)
    {
    	$trade_state = "未付款";
    	if($order["trade_state"]==0){
    		$trade_state = "已付款";
    	}
    	
    	$is_refund = "未退款";
    	if($order["is_refund"] == "true"){
    		$is_refund = "已退款";
    	}
    	
    	$is_split = "未分账";
    	if($order["is_split"] == "true"){
    		$is_split = "已分账";
    	}
    	
        return "{$trade_state},{$is_refund},{$is_split}";
    }
}