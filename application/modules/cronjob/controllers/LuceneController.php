<?php

class Cronjob_LuceneController extends Zend_Controller_Action
{

    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function indexAction()
    {
        //建立全文索引
    }
    
    /**
     * 重新创立索引
     */
    public function reIndexAction() {
        try {
            if($_SERVER['SERVER_ADDR']=='10.0.0.10') {
                $indexPath = dirname(APPLICATION_PATH).'/lucene/index';
                $index = new Zend_Search_Lucene($indexPath, true); 
                $doc = new Zend_Search_Lucene_Document(); 
                $docText = '杨明测试分词yang ming 测试 123.521，结果如何呢？测试测试测试测试';
                $docContent = "杨明具体内容写在这里";
                $doc->addField(Zend_Search_Lucene_Field::Text('title', $docText,'utf-8'));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('contents', $docContent,'utf-8'));     
                $index->addDocument($doc);
                
                $docText = '张三丰测试分词yang ming 测试 123.521，结果如何呢？测试测';
                $docContent = "张三丰具体内容写在这里";
                $doc->addField(Zend_Search_Lucene_Field::Text('title', $docText,'utf-8'));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('contents', $docContent,'utf-8'));
                $index->addDocument($doc);
                
                $index->commit();
                echo 'OK';
            }
            else {
                echo $_SERVER['SERVER_ADDR'];
            }
        }
        catch (Exception $e) {
            var_dump($e->getLine().$e->getMessage());
        }
    }
    
    /**
     * 内容
     */
    public function searchAction() {
        try {
            $indexPath = dirname(APPLICATION_PATH).'/lucene/index';
            $index = Zend_Search_Lucene::open($indexPath);
            $searchText = '测试';
            $query = Zend_Search_Lucene_Search_QueryParser::parse($searchText,'utf-8');
            $hits = $index->find($query,'score',SORT_NUMERIC, SORT_DESC);
            echo "搜索:".$searchText;
            echo "<br /><hr>";
            foreach ($hits as $hit) {
                echo $hit->title.'<br />';
                echo $hit->score.'<br /><hr>';
            }
        }
        catch (Exception $e) {
            var_dump($e->getLine().$e->getMessage());
        }
        
    }
}

