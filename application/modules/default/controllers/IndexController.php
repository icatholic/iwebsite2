<?php

class Default_IndexController extends Zend_Controller_Action
{

    private $_resource;

    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
    }

    /**
     *
     * @name 获取资源列表
     */
    public function indexAction()
    {
        echo 'ok';
        try {
            $model = new Campaign_Model_User_Info();
            var_dump($model->findOne(array()));
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    public function testAction()
    {
        $app = new Weixin_Model_Application();
        $appConfig = $app->getToken();
        $weixin = new Weixin\Client();
        if (! empty($appConfig['access_token'])) {
            $weixin->setAccessToken($appConfig['access_token']);
            $content = '恭喜您获得500积分，您的好友已经接受关注高洁丝官方微信的邀请！';
            $weixin->getMsgManager()
                ->getCustomSender()
                ->sendText('o8NOajuFB07kWd4eHbKhY24OXPFE', $content);
        }
    }
}