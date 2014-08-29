<?php

class Tools_Model_Freight_Campany extends iWebsite_Plugin_Mongo
{

    protected $name = 'iFreight_campany';

    protected $dbName = 'iFreight';

    public function getList()
    {
        $list = $this->findAll(array());
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