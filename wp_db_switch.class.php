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
            
            /*2. 取出圖片本體*/
            $pic_conditions = array("post_parent = '".$post_id."'","post_status='inherit'","post_type='attachment'","post_mime_type IN ('". implode("','", $this->_getWpImageMineTypes())."')");
            $querys[] = $this->setTable($this->_wp_table_pre."posts")->setSelect(array())->getSelectString($pic_conditions);
            
            /*3. 取出圖片的meta*/
            $pic_meta_condition = $this->setTable($this->_wp_table_pre."posts")->setSelect(array("ID"))->getSelectString($pic_conditions,array(),false);
            $querys[] = $this->setTable($this->_wp_table_pre."postmeta")->setSelect(array())->getSelectString(array("post_id IN (".$pic_meta_condition.")","meta_key='_wp_attachment_metadata'"));

            $post_datas = $this->doQuerys($querys, true);
            
            $term_types = array();
            
            $categorys = array();
            $tags = array();
            $images = array();
            
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
                                $images[$data["ID"]] = array(
                                    "title"=>$data["post_title"],
                                    "alt"=>$data["post_content"],
                                    "origin"=>array("src"=>$data["guid"])
                                );
                                break;
                            case "3":
                                if(!empty($images[$data["post_id"]])){
                                    $image_dir = pathinfo($images[$data["post_id"]]["origin"]["src"],PATHINFO_DIRNAME);
                                    $image_meta = unserialize($data["meta_value"]);
                                    $images[$data["post_id"]]["origin"]["width"] = $image_meta["width"];
                                    $images[$data["post_id"]]["origin"]["height"] = $image_meta["height"];
                                    if(!empty($image_meta["sizes"])){foreach($image_meta["sizes"] as $size_index => $size_value){
                                        $images[$data["post_id"]][$size_index] = array(
                                            "src"=> $image_dir."/".$size_value["file"],
                                            "width"=>$size_value["width"],
                                            "height"=>$size_value["height"]
                                        );
                                    }}
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
            $pic_meta_condition = $this->setTable($this->_wp_table_pre."posts")->setSelect(array("ID"))->getSelectString(array("post_type='attachment'","post_mime_type IN ('". implode("','", $this->_getWpImageMineTypes())."')","post_parent IN ('". implode("','", $posts_id)."')"),array("GROUP BY post_parent"),false);
            $querys[] = $this->setTable($this->_wp_table_pre."postmeta")->setSelect(array())->getSelectString(array("meta_key='_wp_attachment_metadata'","post_id IN (".$pic_meta_condition.")"));
            
            /*5.取出文章的精選圖片*/
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
                                    /*精選圖片*/
                                    case "5":
                                        if(!empty($images_meta[$data["ID"]])){
                                            $image_dir = pathinfo($data["guid"],PATHINFO_DIRNAME);
                                            $image_meta = unserialize($images_meta[$data["ID"]]);
                                            $result["articles"][$data["post_parent"]]["image"] = array("src"=>$data["guid"],"alt"=>$data["post_content"],"title"=>$data["post_title"],"meta"=> unserialize($images_meta[$data["ID"]]));
                                            $result["articles"][$data["post_parent"]]["image"] = array("alt"=>$data["post_content"],"title"=>$data["post_title"]);
                                            $result["articles"][$data["post_parent"]]["image"]["origin"] = array("src"=>$data["guid"],"width"=>$image_meta["width"],"height"=>$image_meta["height"]);
                                            if(!empty($image_meta["sizes"])){foreach($image_meta["sizes"] as $size_name => $size_data){
                                                $result["articles"][$data["post_parent"]]["image"][$size_name] = array("src"=>$image_dir."/".$size_data["file"],"width"=>$size_data["width"],"height"=>$size_data["height"]);
                                                $result["articles"][$data["post_parent"]]["image"]["srcset"][$size_data["width"]] = $image_dir."/".$size_data["file"]." ".$size_data["width"]."w";
                                            }ksort($result["articles"][$data["post_parent"]]["image"]["srcset"]);}
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
    
    /**
     * 取得wordpress裡面對於圖片mine type判斷的類型
     * @return array
     */
    protected function _getWpImageMineTypes(){
        return array("image/png","image/jpg","image/jpeg","image/gif");
    }
    
    /*下一個function*/
}
?>