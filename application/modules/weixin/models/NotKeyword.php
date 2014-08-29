<?php

class Weixin_Model_NotKeyword extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixin_not_keyword';

    protected $dbName = 'weixin';
    
    protected $secondary = true;

    public function record($msg)
    {
        $query = array(
            'msg' => $msg
        );
        $count = $this->count($query);
        if ($count > 0) {
            $this->update($query, array(
                '$inc' => array(
                    'times' => 1
                )
            ));
        } else {
            $data = array();
            $data['msg'] = $msg;
            $data['times'] = 1;
            $this->insert($data);
        }
    }
}