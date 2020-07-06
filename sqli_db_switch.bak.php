<?php
class sqli_db_switch{
    protected $_conn = null;
    protected $_is_debug = false;
    
    protected $_db_host = "";
    protected $_db_name = "";
    protected $_db_user = "";
    protected $_db_pass = "";
    protected $_db_charset = "UTF-8";
    
    protected $_table = "";
    protected $_select = "*";

    /**
     * 
     * @param bool $debug
     * @return $this
     */
    public function debug(bool $debug = true){
        $this->_is_debug = $debug;return $this;
    }

    /**
     * 設定連線資訊
     * @param string $host host , 
     * @param string $name
     * @param string $user
     * @param string $pass
     * @param string $charset
     * @return $this
     */
    public function setConnection(string $host,  string $name , string $user , string $pass , string $charset = "UTF-8"){
        $this->setConnectionByArray(array("host"=>$host,"name"=>$name,"user"=>$user,"pass"=>$pass,"charset"=>$charset));
        return $this;
    }

    /**
     * 
     * @param array $connection_info
     * @return $this
     */
    public function setConnectionByArray(array $connection_info){
        $this->_db_host = !empty($connection_info["host"])?$connection_info["host"]:$this->_db_host;
        $this->_db_name = !empty($connection_info["name"])?$connection_info["name"]:$this->_db_name;;
        $this->_db_user = !empty($connection_info["user"])?$connection_info["user"]:$this->_db_user;
        $this->_db_pass = !empty($connection_info["pass"])?$connection_info["pass"]:$this->_db_pass;
        $this->_db_charset = !empty($connection_info["charset"])?$connection_info["charset"]:$this->$this->_db_charset;
        return $this;
    }

    protected function _setConnection(){
        $result = ($this->_conn)?true:false;
        if(!$result && !empty($this->_db_host) && !empty($this->_db_name) && !empty($this->_db_user) && !empty($this->_db_pass) && !empty($this->_db_charset)){
            $this->_conn = mysqli_connect($this->_db_host, $this->_db_user, $this->_db_pass, $this->_db_name);
            if($this->_conn){mysqli_query($this->_conn, "SET NAMES '".$this->_db_charset."';");$result = true;};
        }
        return $result;
    }
    
    public function setTable(string $table){
        $this->_table = $table;return $this;
    }
    
    public function setSelect(array $select = array()){
        $this->_select = !empty($select)?implode(",", $select):"*";return $this;
    }
    
    /**
     * 這一個function不用加LIMIT 0,1
     * @param array $condition
     * @param string $other_condition_str
     * @return type
     */
    public function fetchData(array $condition = array(),string $other_condition_str=""){
        $datas = array();
        if(!empty($this->_table)){
            $query = "SELECT ".$this->_select." FROM ".$this->_table;
            $query .= !empty($condition)?" WHERE ".implode(" AND ", $condition):"";
            $query .= !empty($other_condition_str)?" ".$other_condition_str:"";
            $query .= " LIMIT 0,1;";
            $datas = $this->doQuery($query);
        }
        return !empty($datas)?mysqli_fetch_array($datas,MYSQLI_ASSOC):array();
    }
    
    public function listData(array $condition = array(),string $other_condition_str = ""){
        $datas = array();
        if(!empty($this->_table)){
            $query = "SELECT ".$this->_select." FROM ".$this->_table;
            $query .= !empty($condition)?" WHERE ".implode(" AND ", $condition):"";
            $query .= !empty($other_condition_str)?" ".$other_condition_str:"";
            $query .= ";";
            $datas = $this->doQuery($query);
        }
        return !empty($datas)?mysqli_fetch_all($datas,MYSQLI_ASSOC):array();
    }

    /**
     * 更新資料庫裡面的資料
     * @param array $updates
     * @param array $condition
     * @return $this
     */
    public function updateData(array $updates , array $condition = array()){
        $result = 0;

        if(!empty($this->_table) && !empty($updates)){
            $query = $this->getUpdateQuery($updates, $condition);
            $result = !empty($this->doQuery($query))?mysqli_affected_rows($this->_conn):0;
        }
        return $result;
    }

    /**
     * 取得sql的Update組合語法
     * @param array $updates
     * @param array $condition
     * @return string
     */
    public function getUpdateQuery(array $updates,array $condition = array()){
        $result = "";
        if(!empty($this->_table) && !empty($updates)){
            $buffer = array();
            foreach($updates as $col => $val){$buffer[] = $col."='".$val."'";}
            $result = "UPDATE ".$this->_table." SET ". implode(",", $buffer);
            $result .= !empty($condition)?" WHERE ".implode(" AND ", $condition):"";
            $result .= ";";
        }
        return $result;
    }
    


    /**
     * 
     * @param array $cols
     * @param array $data
     * @return type
     */
    public function appendData(array $cols,array $data,bool $ignore=false){
        $result = -1;
        if(!empty($this->_table) && !empty($cols) && !empty($data)){
            $query = !empty($ignore)?"INSERT IGNORE INTO ".$this->_table."(".implode(",", $cols).") VALUES ('".implode("','", $data)."');":"INSERT INTO ".$this->_table."(".implode(",", $cols).") VALUES ('".implode("','", $data)."');";
            $result = !empty($this->doQuery($query))? mysqli_insert_id($this->_conn):0;
        }
        return $result;
    }
    
    /**
     * 在一句sql指令中新增多筆資料
     * @param array $cols
     * @param array $datas
     * @return type
     */
    public function appendDatas(array $cols , array $datas ,bool $ignore=false){
        $result = 0;
        if(!empty($this->_table) && !empty($cols) && !empty($datas)){
            $query = !empty($ignore)? "INSERT IGNORE INTO ".$this->_table."(".implode(",", $cols).") VALUES ".implode(",", $datas).";": "INSERT INTO ".$this->_table."(".implode(",", $cols).") VALUES ".implode(",", $datas).";";
            $result =( !empty($this->doQuery($query)))?mysqli_affected_rows($this->_conn):0;
        }
        return $result;
    }
    
   /**
    * 執行單行sql語法
    * @param string $query sql語法
    * @return mysqli_query 執行結果
    */
    public function doQuery(string $query){
        $result = ($this->_setConnection())?mysqli_query($this->_conn, $query):null;
        if($this->_is_debug){echo $query;}
        return $result;
    }

    /**
     * 執行多行sql語法
     * @param string $querys sql語法
     * @return mysqli_query 執行結果
     */
    public function doQuerys(array $querys , int $do_limit = 5000){
        $result = array();
        $querys_arr = array_chunk($querys, $do_limit);
        if(!empty($this->_is_debug)){print_r($querys_arr);}
        if(!empty($querys_arr) && !empty($this->_setConnection())){
            foreach($querys_arr as $query_arr){
                $query = implode("", $query_arr);
                $result[] = mysqli_multi_query($this->_conn, $query);
            }
        }
        return $result;
    }
    
    /**
     * 取出所有的資料表
     * @return type
     */
    public function getTables(){
        $result = array();
        $query = "SHOW TABLES;";
        $datas = mysqli_fetch_all($this->doQuery($query));
        if(!empty($datas)){
            foreach($datas as $data){
                if(!empty($data) && is_array($data)){
                    foreach($data as $table_name){
                        $result[] = $table_name;
                    }
                }
            }
        }
        return $result;
    }
    
    /**
     * 過濾要進資料庫的字串
     * @param string $str
     * @return string
     */
    public function dataFilter(string $str){
        return trim(stripslashes(addslashes(htmlspecialchars($str, ENT_QUOTES, 'UTF-8'))));
    }
}
?>