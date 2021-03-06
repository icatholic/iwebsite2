<?php

class Tools_Model_Freight_Area extends iWebsite_Plugin_Mongo
{

    protected $name = 'iFreight_area';

    protected $dbName = 'iFreight';

    /**
     * 通过地理信息获取
     *
     * @param int $code            
     */
    public function getParentByCode($code)
    {}

    /**
     * 规范化数据为6位标准数据
     */
    public function formatCode()
    {
        $areas = $this->findAll(array());
        foreach ($areas as $area) {
            $update = array();
            if (! empty($area['code'])) {
                $update['code'] = $area['code'] * pow(10, (6 - strlen(strval($area['code']))));
            }
            if (! empty($area['parent_code'])) {
                $update['parent_code'] = $area['parent_code'] * pow(10, (6 - strlen(strval($area['parent_code']))));
            }
            
            if (! empty($update)) {
                $this->update(array(
                    '_id' => $area['_id']
                ), array(
                    '$set' => $update
                ));
            }
        }
        
        return true;
    }

    public function getProvinces()
    {
        $areas = $this->findAll(array(
            'level' => 1
        ));
        return $areas;
    }

    public function getCitys($province)
    {
        $areas = $this->findAll(array(
            'parent_code' => $province,
            'level' => 2
        ));
        return $areas;
    }

    public function getAreas($city)
    {
        $areas = $this->findAll(array(
            'parent_code' => $city,
            'level' => 3
        ));
        return $areas;
    }

    public function getInfoByCode($code)
    {
        $info = $this->findOne(array(
            'code' => $code
        ));
        return $info;
    }
}