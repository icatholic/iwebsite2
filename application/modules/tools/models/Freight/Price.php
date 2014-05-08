<?php

class Tools_Model_Freight_Price extends iWebsite_Plugin_Mongo
{

    protected $name = 'iFreight_price';

    protected $dbName = 'iFreight';

    private $_priceList = null;

    private $_priceKey = null;

    public function getPriceList($template, $campany, $warehouse, $unit)
    {
        $priceKey = getClassMethodArgumentCacheKey($this, 'getPriceList');
        
        if (empty($this->_priceList) || $this->_priceKey != $priceKey) {
            $this->_priceList = $this->findAll(array(
                'template' => $template,
                'campany' => $campany,
                'warehouse' => $warehouse,
                'unit' => $unit
            ), array(
                'first' => - 1,
                'add' => - 1
            ), array(
                'target_province' => true,
                'target_city' => true,
                'target_county' => true,
                'first' => true,
                'add' => true
            ));
        }
        
        return $this->_priceList;
    }

    /**
     * 计算单品的运价
     *
     * @param string $template            
     * @param string $campany            
     * @param string $warehouse            
     * @param int $unit            
     * @param int $area            
     * @param int $number            
     */
    public function getPrice($template, $campany, $warehouse, $unit, $area, $number)
    {
        $priceList = $this->getPriceList($template, $campany, $warehouse, $unit);
        if (! empty($priceList)) {
            foreach ($priceList as $price) {
                $match = false;
                if (strpos($area, $price['target_county']) !== false) {
                    $match = true;
                } elseif (strpos($area, $price['target_city']) !== false) {
                    $match = true;
                } elseif (strpos($area, $price['target_province']) !== false) {
                    $match = true;
                }
                
                if ($match)
                    return $this->calculate($number, $price['first'], $price['add']);
            }
        }
        
        return false;
    }

    /**
     * 计算运费
     *
     * @param int $number            
     * @param double $first            
     * @param double $add            
     * @return number
     */
    public function calculate($number, $first, $add)
    {
        $number = (int) $number;
        if ($number == 0) {
            return 0;
        }
        return $first + $add * ($number - 1);
    }
}