<?php
class Cronjob_WeiboController extends Zend_Controller_Action
{
    private $_db;
    
    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        
        //直接连接数据库操作
        $options = array();
        $options['connectTimeoutMS'] = 3600000;
        $options['socketTimeoutMS'] = 3600000;
        $options['w'] = 1;
        $options['wTimeout'] = 3600000;
        $mongoConnect = new MongoClient('mongodb://localhost:27017',$options);
        $mongoConnect->setReadPreference(MongoClient::RP_SECONDARY_PREFERRED);
        $this->_db = $mongoConnect->selectDB('umav3');

    }

    public function indexAction()
    {
        
    }
    
    private function getWord($_id) {
        $wordModel = new MongoCollection($this->_db,'iDatabase.51d537464896193c610002ba');
        $rst =  $wordModel->findOne(array('_id'=>new MongoId($_id)));
        return $rst['keyword'];
    }
    
    public function importAction() {
        try {
            $keyword = $this->_request->getParam('keyword');
            $word = $this->getWord($keyword);
            
            $weiboModel = new MongoCollection($this->_db,'weibo');
            $keywordModel = new MongoCollection($this->_db,'iDatabase.51d52e6f499619e8090002af');
            $keywordModel->remove(array('keyword'=>$keyword));
            
            $cursor = $weiboModel->find(array('scws.word'=>$word,'scws.tf'=>array('$exist'=>true)),array('scws'=>true,'_id'=>true));
            $cursor->limit(100000);
            while($cursor->hasNext()) {
                $row = $cursor->getNext();
                $batchInsert = array();
                foreach($row['scws'] as $wordInfo) {
                    if(isset($wordInfo['tf'])) {
                        $insert = array();
                        $insert['keyword'] = $keyword;
                        $insert['word']    = $wordInfo['word'];
                        $insert['attr']    = $wordInfo['attr'];
                        $insert['doc_id']  = $row['_id']->__toString();
                        $insert['tf']      = $wordInfo['tf'];
                        $insert['idf']     = 0.0;
                        $insert['tf_idf']  = 0.0;
                        $insert['createTime'] = new MongoDate();
                        $batchInsert[] = $insert;
                    }
                }
                
                if(!empty($batchInsert)) {
                    $keywordModel->batchInsert($batchInsert,array('continueOnError'=>true));
                }
            }
            
            echo 'OK';
        }
        catch (Exception $e) {
            echo $e->getLine().$e->getMessage();
        }
    }
    
    public function idfAction() {
        try {
            $keyword = $this->_request->getParam('keyword');
            
            $keywordModel = new MongoCollection($this->_db,'iDatabase.51d52e6f499619e8090002af');
            $total = $keywordModel->count(array('keyword'=>$keyword));
            
            $result = $keywordModel->aggregate(array(
                array('$match'=>array(
                    'keyword'=>$keyword
                )),
                array('$group'=>array(
                    '_id'=>'$word',
                    'count'=>array('$sum'=>1)
                ))        
            ));
            
            foreach($result['result']  as $row) {
                if($row['count']>0) {
                    $idf = log($total/$row['count']);
                    $keywordModel->update(
                        array('word'=>$row['_id'],'keyword'=>$keyword),
                        array('$set'=>array('idf'=>$idf)),
                        array('multiple'=>true)
                    );
                }
            }
            
            echo 'OK';
        }
        catch(Exception $e) {
            echo $e->getLine().$e->getMessage();
        }
    }
    
    public function tfidfAction() {
        try {
            $keyword = $this->_request->getParam('keyword');
            $keywordModel = new MongoCollection($this->_db,'iDatabase.51d52e6f499619e8090002af');
            $cursor = $keywordModel->find(array('keyword'=>$keyword));
            while($cursor->hasNext()) {
                $row = $cursor->getNext();
                $tf_idf = $row['tf']*$row['idf'];
                $keywordModel->update(
                    array('_id'=>$row['_id']),
                    array('$set'=>array('tf_idf'=>$tf_idf))
                );
            }
            
            echo 'OK';
        }
        catch(Exception $e) {
            echo $e->getLine().$e->getMessage();
        }
    }
}

