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
     * @param array $order_by 排序
     * @param string $post_status
     * @return type
     */
    public function getPosts(array $order_by = array() , string $post_status = "publish"){

        /*一次讀取的MySql語句*/
        $querys = array();
        
        /*先把目前的Table設定存起來*/
        $table_buffer = $this->_table;
        
        /*會需要用id來當索引的就要先做*/

        /*取出的文章ID*/
        $posts_id = array();
        
        /*文章的作者id*/
        $posts_author_id = array();
        
        /*先取出文章本體，然後加以整理*/
        $condition = array();
        $condition[] = "post_type='post'";
        $condition[] = "post_status='".$this->dataFilter($post_status)."'";
        
        /*文章本體*/
        $posts = $this->setTable($this->_wp_table_pre."posts")->listData($condition, $order_by);
        unset($condition);
        
        $result = array();
        
        if(!empty($posts)){
            
            foreach($posts as $post){
                $posts_id[] = $post["ID"];
                $posts_author_id[] = $post["post_author"];
            }
            /*0. 取出有出現的作者名字*/
            $querys[] = $this->setTable($this->_wp_table_pre."users")->setSelect(array("ID","display_name"))->getSelectString(array("ID IN ('". implode("','", $posts_author_id)."')"));

            /*1.取出文章的keywords tag 資料 - wp_terms*/
            $article_tags_relation_id = $this->setTable($this->_wp_table_pre."term_relationships")->setSelect(array("term_taxonomy_id"))->getSelectString(array("object_id IN ('". implode("','", $posts_id)."')"),array("ORDER BY term_order ASC"),false);
            $article_term_taxonomy_id = $this->setTable($this->_wp_table_pre."term_taxonomy")->setSelect(array("term_id"))->getSelectString(array("taxonomy='post_tag'","term_taxonomy_id IN (".$article_tags_relation_id.")"),array(),false);

            $querys[] =  $this->setTable($this->_wp_table_pre."terms")->setSelect(array())->getSelectString(array( "term_id IN (".$article_term_taxonomy_id.")"));

            /*2. 取出keywords tag類型表 - wp_term_taxonomy*/
            $querys[] = $this->setTable($this->_wp_table_pre."term_taxonomy")->setSelect(array())->getSelectString(array("taxonomy = 'post_tag'","term_id IN (".$article_term_taxonomy_id.")"));

            /*3. 取出keywords tag的關聯表 0 wp_term_relationships*/
            $querys[] = $this->setTable($this->_wp_table_pre."term_relationships")->setSelect(array())->getSelectString(array("object_id IN ('". implode("','", $posts_id)."')"));

            /*4.取出文章的精選圖片*/
            $querys[] = $this->setTable($this->_wp_table_pre."posts")->setSelect(array())->getSelectString(array("post_type='attachment'","post_parent IN ('". implode("','", $posts_id)."')"));

            $result = $this->_combineWpPostsInfo($posts , $this->doQuerys($querys,true));
        }
        $this->_table = $table_buffer;

        return $result;
    }
    
    /**
     * 組合一篇文章所有的相關資訊
     * @param array $sqli_querys_datas
     * @return array
     */
    protected function _combineWpPostsInfo(array $posts,array $sqli_querys_datas){

        $result = array();

        $authors = array();
        
        $terms = array();
        
        $taxonomys = array();
        
        if(!empty($sqli_querys_datas)){
            foreach($sqli_querys_datas as $datas_chunk){
                if(!empty($datas_chunk)){
                    $datas_chunk[] = $posts;
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