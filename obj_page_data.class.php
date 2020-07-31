<?php
/**
 * 頁面通用的資料物件
 * @author Lornzo Lee(李赤兔) <a6288678@hotmail.com>
 * @version 2020.7.31
 */
class obj_page_data{
    
    /**
     * 頁面的編碼
     * @var string
     */
    public $charset = "utf-8";
    
    /**
     * 頁面的語言
     * @var strin
     */
    public $lang = "zh-TW";
    
    /**
     * 爬蟲設定
     * @var string 
     */
    public $robots = "index,follow";
    
    /**
     * 頁面標題：title跟h1
     * @var string
     */
    public $title = "";
    
    /**
     * 頁面description
     * @var string
     */
    public $description = "";
    
    /**
     * 頁面meta keywords
     * @var string
     */
    public $keywords = "";
    
    /**
     * 頁面的標準網址
     * @var string
     */
    public $canonical = "";
    
    /**
     * 頁面會用到的javascript files，可使用addJsFiles()來轉助增加，也可以手曾增加
     * @var array 
     * 
     */
    public $js_files = array();
    
    /**
     * 頁面會用到的css files
     * @var array 
     */
    public $css_files = array();
    
    /**
     * schema用
     * @var array 
     */
    public $json_ld = array();
    
    /**
     * 頁面的麵包屑，可使用addBreadCrumb來輔助增加
     * @var array
     */
    public $breadcrumb = array();
    
    public function __construct(string $root_url,string $site_name) {
        $this->breadcrumb[] = array("name"=>$site_name,"url"=>$root_url);
    }
    
    /**
     * 頁面麵包屑的輔助增加function
     * @param string $page_url 頁面的url，要帶http或是https
     * @param string $page_name 頁面的名稱
     * @return $this
     */
    public function addBreadCrumb(string $page_url,string $page_name){
        $this->breadcrumb[] = array("name"=>$page_name,"url"=>$page_url);return $this;
    }
    
    /**
     * 頁面javascript引內輔助function
     * @param string $file_path js的url path
     * @param bool $async 是否要async
     * @param string $amp_custom amp用的
     * @return $this
     */
    public function addJsFiles(string $file_path,bool $async=true,string $amp_custom=""){
        $this->js_files[] = array("path"=>$file_path,"async"=>$async,"amp_custom"=>$amp_custom);return $this;
    }
}
?>