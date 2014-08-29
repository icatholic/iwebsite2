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
            $total = 0;
            if (! empty($cart)) {
                // 获取商品信息
                $modelGoods = new Weixinshop_Model_Goods();
                $gids = array_keys($cart);
                $goodsList = $modelGoods->getList(false, $gids);
                
                foreach ($cart as $gid => &$value) {
                    if (array_key_exists($gid, $goodsList))                     // 存在
                    {
                        $info = $goodsList[$gid];
                        $value['name'] = $info['name']; // 商品名
                        $value['prize'] = $info['prize']; // 商品单价
                        $value['pic'] = $info['gpic1']; // 商品图片
                        $value['amount'] = $info['prize'] * $value['num']; // 商品金额
                        $value['stock_num'] = $info['stock_num']; // 商品库存;
                        $value['spec'] = $info['spec']; // 商品规格;
                        
                        $total += $value['amount'];
                    } else {
                        unset($value); // 删除
                    }
                }
            }
            $this->assign('total', $total); // 总计
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
            $OpenId = $this->getRequest()->getCookie("openid", '');
            if (empty($OpenId)) {
                throw new Exception("微信号为空");
            }
            
            $Products = trim($this->get('Products', '')); // 所选商品信息
            if (empty($Products)) {
                throw new Exception("商品为空");
            }
            if (! isJson($Products)) {
                throw new Exception("格式不正确");
            }
            
            $Products = json_decode($Products, true);
            $ProductIds = array();
            $nums = array();
            foreach ($Products as $product) {
                if (empty($product['gid'])) {
                    throw new Exception("商品号为空");
                }
                if (empty($product['num'])) {
                    throw new Exception("商品数量为空");
                }
                $ProductIds[] = $product['gid'];
                $nums[] = $product['num'];
            }
            
            // 检查商品的信息
            $modelGoods = new Weixinshop_Model_Goods();
            $goodsList = $modelGoods->getList(false, $ProductIds);
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
            
            // 清空购物车
            self::setCookie('cart', array());
            
            echo ($this->result("OK", myMongoId($orderInfo["_id"])));
            
            //$_SESSION['checkout']["goods"] = $goodsList;
            //$_SESSION['checkout']["OpenId"] = $OpenId;
            //echo ($this->result("OK",""));
            
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
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
            $gid = trim($this->get('gid', '')); // 商品ID
            $num = intval($this->get('num')); // 商品数量
            if (empty($gid)) {
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
            if (key_exists($gid, $cart)) {
                $cart[$gid]['num'] = intval($cart[$gid]['num']) + $num;
            } else {
                // 判断商品ID是否正确
                $modelGoods = new Weixinshop_Model_Goods();
                $info = $modelGoods->getInfoByGid($gid);
                if (empty($info)) {
                    echo ($this->error("-4", "商品ID不存在"));
                    return false;
                }
                $cart[$gid]['name'] = $info['name']; // 商品名
                $cart[$gid]['prize'] = $info['prize']; // 商品单价
                $cart[$gid]['num'] = $num;
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
            $gid = trim($this->get('gid', '')); // 商品ID
            $num = intval($this->get('num')); // 商品数量
            if (empty($gid)) {
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
            if (key_exists($gid, $cart)) {
                $cart[$gid]['num'] = $num;
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
            $gid = trim($this->get('gid', '')); // 商品ID
            if (empty($gid)) {
                echo ($this->error("-1", "商品ID为空"));
                return false;
            }
            // 从COOKIE中获取内容
            $cart = self::getCookie('cart');
            $cart = $cart ? $cart : array();
            if (key_exists($gid, $cart)) {
                unset($cart[$gid]);
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
                // 获取商品信息
                $modelGoods = new Weixinshop_Model_Goods();
                $gids = array_keys($cart);
                $goodsList = $modelGoods->getList(false, $gids);
                
                foreach ($cart as $gid => $goodsInfo) {
                    if (array_key_exists($gid, $goodsList))                     // 存在
                    {
                        $num = $goodsInfo['num'];
                        $prize = $goodsList[$gid]['prize'];
                        $info['goods_count'] ++;
                        $info['amount'] += $num * $prize;
                    }
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

