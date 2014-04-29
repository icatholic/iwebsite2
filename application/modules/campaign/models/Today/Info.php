<?php

class Campaign_Model_Today_Info extends iWebsite_Plugin_Mongo
{

    protected $name = 'today_info';

    protected $dbName = 'default';

    /**
     * 根据状态获取信息
     *
     * @param string $st            
     * @param int $day            
     * @return array
     */
    public function getInfoBySt($st, $day = null)
    {
        $d = 0;
        switch ($st) {
            case '卵泡期':
                $d = 10;
                break;
            case '月经期间':
                $d = 10;
                break;
        }
        
        if ($day == null) {
            $query = array(
                'type' => $st
            );
        } else {
            if ($d > 0 && $day >= $d) {
                $day = $day % $d;
            }
            
            if ($day == 1 || $day == 0) {
                $day = 2;
            }
            
            if ($day == 9) {
                $day = 8;
            }
            
            $query = array(
                'type' => $st,
                'day' => $day
            );
        }
        $info = $this->findOne($query);
        fb($query, 'LOG');
        return $info;
    }

    /**
     * 获取提示信息
     *
     * @param string $type            
     * @param int $day            
     * @return string
     */
    public function getInfoByType($type, $day)
    {
        switch ($type) {
            case 'from':
                $d = 7;
                break;
            case 'to':
                $d = 23;
            default:
                $d = 5;
        }
        
        if ($day > $d) {
            $day = $day % $d;
        }
        
        if ($day == 0)
            $day = 1;
        
        $info = $this->findOne(array(
            'type' => $type,
            'day' => $day
        ));
        
        if ($info == null)
            var_dump(array(
                'type' => $type,
                'day' => $day
            ));
        return $info;
    }

    /**
     * 根据ID查询产品信息
     *
     * @param MongoId $_id            
     */
    public function getInfoById($_id)
    {
        if (! ($_id instanceof MongoId))
            $_id = myMongoId($_id);
        
        return $this->findOne(array(
            '_id' => $_id
        ));
    }
}