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
                'template_name' => $template,
                'freight_campany' => $campany,
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
     * @param array $area            
     * @param array $info            
     */
    public function getPrice($template, $campany, $warehouse, $unit, array $area, $info)
    {
        $priceList = $this->getPriceList($template, $campany, $warehouse, $unit);
        if (! empty($priceList)) {
            foreach ($priceList as $price) {
                $match = false;
                if (! empty($area['target_county']) && ($area['target_county'] == $price['target_county'])) {
                    $match = true;
                } elseif (! empty($area['target_city']) && ($area['target_city'] == $price['target_city'])) {
                    $match = true;
                } elseif (! empty($area['target_province']) && ($area['target_province'] == $price['target_province'])) {
                    $match = true;
                }
                
                if ($match) {
                    if ($unit == "number") { // 按件数
                        $value = $info['number'];
                    } else
                        if ($unit == "weight") { // 按重量g
                        $value = $info['weight']/1000;
                    } else
                        if ($unit == "volume") { // 按体积m3
                        $value = $info['volume'];
                    }
                    
                    return $this->calculate($value, $price['first'], $price['add']);
                }
            }
        }
        
        return false;
    }

    /**
     * 计算运费
     *
     * @param double $value            
     * @param double $first            
     * @param double $add            
     * @return number
     */
    public function calculate($value, $first, $add)
    {
        $value = ceil($value);
        if (empty($value)) {
            return 0;
        }
        return $first + $add * ($value - 1);
    }
}