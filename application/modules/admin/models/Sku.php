<?php

class Admin_Model_Sku extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinPay_Sku';

    protected $dbName = 'weixinshop';

    /**
     * 根据ID获取信息
     *
     * @param string $id            
     * @return array
     */
    public function getInfoById($id)
    {
        $query = array(
            '_id' => myMongoId($id)
        );
        $info = $this->findOne($query);
        return $info;
    }

    /**
     * 根据SKU编号获取信息
     *
     * @param string $no            
     * @return array
     */
    public function getInfoByNo($no)
    {
        $query = array(
            'no' => $no
        );
        $info = $this->findOne($query);
        return $info;
    }

    /**
     * 获取订单列表信息
     *
     * @param Zend_Filter_Input $input            
     * @return array
     */
    public function getList(Zend_Filter_Input $input)
    {
        $pager = new Admin_Model_Pager();
        $result = $pager->get_filter();
        
        if ($result === false) {
            /* 过滤信息 */
            $filter['name'] = trim($input->name);
            $filter['sort_by'] = trim($input->sort_by);
            $filter['sort_order'] = trim($input->sort_order);
            /* 分页大小 */
            $filter = $pager->getPage($filter, $input);
        } else {
            $filter = $result['filter'];
        }
        
        // 根据Filter获取查询条件
        $where = $this->getConditionByFilter($filter);
        
        $sort = array();
        $sort[$filter['sort_by']] = ('DESC' == $filter['sort_order']) ? - 1 : 1;
        
        /* 记录总数 */
        $list = $this->find($where, $sort, $filter['start'], $filter['page_size']);
        $filter['record_count'] = $list['total'];
        
        /* page 总数 */
        $filter = $pager->setPage($filter, $input);
        
        $pager->set_filter($filter);
        
        return array(
            'data' => $list['datas'],
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        );
    }

    /**
     * 根据画面条件获取订单查询条件
     *
     * @param array $filter            
     * @return array
     */
    public function getConditionByFilter($filter)
    {
        $where = array();
        if (! empty($filter['cat_id'])) {
            $where['cat_id'] = $filter['cat_id'];
        }
        
        if (! empty($filter['name'])) {
            $where['name'] = new MongoRegex('/' . urldecode($filter['name']) . '/i');
        }
        return $where;
    }

    public function checkName($id, $name)
    {
        /* 判断是否已经存在 */
        $query = array();
        $query['name'] = $name;
        if (! empty($id)) {
            $query['_id'] = array(
                '$ne' => myMongoId($id)
            );
        }
        $num = $this->count($query);
        if ($num > 0) {
            throw new Exception(sprintf("SKU名已存在", stripslashes($name)), 1);
        }
    }

    public function checkNo($id, $no)
    {
        /* 判断是否已经存在 */
        $query = array();
        $query['no'] = $no;
        if (! empty($id)) {
            $query['_id'] = array(
                '$ne' => myMongoId($id)
            );
        }
        
        $num = $this->count($query);
        if ($num > 0) {
            throw new Exception("您输入的SKU编号已存在，请换一个");
        }
    }

    public function checkPrice($price)
    {
        if ($price <= 0) {
            throw new Exception('您输入了一个非法的价格。');
        }
    }

    public function processInsertOrUpdate($input, $skuRow = array())
    {
        try {
            $data = array();
            $data['no'] = trim($input->no);
            $data['name'] = trim($input->name);
            $data['prize'] = intval($input->prize);
            if (empty($skuRow) || empty($skuRow['_id'])) {
                $this->insert($data);
            } else {
                $query['_id'] = $skuRow['_id'];
                $this->update($query, array(
                    '$set' => $data
                ));
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function copy(array $oldSkuRow)
    {
        // SKU信息
        $newSkuRow = array();
        foreach ($oldSkuRow as $key => $value) {
            $newSkuRow[$key] = $value;
        }
        $newSkuRow["no"] = "";
        $newSkuRow["name"] = $newSkuRow["name"] . '-copy';
        
        return $newSkuRow;
    }

    /**
     * 获取空行数据
     *
     * @return array
     */
    public function getEmptyRow()
    {
        $row = array();
        $row['no'] = "";
        $row['name'] = "";
        $row['prize'] = 1;
        return $row;
    }

    /**
     * 上传图片
     *
     * @param string $fieldName            
     * @return string
     */
    protected function uploadPicture($fieldName)
    {
        $picture = $this->uploadFile($fieldName);
        return $picture[$fieldName]['_id']['$id'];
    }

    /**
     * 根据组合sku编号获取信息
     */
    public function getListByCompositeSkuNo($skuList, $composite_sku_no)
    {
        $skuIdList = explode(",", $composite_sku_no);
        $list = array();
        foreach ($skuIdList as $skuNo) {
            if (key_exists($skuNo, $skuList)) {
                $list[$skuNo] = $skuList[$skuNo];
            }
        }
        return $list;
    }

    /**
     * 获取所有信息
     */
    public function getAllList()
    {
        $query = array();
        $list = $this->findAll($query);
        $skuList = array();
        if (! empty($list)) {
            foreach ($list as $sku) {
                $skuList[$sku['no']] = $sku;
            }
        }
        return $skuList;
    }
}