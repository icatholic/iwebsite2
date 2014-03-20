<?php

class Lottery_IndexController extends iWebsite_Controller_Action
{

    private $_activity;

    private $_code;

    private $_exchange;

    private $_identity;

    private $_limit;

    private $_prize;

    private $_record;

    private $_result_msg;

    private $_rule;

    private $_source;

    public function init()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $this->_activity = new Lottery_Model_Activity();
        $this->_code = new Lottery_Model_Code();
        $this->_exchange = new Lottery_Model_Exchange();
        $this->_identity = new Lottery_Model_Identity();
        $this->_limit = new Lottery_Model_Limit();
        $this->_prize = new Lottery_Model_Prize();
        $this->_record = new Lottery_Model_Record();
        $this->_result_msg = new Lottery_Model_ResultMsg();
        $this->_rule = new Lottery_Model_Rule();
        $this->_source = new Lottery_Model_Source();
    }

    /**
     * 使用一个极简的签名算法，做安全处理
     *
     * @param string $uniqueId            
     * @param string $key            
     * @return string
     */
    private function checkSign($uniqueId, $key)
    {
        return md5($uniqueId . $key);
    }

    /**
     * 抽奖
     */
    public function getAction()
    {
        $activity_id = isset($_GET['activity_id']) ? trim($_GET['activity_id']) : '';
        $sign = isset($_GET['sign']) ? trim($_GET['sign']) : '';
        
        if (isset($_GET['FromUserName'])) {
            $uniqueId = trim($_GET['FromUserName']);
            $source = Lottery_Model_Identity::SOURCE_WEIXIN;
        } else 
            if (isset($_GET['weibo_id'])) {
                $uniqueId = trim($_GET['weibo_id']);
                $source = Lottery_Model_Identity::SOURCE_WEIBO;
            } else 
                if (isset($_GET['other_id'])) {
                    $uniqueId = intval($_GET['other_id']);
                    $source = Lottery_Model_Identity::SOURCE_OTHERS;
                } else 
                    if (isset($_GET['other_string_id'])) {
                        $uniqueId = intval($_GET['other_string_id']);
                        $source = Lottery_Model_Identity::SOURCE_OTHERS;
                    }
        
        // 通过ajax请求时，不校验签名
        // if(empty($sign) || $this->checkSign($uniqueId, $key)!==$sign) {
        // return $this->error(505, "签名错误");
        // }
        
        // 如果使用微信，可能需要校验FromUserName是否有效
        
        // 校验结束
        
        try {
            // 检查活动信息
            if (! $this->_activity->checkActivityActive($activity_id)) {
                echo $this->error(500, "活动尚未开始");
                return false;
            }
            
            // 产生用户身份信息
            $this->_identity->setSource($source);
            $info = array();
            $identity_id = $this->_identity->record($uniqueId, $info);
            
            // 检测是否存在未领取或者未激活的中奖奖品，有的话，再次让其中同样的奖品完善个人信息。
            $invalidExchange = $this->_exchange->getExchangeInvalidById($identity_id);
            if (! empty($invalidExchange)) {
                echo $this->result("OK", convertToPureArray($invalidExchange));
                return true;
            }
            
            // 检查中奖情况和中奖限制条件的关系
            $limit = $this->_limit->checkLimit($activity_id, $identity_id, 'all');
            if ($limit == false) {
                echo $this->error(501, "到达今日抽奖限制的上限制");
                return false;
            }
            
            // 检查中奖规则，检测用户是否中奖
            $this->_rule->setLimitModel($this->_limit); // 装在limit,不再重新加载数据
            $rule = $this->_rule->lottery($activity_id, $identity_id);
            if ($rule == false) {
                echo $this->error(502, "很遗憾，您没有中奖");
                return false;
            }
            
            // 更新中奖信息
            if (! $this->_rule->updateRemain($rule)) {
                echo $this->error(503, "竞争争夺奖品失败");
                return false;
            }
            
            // 竞争到奖品，根据奖品的属性标记状态
            $prizeInfo = $this->_prize->getPrizeInfo($rule['prize_id']);
            
            $result = array();
            $result['identity_id'] = $identity_id;
            $result['prizeInfo'] = $prizeInfo;
            
            // 奖品类型与状态
            $isReal = false;
            $isFinished = false; // true表示立即有效 false
            if (! empty($prizeInfo['is_virtual'])) {
                // 虚拟物品
                if (! empty($prizeInfo['immediately'])) {
                    // 虚拟物品且立即生效的
                    $isFinished = true;
                }
                $code = $this->_code->getCode($activity_id, $rule['prize_id']);
                if ($code == false) {
                    echo $this->error(504, "该活动的该类型虚拟奖品已经发完");
                    return false;
                }
            } else {
                $isReal = true;
            }
            
            // 记录中奖记录
            $prizeCode = ! empty($code) ? $code : array();
            $identityInfo = $this->_identity->getIdentityById($indentity_id);
            $identityContact = array();
            
            // 记录信息
            $exchangeInfo = $this->_exchange->record($activity_id, $rule['prize_id'], $prizeInfo, $prizeCode, $identity_id, $identityInfo, $identityContact, $isFinished, $source);
            
            echo $this->result("OK", convertToPureArray($exchangeInfo));
            return true;
        } catch (Exception $e) {
            var_dump($e);
            exit($this->error(505, $e->getMessage()));
        }
    }

    /**
     * 记录中奖用户的信息
     */
    public function recordAction()
    {
        $diaplay_name = trim($this->get('diaplay_name', ''));
        $name = trim($this->get('name', ''));
        $mobile = trim($this->get('mobile', ''));
        $tel = trim($this->get('tel', ''));
        $address = trim($this->get('address', ''));
        $zip = trim($this->get('zip', ''));
        $id_number = trim($this->get('id_number', ''));
        $address = trim($this->get('address', ''));
        $exchange_id = trim($this->get('exchange_id', ''));
        $indentity_id = trim($this->get('indentity_id', ''));
        
        $exchangeInfo = $this->_exchange->checkExchangeBy($indentity_id, $exchange_id);
        if ($exchangeInfo == null) {
            echo $this->error(506, "该用户无此兑换信息");
            return false;
        }
        
        $info = array();
        
        if (! empty($diaplay_name))
            $info['name'] = $diaplay_name;
        
        if (! empty($name))
            $info['name'] = $name;
        
        if (! empty($mobile))
            $info['mobile'] = $mobile;
        
        if (! empty($tel))
            $info['tel'] = $tel;
        
        if (! empty($address))
            $info['address'] = $address;
        
        if (! empty($zip))
            $info['zip'] = $zip;
        
        if (! empty($id_number))
            $info['id_number'] = $id_number;
        
        $this->_identity->updateIdentityInfo($indentity_id, $info);
        
        $this->_exchange->updateExchangeInfo($exchange_id, $datas);
        
        $this->result('OK', "提交成功");
    }
}

