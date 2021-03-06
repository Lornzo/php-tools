<?php
require_once(__DIR__."/wp_db_switch.class.php");
/**
 * 繼承自wp_db_switch開發的MySQL操作物件，主要是用於wordpress的文章讀取
 * @requires wp_db_switch.class.php 父類別物件
 * @author Lornzo Lee(李赤兔) <a6288678@hotmail.com>
 * @version 2020.8.24
 */
class wp_db_posts extends wp_db_switch{
    /**
     * 取出單篇文章及相關資訊
     * @param string $post_id 文章在表裡面的id
     * @return array
     */
    public function getPost(string $post_id){
        $result = array();

        /*取出文章本體*/
        $condition = array();
        $condition[] = "ID='".(int)$post_id."'";
        $condition[] = "post_date <= '".date("Y-m-d H:i:s")."'";
        $condition[] = "post_status = 'publish'";
        $condition[] = "post_type='post'";
        $post = $this->setTable($this->_wp_table_pre."posts")->fetchData($condition);

        /*從其它張表取出文章會用到的其它資訊*/
        if(!empty($post)){
            $post_id = $post["ID"];
            $querys = array();

            /*term_relationships - 取出對應的post id的taxonomy_id*/
            $term_taxonomy_ids = $this->setTable($this->_wp_table_pre."term_relationships")->setSelect(array("term_taxonomy_id"))->getSelectString(array("object_id='".$post_id."'"),array(),false);

            /*term_taxonomy*/
            $term_ids_condition = array("term_taxonomy_id IN (".$term_taxonomy_ids.")");
            $term_ids = $this->setTable($this->_wp_table_pre."term_taxonomy")->setSelect(array("term_id"))->getSelectString($term_ids_condition,array(),false);

            /*0. 也要從term_taxonomy裡面取出資料，用在之後的分類用*/
            $querys[] = $this->setTable($this->_wp_table_pre."term_taxonomy")->setSelect(array())->getSelectString($term_ids_condition);

            /*1. 從terms取出名稱*/
            $querys[] = $this->setTable($this->_wp_table_pre."terms")->setSelect(array())->getSelectString(array("term_id IN (".$term_ids.")"));

            /*2. 取出圖片的meta*/
            $pic_conditions = array("post_parent = '".$post_id."'","post_status='inherit'","post_type='attachment'","post_mime_type IN ('". implode("','", $this->_getWpImageMineTypes())."')");
            $pic_meta_condition = $this->setTable($this->_wp_table_pre."posts")->setSelect(array("ID"))->getSelectString($pic_conditions,array(),false);
            $querys[] = $this->setTable($this->_wp_table_pre."postmeta")->setSelect(array())->getSelectString(array("post_id IN (".$pic_meta_condition.")","meta_key='_wp_attachment_metadata'"));

            /*3. 取出圖片本體*/
            $querys[] = $this->setTable($this->_wp_table_pre."posts")->setSelect(array())->getSelectString($pic_conditions);

            $post_datas = $this->doQuerys($querys, true);

            $term_types = array();

            $categorys = array();
            $tags = array();
            $images = array();

            $images_metas = array();

            if(!empty($post_datas)){foreach($post_datas as$post_data_chunk){
                if(!empty($post_data_chunk)){foreach($post_data_chunk as $section_id => $datas){
                    if(!empty($datas)){foreach($datas as $data){
                        switch($section_id){
                            case "0":
                                $term_types[$data["term_id"]] = $data;
                                break;
                            case "1":
                                if(!empty($term_types[$data["term_id"]])){
                                    if($term_types[$data["term_id"]]["taxonomy"] == "post_tag"){
                                        $tags[$data["term_id"]] = $data["name"];
                                    }else{
                                        $categorys[$data["term_id"]] = $data;
                                    }
                                }
                                break;
                            case "2":
                                $images_metas[$data["post_id"]] = $data["meta_value"];
                                break;
                            case "3":
                                if(!empty($images_metas[$data["ID"]])){
                                    $images[$data["ID"]] = $this->_setupWpImageData($data, $images_metas[$data["ID"]]);
                                }
                                break;
                        }
                    }}
                }}
            }}
            $result = $post;
            $result["images"] = $images;
            $result["keywords"] = $tags;
            $result["category"] = $categorys;
        }
        return $result;
    }

    /**
     * @取出文章列表
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
            $article_term_taxonomy_id = $this->setTable($this->_wp_table_pre."term_taxonomy")->setSelect(array("term_id"))->getSelectString(array("taxonomy IN ('post_tag','category')","term_taxonomy_id IN (".$article_tags_relation_id.")"),array(),false);

            $querys[] =  $this->setTable($this->_wp_table_pre."terms")->setSelect(array())->getSelectString(array( "term_id IN (".$article_term_taxonomy_id.")"));

            /*2. 取出keywords tag類型表 - wp_term_taxonomy*/
            $querys[] = $this->setTable($this->_wp_table_pre."term_taxonomy")->setSelect(array())->getSelectString(array("taxonomy IN ('post_tag','category')","term_id IN (".$article_term_taxonomy_id.")"));

            /*3. 取出keywords tag的關聯表 0 wp_term_relationships*/
            $querys[] = $this->setTable($this->_wp_table_pre."term_relationships")->setSelect(array())->getSelectString(array("object_id IN ('". implode("','", $posts_id)."')"));

            /*4.取出文章的精選圖片的meta*/
            $pic_meta_condition = $this->setTable($this->_wp_table_pre."postmeta")->setSelect(array("meta_value"))->getSelectString(array("post_id IN ('". implode("','", $posts_id)."')","meta_key='_thumbnail_id'"), array(), false);
            $querys[] = $this->setTable($this->_wp_table_pre."postmeta")->setSelect(array())->getSelectString(array("meta_key='_wp_attachment_metadata'","post_id IN (".$pic_meta_condition.")"));

            /*5. 取出文章精選圖片的對照表*/
            $querys[] = $this->setTable($this->_wp_table_pre."postmeta")->setSelect(array())->getSelectString(array("post_id IN ('". implode("','", $posts_id)."')","meta_key='_thumbnail_id'"));

            /*6.取出文章的精選圖片*/
            $querys[] = $this->setTable($this->_wp_table_pre."posts")->setSelect(array())->getSelectString(array("ID IN(".$pic_meta_condition.")"));

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

        $result = array(
            "authors"=>array(),
            "taxonomys"=>array(),
            "categorys"=>array(),
            "tags"=>array(),
            "keywords"=>array(),
            "articles"=>array()
        );

        $terms = array();

        $images_meta = array();

        $images_relation = array();

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
                                        $result["authors"][$data["ID"]] = array("name"=>$data["display_name"]);
                                        break;
                                    /*$terms*/
                                    case "1":
                                        $terms[$data["term_id"]] = $data;
                                        break;
                                    /*wp_term_taxonomy*/
                                    case "2":
                                        if(!empty($terms[$data["term_id"]])){$result["taxonomys"][$data["term_taxonomy_id"]] = array("term_id"=>$data["term_id"],"term_name"=>$terms[$data["term_id"]]["name"],"type"=>$data["taxonomy"]);}
                                        break;
                                    /*wp_term_relationships*/
                                    case "3":
                                        $taxonomy_id = $data["term_taxonomy_id"];
                                        if(!empty($result["taxonomys"][$taxonomy_id])){
                                            $taxonomy = $result["taxonomys"][$taxonomy_id];
                                            $result["taxonomys"][$taxonomy_id] = $taxonomy;

                                            if(empty($result["keywords"][$taxonomy_id])){
                                                $result["keywords"][$taxonomy_id] = 1;
                                            }else{
                                                $result["keywords"][$taxonomy_id] += 1;
                                            }

                                            if($taxonomy["type"] == "category"){
                                                $result["categorys"][$taxonomy_id] = array("term_id"=>$taxonomy["term_id"],"term_name"=>$taxonomy["term_name"]);
                                            }else{
                                                $result["tags"][$taxonomy_id] = array("term_id"=>$taxonomy["term_id"],"term_name"=>$taxonomy["term_name"]);
                                            }                                            
                                        }
                                        break;
                                    /*images meta*/
                                    case "4":
                                        $images_meta[$data["post_id"]] = $data["meta_value"];
                                        break;
                                    case "5":
                                        if(!empty($images_meta[$data["meta_value"]])){
                                            $images_relation[$data["meta_value"]] = $data["post_id"];
                                        }
                                        break;
                                    /*精選圖片*/
                                    case "6":
                                        if(!empty($images_meta[$data["ID"]]) && !empty($images_relation[$data["ID"]])){
                                            $result["articles"][$images_relation[$data["ID"]]]["image"] = $this->_setupWpImageData($data, $images_meta[$data["ID"]]);
                                        }
                                        break;
                                    /*文章本體*/
                                    default:
                                        $result["articles"][$data["ID"]]["headline"] = $data["post_title"];
                                        $result["articles"][$data["ID"]]["description"] = $data["post_excerpt"];
                                        $result["articles"][$data["ID"]]["datepublished"] = $data["post_date"];
                                        $result["articles"][$data["ID"]]["author"] = !empty($result["authors"][$data["post_author"]])?$result["authors"][$data["post_author"]]["name"]:"";
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }
        if(!empty($result["keywords"])){arsort($result["keywords"]);}
        unset($terms,$images_meta);
        return $result;
    }
}
?>