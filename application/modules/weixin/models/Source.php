<?php

class Weixin_Model_Source extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixin_source';

    protected $dbName = 'weixin';
    
    protected $secondary = true;

    private $_params = array();

    /**
     * 获取字段列表
     *
     * @return array
     */
    public function getFields()
    {
        return array_keys($this->getSchema());
    }

    /**
     * 获取信息接收信息
     *
     * @return array
     */
    public function revieve()
    {
        $postStr = file_get_contents('php://input');
        $datas = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $datas = $this->object2array($datas);
        
        if (isset($datas['Event']) && $datas['Event'] === 'LOCATION') {
            $Latitude = isset($datas['Latitude']) ? floatval($datas['Latitude']) : 0;
            $Longitude = isset($datas['Longitude']) ? floatval($datas['Longitude']) : 0;
            $datas['coordinate'] = array(
                $Latitude,
                $Longitude
            );
        }
        
        if (isset($datas['MsgType']) && $datas['MsgType'] === 'location') {
            $Location_X = isset($datas['Location_X']) ? floatval($datas['Location_X']) : 0;
            $Location_Y = isset($datas['Location_Y']) ? floatval($datas['Location_Y']) : 0;
            $datas['coordinate'] = array(
                $Location_X,
                $Location_Y
            );
        }
        
        $this->ensureIndex(array(
            'coordinate' => '2d'
        ));
        
        return $datas;
    }

    /**
     * 转化方法 很重要
     *
     * @param object $object
     */
    public function object2array($object)
    {
        //return @json_decode(@json_encode($object), 1);
        return @json_decode(preg_replace('/{}/', '""', @json_encode($object)), 1);
    }
}