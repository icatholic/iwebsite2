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
        $this->_code = new Lottery_Model_Exchange();
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
            $this->_identity->setSource(Lottery_Model_Identity::SOURCE_WEIXIN);
        } else 
            if (isset($_GET['weibo_id'])) {
                $uniqueId = trim($_GET['weibo_id']);
                $this->_identity->setSource(Lottery_Model_Identity::SOURCE_WEIBO);
            } else 
                if (isset($_GET['other_id'])) {
                    $uniqueId = intval($_GET['other_id']);
                    $this->_identity->setSource(Lottery_Model_Identity::SOURCE_OTHERS);
                } else 
                    if (isset($_GET['other_string_id'])) {
                        $uniqueId = intval($_GET['other_string_id']);
                        $this->_identity->setSource(Lottery_Model_Identity::SOURCE_OTHERS);
                    }
        
        // 通过ajax请求时，不校验签名
        // if(empty($sign) || $this->checkSign($uniqueId, $key)!==$sign) {
        // return $this->error(505, "签名错误");
        // }
        
        // 如果使用微信，可能需要校验FromUserName是否有效
        
        // 校验结束
        
        try {
            if (! $this->_activity->checkActivityActive($activity_id)) {
                echo $this->error(500, "活动尚未开始");
                return false;
            }
            
            // 产生用户身份信息
            $identity_id = $this->_identity->record($uniqueId, $info);
            
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
            
            if ($prizeInfo['is_virtual']) {
                // 虚拟物品，标记为有效或者无效
                if (empty($prizeInfo['immediately'])) {
                    // 标记状态为无效，有效后方可使用
                    $this->_exchange->record($activity_id, $rule['prize_id'], $prizeInfo, $identity_id, false);
                } else {
                    // 直接发放并有效
                    $this->_exchange->record($activity_id, $rule['prize_id'], $prizeInfo, $identity_id, true);
                }
            } else {
                // 实物类奖品，引导用户去完善个人信息
                $this->_exchange->record($activity_id, $rule['prize_id'], $prizeInfo, $identity_id, false);
            }
            echo $this->result("OK", $result);
            return false;
        } catch (Exception $e) {
            exit($this->error(505, $e->getMessage()));
        }
    }

    /**
     * 记录中奖用户的信息
     */
    public function recordAction()
    {
        // http://iwebsite.umaman.com/Lottery/index/record?jsonpcallback=?&exchangeId=123&name=guo&mobile=1233&address=232323
        try {
            $name = trim($this->get('name'));
            $mobile = trim($this->get('mobile'));
            $address = trim($this->get('address'));
            $exchangeId = trim($this->get('exchangeId'));
            
            if (empty($mobile)) {
                exit($this->response(false, '请填写手机号码或电话号码'));
            }
            if (! isValidMobile($mobile)) {
                exit($this->response(false, '手机格式不正确'));
            }
            
            if (empty($exchangeId)) {
                exit($this->response(false, '中奖ID为空'));
            }
            $modelExchange = new Lottery_Model_Exchange();
            $exchangeInfo = $modelExchange->getInfoById($exchangeId);
            if (empty($exchangeInfo)) {
                exit($this->response(false, '您今天未获得奖品'));
            }
            if (empty($address)) {
                if ($exchangeInfo['prizeInfo']['is_real']) { // 如果中奖的奖品是实体奖
                    exit($this->response(false, '请填写寄送地址'));
                }
            }
            if (empty($name)) {
                if ($exchangeInfo['prize_code']) { // 如果中奖的奖品是实体奖
                    exit($this->response(false, '请填写你的联系人姓名'));
                }
            }
            // 更新中奖人信息
            $modelExchange->updateExchangeInfo($exchangeId, $name, $mobile, $address);
            exit($this->response(true, "记录处理结束"));
        } catch (Exception $e) {
            exit($this->response(false, $e->getMessage()));
        }
    }

    /**
     * 获取我的中奖的信息
     */
    public function myPrizeAction()
    {
        // http://iwebsite.umaman.com/Lottery/index/my-prize?jsonpcallback=?&identity_id=232323
        try {
            $identity_id = trim($this->get('identity_id'));
            if (empty($identity_id)) {
                exit($this->response(false, '抽奖用户ID为空'));
            }
            $modelLotteryIdentity = new Lottery_Model_LotteryIdentity();
            $lotteryIdentity = $modelLotteryIdentity->findOne(array(
                '_id' => $identity_id
            ));
            if (empty($lotteryIdentity)) {
                exit($this->response(false, '抽奖用户ID不正确'));
            }
            $skip = intval($this->get('skip', '0'));
            $limit = intval($this->get('limit', '1000'));
            $modelExchange = new Lottery_Model_Exchange();
            
            $datas = $modelExchange->getPrizeList($lotteryIdentity, $skip, $limit, false, false);
            exit($this->response(true, '获取处理结束', $datas));
        } catch (Exception $e) {
            exit($this->response(false, $e->getMessage()));
        }
    }
    /*
     * 获取中奖名单
     */
    public function winnerAction()
    {
        // http://iwebsite.umaman.com/Lottery/index/winner?jsonpcallback=?&skip=0&limit=10
        try {
            $skip = intval($this->get('skip', '0'));
            $limit = intval($this->get('limit', '10'));
            $modelExchange = new Lottery_Model_Exchange();
            $datas = $modelExchange->getPrizeList(null, $skip, $limit, true, true);
            exit($this->response(true, '获取处理结束', $datas));
        } catch (Exception $e) {
            exit($this->response(false, $e->getMessage()));
        }
    }

    /**
     * 兑换码短信发送
     */
    public function sendAction()
    {
        try {
            $mobile = trim($this->get('mobile'));
            $message = trim($this->get('message'));
            $exchange_id = trim($this->get('exchange_id'));
            if (empty($mobile)) {
                exit($this->response(false, '手机号码为空'));
            }
            if (! isValidMobile($mobile)) {
                exit($this->response(false, '手机格式不正确'));
            }
            if (empty($message)) {
                exit($this->response(false, '内容为空'));
            }
            if (empty($exchange_id)) {
                exit($this->response(false, 'exchange_id为空'));
            }
            
            // 中奖记录
            $modelExchange = new Lottery_Model_Exchange();
            $exchangeInfo = $modelExchange->getInfoById($exchange_id);
            if (empty($exchangeInfo)) {
                exit($this->response(false, '对不起，您今天没有中奖！'));
            }
            // 短信
            $modelSmsLog = new Lottery_Model_SmsLog();
            $user_id = $exchangeInfo['weibo_uid'];
            $user_name = $exchangeInfo['weibo_screen_name'];
            $modelSmsLog->sendSms($user_id, $user_name, $mobile, $message);
            exit($this->response(true, '短信发送处理结束', array()));
        } catch (Exception $e) {
            exit($this->response(false, $e->getMessage()));
        }
    }

    /**
     * 中奖生效
     */
    public function doPrizeValidationAction()
    {
        // http://iwebsite.umaman.com/lottery/index/do-prize-validation?jsonpcallback=?&exchangeId=1233
        try {
            $exchangeId = trim($this->get('exchangeId'));
            if (empty($exchangeId)) {
                exit($this->response(false, '中奖ID为空'));
            }
            $modelExchange = new Lottery_Model_Exchange();
            $exchangeInfo = $modelExchange->getInfoById($exchangeId);
            if (empty($exchangeInfo)) {
                exit($this->response(false, '您今天未获得奖品'));
            }
            if (! empty($exchangeInfo['is_valid'])) {
                exit($this->response(false, '中奖已经生效'));
            }
            // 中奖生效
            $modelExchange->doPrizeValidation($exchangeId);
            exit($this->response(true, "处理结束"));
        } catch (Exception $e) {
            exit($this->response(false, $e->getMessage()));
        }
    }

    /**
     * 中奖的最高纪录
     */
    public function topAction()
    {
        // http://iwebsite.umaman.com/lottery/index/top?jsonpcallback=?&hid=1233
        try {
            $hid = trim($this->get('hid')); // HID
            if (empty($hid)) {
                exit($this->response(false, '$hid为空'));
            }
            // 获取抽奖参与人信息
            $modelLotteryIdentity = new Lottery_Model_LotteryIdentity();
            // 根据HID生成一个抽奖的身份凭证
            $lotteryIdentity = $modelLotteryIdentity->getIdentity("", "", "", "", "", "", $hid);
            // 用途
            $modelPrizeSource = new Lottery_Model_PrizeSource();
            $prize_source = $modelPrizeSource->getCaiquan();
            
            $modelExchange = new Lottery_Model_Exchange();
            $info = $modelExchange->getTopInfo($lotteryIdentity, $prize_source);
            exit($this->response(true, '获取处理结束', $info));
        } catch (Exception $e) {
            exit($this->response(false, $e->getMessage()));
        }
    }
}

