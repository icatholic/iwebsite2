<?php

class Weixinsign_IndexController extends iWebsite_Controller_Action
{

    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender(false);
    }

    /**
     * 首页
     */
    public function indexAction()
    {}

}

