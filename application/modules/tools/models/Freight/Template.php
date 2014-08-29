<?php

class Tools_Model_Freight_Template extends iWebsite_Plugin_Mongo
{

    protected $name = 'iFreight_template';

    protected $dbName = 'iFreight';
    
    public function getList($key = "")
    {
        $ret = $this->findAll(array());
        $list = array();
        if (! empty($ret)) {
            foreach ($ret as $item) {
                if ($key == "name") {
                    $list[$item['name']] = $item;
                } else {
                    $list[myMongoId($item['_id'])] = $item;
                }
            }
        }
        return $list;
    }
    
    public function getInfoById($id)
    {
        $info = $this->findOne(array(
            '_id' => myMongoId($id)
        ));
        return $info;
    }

}