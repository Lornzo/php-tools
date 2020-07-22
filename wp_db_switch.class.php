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
     * @取出文章
     * @param array $order_by
     * @param string $post_status
     * @return type
     */
    public function getPosts(array $order_by = array() , string $post_status = "publish"){
        
        $result = array();
        
        $querys = array();
        
        /*會需要用id來當索引的就要先做*/

        /*取出文章內容本體的MySQL語句*/
        $condition = array();
        $condition[] = "post_type='post'";
        $condition[] = "post_status='".$this->dataFilter($post_status)."'";
        
        /**
         * @var string 提取條件下文章的MySql query string
         */
        $article_query = $this->setTable("wp_posts")->getSelectString($condition,$order_by);
        
        /**
         * @var string 提取有被拿出來的文章ID(MySql Query String)，要拿來取出精選圖片用的
         */
        $article_id_query = $this->setTable("wp_posts")->setSelect(array("ID"))->getSelectString($condition, $order_by, false);
        
        /**
         * @var string 提取有被拿出來的作者ID(MySql Query String)，要拿來取出作者名字用的
         */
        $article_author_query = $this->setTable("wp_posts")->setSelect(array("DISTINCT post_author"))->getSelectString($condition,$order_by,false);

        /*select歸零*/
        $this->setSelect(array());
        
        /*0. 取出有出現的作者名字*/
        $condition = array();
        $condition[] = "ID IN (".$article_author_query.")";
        $querys[] = $this->setTable("wp_users")->setSelect(array("ID","display_name"))->getSelectString($condition);
        
        /*1.取出文章的keywords tag 資料 - wp_terms*/
        $condition = array();
        $condition[] = "object_id IN (".$article_id_query.")";
        $article_tags_relation_id = $this->setTable("wp_term_relationships")->setSelect(array("term_taxonomy_id"))->getSelectString($condition,array("ORDER BY term_order ASC"),false);
        
        $condition = array();
        $condition[] = "taxonomy='post_tag'";
        $condition[] = "term_taxonomy_id IN (".$article_tags_relation_id.")";
        $article_term_taxonomy_id = $this->setTable("wp_term_taxonomy")->setSelect(array("term_id"))->getSelectString($condition,array(),false);
        
        $condition = array();
        $condition[] = "term_id IN (".$article_term_taxonomy_id.")";
        $querys[] =  $this->setTable("wp_terms")->setSelect(array())->getSelectString($condition);

        /*2. 取出keywords tag類型表 - wp_term_taxonomy*/
        $condition = array();
        $condition[] = "taxonomy = 'post_tag'";
        $condition[] = "term_id IN (".$article_term_taxonomy_id.")";
        $querys[] = $this->setTable("wp_term_taxonomy")->setSelect(array())->getSelectString($condition);
        
        /*3. 取出keywords tag的關聯表 0 wp_term_relationships*/
        $condition = array();
        $condition[] = "object_id IN (".$article_id_query.")";
        $querys[] = $this->setTable("wp_term_relationships")->setSelect(array())->getSelectString($condition);
        
        
        /*4.取出文章的精選圖片*/
        $condition = array();
        $condition[] = "post_type='attachment'";
        $condition[] = "post_parent IN (".$article_id_query.")";
        $querys[] = $this->setTable("wp_posts")->setSelect(array())->getSelectString($condition,$order_by);

        /*最後取出文章本體*/
        $querys[] = $article_query;

        return $this->_combineWpPostsInfo($this->doQuerys($querys,true));
    }
    
    /**
     * 組合一篇文章所有的相關資訊
     * @param array $sqli_querys_datas
     * @return array
     */
    protected function _combineWpPostsInfo(array $sqli_querys_datas){
        $result = array();

        $authors = array();
        
        $terms = array();
        
        $taxonomys = array();
        
        if(!empty($sqli_querys_datas)){
            foreach($sqli_querys_datas as $datas_chunk){
                if(!empty($datas_chunk)){
                    foreach($datas_chunk as $section_no => $querys_sections){
                        if(!empty($querys_sections)){
                            foreach($querys_sections as  $data){
                                /*section no 跟放進去的query string 順序有關係*/
                                switch($section_no){
                                    /*作者資料表*/
                                    case "0":
                                        $authors[$data["ID"]] = $data["display_name"];
                                        break;
                                    /*$terms*/
                                    case "1":
                                        $terms[$data["term_id"]] = $data;
                                        break;
                                    /*wp_term_taxonomy*/
                                    case "2":
                                        if(!empty($terms[$data["term_id"]])){
                                            $taxonomys[$data["term_taxonomy_id"]] = $data;
                                            $taxonomys[$data["term_taxonomy_id"]]["term_name"] = $terms[$data["term_id"]]["name"];
                                        }
                                        break;
                                    /*wp_term_relationships*/
                                    case "3":
                                        if(!empty($taxonomys[$data["term_taxonomy_id"]])){
                                            $result[$data["object_id"]]["keywords"][$taxonomys[$data["term_taxonomy_id"]]["term_id"]] = $taxonomys[$data["term_taxonomy_id"]]["term_name"];
                                            $result[$data["object_id"]]["keywords_info"][$data["term_taxonomy_id"]] = $taxonomys[$data["term_taxonomy_id"]];
                                        }
                                        break;
                                    /*精選圖片*/
                                    case "4":
                                        $result[$data["post_parent"]]["show_pic"] = array("src"=>$data["guid"],"alt"=>$data["post_content"],"title"=>$data["post_title"]);
                                        break;
                                    /*文章本體*/
                                    default:
                                        $result[$data["ID"]]["post_author_name"] = !empty($authors[$data["post_author"]])?$authors[$data["post_author"]]:"";
                                        $result[$data["ID"]]["data"] = $data;
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }
    
    /*下一個function*/
}
?>