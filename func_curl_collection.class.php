<?php
if(!class_exists("func_curl")){require(__DIR__."/func_curl.class.php");}
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class func_curl_collection extends func_curl{
    

    public function setCollectionRule(){}

    /**
     * 
     */
    public function collection(){

        $html = str_replace(array("\n"), array(""), $this->getRequest());
        
        $dom = new DOMDocument();
        $dom->formatOutput=true;
        $dom->preserveWhiteaSpace = false;
        
        /*暫時使用語法糖來避免錯誤被輸出*/
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        //$html = $dom->getElementsByTagName("html");
        $html = $dom->getElementById("dRListBox");
//        print_r($html);exit();
        /*在這裡就直接指定主要的html位置*/
        $node_arr["html"] = $this->getHtmlNode($html);
        print_r($node_arr);
        exit();

    }
    
    /**
     * 取出某個節點以下的所有內容
     * @param DOMElement $node
     * @return array
     */
    public function getHtmlNode(DOMElement $node){
        $result = $this->getHtmlNodeByTag($node);
        if($node->hasChildNodes() && !empty($result)){
            for($i=0;$i<$node->childNodes->length;$i+=1){
                if(!empty($node->childNodes->item($i)->localName)){
                    $_node_data = $this->getHtmlNode($node->childNodes->item($i));
                    if(!empty($_node_data)){
                        $result["child"][] = $_node_data;
                    }
                }
            }
        }
        return $result;
    }
    
    /**
     * 取出單一Node裡面的內容
     * @param DOMElement $node
     * @return array
     */
    public function getHtmlNodeByTag(DOMElement $node){
        $result = array();
        if(!empty($node->localName) && !in_array($node->localName,$this->_getNoNeedToDoTrash())){
            $result = array("tag"=>$node->localName);
            
            if($node->hasAttribute("class")){
                $result["classes"] = array_filter(explode(" ", $node->getAttribute("class")));
                $result["class"] = implode(" ", $result["classes"]);
            }
            
            if($node->hasAttribute("id")){
                $result["ids"] = array_filter(explode(" ", $node->getAttribute("id")));
                $result["id"] = implode(" ", $result["ids"]);
            }

            if(!in_array($node->localName, $this->_getNoNeedToDo())){
                $_content = !empty($node->textContent)?str_replace(array(" ","\n"), "",$node->textContent):"";
                switch($node->localName){
                    case "title":
                        $result["content"] = $node->textContent;
                        break;
                    case "meta":
                        $result["name"] = !empty($node->getAttribute("charset"))?"charset":$node->getAttribute("name");
                        $result["content"] = !empty($node->getAttribute("charset"))?$node->getAttribute("charset"):$node->getAttribute("content");
                        break;
                    case "script":
                        break;
                    case "style":
                        break;
                    case "a":
                        $result["href"] = $node->hasAttribute("href")?$node->getAttribute("href"):"";
                        $result["content"] = $_content;
                        break;
                    default:
                        $result["content"] = $_content;
                        break;
                }
            }
        }
        return !empty($result)?$result:array();
    }

    /**
     * 取出在採集時，除了tag名稱以外，其寫勵資料都不需要收枈的元素
     * @return array
     */
    protected function _getNoNeedToDo(){
        return array(
            "html","head","body"
        );
    }
    
    /**
     * 取出在採集時，可以直接跳過不做的元素
     * @return array
     */
    protected function _getNoNeedToDoTrash(){
        return array("br","\n");
    }
}
?>
