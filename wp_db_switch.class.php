<?php
if(!class_exists("sqli_db_switch")){require(__DIR__."/sqli_db_switch.class.php");}
/**
 * 繼承自sqli_db_switch開發的MySQL操作物件，主要是用於wordpress的資庫讀取
 * @requires sqli_db_switch.class.php 父類別物件
 * @author Lornzo Lee(李赤兔) <a6288678@hotmail.com>
 * @version 2020.7.22
 */
class wp_db_switch extends sqli_db_switch{
    
    protected $_wp_table_pre = "wp_";
    protected $_wp_table = "";
    
    public function setWpTablePre(string $pre){
        $this->_wp_table = $pre;return $this;
    }
    
    public function setWpTable(string $table_name){
        $this->_wp_table = $table_name;return $this;
    }
    
    protected function getWpTable(){
        
    }
    
    public function getPosts(string $order_by="" , string $post_status = "publish"){
        
        $result = array();
        
        $querys = array();
        
        /*會需要用id來當索引的就要先做*/
        
        
        
        /*0.取出文章本體*/
        
        /*取出文章內容本體的MySQL語句*/
        $condition = array();
        $condition[] = "post_type='post'";
        $condition[] = "post_status='".$this->dataFilter($post_status)."'";
        
        /**
         * @var string 提取條件下文章的MySql query string
         */
        $article_query = $this->setTable("wp_posts")->getSelectString($condition);
        
        /**
         * @var string 提取有被拿出來的文章ID(MySql Query String)，要拿來取出精選圖片用的
         */
        $article_id_query = $this->setTable("wp_posts")->setSelect(array("ID"))->getSelectString($condition, array(), false);
        
        /**
         * @var string 提取有被拿出來的作者ID(MySql Query String)，要拿來取出作者名字用的
         */
        $article_author_query = $this->setTable("wp_posts")->setSelect(array("DISTINCT post_author"))->getSelectString($condition,array(),false);

        /*select歸零*/
        $this->setSelect(array());
        
        /*0. 取出有出現的作者名字*/
        $condition = array();
        $condition[] = "ID IN (".$article_author_query.")";
        $querys[] = $this->setTable("wp_users")->setSelect(array("ID","display_name"))->getSelectString($condition);
        
        /*1.取出文章的精選圖片*/
        $condition = array();
        $condition[] = "post_type='attachment'";
        $condition[] = "post_parent IN (".$article_id_query.")";
        $querys[] = $this->setTable("wp_posts")->setSelect(array())->getSelectString($condition);

        /*2.取出文章本體*/
        $querys[] = $article_query;

        $datas = $this->_combineWpPostsInfo($this->doQuerys($querys,true));
        print_r($datas);

    }
    
    protected function _combineWpPostsInfo(array $sqli_querys_datas){
        $result = array();
        print_r($sqli_querys_datas);
        $authors = array();
        if(!empty($sqli_querys_datas)){
            foreach($sqli_querys_datas as $datas_chunk){
                if(!empty($datas_chunk)){
                    foreach($datas_chunk as $section_no => $querys_sections){
                        if(!empty($querys_sections)){
                            foreach($querys_sections as  $data){
                                switch($section_no){
                                    /*作者資料表*/
                                    case "0":
                                        $authors[$data["ID"]] = $data["display_name"];
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        print_r($authors);exit();
        return $result;
    }
    
    /*下一個function*/
}
?>