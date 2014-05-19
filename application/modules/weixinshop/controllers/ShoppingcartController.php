<?php

/**
 * 微信商城--购物车
 * @author 郭永荣
 *
 */
class Weixinshop_ShoppingcartController extends iWebsite_Controller_Action
{

    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender(false);
    }

    /**
     * 显示购物车结算
     */
    public function indexAction()
    {
        try {
            // 从COOKIE中获取内容
            $cart = self::getCookie('cart');
            $cart = $cart ? $cart : array();
            if (! empty($cart)) {
                // 获取商品信息
                $modelGoods = new Weixinshop_Model_Goods();
                $goodsIds = key($cart);
                $goodsList = $modelGoods->getList(0, $goodsIds);
                
                foreach ($cart as $goodsId => &$value) {
                    if (array_key_exists($goodsId, $goodsList))                     // 存在
                    {
                        $info = $goodsList[$goodsId];
                        $value['name'] = $info['gname']; // 商品名
                        $value['prize'] = $info['gprize']; // 商品单价
                        $value['gpic'] = $info['gpic1']; // 商品图片
                        $value['amount'] = $info['gprize'] * $value['num']; // 商品图片
                        $value['stock_num'] = $info['stock_num']; // 商品库存;
                    } else {
                        unset($value); // 删除
                    }
                }
            }
            $this->assign('cart', $cart);
        } catch (Exception $e) {
            exit($this->error($e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 结算处理
     */
    public function checkoutAction()
    {
        try {
            $OpenId = $this->getRequest()->getCookie("FromUserName", '');
            if (empty($OpenId)) {
                throw new Exception("微信号为空");
            }
            
            $ProductIds = trim($this->get('ProductIds', '')); // 商品号
            if (empty($ProductIds)) {
                throw new Exception("商品号为空");
            }
            
            $nums = trim($this->get('nums', '')); // 商品数量
            if (empty($nums)) {
                throw new Exception("商品数量为空");
            }
            
            // 检查商品的信息
            $modelGoods = new Weixinshop_Model_Goods();
            $goodsList = $modelGoods->getList(0, $ProductIds);
            foreach ($ProductIds as $index => $ProductId) {
                if (! key_exists($ProductId, $goodsList)) {
                    throw new Exception("商品号{$ProductId}的商品不存在");
                } else {
                    // 商品购买在库数检查
                    if (! $modelGoods->hasStock($ProductId, $nums[$index])) {
                        throw new Exception("该商品已卖完");
                    }
                    // 通过的话
                    $goodsList[$ProductId]['num'] = $nums[$index];
                }
            }
            
            // 生成订单
            $modelOrder = new Weixinshop_Model_Order();
            $orderInfo = $modelOrder->createOrder($OpenId, $goodsList);
            
            // 画面跳转至订单支付页面
            $orderId = $orderInfo['_id']->__toString();
            $url = $this->_helper->url("pay", "order");
            $url = $url . "?orderId={$orderId}";
            $this->_redirect($url);
        } catch (Exception $e) {
            exit($this->error($e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 查看购物车
     */
    public function viewAction()
    {
        try {
            // 从COOKIE中获取内容
            $cart = self::getCookie('cart');
            $cart = $cart ? $cart : array();
            if (empty($cart)) {
                echo ($this->result("购物车没有内容"));
                return true;
            }
            echo ($this->result("OK", $cart));
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    /**
     * 向购物车中增加商品
     */
    public function addAction()
    {
        try {
            $goodsId = trim($this->get('goodsId', '')); // 商品ID
            $num = intval($this->get('num')); // 商品数量
            if (empty($goodsId)) {
                echo ($this->error("-1", "商品ID为空"));
                return false;
            }
            if (empty($num)) {
                echo ($this->error("-2", "数量不能为空或0"));
                return false;
            }
            if (($num) < 0) {
                echo ($this->error("-3", "数量不正确"));
                return false;
            }
            
            // 从COOKIE中获取内容
            $cart = self::getCookie('cart');
            $cart = $cart ? $cart : array();
            if (key_exists($goodsId, $cart)) {
                $cart[$goodsId]['num'] = intval($cart[$goodsId]) + $num;
            } else {
                // 判断商品ID是否正确
                $modelGoods = new Weixinshop_Model_Goods();
                $info = $modelGoods->getInfoById($goodsId);
                if (empty($info)) {
                    echo ($this->error("-4", "商品ID不存在"));
                    return false;
                }
                $cart[$goodsId]['name'] = $info['gname']; // 商品名
                $cart[$goodsId]['prize'] = $info['gprize']; // 商品单价
                $cart[$goodsId]['num'] = $num;
            }
            // 最新的内容存入COOKIE中
            self::setCookie('cart', $cart);
            echo ($this->result("OK", $cart));
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    /**
     * 更改购物车内某商品的数量
     */
    public function updateNumAction()
    {
        try {
            $goodsId = trim($this->get('goodsId', '')); // 商品ID
            $num = intval($this->get('num')); // 商品数量
            if (empty($goodsId)) {
                echo ($this->error("-1", "商品ID为空"));
                return false;
            }
            if (empty($num)) {
                echo ($this->error("-2", "数量不能为空或0"));
                return false;
            }
            if (($num) < 0) {
                echo ($this->error("-3", "数量不正确"));
                return false;
            }
            // 从COOKIE中获取内容
            $cart = self::getCookie('cart');
            $cart = $cart ? $cart : array();
            if (key_exists($goodsId, $cart)) {
                $cart[$goodsId]['num'] = $num;
            } else {
                echo ($this->error("-4", "商品ID不存在"));
                return false;
            }
            // 最新的内容存入COOKIE中
            self::setCookie('cart', $cart);
            echo ($this->result("OK", $cart));
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    /**
     * 删除购物车内某商品
     */
    public function deleteAction()
    {
        try {
            $goodsId = trim($this->get('goodsId', '')); // 商品ID
            if (empty($goodsId)) {
                echo ($this->error("-1", "商品ID为空"));
                return false;
            }
            // 从COOKIE中获取内容
            $cart = self::getCookie('cart');
            $cart = $cart ? $cart : array();
            if (key_exists($goodsId, $cart)) {
                unset($cart[$goodsId]);
            } else {
                echo ($this->error("-2", "商品ID不存在"));
                return false;
            }
            // 最新的内容存入COOKIE中
            self::setCookie('cart', $cart);
            
            echo ($this->result("OK", $cart));
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    /**
     * 清空购物车
     */
    public function clearAction()
    {
        try {
            $cart = array();
            // 最新的内容存入COOKIE中
            self::setCookie('cart', $cart);
            echo ($this->result("OK", $cart));
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    /**
     * 获取购物车的统计信息
     */
    public function statisticsAction()
    {
        try {
            $info = array(
                'goods_count' => 0,
                'amount' => 0
            );
            // 从COOKIE中获取内容
            $cart = self::getCookie('cart');
            $cart = $cart ? $cart : array();
            
            if (! empty($cart)) {
                foreach ($cart as $goodsId => $goodsInfo) {
                    $num = $goodsInfo['num'];
                    $prize = $goodsInfo['prize'];
                    $info['goods_count'] ++;
                    $info['amount'] += $num * $prize;
                }
            }
            echo ($this->result("OK", $info));
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    public function __destruct()
    {}
}

