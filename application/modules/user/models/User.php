<?php

class User_Model_User extends iWebsite_Plugin_Mongo
{
    protected $name = 'iUser';
    protected $dbName = 'user';
    
    //更新用户信息
    public function saveUser($uniqueValue,$name='',$mobile='',$id_card='',$tel = '',$address = '',$zip = '',$email='',$custom='',$others = array())
    {
        $arrayData = array();
        if($name)
            $arrayData['name'] = $name;
        if($mobile)
        	$arrayData['mobile'] = $mobile;
        if($id_card)
        	$arrayData['id_card'] = $id_card;
        if($tel)
        	$arrayData['tel'] = $tel;
        if($address)
        	$arrayData['address'] = $address;
        if($zip)
        	$arrayData['zip'] = $zip;
        if($email)
        	$arrayData['email'] = $email;
        if($custom)
        	$arrayData['custom'] = $custom;
        if(count($others))
            $arrayData['others'] = $others;
    	$this->update(array('unique'=>$uniqueValue),array('$set'=>$arrayData));
    }
}