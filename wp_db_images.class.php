<?php
require_once(__DIR__."/wp_db_switch.class.php");
/**
 * 繼承自wp_db_switch開發的MySQL操作物件，主要是用於wordpress系統的圖片讀取
 * @requires wp_db_switch.class.php 父類別物件
 * @author Lornzo Lee(李赤兔) <a6288678@hotmail.com>
 * @version 2020.8.24
 */
class wp_db_images extends wp_db_switch{
    /**
     * 取出所有圖片的縮圖
     * @param array $wp_posts_ids 想要選的id
     * @param array $ignore 不想要選的post id
     * @return array
     */
    public function getWpImageThumbnail(array $wp_posts_ids = array(),array $ignore=array()){
        $this->setBuffer();
        
        /*先取出圖片id*/
        $condition = array();
        $condition[] = "post_type='attachment'";
        $condition[] = "post_mime_type IN ('".implode("','", $this->_getWpImageMineTypes())."')";
        if(!empty($wp_posts_ids)){$condition[] = "ID IN ('".implode("','", $wp_posts_ids)."')";}
        if(!empty($ignore)){$condition[] = "ID NOT IN('".implode("','", $ignore)."')";}
        $images_data = $this->setTable($this->_wp_table_pre."posts")->setSelect(array())->useLimit(false)->listData($condition, array("ORDER BY post_date DESC"));
        $images_id = array();
        $images = array();
        if(!empty($images_data)){
            foreach($images_data as $img_data){
                $images_id[] = $img_data["ID"];
                $images[$img_data["ID"]] = $img_data;
            }
        }

        $result = array();

        /*直接取出圖片的meta*/
        $condition = array();
        $condition[] = "meta_key = '_wp_attachment_metadata'";
        $condition[] = "post_id IN ('". implode("','", $images_id)."')";
        $images_meta = $this->setSelect(array())->setTable($this->_wp_table_pre."postmeta")->listData($condition);
        if(!empty($images_meta)){
            foreach($images_meta as $img_meta){
                if(!empty($images[$img_meta["post_id"]])){
                    $buffer = $this->_setupWpImageData($images[$img_meta["post_id"]], $img_meta["meta_value"]);
                    if(!empty($buffer["thumb"]["file"])){
                        $result[$img_meta["post_id"]] = array("name"=>!empty($images[$img_meta["post_id"]]["post_title"])?$images[$img_meta["post_id"]]["post_title"]:"","file"=>$buffer["thumb"]["file"]);
                    }
                }
            }
        }
        unset($images_data,$images_id,$images,$images_meta);
        $this->releaseBuffer();
        return $result;
    }
}
?>