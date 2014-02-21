<?php
class Integral_Model_IntegralDetailTrack extends iWebsite_Plugin_Mongo
{
    protected $name = 'integral_detail_track';
    protected $dbName = 'integral';
    
    //处理
    public function handle($integralIdentity,$get_by,$integral)
    {
    	$data=array();
    	$data['integral_identity_id']=$integralIdentity['_id'];
    	$data['integral']=$integral;
    	$data['get_time']=date('Y-m-d H:i:s');
    	$data['get_by']=$get_by;
    	$info=$this->insert($data);
    	return $info;
    }
}