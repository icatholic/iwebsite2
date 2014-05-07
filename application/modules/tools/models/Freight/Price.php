<?php

class Tools_Model_Freight_Price extends iWebsite_Plugin_Mongo
{

    protected $name = 'iFreight_price';

    protected $dbName = 'iFreight';

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
        $info = $this->findAll(array(
            'template' => $template,
            'campany' => $campany,
            'warehouse'=>$warehouse,
            'unit'=>$unit
        ));
    }
}