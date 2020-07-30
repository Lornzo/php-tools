<?php
/**
 * 基於mysqli開發的MySQL讀寫物件
 * @author Lornzo Lee(李赤兔) <a6288678@hotmail.com>
 * @version 2020.7.30
 */
class func_curl{

    protected $_curl_option = array(CURLOPT_RETURNTRANSFER=>1);

    /**
     * 設定想要curl的網址
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url){
        $this->_curl_option[CURLOPT_URL] = $url;return $this;
    }

    /**
     * 設定想要打出去的header
     * @param array $header
     * @return $this
     */
    public function setHeader(array $header){
        $this->setCurlOpt(CURLOPT_HEADER, $header);return $this;
    }
    
    /**
     * 設定curl的參數選項
     * @param array $options
     * @return $this
     */
    public function setCurlOpt(array $options){
        if(!empty($options)){foreach($options as $key => $option){$this->_curl_option[$key] = $option;}}return $this;
    }
    
    /**
     * 移除curl的參數選項
     * @param array $keys
     * @return $this
     */
    public function rmCurlOpt(array $keys){
        if(!empty($keys)){foreach($keys as $key){if(array_key_exists($key, $this->_curl_option)){unset($this->_curl_option[$key]);}}}return $this;
    }

    public function get(){
        return $this->fetchPage();
    }
    
    public function post(){}
    
    public function fetchPage(){
        $ch = curl_init();
        curl_setopt_array($ch, $this->_curl_option);
        $html = curl_exec($ch);
        curl_close($ch);
        
        $dom = new DOMDocument(5,"UTF-8");
        $dom->loadHTML($html);
        print_r($dom->getElementsByTagName("section"));
        
        
        //$rule_str = "/(<([\w]+)[^>]*>)(.*?)(<\/\\2>)/";
//        $rule_str = "/<(.*)>.*<\/\1>|<(.*) \/>/";
        //echo preg_match("/(<([\w]+)[^>]*>)(.*?)(<\/\\2>)/", $data,$match);exit();
        //preg_match_all('#<body[^>]*>#i', $data, $match);  
//        preg_match_all('/<body[^>]*>(.*)<\/body>/is',$data,$match);
//preg_match_all("/(<([\w]+)[^>]*>)(.*?)(<\/\\2>)/", $data, $match,PREG_SET_ORDER);
//        preg_match_all('/<div[^>]*>(.*)<\/div>/is',$data,$match);
//        print_r($match);exit();
        //print_r(strip_tags($data,"div"));
//preg_match_all('/<div[^>]+>/i',$html, $result); 

//print_r($result);
//        exit();

        exit();
        return $data;
    }
    


}