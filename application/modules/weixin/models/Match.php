<?php

class Weixin_Model_Match extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixin_keyword_match_info';

    protected $dbName = 'weixin';

//     public function addMatchinfo($type, $keyword_id)
//     {
//         $data = array();
//         $match['match_type'] = $type;
//         $match['keyword_id'] = $keyword_id;
//         $this->insertAsync($match);
//     }
}