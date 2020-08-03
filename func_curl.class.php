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
     * 全手動設定curlopt
     * @param array $options
     * @return $this
     */
    public function setCurlOptAll(array $options){
        $this->_curl_option = $optionsr;return $this;
    }
    
    /**
     * 移除curl的參數選項
     * @param array $keys
     * @return $this
     */
    public function rmCurlOpt(array $keys){
        if(!empty($keys)){foreach($keys as $key){if(array_key_exists($key, $this->_curl_option)){unset($this->_curl_option[$key]);}}}return $this;
    }

    /**
     * 送出一個get request，並且return結果
     * @return string 照常理來說，會是字串。
     */
    public function getRequest(){
        return $this->sendRequest();
    }
    
    /**
     * 送出一個post request，並且retur n結果
     * @return string 照常理來說會是字串
     */
    public function postRequest(){
        return $this->sendRequest();
    }
    
    /**
     * 送出request
     * @return string
     */
    public function sendRequest(){
        $ch = curl_init();
        curl_setopt_array($ch, $this->_curl_option);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}