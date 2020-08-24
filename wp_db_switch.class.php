<?php
if(!class_exists("sqli_db_switch")){require(__DIR__."/sqli_db_switch.class.php");}
/**
 * 繼承自sqli_db_switch開發的MySQL操作物件，主要是用於wordpress的資庫讀取
 * @requires sqli_db_switch.class.php 父類別物件
 * @author Lornzo Lee(李赤兔) <a6288678@hotmail.com>
 * @version 2020.7.22
 */
class wp_db_switch extends sqli_db_switch{
    
    /**
     * @var string wordpress資料表的前綴 
     */
    protected $_wp_table_pre = "wp_";
    
    /**
     * 設定wordpress資料表的前綴
     * @param string $pre
     * @return $this
     */
    public function setWpTablePre(string $pre){
        $this->_wp_table_pre = $pre;return $this;
    }

    /**
     * 取得wordpress裡面對於圖片mine type判斷的類型
     * @return array
     */
    protected function _getWpImageMineTypes(){
        return array("image/png","image/jpg","image/jpeg","image/gif");
    }
    
    /**
     * 整理wordpress的圖片資訊
     * @param array $image_post post
     * @param string $image_post_meta post_meta裡面的資料
     * @return string
     */
    protected function _setupWpImageData(array $image_post , string $image_post_meta){
        $image_dir = pathinfo($image_post["guid"],PATHINFO_DIRNAME);
        $image_meta = unserialize($image_post_meta);
        $result = array(
            "alt"=>$image_post["post_content"],
            "title"=>$image_post["post_title"],
            "origin"=>array("src"=>$image_post["guid"],"width"=>$image_meta["width"],"height"=>$image_meta["height"]),
            "srcset"=>array($image_meta["width"]=>$image_post["guid"]." ".$image_meta["width"]."w"),
            "thumb"=>array("size"=>$image_meta["width"],"file"=>$image_post["guid"])
        );
        if(!empty($image_meta["sizes"])){foreach($image_meta["sizes"] as $size_name => $size_data){
            /**
             * (勿刪)暫時先註解掉：存在切圖的各種size
             * $result["articles"][$data["post_parent"]]["image"][$size_name] = array("src"=>$image_dir."/".$size_data["file"],"width"=>$size_data["width"],"height"=>$size_data["height"]);
             */
            $result["srcset"][$size_data["width"]] = $image_dir."/".$size_data["file"]." ".$size_data["width"]."w";
            if($size_data["width"] < $result["thumb"]["size"]){$result["thumb"]=array("size"=>$size_data["width"],"file"=>$image_dir."/".$size_data["file"]);}
        }ksort($result["srcset"]);}
        return $result;
    }

    /*下一個function*/
}
?>