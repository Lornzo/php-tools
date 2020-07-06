<?php
/**
 * 
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
    public function fetchData(array $condition = array() , array $back_strings = array()){
        $result = array();
        if($this->_setConnection() && !empty($this->_table)){
            $query = "SELECT ".$this->_select." FROM ".$this->_table;
            $query.= !empty($condition)?" WHERE ".implode(" AND ", $condition):"";
            $query.= !empty($back_strings)?" ".implode(" ", $back_strings):"";
            $query .= " LIMIT 0,1;";
            $result = mysqli_fetch_assoc($this->doQuery($query));
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
        return ($this->_setConnection())?mysqli_query($this->_conn, $query):false;
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
     * @return type
     */
    public function setConnection(string $db_host,string $db_name,string $db_user,string $db_pass,string $db_charset="utf8",string $db_port = ""){
        return $this->setConnectionByArray(array("db_host"=>$db_host,"db_name"=>$db_name,"db_user"=>$db_user,"db_pass"=>$db_pass,"db_port"=>$db_port,"db_charset"=>$db_charset));
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