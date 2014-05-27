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

    private $_user;
    
    private $_lock;
    
    private $_activity_id;
    
    private $_uniqueId;

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
        $this->_lock = new Lottery_Model_Lock();
        
        // 额外增加的业务处理逻辑
        $this->_user = new Weixin_Model_User();
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
        
        if (empty($uniqueId)) {
            echo $this->error(499, "唯一识别ID为空");
            return false;
        }
        
        if(empty($activity_id)) {
            echo $this->error(498, "活动编号为空");
            return false;
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
            
            //如果是活跃的项目，那么对于参与终于进行lock，防止并发请求导致的问题。
            $this->_lock->lock($activity_id, $uniqueId);
            
            // 产生用户身份信息
            $this->_identity->setSource($source);
            
            //检索是否该用户上一次抽奖是否完成
            
            
            // 增加微信昵称等信息
            $info = array();
            $userInfo = $this->_user->getUserInfoById($uniqueId);
            if (! empty($userInfo)) {
                $info = array(
                    'diaplay_name' => $userInfo['nickname'],
                    'nickname' => $userInfo['nickname']
                );
            } else {
                echo $this->error(508, "您需要从高洁丝官方微信参与本活动");
                return false;
            }
            
            $identityInfo = $this->_identity->record($uniqueId, $info);
            if ($identityInfo == false) {
                echo $this->error(507, "创建用户信息失败");
                return false;
            }
            
            $identity_id = $identityInfo['_id']->__toString();
            // 检测是否存在未领取或者未激活的中奖奖品，有的话，再次让其中同样的奖品完善个人信息。
            $invalidExchange = $this->_exchange->getExchangeInvalidById($identity_id);
            if (! empty($invalidExchange)) {
                if (isset($invalidExchange['_id'])) {
                    $invalidExchange['exchange_id'] = $invalidExchange['_id'] instanceof MongoId ? $invalidExchange['_id']->__toString() : $invalidExchange['_id'];
                }
                echo $this->result("OK", convertToPureArray($invalidExchange));
                return true;
            }
            
            // 检查中奖情况和中奖限制条件的关系
            $limit = $this->_limit->checkLimit($activity_id, $identity_id, 'all');
            if ($limit == false) {
                $this->_record->record($activity_id, $identity_id, 501, $source);
                echo $this->error(501, "到达抽奖限制的上限制");
                return false;
            }
            
            // 检查中奖规则，检测用户是否中奖
            $this->_rule->setLimitModel($this->_limit); // 装在limit,不再重新加载数据
            $rule = $this->_rule->lottery($activity_id, $identity_id);
            if ($rule == false) {
                $this->_record->record($activity_id, $identity_id, 502, $source);
                echo $this->error(502, "很遗憾，您没有中奖");
                return false;
            }
            
            // 更新中奖信息
            if (! $this->_rule->updateRemain($rule)) {
                $this->_record->record($activity_id, $identity_id, 503, $source);
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
                    $this->_record->record($activity_id, $identity_id, 504, $source);
                    echo $this->error(504, "该活动的该类型虚拟奖品已经发完");
                    return false;
                }
            } else {
                $isReal = true;
            }
            
            // 记录中奖记录
            $prizeCode = ! empty($code) ? $code : array();
            $identityInfo = $this->_identity->getIdentityById($identity_id);
            $identityContact = array();
            
            // 记录信息
            $exchangeInfo = $this->_exchange->record($activity_id, $rule['prize_id'], $prizeInfo, $prizeCode, $identity_id, $identityInfo, $identityContact, $isFinished, $source);
            if (isset($exchangeInfo['_id'])) {
                $exchangeInfo['exchange_id'] = $exchangeInfo['_id'] instanceof MongoId ? $exchangeInfo['_id']->__toString() : $exchangeInfo['_id'];
            }
            $this->_record->record($activity_id, $identity_id, 1, $source);
            echo $this->result("OK", convertToPureArray($exchangeInfo));
            return true;
        } catch (Exception $e) {
            $this->_record->record($activity_id, $identity_id, 505, $source);
            exit($this->error(505, $e->getFile() . $e->getLine() . $e->getMessage()));
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
        $identity_id = trim($this->get('identity_id', ''));
        
        $exchangeInfo = $this->_exchange->checkExchangeBy($identity_id, $exchange_id);
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
        
        try {
            $identityInfo = array();
            if (! empty($info)) {
                $identityInfo = $this->_identity->updateIdentityInfo($identity_id, $info);
            }
            
            $datas = array();
            $datas['is_valid'] = true;
            if (! empty($identityInfo)) {
                $datas['identity_info'] = $identityInfo;
            }
            $datas['identity_contact'] = $info;
            
            $this->_exchange->updateExchangeInfo($exchange_id, $datas);
            
            if (isset($exchangeInfo['activity_id']) && $exchangeInfo['activity_id'] == "532058de489619f50d7eb1b7") {
                // 写入优惠券信息
                $exchange_id = $exchangeInfo['_id']->__toString();
                $openid = $exchangeInfo['identity_info']['weixin_openid'];
                $activityInfo = $this->_activity->getActivityInfo($exchangeInfo['activity_id']);
                $activity_name = $activityInfo['name'];
                $coupon_name = $exchangeInfo['prize_info']['prize_name'];
                $code = $exchangeInfo['prize_code']['code'];
                $pwd = $exchangeInfo['prize_code']['pwd'];
                $start_time = $exchangeInfo['prize_code']['start_time'];
                $end_time = $exchangeInfo['prize_code']['end_time'];
                $couponModel = new Campaign_Model_User_Coupon();
                $couponModel->record($openid, $activity_name, $coupon_name, $code, $pwd, $start_time, $end_time, $exchange_id);
                
                // 主动推送消息给到给用户
                $app = new Weixin_Model_Application();
                $coupon = new Campaign_Model_User_Coupon();
                $appConfig = $app->getToken();
                $weixin = new Weixin\Client();
                if (! empty($appConfig['access_token'])) {
                    $weixin->setAccessToken($appConfig['access_token']);
                    $weixin->getMsgManager()
                        ->getCustomSender()
                        ->sendText($openid, $coupon->getCouponsMsg($openid));
                }
            }
            
            echo $this->result('OK', "提交成功");
            return true;
        } catch (Exception $e) {
            exit($this->error(505, $e->getFile() . $e->getLine() . $e->getMessage()));
        }
    }
    
    public function __destruct() {
        $this->_lock->release();
        if(rand(0,9)===1) {
            $this->_lock->expireRelease();
        }
    }
}

