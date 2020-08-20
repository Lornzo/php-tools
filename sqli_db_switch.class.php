<?php
/**
 * 基於mysqli開發的MySQL讀寫物件
 * @author Lornzo Lee(李赤兔) <a6288678@hotmail.com>
 * @version 2020.7.7
 */
class sqli_db_switch{
    
    /**
     * @var string 資料庫的位置
     */
    protected $_host = "";
    
    /**
     * @var string 資料庫的名稱 
     */
    protected $_name = "";
    
    /**
     * @var string 資料庫登入的帳號 
     */
    protected $_user = "";
    
    /**
     * @var string 資料庫登入的密碼 
     */
    protected $_pass = "";
    
    /**
     * @var string 資料庫讀取的編碼 
     */
    protected $_charset = "utf8";
    
    /**
     * @var string 資料庫的port
     */
    protected $_port = "";
    
    /**
     * @var mysqli_connect() mysqli連線 
     */
    protected $_conn = null;
    
    /**
     * @var string 操作的資料表 
     */
    protected $_table = "";
    
    /**
     * @var string 在進行select操作時所要select的欄位，預設* 
     */
    protected $_select = "*";
    
    /**
     * @var bool 是否開啟偵錯模式
     */
    protected $_debug = false;
    
    /**
     * @var bool 在listData是否要加上資料上限 
     */
    protected $_limit = false;
    
    /**
     * @var int 在$_limit為true的時候，一次要跑出幾筆 
     */
    protected $_limit_size = 20;
    
    /**
     * @var int 在$_limit為true的時候，計算總共有幾頁 
     */
    protected $_limit_total = 1;
    
    /**
     * @var int 在$_limit為true的時候，現在所在的頁數 
     */
    protected $_limit_page = 1;
    
    /**
     * @var int 在$_limit為true的時候，顯示出前後的頁碼 
     */
    protected $_limit_page_half = 5;
    
    /**
     * @var array 用在儲存工作階段的所有資訊 
     */
    protected $_buffer = array();
    
    public function __construct() {
    }
    
    /**
     * 設定是否履開啟偵錯模式
     * @param bool $debug
     * @return $this
     */
    public function setDebug(bool $debug){
        $this->_debug = $debug;return $this;
    }
    
    /**
     * 設定要操作的資料表
     * @param string $table
     * @return $this
     */
    public function setTable(string $table){
        $this->_table = $table;return $this;
    }
    
    /**
     * 輸入單筆資料
     * @param array $input array("cols"=>"value")
     * @param bool $ignore 是否要ignore，如果為true的時候會無視因重複key而輸入失敗的情況
     * @return int -1為沒有執行，否執會return mysqli_insert_id
     */
    public function appendData(array $input , bool $ignore=false){
        $result = -1;
        if(!empty($this->_table) && !empty($input)){
            $query = !empty($ignore)?"INSERT IGNORE INTO ".$this->_table."(".implode(",", array_keys($input)).") VALUES ('".implode("','", $input)."');":"INSERT INTO ".$this->_table."(".implode(",", array_keys($input)).") VALUES ('".implode("','", $input)."');";
            $result = !empty($this->doQuery($query))? mysqli_insert_id($this->_conn):0;
        }
        return $result;
    }

    /**
     * 在一句sql指令中新增多筆資料
     * @param array $cols 輸入的欄位
     * @param array $datas 可帶入$this->getAppendDataString所得出的字串組成的陣列array("('aa','bb')","('bb','cc')")
     * @param bool $ignore 是否要ignore，如果為true的時候會無視因重複key而輸入失敗的情況
     * @param int $do_insert_size 一次插入的資料上限，如果資料量大於這個數，執行語句的時候會拆開來批次執行
     * @return int 插入的列數
     */
    public function appendDatas(array $cols , array $datas ,bool $ignore=false ,int $do_insert_size = 5000){
        $result = 0;
        if(!empty($this->_table) && !empty($cols) && !empty($datas)){
            $inputs = array_chunk($datas, $do_insert_size);
            foreach($inputs as $input){
                $query = !empty($ignore)? "INSERT IGNORE INTO ".$this->_table."(".implode(",", $cols).") VALUES ".implode(",", $input).";": "INSERT INTO ".$this->_table."(".implode(",", $cols).") VALUES ".implode(",", $input).";";
                $result +=  !empty($this->doQuery($query))?mysqli_affected_rows($this->_conn):0;
            }
        }
        return $result;
    }
    
    /**
     * 組成輸入資料庫的字串
     * @param array $input_data array('value1','value2','value3')
     * @return string ('value1','value2','value3')
     */
    public function getAppendDataString(array $input_data){
        return !empty($input_data)? "('".implode("','", $input_data)."')":"";
    }
    
    /**
     * 設定在使用listData的時候要不要有資料上限
     * @param bool $use_limit
     * @return $this
     */
    public function useLimit(bool $use_limit){
        $this->_limit = $use_limit;return $this;
    }
    
    /**
     * 設定在使用listData的時候，每一頁要有多少資料
     * @param int $size 如果小於1的話就會變回預設的20
     * @return $this
     */
    public function setPageSize(int $size){
        $this->_limit_size = ($size > 0)?$size:20;return $this;
    }
    
    /**
     * 設定這一頁的頁數，如果有頁數的話，必定要先呼叫countTotalPage
     * @param int $page
     * @param $this
     */
    public function setPage(int $page){
        $this->_limit_page = 1;
        if($page > 1){$this->_limit_page = ($page <= $this->_limit_total)?$page:$this->_limit_total;}
        return $this;
    }
    
    /**
     * 在$_limit=true的情況下，計算出固有條件下有幾頁
     * @param array $condition
     * @param array $back_strings
     * @return $this
     */
    public function countTotalPage(array $condition,array $back_strings = array()){
        if($this->_limit && !empty($this->_table) && $this->_limit_size > 0){
            $select_buffer = $this->_select;
            $data = $this->setSelect(array("COUNT(*) AS total"))->fetchData($condition, $back_strings);
            $result = !empty((int)$data["total"])?ceil((int)$data["total"] / $this->_limit_size):1;
            $this->_select = $select_buffer;
            $this->_limit_total = $result;
        }
        return $this;
    }
    
    /**
     * 取得countTotalPage計算之後的總頁數，必需要先呼叫$this->countTotalPage()之後才會拿到正確的值
     * @return int
     */
    public function getTotalPage(){
        return $this->_limit_total;
    }
    
    /**
     * 設定一次要顯示的頁碼數
     * @param int $size
     * @return $this
     */
    public function setWindowSize(int $size){
        $this->_limit_page_half = $size > 0 ? $size:5;return $this;
    }
    
    /**
     * 取得當頁所可以顯示的頁碼數，必需要先呼叫countTotalPage
     * @return array array("start","end")
     */
    public function getPagination(){
        $result = array("start"=>1,"end"=>1);
        if($this->_limit && $this->_limit_total > 1 && $this->_limit_page_half >0){
            $this->_limit_page = 20;
            $result = array("start"=>1,"end"=>$this->_limit_total);
            if($this->_limit_total > (($this->_limit_page_half * 2) + 1)){
                $result = array("start"=>$this->_limit_page - $this->_limit_page_half,"end"=>$this->_limit_page+$this->_limit_page_half);
                if($result["start"] < 1){
                    $result["start"] = 1;
                    $result["end"] = ($this->_limit_page_half * 2) + 1;
                }elseif($result["end"] > $this->_limit_total){
                    $result["end"] = $this->_limit_total;
                    $result["start"] = $this->_limit_total - (($this->_limit_page_half * 2));
                }
            }
        }
        return $result;
    }

    /**
     * 設定查詢語句的欄位
     * @param array $select 陣列值為欄位名稱，若為空，則為全部欄位
     * @return $this
     */
    public function setSelect(array $select = array()){
        $this->_select = !empty($select)?implode(",", $select):"*";return $this;
    }

    /**
     * 取出單一行資料
     * @param array $condition 查詢條件
     * @param array $back_strings 後面要下的語句，用array包起來，like array("ORDER BY aa DESC","cc ASC")，採implode方式組合起來的
     * @return type
     */
    public function fetchData(array $condition = array(),array $select_back=array()){
        $select_back[] = "LIMIT 0,1";
        $data = $this->doQuery($this->getSelectString($condition, $select_back));
        return !empty($data)?mysqli_fetch_assoc($data):array();
    }
    
    /**
     * 取出多筆資料
     * @param array $condition
     * @param array $back_strings
     * @return type
     */
    public function listData(array $condition = array(),array $select_back=array()){
        if($this->_limit){$select_back[] = "LIMIT ". (($this->_limit_page-1)*$this->_limit_size).",".$this->_limit_size;}
        $data = $this->doQuery($this->getSelectString($condition, $select_back));
        return !empty($data)?mysqli_fetch_all($data,MYSQLI_ASSOC):array();
    }
    
    /**
     * 更新資料
     * @param array $update
     * @param array $condition
     * @return type
     */
    public function updateData(array $update,array $condition = array()){
        return !empty($this->doQuery($this->getUpdateQuery($update, $condition)))?mysqli_affected_rows($this->_conn):0;
    }
    
        /**
     * 取得sql的Update組合語法
     * @param array $update
     * @param array $condition
     * @return string
     */
    public function getUpdateQuery(array $update,array $condition = array()){
        $result = "";
        if(!empty($this->_table) && !empty($update)){
            $buffer = array();
            foreach($update as $col => $val){$buffer[] = $col."='".$val."'";}
            $result = "UPDATE ".$this->_table." SET ". implode(",", $buffer);
            $result .= !empty($condition)?" WHERE ".implode(" AND ", $condition):"";
            $result .= ";";
        }
        return $result;
    }
    
    /**
     * 刪除資料
     * @param array $condition 不可為空，不然的話直接用TRUNCATE就好了。
     * @return int 刪除的數量
     */
    public function deleteData(array $condition){
        $result = 0;
        if(!empty($condition) && !empty($this->_table)){$result = !empty($this->doQuery("DELETE FROM ".$this->_table." WHERE ".implode(" AND ", $condition).";"))?mysqli_affected_rows($this->_conn):0;}
        return $result;
    }
    
    /**
     * 取得Mysql Select的語法字串
     * @param array $condition
     * @param array $back_strings
     * @return string
     */
    public function getSelectString(array $condition = array(), array $back_strings=array(),bool $semicolon=true){
        $result = "";
        if(!empty($this->_table)){
            $result = "SELECT ".$this->_select." FROM ".$this->_table;
            $result.= !empty($condition)?" WHERE ".implode(" AND ", $condition):"";
            $result.= !empty($back_strings)?" ".implode(" ", $back_strings):"";
            $result .= $semicolon?";":"";
        }
        return $result;
    }

    /**
     * 執行mysql語法
     * @param string $query
     * @return mysqli_result
     */
    public function doQuery(string $query){
        if($this->_debug){echo $query;}
        return ($this->_setConnection() && !empty($query))?mysqli_query($this->_conn, $query):false;
    }
    
    /**
     * 執行多行sql語法
     * @param array $querys
     * @param bool $return 是否要取出執行資料
     * @param int $do_query_size 每一次執行的語法數
     * @return array
     */
    public function doQuerys(array $querys,bool $return = false,int $do_query_size = 5000){
        $result = array();
        if($this->_debug){;print_r($querys);}
        if($this->_setConnection() && !empty($querys)){
            $do_querys = array_chunk($querys, $do_query_size);
            foreach($do_querys as $index => $do_query){
                $result[$index] = mysqli_multi_query($this->_conn, implode("", $do_query));
                if($result[$index] && $return){
                    $buffer = array();
                    do{
                        $buffer[] =($datas = mysqli_store_result($this->_conn))? mysqli_fetch_all($datas,MYSQLI_ASSOC):false;
                        mysqli_free_result($datas);   
                    }while (mysqli_next_result($this->_conn));
                    $result[$index] = $buffer;
                }
            }
        }
        return $result;
    }

    /**
     * 設定資料庫的連線參數
     * @param string $db_host
     * @param string $db_name
     * @param string $db_user
     * @param string $db_pass
     * @param string $db_charset
     * @param string $db_port
     * @return $this
     */
    public function setConnection(string $db_host,string $db_name,string $db_user,string $db_pass,string $db_charset="utf8",string $db_port = ""){
        $this->setConnectionByArray(array("db_host"=>$db_host,"db_name"=>$db_name,"db_user"=>$db_user,"db_pass"=>$db_pass,"db_port"=>$db_port,"db_charset"=>$db_charset));return $this;
    }

    /**
     * 使用陣列的方式來設定資料庫連線參數
     * @param array $connection_array db_host,db_name,db_user,db_pass,db_charset , db_port
     * @return $this
     */
    public function setConnectionByArray(array $connection_array){
        if(!empty($connection_array["db_host"]) && !empty($connection_array["db_name"]) && !empty($connection_array["db_user"]) && !empty($connection_array["db_pass"])){
            $this->_checkConnection($connection_array);
            $this->_host = $connection_array["db_host"];
            $this->_name = $connection_array["db_name"];
            $this->_user = $connection_array["db_user"];
            $this->_pass = $connection_array["db_pass"];
            $this->_charset = !empty($connection_array["db_charset"])?$connection_array["db_charset"]:"utf8";
            $this->_port = !empty($connection_array["db_port"])?$connection_array["db_port"]:"";
        }
        return $this;
    }
    
    /**
     * 過濾要進資料庫的字串
     * @param string $str
     * @return string
     */
    public function dataFilter(string $str){
        return trim(stripslashes(addslashes(htmlspecialchars($str, ENT_QUOTES, 'UTF-8'))));
    }
    
    /**
     * 檢查連線型態，如果是已經連線的狀態下，根據連線參數是否不同而做出：清空連線、更改使用者或是切換Databasse的動作
     * @param array $connection_array
     */
    protected function _checkConnection(array $connection_array){
        if($this->_conn){
            if( ($this->_host != $connection_array["db_host"]) || (!empty($connection_array["db_port"]) && $this->_port != $connection_array["db_port"])){
                mysqli_close($this->_conn);
                $this->_conn = null;
            }elseif($this->_user != $connection_array["db_user"]){
                mysqli_change_user($this->_conn, $connection_array["db_user"], $connection_array["db_pass"], $connection_array["db_name"]);
            }elseif($this->_name != $connection_array["db_name"]){
                mysqli_select_db($this->_conn, $connection_array["db_name"]);
            }
            if($this->_conn && !empty($this->_charset) && !empty($connection_array["db_charset"]) && $this->_charset != $connection_array["db_charset"]){
                mysqli_set_charset($this->_conn, $connection_array["db_charset"]);
            }
        }
    }
    
    /**
     * 設定工作階段buffer，用在暫存table,select,還有limit
     * @return $this
     */
    public function setBuffer(){
        $this->_buffer = array("table"=>$this->_table,"select" => $this->_select,"limit"=>$this->_limit);return $this;
    }
    
    /**
     * 剛setBuffer()所暫存的資料放回去
     * @return $this
     */
    public function releaseBuffer(){
        if(!empty($this->_buffer)){
            $this->_table = $this->_buffer["table"];
            $this->_select = $this->_buffer["select"];
            $this->_limit = $this->_buffer["limit"];
        }
        return $this;
    }

    /**
     * 設定最初級的mysql連線，並設定連絡編碼，預設utf8
     * @return bool 連線成功return true，否則return false
     */
    protected function _setConnection(){
        if(!$this->_conn && !empty($this->_host) && !empty($this->_name) && !empty($this->_user) && !empty($this->_pass)){
            $this->_conn = !empty($this->_port)?mysqli_connect($this->_host, $this->_user, $this->_pass, $this->_name, $this->_port):mysqli_connect($this->_host,$this->_user,$this->_pass,$this->_name);    
            if($this->_conn && !empty($this->_charset)){mysqli_set_charset($this->_conn, $this->_charset);}
        }
        return ($this->_conn)?true:false;
    }

    public function __destruct() {
        /*關閉資料庫連線，並清空變數*/
        if($this->_conn){mysqli_close($this->_conn);$this->_conn=null;}
    }
}
?>