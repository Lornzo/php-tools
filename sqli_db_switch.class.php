<?php

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
    
    public function __construct() {
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
     * 執行mysql語法
     * @param string $query
     * @return mysqli_result
     */
    public function doQuery(string $query){
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
        if($this->_conn){mysqli_close($this->_conn);}
    }
}
?>