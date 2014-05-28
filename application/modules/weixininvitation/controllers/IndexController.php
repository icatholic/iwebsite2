<?php

class Weixininvitation_IndexController extends iWebsite_Controller_Action
{

    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender(true);
    }

    /**
     * 首页
     */
    public function indexAction()
    {
        // http://iwebsite2/weixininvitation/index/index?jsonpcallback=?&FromUserName=xxx
        try {
            $FromUserName = trim($this->get('FromUserName', ''));
            // 获取发送过几次邀请函
            $modelInvitation = new Weixininvitation_Model_Invitation();
            $count = $modelInvitation->getSentCount($FromUserName);
            $this->assign('sendNum', $count);
            
            // 判断是否已领过
            $modelInvitationGotDetail = new Weixininvitation_Model_InvitationGotDetail();
            $info = $modelInvitationGotDetail->getInfoByFromUserName($FromUserName);
            $this->assign("info", $info);
            $this->assign('isGot', empty($info) ? 0 : 1);
        } catch (Exception $e) {
            var_dump(exceptionMsg($e));
        }
    }

    /**
     * 发邀请函接口
     *
     * @return boolean
     */
    public function sendAction()
    {
        // http://iwebsite2/weixininvitation/index/send?jsonpcallback=?&FromUserName=xx&nickname=xxx&desc=xxx
        try {
            $FromUserName = trim($this->get('FromUserName', ''));
            if (empty($FromUserName)) {
                echo ($this->error(- 1, "微信ID不能为空"));
                return false;
            }
            $nickname = trim($this->get('nickname', '')); // 邀请函昵称
            $desc = trim($this->get('desc', '')); // 邀请函说明
            if (empty($nickname)) {
                echo ($this->error(- 2, "昵称不能为空"));
                return false;
            }
            if (empty($desc)) {
                echo ($this->error(- 3, "说明不能为空"));
                return false;
            }
            $worth = intval($this->get('worth', '0')); // 价值
            $invited_total = intval($this->get('invited_total', '1')); // 接受邀请总次数
                                                                       
            // 获取发送过几次邀请函
            $modelInvitation = new Weixininvitation_Model_Invitation();
            $count = $modelInvitation->getSentCount($FromUserName);
            
            if ($count > 0) { // 不是第一次
            }
            
            // 生成邀请函
            $recordInfo = $modelInvitation->create($FromUserName, $nickname, $desc, $worth, $invited_total);
            
            // 发送成功
            echo ($this->result("OK", $recordInfo));
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    /**
     * 我的邀请函
     */
    public function listAction()
    {
        // http://iwebsite2/weixininvitation/index/list?jsonpcallback=?&FromUserName=xxx
        try {
            $FromUserName = trim($this->get('FromUserName', ''));
            // 获取邀请函列表
            $modelInvitation = new Weixininvitation_Model_Invitation();
            $list = $modelInvitation->getListByPage($FromUserName, 1, 100);
            $this->assign('list', $list['datas']);
        } catch (Exception $e) {
            var_dump(exceptionMsg($e));
        }
    }

    /**
     * 邀请函详细信息及领取情况信息页面
     *
     * @throws Exception
     */
    public function detailsAction()
    {
        // http://iwebsite2/weixininvitation/index/details?jsonpcallback=?&FromUserName=xx
        try {
            $invitationId = trim($this->get('invitationId', ''));
            if (empty($invitationId)) {
                throw new Exception("邀请函ID为空");
            }
            $modelInvitation = new Weixininvitation_Model_Invitation();
            $info = $modelInvitation->getInfoById($invitationId);
            $this->assign('Invitation', $info);
            if (empty($info)) {
                throw new Exception("邀请函不存在");
            }
            // 获取领过的邀请函列表
            $modelInvitationGotDetail = new Weixininvitation_Model_InvitationGotDetail();
            $list = $modelInvitationGotDetail->getListByPage($invitationId, 1, $info['invited_total']);
            $this->assign('list', $list['datas']);
        } catch (Exception $e) {
            var_dump(exceptionMsg($e));
        }
    }

    /**
     * 从分享url点击进来，先经过微信授权之后，进入这个页面，获取邀请函
     *
     * @throws Exception
     */
    public function receiveAction()
    {
        // http://iwebsite2/weixininvitation/index/receive?jsonpcallback=?&FromUserName=xxx&invitationId=xxx
        try {
            $FromUserName = trim($this->get('FromUserName', '')); // FromUserName
            if (empty($FromUserName)) {
                throw new Exception("微信ID为空", - 1);
            }
            $invitationId = trim($this->get('invitationId', ''));
            if (empty($invitationId)) {
                throw new Exception("邀请函ID为空", - 2);
            }
            // 获取发送过几次邀请函
            $modelInvitation = new Weixininvitation_Model_Invitation();
            $info = $modelInvitation->getInfoById($invitationId);
            $this->assign('Invitation', $info);
            if (empty($info)) {
                throw new Exception("邀请函不存在", - 3);
            }
            
            // 判断该邀请函是否被同一个人领了
            $isSame = $modelInvitation->isSame($info, $FromUserName);
            $this->assign('isSame', $isSame ? 1 : 0);
            if ($isSame) {
                throw new Exception("被同一个人领了", - 4);
            }
            
            // 判断该邀请函是否领完了
            $isOver = $modelInvitation->isOver($info);
            $this->assign('isOver', $isOver ? 1 : 0);
            if ($isOver) {
                throw new Exception("领完了", - 5);
            }
            
            // 判断是否已领过
            $modelInvitationGotDetail = new Weixininvitation_Model_InvitationGotDetail();
            $isGot = $modelInvitationGotDetail->isGot($FromUserName);
            $this->assign('isGot', $isGot ? 1 : 0);
            if ($isGot) {
                throw new Exception("已领过", - 6);
            }
            
            // 增加接受邀请函处理
            try {
                // 检查是否锁定，如果没有锁定加锁
                if ($modelInvitation->lock($invitationId)) {
                    // 前次操作尚未完成
                    throw new Exception('前次操作尚未完成', - 7);
                }
                
                $InvitationGotInfo = $modelInvitationGotDetail->create($invitationId, $info['FromUserName'], $FromUserName);
                $this->assign('InvitationGotInfo', $InvitationGotInfo);
                
                // 领取次数加一
                $modelInvitation->incGotNum($invitationId);
                // unlock
                // 释放锁定
                $modelInvitation->unlock($invitationId);
            } catch (Exception $e) {
                throw new Exception("系统发生了错误", $e->getCode());
            }
        } catch (Exception $e) {
            var_dump(exceptionMsg($e));
        }
    }
}

