<?php

class Weixinshop_Model_Order extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinPay_Order';

    protected $dbName = 'weixinshop';

    /**
     * 默认排序
     */
    public function getDefaultSort()
    {
        $sort = array(
            '_id' => - 1
        );
        return $sort;
    }

    /**
     * 默认查询条件
     */
    public function getQuery()
    {
        $query = array();
        return $query;
    }

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
     * 根据商户订单号获取信息
     *
     * @param string $out_trade_no            
     * @return array
     */
    public function getInfoByOutTradeNo($out_trade_no)
    {
        $query = array(
            'out_trade_no' => $out_trade_no
        );
        $info = $this->findOne($query);
        return $info;
    }

    public function getProductFee(array $goodsList)
    {
        $product_fee = 0;
        foreach ($goodsList as $goods) {
            $product_fee += $goods['num'] * $goods['prize'];
        }
        return $product_fee; // $prize * $gnum;
    }

    public function getTotalFee($transport_fee, $product_fee)
    {
        return $transport_fee + $product_fee;
    }

    /**
     * 生成订单
     *
     * @param string $OpenId            
     * @param array $goodsList            
     * @param string $ProductId            
     * @param string $body            
     * @param string $attach            
     * @param string $goods_tag            
     * @param int $transport_fee            
     * @param string $consignee_province            
     * @param string $consignee_city            
     * @param string $consignee_area            
     * @param string $consignee_name            
     * @param string $consignee_address            
     * @param string $consignee_tel            
     * @param string $consignee_zipcode            
     * @param string $freight_campany            
     * @param int $fee_type            
     * @param string $input_charset            
     * @param string $bank_type            
     * @param string $signType            
     * @throws Exception
     * @return array
     */
    public function createOrder($OpenId, array $goodsList, $ProductId = "", $body = "", $attach = "", $goods_tag = "", $transport_fee = 0, $consignee_province = "", $consignee_city = "", $consignee_area = "", $consignee_name = "", $consignee_address = "", $consignee_tel = "", $consignee_zipcode = "", $freight_campany = "", $fee_type = 1, $input_charset = "UTF-8", $bank_type = "WX", $signType = 'SHA1')
    {
        $data = array();
        // 商户系统内部的订单号
        $out_trade_no = $this->createOutTradeNo();
        $data['out_trade_no'] = $out_trade_no;
        
        // 微信号
        $data['OpenId'] = $OpenId;
        if (empty($ProductId)) {
            foreach ($goodsList as $gid => $goods) {
                $ProductId .= ($gid . ","); // 商品号
            }
        }
        // 商品号
        $data['ProductId'] = trim($ProductId, ",");
        
        if (empty($body)) {
            foreach ($goodsList as $gid => $goods) {
                $body .= ($goods['name'] . ","); // 商品详细
            }
        }
        
        // 商品详细
        $data['body'] = mb_substr(trim($body, ","), 0, 128, 'utf-8');
        // $data['body'] = trim($body, ",");
        
        // 附加数据
        $data['attach'] = $attach;
        // 商品标记，优惠券时可能用到
        $data['goods_tag'] = $goods_tag;
        // 购买数量
        $gnum = 0;
        foreach ($goodsList as $goods) {
            $gnum += $goods['num'];
        }
        $data['gnum'] = $gnum;
        
        // 商品单价
        $data['gprize'] = 0;
        
        // 物流费用，单位为分
        $data['transport_fee'] = $transport_fee;
        // 商品费用，单位为分
        $data['product_fee'] = $this->getProductFee($goodsList);
        
        // 订单总金额
        $data['total_fee'] = $this->getTotalFee($data['transport_fee'], $data['product_fee']);
        
        // 订单生成的机器IP
        $ip = getIp();
        $data['client_ip'] = $ip;
        $data['spbill_create_ip'] = ($ip);
        // 订单生成时间， 格式为yyyyMMddHHmmss
        $data['time_start'] = date("YmdHis");
        $data['uma_time_start'] = new MongoDate();
        // 订单失效时间， 格式为yyyyMMddHHmmss
        $data['time_expire'] = "";
        $data['uma_time_expire'] = new MongoDate(0);
        // 通知URL
        $config = Zend_Registry::get('config');
        $notify_url = $config['iWeixin']['pay']['notify_url'];
        $data['notify_url'] = $notify_url;
        
        // 现金支付币种,取值：1（人民币）
        $data['fee_type'] = $fee_type;
        // 付款银行bank_type是String(16)银行类型，在微信中使用WX
        $data['bank_type'] = "WX";
        // 传入参数字符编码
        $data['input_charset'] = $input_charset;
        
        // 其他信息设置
        // trade_state 是订单状态，0 为成功，其他为失败；
        $data['trade_state'] = - 1;
        // trade_mode 是交易模式，1 为即时到帐，其他保留；
        $data['trade_mode'] = 0;
        // partner 是财付通商户号，即前文的partnerid；
        $data['partner'] = '';
        // bank_billno 是银行订单号；
        $data['bank_billno'] = '';
        // transaction_id 是财付通订单号；
        $data['transaction_id'] = '';
        // is_split 表明是否分账，false 为无分账，true 为有分账；
        $data['is_split'] = 'false';
        // is_refund 表明是否退款，false 为无退款，ture 为退款；
        $data['is_refund'] = 'false';
        // time_end 是支付完成时间；
        $data['time_end'] = '';
        $data['uma_time_end'] = new MongoDate(0);
        // discount 是折扣价格，单位为分；
        $data['discount'] = 0;
        // rmb_total_fee 是换算成人民币之后的总金额，单位为分，一般看total_fee 即可。
        $data['rmb_total_fee'] = 0;
        
        // timeStamp 时间戳；
        $data['timeStamp'] = time();
        // nonceStr 随机串。
        $data['nonceStr'] = createRandCode(32);
        // 订单状态修改时间
        $data['status_time'] = new MongoDate();
        // 订单状态修改方式
        $data['status_change_by'] = 'create';
        
        // 收货人省份
        $data['consignee_province'] = $consignee_province;
        // 收货人城市
        $data['consignee_city'] = $consignee_city;
        // 收货人区或县
        $data['consignee_area'] = $consignee_area;
        // 收货人地址
        $data['consignee_address'] = $consignee_address;
        // 收货人
        $data['consignee_name'] = $consignee_name;
        // 收货人地址
        $data['consignee_tel'] = $consignee_tel;
        // 收货人
        $data['consignee_zipcode'] = $consignee_zipcode;
        // 快递公司
        $data['freight_campany'] = $freight_campany;
        
        // UMA订单状态
        $data['uma_order_status'] = 0; // UMA订单状态:未支付
        $data['uma_shipping_status'] = 0; // UMA发货状态:未发货
        $data['uma_feedback_status'] = 0; // UMA维权状态:未维权
        
        $data['memo'] = ""; // 备注
                            
        // 组合SKU编号
        $composite_sku_no = "";
        if (! empty($goodsList)) {
            foreach ($goodsList as $gid => $goods) {
                if (! empty($goods['composite_sku_no'])) {
                    $composite_sku_no .= ($goods['composite_sku_no'] . ","); // 商品SKU
                }
            }
        }
        $data['composite_sku_no'] = trim($composite_sku_no, ",");
        
        // 获取Package
        $weixinPay = $this->getWeixinPayInstance();
        $data['package'] = $weixinPay->getPackage4JsPay($data['body'], $data['attach'], $data['out_trade_no'], $data['total_fee'], $data['notify_url'], $data['spbill_create_ip'], $data['time_start'], $data['time_expire'], $data['transport_fee'], $data['product_fee'], $data['goods_tag'], $data['bank_type'], $data['fee_type'], $data['input_charset']);
        
        $data['appid'] = $weixinPay->getAppId();
        // 获取app_signature
        $para = array(
            "appid" => $data['appid'],
            "appkey" => $weixinPay->getPaySignKey(),
            "package" => $data['package'],
            "timestamp" => $data['timeStamp'],
            "noncestr" => $data['nonceStr']
        );
        $data['AppSignature'] = $weixinPay->getPaySign($para);
        $data['signType'] = $signType;
        
        $data['details'] = $goodsList;
        
        // 减少库存数量
        $this->subStock($data);
        
        // 生成订单
        $newOrderInfo = $this->insert($data);
        return $newOrderInfo;
    }

    /**
     * 修改订单的信息
     *
     * @param array $orderInfo            
     * @param int $transport_fee            
     * @param string $consignee_province            
     * @param string $consignee_city            
     * @param string $consignee_area            
     * @param string $consignee_name            
     * @param string $consignee_address            
     * @param string $consignee_tel            
     * @param string $consignee_zipcode            
     * @param string $freight_campany            
     */
    public function updateOrder($orderInfo, $transport_fee, $consignee_province, $consignee_city, $consignee_area, $consignee_name, $consignee_address, $consignee_tel, $consignee_zipcode, $freight_campany)
    {
        $data = array();
        // 收货人省份
        $data['consignee_province'] = $consignee_province;
        // 收货人城市
        $data['consignee_city'] = $consignee_city;
        // 收货人区或县
        $data['consignee_area'] = $consignee_area;
        // 收货人地址
        $data['consignee_address'] = $consignee_address;
        // 收货人
        $data['consignee_name'] = $consignee_name;
        // 收货人地址
        $data['consignee_tel'] = $consignee_tel;
        // 收货人
        $data['consignee_zipcode'] = $consignee_zipcode;
        // 快递公司
        $data['freight_campany'] = $freight_campany;
        // 物流费用，单位为分
        $data['transport_fee'] = $transport_fee;
        // 商品费用，单位为分
        $data['product_fee'] = $orderInfo['product_fee'];
        // 订单总金额
        $data['total_fee'] = $data['product_fee'] + $data['transport_fee'];
        
        // 订单生成的机器IP
        $ip = getIp();
        $data['client_ip'] = $ip;
        $data['spbill_create_ip'] = ($ip);
        // 订单生成时间， 格式为yyyyMMddHHmmss
        $data['time_start'] = date("YmdHis");
        $data['uma_time_start'] = new MongoDate();
        // 订单失效时间， 格式为yyyyMMddHHmmss
        $data['time_expire'] = "";
        $data['uma_time_expire'] = new MongoDate(0);
        
        // 通知URL
        $config = Zend_Registry::get('config');
        $notify_url = $config['iWeixin']['pay']['notify_url'];
        $data['notify_url'] = $notify_url;
        
        // timeStamp 时间戳；
        $data['timeStamp'] = time();
        // nonceStr 随机串。
        $data['nonceStr'] = createRandCode(32);
        
        // 获取Package
        $weixinPay = $this->getWeixinPayInstance();
        $data['package'] = $weixinPay->getPackage4JsPay($orderInfo['body'], $orderInfo['attach'], $orderInfo['out_trade_no'], $data['total_fee'], $data['notify_url'], $data['spbill_create_ip'], $data['time_start'], $data['time_expire'], $data['transport_fee'], $data['product_fee'], $orderInfo['goods_tag'], $orderInfo['bank_type'], $orderInfo['fee_type'], $orderInfo['input_charset']);
        
        $data['appid'] = $weixinPay->getAppId();
        // 获取app_signature
        $para = array(
            "appid" => $data['appid'],
            "appkey" => $weixinPay->getPaySignKey(),
            "package" => $data['package'],
            "timestamp" => $data['timeStamp'],
            "noncestr" => $data['nonceStr']
        );
        $data['AppSignature'] = $weixinPay->getPaySign($para);
        
        $options = array(
            "query" => array(
                "_id" => $orderInfo['_id']
            ),
            "update" => array(
                '$set' => $data
            ),
            "new" => true
        );
        $return_result = $this->findAndModify($options);
        
        $newOrderInfo = $return_result["value"];
        $newOrderInfo['order_id'] = myMongoId($newOrderInfo['_id']);
        return $newOrderInfo;
    }

    /**
     * 修改订单的状态
     *
     * @param array $info            
     * @param array $updateData            
     * @param string $status_change_by            
     * @return array
     */
    public function changeStatus($info, $updateData, $status_change_by)
    {
        // "ret_code":0,
        // "ret_msg":"",
        // "input_charset":"GBK",
        // "trade_state":"0",
        // "trade_mode":"1",
        // "partner":"1900000109",
        // "bank_type":"CMB_FP",
        // "bank_billno":"207029722724",
        // "total_fee":"1",
        // "fee_type":"1",
        // "transaction_id":"1900000109201307020305773741",
        // "out_trade_no":"2986872580246457300",
        // "is_split":"false",
        // "is_refund":"false",
        // "attach":"",
        // "time_end":"20130702175943",
        // "transport_fee":"0",
        // "product_fee":"1",
        // "discount":"0",
        // "rmb_total_fee":""
        $data = array();
        if (isset($updateData['input_charset'])) {
            $data["input_charset"] = $updateData['input_charset'];
        }
        if (isset($updateData['trade_state'])) {
            $data["trade_state"] = intval($updateData['trade_state']);
        }
        if (isset($updateData['trade_mode'])) {
            $data["trade_mode"] = intval($updateData['trade_mode']);
        }
        if (isset($updateData['partner'])) {
            $data["partner"] = $updateData['partner'];
        }
        if (isset($updateData['bank_type'])) {
            $data["bank_type"] = $updateData['bank_type'];
        }
        if (isset($updateData['bank_billno'])) {
            $data["bank_billno"] = $updateData['bank_billno'];
        }
        if (isset($updateData['total_fee'])) {
            $data["total_fee"] = intval($updateData['total_fee']);
        }
        if (isset($updateData['fee_type'])) {
            $data["fee_type"] = intval($updateData['fee_type']);
        }
        if (isset($updateData['transaction_id'])) {
            $data["transaction_id"] = $updateData['transaction_id'];
        }
        if (isset($updateData['is_split'])) {
            $data["is_split"] = $updateData['is_split'];
        }
        if (isset($updateData['is_refund'])) {
            $data["is_refund"] = $updateData['is_refund'];
        }
        if (isset($updateData['attach'])) {
            $data["attach"] = $updateData['attach'];
        }
        if (isset($updateData['time_end'])) {
            $data["time_end"] = $updateData['time_end'];
            // 20130702175943
            // $year=substr($updateData['time_end'],0,4);//取得年份
            // $month=substr($updateData['time_end'],4,2);//取得月份
            // $day=substr($updateData['time_end'],6,2);//取得几号
            // $hour=substr($updateData['time_end'],8,2);//取得年份
            // $minute=substr($updateData['time_end'],10,2);//取得月份
            // $second=substr($updateData['time_end'],12,2);//取得几号
            // $data["uma_time_end"] = new MongoDate(mktime($hour,$minute,$second,$month,$day,$year));
            $data["uma_time_end"] = new MongoDate(strtotime($updateData['time_end']));
        }
        if (isset($updateData['transport_fee'])) {
            $data["transport_fee"] = intval($updateData['transport_fee']);
        }
        if (isset($updateData['product_fee'])) {
            $data["product_fee"] = intval($updateData['product_fee']);
        }
        if (isset($updateData['discount'])) {
            $data["discount"] = intval($updateData['discount']);
        }
        if (isset($updateData['rmb_total_fee'])) {
            $data["rmb_total_fee"] = intval($updateData['rmb_total_fee']);
        }
        
        // 订单状态修改时间
        $data['status_time'] = new MongoDate();
        // 订单状态修改方式
        $data['status_change_by'] = $status_change_by;
        
        // UMA订单状态
        $data['uma_order_status'] = $this->getUmaOrderStatus($data);
        
        $options = array(
            "query" => array(
                "_id" => $info['_id']
            ),
            "update" => array(
                '$set' => $data
            ),
            "new" => true
        );
        $return_result = $this->findAndModify($options);
        
        $newOrderInfo = $return_result["value"];
        
        return $newOrderInfo;
    }

    /**
     * 判断支付完成
     *
     * @param int $trade_state            
     * @param int $trade_mode            
     * @return boolean
     */
    public function isOK($trade_state, $trade_mode)
    {
        if (is_numeric($trade_state) && intval($trade_state) === 0 && is_numeric($trade_mode) && intval($trade_mode) === 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取已支付订单列表
     *
     * @param string $OpenId            
     * @param int $page            
     * @param int $limit            
     * @return array
     */
    public function getList4PayFinished($OpenId, $page = 1, $limit = 10)
    {
        $sort = $this->getDefaultSort();
        $query = $this->getQuery();
        $query['OpenId'] = $OpenId;
        $query['trade_state'] = 0;
        $query['trade_mode'] = 1;
        
        $list = array();
        if (empty($list)) {
            $list = $this->find($query, $sort, ($page - 1) * $limit, $limit);
            if (! empty($list['datas'])) {
                foreach ($list['datas'] as &$order) {
                    // 获取财富通的订单结果并修改
                    $order = $this->updateFromTenpay($order);
                }
            }
        }
        return $list;
    }

    /**
     * 获取微信支付对象
     *
     * @return \Weixin\Pay
     */
    public function getWeixinPayInstance()
    {
        $config = Zend_Registry::get('config');
        $token = $config['iWeixin']['token'];
        $project_id = $config['iWeixin']['project_id'];
        
        $modelWeixinApplication = new Weixin_Model_Application();
        $token = $modelWeixinApplication->getToken();
        
        $iWeixinPay = new Weixin\Pay();
        $iWeixinPay->setAppId($config['iWeixin']['pay']['appId']);
        $iWeixinPay->setAppSecret($config['iWeixin']['pay']['appSecret']);
        $iWeixinPay->setPaySignKey($config['iWeixin']['pay']['paySignKey']);
        $iWeixinPay->setPartnerId($config['iWeixin']['pay']['partnerId']);
        $iWeixinPay->setPartnerKey($config['iWeixin']['pay']['partnerKey']);
        $iWeixinPay->setAccessToken($token['access_token']);
        return $iWeixinPay;
    }

    /**
     * 更新微信ID
     *
     * @param string $out_trade_no            
     * @param string $openId            
     */
    public function updateOpenId($out_trade_no, $openId)
    {
        $query = array(
            'out_trade_no' => $out_trade_no
        );
        $data = array();
        $data['OpenId'] = $openId;
        $this->update($query, array(
            '$set' => $data
        ));
    }

    /**
     *
     * @param string $out_trade_no            
     * @throws Exception
     * @return array
     */
    public function queryTenpayInfo($out_trade_no = "")
    {
        // ---------------------------------------------------------
        // 财付通订单查后台调用示例，商户按照此文档进行开发即可
        // ---------------------------------------------------------
        require_once (APPLICATION_PATH . "/../library/tenpay_api_b2c/classes/RequestHandler.class.php");
        require_once (APPLICATION_PATH . "/../library/tenpay_api_b2c/classes/client/ClientResponseHandler.class.php");
        require_once (APPLICATION_PATH . "/../library/tenpay_api_b2c/classes/client/TenpayHttpClient.class.php");
        
        $config = Zend_Registry::get('config');
        /* 商户号 */
        $partner = $config['iWeixin']['pay']['partnerId'];
        /* 密钥 */
        $key = $config['iWeixin']['pay']['partnerKey'];
        
        /* 创建支付请求对象 */
        $reqHandler = new RequestHandler();
        
        // 通信对象
        $httpClient = new TenpayHttpClient();
        
        // 应答对象
        $resHandler = new ClientResponseHandler();
        
        // -----------------------------
        // 设置请求参数
        // -----------------------------
        $reqHandler->init();
        $reqHandler->setKey($key);
        
        $reqHandler->setGateUrl("https://gw.tenpay.com/gateway/normalorderquery.xml");
        $reqHandler->setParameter("partner", $partner);
        // out_trade_no和transaction_id至少一个必填，同时存在时transaction_id优先
        $reqHandler->setParameter("out_trade_no", $out_trade_no);
        // $reqHandler->setParameter("transaction_id",
        // "1218088301201403248351488280");
        
        // -----------------------------
        // 设置通信参数
        // -----------------------------
        $httpClient->setTimeOut(30);
        // 设置请求内容
        $httpClient->setReqContent($reqHandler->getRequestURL());
        
        // 后台调用
        if ($httpClient->call()) {
            // 设置结果参数
            $resHandler->setContent($httpClient->getResContent());
            $resHandler->setKey($key);
            
            // 判断签名及结果
            // 只有签名正确并且retcode为0才是请求成功
            if ($resHandler->isTenpaySign() && $resHandler->getParameter("retcode") == "0") {
                
                $parameters = $resHandler->getAllParameters();
                return $parameters;
            } else {
                // 错误时，返回结果可能没有签名，记录retcode、retmsg看失败详情。
                throw new Exception("验证签名失败 或 业务错误信息:retcode=" . $resHandler->getParameter("retcode") . ",retmsg=" . $resHandler->getParameter("retmsg"));
            }
        } else {
            // 后台调用通信失败
            throw new Exception("call err:" . $httpClient->getResponseCode() . "," . $httpClient->getErrInfo());
            // 有可能因为网络原因，请求已经处理，但未收到应答。
        }
    }

    /**
     * 获取订单列表
     *
     * @param string $OpenId            
     * @param int $page            
     * @param int $limit            
     * @return array
     */
    public function getList($OpenId, $page = 1, $limit = 10)
    {
        $sort = $this->getDefaultSort();
        $query = $this->getQuery();
        $query['OpenId'] = $OpenId;
        $list = array();
        if (empty($list)) {
            $list = $this->find($query, $sort, ($page - 1) * $limit, $limit);
            if (! empty($list['datas'])) {
                foreach ($list['datas'] as &$order) {
                    // 获取财富通的订单结果并修改
                    $order = $this->updateFromTenpay($order);
                }
            }
        }
        return $list;
    }

    /**
     * 生成商户系统内部的订单号
     *
     * @return string
     */
    public function createOutTradeNo()
    {
        // $var = new MongoId();
        // return $var->__toString();
        $modelSeq = new Weixinshop_Model_Seq();
        $currentNum = $modelSeq->getRecordNum();
        $currentNum = $currentNum % 1000;
        $currentNum = str_pad($currentNum, 4, "0", STR_PAD_LEFT);
        
        // 年月日小时分钟+4位自增数
        // 2014-04-08 13:50分下了一单，单号为：2014040813501234
        // return date("YmdHi") . $currentNum;
        
        // 所以建议在订单规则中，“计数”部分前增加2位，设置为一个固定的值，如60，同时考虑订单号太长的话，可以将分钟去掉。这样订单规则为：
        // “年月日时+渠道(2位)+计数(4位)” 示例：2014072314600018
        return date("YmdH") . "60" . $currentNum;
    }

    /**
     * 获取订单状态
     *
     * @param array $data            
     * @return number
     */
    public function getUmaOrderStatus($data)
    {
        // 支付状态码 trade_state 是 Int 支付结果状态码,0表示成功,其它为失败
        // 交易模式 trade_mode 是 Int 1-即时到账其他保留
        // 是否分账 is_split 是 boolean 是否分账，false无分账，true分账
        // 是否退款 is_refund 是 boolean 是否退款，false无退款，true退款
        $trade_state = ! isset($data['trade_state']) ? "-1" : $data['trade_state'];
        $trade_mode = ! isset($data['trade_mode']) ? "0" : $data['trade_mode'];
        $is_split = ! isset($data['is_split']) ? "false" : $data['is_split'];
        $is_refund = ! isset($data['is_refund']) ? "false" : $data['is_refund'];
        $isOK = $this->isOK($trade_state, $trade_mode);
        if ($isOK) {
            return 1; // 已支付
        } else {
            return 0; // 未支付
        }
    }

    /**
     * 计算运费
     *
     * @param array $orderInfo            
     * @param string $campany            
     * @param array $area            
     * @return int
     */
    public function getTransportFee($orderInfo, $campany, array $area)
    {
        $transport_fee = 0; // 运费
        $modelPrice = new Tools_Model_Freight_Price();
        
        $modelFreightGoods = new Tools_Model_Freight_Goods();
        foreach ($orderInfo['details'] as $product) {
            if (empty($product['transport_fee_mode'])) { // 按计算
                $info = array();
                $info['number'] = $product['num']; // 商品数量
                $info['weight'] = empty($product['weight']) ? 0.0 : $product['weight'] * $info['number']; // 商品重量g
                $info['volume'] = empty($product['volume']) ? 0.0 : $product['volume'] * $info['number']; // 商品体积m3
                
                $settings = $modelFreightGoods->getInfoByGoods($product['gid']);
                if (! empty($settings)) {
                    $calc_fee = $modelPrice->getPrice($settings['template'], $campany, $settings['warehouse'], $settings['unit'], $area, $info);
                    if (empty($calc_fee)) {
                        throw new Exception("商品号为{$product['gid']}的非免运费商品运费规则没有设置");
                    }
                    $transport_fee += $calc_fee;
                } else {
                    throw new Exception("商品号为{$product['gid']}的非免运费商品运费规则没有设置");
                }
            } else {
                if (intval($product['transport_fee_mode']) === 1) { // 固定运费
                    $transport_fee += intval(empty($product['transport_fee']) ? 0 : $product['transport_fee']);
                }
            }
        }
        
        return $transport_fee;
    }

    /**
     * 确认收货并评价
     *
     * @param array $orderInfo            
     * @param string $comment            
     * @param string $advice            
     */
    public function confirmShipping($orderInfo, $comment, $advice)
    {
        $data['uma_shipping_status'] = 2; // 已收货
        $data['comment'] = $comment;
        $data['advice'] = $advice;
        
        $this->update(array(
            '_id' => $orderInfo['_id']
        ), array(
            '$set' => $data
        ));
    }

    /**
     * 更新维权的状态
     *
     * @param array $feedbackInfo            
     */
    public function updateFeedbackStatus(array $feedbackInfo)
    {
        $data['uma_feedback_status'] = empty($feedbackInfo['process_status']) ? 1 : $feedbackInfo['process_status']; // 维权中
        
        $this->update(array(
            'transaction_id' => $feedbackInfo['TransId']
        ), array(
            '$set' => $data
        ));
    }

    /**
     * 减少商品库存
     *
     * @param array $orderInfo            
     */
    public function subStock(array $orderInfo)
    {
        $modelGoods = new Weixinshop_Model_Goods();
        foreach ($orderInfo['details'] as $goods) {
            // 减少库存数量
            $modelGoods->subStock($orderInfo['out_trade_no'], $goods['gid'], $goods['num']);
        }
    }

    /**
     * 增加商品购买数量
     *
     * @param array $orderInfo            
     */
    public function incPurchaseNum(array $orderInfo)
    {
        $modelGoods = new Weixinshop_Model_Goods();
        foreach ($orderInfo['details'] as $goods) {
            // 增加商品购买数量
            $modelGoods->incPurchaseNum($goods['gid'], $goods['num']);
        }
    }

    /**
     * 更新组合SKU编号
     *
     * @param string $out_trade_no            
     * @param array $skuNoList            
     */
    public function updateCompositeSkuNo($out_trade_no, array $skuNoList)
    {
        $composite_sku_no = implode(",", $skuNoList);
        $query = array(
            'out_trade_no' => $out_trade_no
        );
        $data = array();
        $data['composite_sku_no'] = $composite_sku_no;
        $this->update($query, array(
            '$set' => $data
        ));
    }

    /**
     * 从财付通获取查询订单信息并且修改
     *
     * @param array $order            
     * @return array
     */
    public function updateFromTenpay(array $order)
    {
        // 获取财富通的订单结果
        $updateData = $this->queryTenpayInfo($order['out_trade_no']);
        // 更新订单状态
        $order = $this->changeStatus($order, $updateData, "orderquery4tenpay");
        return $order;
    }
}