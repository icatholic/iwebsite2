<?php

class Admin_Model_Goods extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinPay_Goods';

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
     * 根据商品号获取信息
     *
     * @param string $gid            
     * @return array
     */
    public function getInfoByGid($gid)
    {
        $query = array(
            'gid' => $gid
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
            $filter['cat_id'] = trim($input->cat_id);
            $filter['keyword'] = trim($input->keyword);
            $filter['is_on_sale'] = trim($input->is_on_sale);
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
        
        if (! empty($filter['keyword'])) {
            $where['keywords'] = new MongoRegex('/' . urldecode($filter['keyword']) . '/i');
        }
        
        if (! empty($filter['is_on_sale'])) {
            if($filter['is_on_sale'] == 1){ //上架
                $where['is_on_sale'] = true;
            }
            if($filter['is_on_sale'] == 2){ //下架
                $where['is_on_sale'] = false;
            }
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
            throw new Exception(sprintf("商品名已存在", stripslashes($name)), 1);
        }
    }

    public function checkStockNum($id, $stock_num)
    {
        if ($stock_num <= 0) {
            throw new Exception('商品库存数量错误');
        }
    }

    public function checkGoodsPrice($goods_price)
    {
        if ($goods_price <= 0) {
            throw new Exception('您输入了一个非法的价格。');
        }
    }

    public function checkGid($id, $gid)
    {
        /* 判断是否已经存在 */
        $query = array();
        $query['gid'] = $gid;
        if (! empty($id)) {
            $query['_id'] = array(
                '$ne' => myMongoId($id)
            );
        }
        
        $num = $this->count($query);
        if ($num > 0) {
            throw new Exception("您输入的货号已存在，请换一个");
        }
    }

    public function processInsertOrUpdate($input, $goodsRow = array())
    {
        try {
            $data = array();
            $onsale_time = 0;
            $offsale_time = 0;
            $is_on_sale = intval($input->is_on_sale);
            if ($is_on_sale) {
                $onsale_time = new MongoDate(strtotime($input->onsale_time));
                $offsale_time = new MongoDate(strtotime($input->offsale_time));
            }
            $data['gid'] = trim($input->gid);
            $data['name'] = trim($input->name);
            $data['prize'] = intval($input->prize);
            $data['unitname'] = trim($input->unitname);
            if ($_FILES['prize_pic']['error'] != UPLOAD_ERR_NO_FILE) {
                $data['prize_pic'] = $this->uploadPicture('prize_pic');
            }
            $data['stock_num'] = intval($input->stock_num);
            $data['is_on_sale'] = empty($is_on_sale) ? false : true;
            $data['onsale_time'] = $onsale_time;
            $data['offsale_time'] = $offsale_time;
            $composite_sku_no = implode(",", array_unique($input->composite_sku_no));
            $data['composite_sku_no'] = $composite_sku_no;
            $data['headline'] = trim($input->headline);
            $data['keywords'] = trim($input->getUnescaped('keywords'));
            $data['spec'] = trim($input->spec);
            $data['brief'] = trim($input->brief);
            $data['desc'] = trim($input->getUnescaped('desc'));
            $data['cat_id'] = trim($input->cat_id);
            $data['other_cat'] = $input->other_cat;
            $data['show_order'] = intval($input->show_order);
            $data['is_show'] = empty($input->is_show) ? false : true;
            $data['is_purchase_inner'] = empty($input->is_purchase_inner) ? false : true;
            $data['transport_fee_mode'] = intval($input->transport_fee_mode);
            $data['transport_fee'] = intval($input->transport_fee);
            $data['weight'] = floatval($input->weight);
            $data['volume'] = floatval($input->volume);
            
            if ($_FILES['gdetail_pic1']['error'] != UPLOAD_ERR_NO_FILE) {
                $data['gdetail_pic1'] = $this->uploadPicture('gdetail_pic1');
            }
            
            if ($_FILES['gparams_pic1']['error'] != UPLOAD_ERR_NO_FILE) {
                $data['gparams_pic1'] = $this->uploadPicture('gparams_pic1');
            }
            
            for ($i = 1; $i < 11; $i ++) {
                if ($_FILES['gpic' . $i]['error'] != UPLOAD_ERR_NO_FILE) {
                    $data['gpic' . $i] = $this->uploadPicture('gpic' . $i);
                }
            }
            
            $data['is_best'] = empty($input->is_best) ? false : true;
            $data['is_new'] = empty($input->is_new) ? false : true;
            $data['is_hot'] = empty($input->is_hot) ? false : true;
            
            $data['Freight_template'] = "";
            $data['Freight_unit'] = "";
            
            if (! empty($input->iFreight_template)) {
                $modelFreightGoods = new Tools_Model_Freight_Goods();
                $modelFreightGoods->configure($input->gid, "536c89864996196f198b458a", trim($input->iFreight_template));
            }
            
            if (empty($goodsRow) || empty($goodsRow['_id'])) {
                
                $data['order_num'] = 0;
                $data['purchase_num'] = 0;
                
                $this->insert($data);
            } else {
                $query['_id'] = $goodsRow['_id'];
                $this->update($query, array(
                    '$set' => $data
                ));
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function copy(array $oldgoodsRow)
    {
        // 商品信息
        $newgoodsRow = array();
        foreach ($oldgoodsRow as $key => $value) {
            $newgoodsRow[$key] = $value;
        }
        $newgoodsRow["gid"] = "";
        $newgoodsRow["name"] = $newgoodsRow["name"] . '-copy';
        
        return $newgoodsRow;
    }

    /**
     * 修改商品分类
     *
     * @param string $from_cat_id            
     * @param string $target_cat_id            
     */
    public function updateCategory($from_cat_id, $target_cat_id)
    {
        $where = array(
            "cat_id" => trim($from_cat_id)
        );
        $data = array(
            "cat_id" => trim($target_cat_id)
        );
        
        $this->update($where, array(
            '$set' => $data
        ));
    }

    /**
     * 某分类下的商品个数
     *
     * @param string $cat_id            
     */
    public function getNumByCategory($cat_id)
    {
        $query = array();
        $query['cat_id'] = $cat_id;
        $num = $this->count($query);
    }

    /**
     * 获取空行数据
     *
     * @return multitype:string number boolean
     */
    public function getEmptyRow()
    {
        // $datas = $this->getSchema();
        $row = array();
        $row['gid'] = "";
        $row['name'] = "";
        $row['prize'] = "";
        $row['unitname'] = "";
        $row['prize_pic'] = "";
        $row['stock_num'] = 9999;
        $row['is_on_sale'] = true;
        $row['onsale_time'] = new MongoDate();
        $row['offsale_time'] = new MongoDate(time() + 3600 * 24 * 30);
        $row['composite_sku_no'] = "";
        $row['composite_sku_no'] = explode(",", $row['composite_sku_no']);
        $row['headline'] = "";
        $row['keywords'] = "";
        $row['brief'] = "";
        $row['spec'] = "";
        $row['desc'] = "";
        $row['cat_id'] = "";
        $row['other_cat'] = array();
        $row['show_order'] = 1;
        $row['is_show'] = true;
        $row['is_purchase_inner'] = false;
        $row['transport_fee_mode'] = 0;
        $row['transport_fee'] = 0;
        $row['weight'] = 0;
        $row['volume'] = 0;
        
        $row['gpic1'] = "";
        $row['gpic2'] = "";
        $row['gpic3'] = "";
        $row['gpic4'] = "";
        $row['gpic5'] = "";
        $row['gpic6'] = "";
        $row['gpic7'] = "";
        $row['gpic8'] = "";
        $row['gpic9'] = "";
        $row['gpic10'] = "";
        $row['gdetail_pic1'] = "";
        $row['gparams_pic1'] = "";
        
        $row['is_best'] = false;
        $row['is_new'] = false;
        $row['is_hot'] = false;
        $row['order_num'] = 0;
        $row['purchase_num'] = 0;
        
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
}