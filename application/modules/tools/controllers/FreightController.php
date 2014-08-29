<?php

class Tools_FreightController extends iWebsite_Controller_Action
{

    private $_price;

    public function init()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $this->_config = Zend_Registry::get('config');
        $this->_price = new Tools_Model_Freight_Price();
    }

    /**
     * 计算运价接口
     */
    public function calculateAction()
    {
        $products = trim($this->get('products', null));
        if (empty($products)) {
            echo $this->error(500, '产品信息不能为空');
            return false;
        }
        
        if (! isJson($products)) {
            echo $this->error(501, '$products信息必须是json格式');
            return false;
        }
        
        $products = json_decode($products, true);
        if (! is_array($products)) {
            echo $this->error(501, '$products信息必须是数组');
            return false;
        }
        
        foreach ($products as $product) {
            $this->_price->getPrice($template, $campany, $warehouse, $unit, $area, $number);
        }
    }

    /**
     * 格式化不符合要求的地理位置信息
     */
    public function formatAction()
    {
        try {
            $area = new Tools_Model_Freight_Area();
            $area->formatCode();
            echo "OK";
        } catch (Exception $e) {
            var_dump(exceptionMsg($e));
        }
    }

    /**
     * 获取某个省下的城市接口
     */
    public function getCitysAction()
    {
        $province = intval($this->get('province', '0'));
        if (empty($province)) {
            echo $this->error(500, '省份信息不能为空');
            return false;
        }
        $modelFreightArea = new Tools_Model_Freight_Area();
        $provinceInfo = $modelFreightArea->getInfoByCode($province);
        if (empty($provinceInfo)) {
            echo $this->error(501, '省份不存在');
            return false;
        }
        $cityList = $modelFreightArea->getCitys($province);
        if (! empty($cityList)) {
            foreach ($cityList as &$city) {
                if ($city['name'] == "市辖区") {
                    $city['name'] = $provinceInfo['name'];
                }
            }
        }
        echo $this->result("获取成功", $cityList);
        return true;
    }

    /**
     * 获取某个城市的县或区接口
     */
    public function getAreasAction()
    {
        $city = intval($this->get('city', '0'));
        if (empty($city)) {
            echo $this->error(500, '城市信息不能为空');
            return false;
        }
        $modelFreightArea = new Tools_Model_Freight_Area();
        $cityInfo = $modelFreightArea->getInfoByCode($city);
        if (empty($cityInfo)) {
            echo $this->error(501, '城市不存在');
            return false;
        }
        $areaList = $modelFreightArea->getAreas($city);
        echo $this->result("获取成功", $areaList);
        return true;
    }

    public function getAllAction()
    {
        $modelFreightArea = new Tools_Model_Freight_Area();
        $areaList = $modelFreightArea->findAll(array(), array(), array(
            'code' => true,
            'level' => true,
            'name' => true,
            'parent_code' => true
        ));
        foreach ($areaList as $key => &$value) {
            unset($value['_id']);
        }
        echo json_encode($areaList);
        return true;
    }

    public function getAll2Action()
    {
        $modelFreightArea = new Tools_Model_Freight_Area();
        $areaList = $modelFreightArea->findAll(array(), array(
            'level' => 1
        ), array(
            'code' => true,
            'level' => true,
            'name' => true,
            'parent_code' => true
        ));
        $list = array();
        $provinces = array();
        $citys = array();
        $areas = array();
        
        foreach ($areaList as $key => &$value) {
            unset($value['_id']);
            
            if ($value['level'] == 1) { // 省
                $provinces['code:' . $value['code']]['parent_code'] = $value['parent_code'];
                $provinces['code:' . $value['code']]['code'] = $value['code'];
                $provinces['code:' . $value['code']]['name'] = $value['name'];
                $provinces['code:' . $value['code']]['citys'] = array();
            }
            if ($value['level'] == 2) { // 市或县
                $citys['code:' . $value['code']]['parent_code'] = $value['parent_code'];
                $citys['code:' . $value['code']]['code'] = $value['code'];
                $citys['code:' . $value['code']]['name'] = $value['name'];
                $citys['code:' . $value['code']]['areas'] = array();
                $provinces['code:' . $value['parent_code']]['citys']['code:' . $value['code']] = $citys['code:' . $value['code']];
            }
            if ($value['level'] == 3) { // 区或县
                $areas['code:' . $value['code']]['parent_code'] = $value['parent_code'];
                $areas['code:' . $value['code']]['code'] = $value['code'];
                $areas['code:' . $value['code']]['name'] = $value['name'];
                $citys['code:' . $value['parent_code']]['areas']['code:' . $value['code']] = $areas['code:' . $value['code']];
                $parent_code = $citys['code:' . $value['parent_code']]['parent_code'];
                $provinces['code:' . $parent_code]['citys']['code:' . $value['parent_code']] = $citys['code:' . $value['parent_code']];
            }
        }
        
        //echo json_encode($provinces);
        //die();
        
        $i = 0;
        foreach ($provinces as $province) {            
            $citys = array();
            $j =0;
            foreach ($province['citys'] as $city) {
                $citys[$j]['n'] = $city['name'] . "|" . $city['code'];
                foreach ($city['areas'] as $area) {
                    $citys[$j]['a'][] = $area['name'] . "|" . $area['code'];
                }
                $j++;
            }
            $list[$i]['n'] = $province['name'] . "|" . $province['code'];
            $list[$i]['c'] = $citys; 
            $i++;           
        }
        echo json_encode($list);
        return true;
    }
}