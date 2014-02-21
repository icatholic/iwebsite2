<?php 
class iWebsite_Plugin_Lucene_Scws extends Zend_Search_Lucene_Analysis_Analyzer_Common
{
    protected $_encoding = 'UTF-8';
    
    public function reset() {
        $this->_position = 0;
        $this->_input = scws($this->_input);
    }
    
    public function nextToken() {
        if ($this->_input===null||empty($this->_input)) {
            return null;
        }
        
        do {
            if(!isset($this->_input[$this->_position])) {
                return null;
            }
            $text   = $this->_input[$this->_position]['word'];
            $start  = $this->_input[$this->_position]['off'];
            $end    = $start + $this->_input[$this->_position]['len'];
            $token  = $this->normalize(new Zend_Search_Lucene_Analysis_Token($text, $start, $end));
            $this->_position += 1;
        }
        while ($token === null);
        return $token;
    }
}