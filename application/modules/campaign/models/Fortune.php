<?php

class Campaign_Model_Fortune extends iWebsite_Plugin_Mongo
{

    protected $name = 'fortune';

    protected $dbName = 'default';

    /**
     * 随机获取一条某种类型的运势
     * @param int $type
     * @return mixed
     */
    public function getRandomContentByType($type)
    {
        $datas = $this->findAll(array(
            'type' => $type
        ));
        $random = $datas[array_rand($datas)];
        return $random['content'];
    }
}