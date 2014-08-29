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
    	
    	$shipping = "未发货";
    	if($order["uma_shipping_status"] == SS_SHIPPED){
    	    $shipping = "已发货";
    	}
    	
    	$uma_order_status = "";
    	if($order["uma_order_status"] == OS_CANCELED){
    	    $uma_order_status = "已取消";
    	}
    	if($order["uma_order_status"] == OS_INVALID){
    	    $uma_order_status = "已无效";
    	}
    	if($order["uma_order_status"] == OS_RETURNED){
    	    $uma_order_status = "已退货";
    	}
    	
    	$feedback = "未维权";
    	if($order["uma_feedback_status"] == FDS_REQUEST){
    	    $feedback = "维权中";
    	}
    	if($order["uma_feedback_status"] == FDS_WAIT){
    	    $feedback = "等待用户确认";
    	}
    	if($order["uma_feedback_status"] == FDS_FINISHED){
    	    $feedback = "维权已确认";
    	}
        return trim("{$trade_state},{$is_refund},{$is_split},{$shipping},{$feedback},{$uma_order_status}",',');
    }
}