<?php

class Cronjob_TfidfController extends Zend_Controller_Action
{
    private $_weixin;
    private $_total_article;
    private $_db;
    
    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_total_article = $this->totalArticle();
        
        //直接连接数据库操作
        $options = array();
        $options['connectTimeoutMS'] = 300000;
        $options['socketTimeoutMS'] = 300000;
        $options['w'] = 1;
        $options['wTimeout'] = 30000;
        $mongoConnect = new MongoClient('mongodb://localhost:27017',$options);
        $mongoConnect->setReadPreference(MongoClient::RP_SECONDARY_PREFERRED);
        $this->_db = $mongoConnect->selectDB('umav3');

    }

    public function indexAction()
    {
        
    }
    
    public function importAction() {
        $titleContentModel = new Cronjob_Model_Tfidf_TitleContent();
        $replyModel = new Cronjob_Model_Dumex_Reply();
        $rst = $replyModel->findAll(array());
        
        $titleContentModel->remove(array());

        foreach ($rst['datas'] as $row) {
            if(isset($row['title']) && $row['title']!='') {
                $titleContentModel->insert(array('title'=>$row['title'],'content'=>$row['description']));
            }
        }
        echo 'finished';
    }
    
    private function totalArticle() {
        $titleContentModel = new Cronjob_Model_Tfidf_TitleContent();
        return $titleContentModel->count(array());
    }
    
    public function splitAction() {
        $tfIdfModel = new MongoCollection($this->_db,'iDatabase.51b9789c499619352800069f');
        $tfIdfModel->remove(array());
        $titleContentModel = new Cronjob_Model_Tfidf_TitleContent();
        $all = $titleContentModel->findAll(array(),null,array('title'=>true,'content'=>true));
        foreach($all['datas'] as $row) {
            $title = $row['title'].$row['content'];
            if($title!='') {
                $arr = scws($title);
                $total = count($arr);
                if(is_array($arr) && $total>0) {
                    $insertArr = array();
                    foreach($arr as $cell) {
                        $tf = doubleval(substr_count($title,$cell['word'])/$total);
                        $word = trim($cell['word']);
                        if($word!='') {
                            $insertArr[] = array('word'=>$word,'reply_id'=>$row['_id'],'tf'=>$tf,'attr'=>$cell['attr'],'createTime'=>new MongoDate());
                        }
                    }
                    $tfIdfModel->batchInsert($insertArr,array('continueOnError'=>true));
                }
            }
        }
        echo __FUNCTION__.' is finished';
    }
    
    public function split1Action() {
        $tfIdfModel = new MongoCollection($this->_db,'iDatabase.51b9789c499619352800069f');
        $tfIdfModel->remove(array());
        $titleContentModel = new Cronjob_Model_Tfidf_TitleContent();
        $all = $titleContentModel->findAll(array(),null,array('title'=>true,'content'=>true));
        foreach($all['datas'] as $row) {
            $title = $row['title'].$row['content'];
            if($title!='') {
                $arr = scws($title);
                $total = count($arr);
                if(is_array($arr) && $total>0) {
                    $insertArr = array();
                    foreach($arr as $cell) {
                        $tf = doubleval(substr_count($title,$cell['word'])/$total);
                        $insertArr[] = array('word'=>$cell['word'],'reply_id'=>$row['_id'],'tf'=>$tf,'createTime'=>new MongoDate());
                    }
                    $tfIdfModel->batchInsert($insertArr,array('continueOnError'=>true));
                }
            }
        }
        echo __FUNCTION__.' is finished';
    }
    
    
    public function idfAction() {
        $tfIdfModel = new MongoCollection($this->_db,'iDatabase.51b9789c499619352800069f');
        $cursor = $tfIdfModel->find(
            array('$or'=>array(
                array('idf'=>array('$exists'=>false)),
                array('idf'=>0)
            ))
        );
        $cursor->sort(array('_id'=>-1));
        while($cursor->hasNext()) {
            $row = $cursor->getNext();
            $distinct = $tfIdfModel->distinct('reply_id', array('word'=>$row['word']));
            $number   = count($distinct);
            if ($number==0) $number = 1;
            $idf      = log($this->_total_article/$number);
            $tfIdfModel->update(array('_id'=>$row['_id']), array('$set'=>array('idf'=>$idf,'tf_idf'=>$row['tf']*$idf)));
        }
        echo __FUNCTION__.' is finished';
    }
    

    public function keywordAction() {
        $keywordModel = new MongoCollection($this->_db,'iDatabase.51bee5b0499619487200021f');
        $tfIdfModel = new MongoCollection($this->_db,'iDatabase.51b9789c499619352800069f');
        
        $keywordModel->remove(array());
        
        $attrs = array('Ag','a','ad','an','Dg','d','f','g','i','l','Ng','n','nr','ns','nt','nz','s','Tg','t','Vg','v','vd','vn','x','z');
        
        $cursor = $tfIdfModel->find(array('attr'=>array('$in'=>$attrs)));
        $cursor->sort(array('tf_idf'=>-1));
        while($cursor->hasNext()) {
            $row = $cursor->getNext();
            if(trim($row['word'])!='') {
                $keywordModel->findAndModify(
                    array('keyword'=>$row['word']),
                    array('$set'=>array('keyword'=>$row['word'],'tf_idf'=>$row['tf_idf'],'attr'=>$row['attr'],'createTime'=>new MongoDate())),
                    null,
                    array('upsert'=>true) 
                );
            }
        }
        echo __FUNCTION__.' is finished';
    }
    
    public function testAction() {
        echo '123';
    }
    
}

