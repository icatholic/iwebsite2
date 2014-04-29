<?php

class Lottery_TestController extends iWebsite_Controller_Action
{

    private $_lock;

    public function init()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $this->_lock = new Lottery_Model_Lock();
    }

    public function indexAction()
    {
        try {
            $activity_id = '5355f0424996197f2c8b457d';
            $uniqueId = date("YmdH");
            $check = $this->_lock->lock($activity_id, $uniqueId);
            var_dump($check);
            if($check) {
                echo "locking";
                return false;
            }
            sleep(5);
            $this->_lock->release($activity_id, $uniqueId);
            echo 'end';
        } catch (Exception $e) {
            var_dump(exceptionMsg($e));
        }
    }
}