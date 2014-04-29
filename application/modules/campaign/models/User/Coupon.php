<?php

class Campaign_Model_User_Coupon extends iWebsite_Plugin_Mongo
{

    protected $name = 'user_coupon';

    protected $dbName = 'default';

    /**
     * 记录用户获取的优惠券
     *
     * @param string $openid            
     * @param string $coupon_name            
     * @param string $code            
     * @param string $pwd            
     * @param MongoDate $start_time            
     * @param MongoDate $end_time            
     * @param string $exchange_id            
     */
    public function record($openid, $activity_name, $coupon_name, $code, $pwd, $start_time, $end_time, $exchange_id)
    {
        $datas = array();
        $datas['openid'] = $openid;
        $datas['activity_name'] = $activity_name;
        $datas['coupon_name'] = $coupon_name;
        $datas['code'] = $code;
        $datas['pwd'] = $pwd;
        $datas['start_time'] = $start_time;
        $datas['end_time'] = $end_time;
        $datas['exchange_id'] = $exchange_id;
        $datas['is_used'] = false;
        
        return $this->update(array(
            'exchange_id' => $exchange_id
        ), array(
            '$set' => $datas
        ), array(
            'upsert' => true
        ));
    }
    
    /**
     * 从中奖数据中获取优惠券信息并写入优惠券的表
     * @param string $identity_id
     * @param string $exchange_id
     */
    public function recordFromExchange($identity_id, $exchange_id) {
        $modelExchange = new Lottery_Model_Exchange();
        $modelActivity = new Lottery_Model_Activity();
        
        $exchangeInfo = $modelExchange->checkExchangeBy($identity_id, $exchange_id);
        $exchange_id = $exchangeInfo['_id']->__toString();
        $openid = $exchangeInfo['identity_info']['weixin_openid'];
        $activityInfo = $modelActivity->getActivityInfo($exchangeInfo['activity_id']);
        $activity_name = $activityInfo['name'];
        $coupon_name = $exchangeInfo['prize_info']['prize_name'];
        $code = $exchangeInfo['prize_code']['code'];
        $pwd = $exchangeInfo['prize_code']['pwd'];
        $start_time = $exchangeInfo['prize_code']['start_time'];
        $end_time = $exchangeInfo['prize_code']['end_time'];
        $this->record($openid, $activity_name, $coupon_name, $code, $pwd, $start_time, $end_time, $exchange_id);
    }

    /**
     * 获取全部优惠券
     *
     * @param string $openid            
     */
    public function getCoupons($openid)
    {
        return $this->findAll(array(
            'openid' => $openid,
            'end_time' => array(
                '$gte' => new MongoDate(time() - 5 * 24 * 3600)
            ),
            'is_used' => array(
                '$ne' => true
            )
        ), array(
            'start_time' => 1
        ));
    }

    /**
     * 获取优惠券文字
     *
     * @param string $openid            
     */
    public function getCouponsMsg($openid)
    {
        $alpha = 'abcdefghijklmnopqrstuvwxy';
        $coupons = $this->getCoupons($openid);
        if (! empty($coupons)) {
            $msg = "您好，以下是您目前所有的优惠券：\n";
            // 优惠券
            foreach ($coupons as $index => $coupon) {
                $startTime = date("Y-m-d", $coupon['start_time']->sec);
                $endTime = date("Y-m-d", $coupon['end_time']->sec-1);
                $msg .= $alpha[$index] . ". " . $coupon['coupon_name'] . "\n有效期至{$endTime}\n";
            }
            $msg .= "回复字母编号，获得优惠券代码。\n回复“规则”查看优惠券使用规则";
        } else {
            // 还没有优惠券的文本
            $msg = false;
        }
        return $msg;
    }

    /**
     * 通过$openid和字母查询优惠券
     */
    public function getCouponByAlpha($openid, $alpha)
    {
        $alphaString = 'abcdefghijklmnopqrstuvwxyz';
        $alphaIndex = strpos($alphaString, $alpha);
        $coupons = $this->getCoupons($openid);
        if (! empty($coupons) && isset($coupons[$alphaIndex])) {
            //return $coupons[$alphaIndex]['code'];
            return $coupons[$alphaIndex];
        } else {
            return false;
        }
    }
}