<?php
if(!class_exists("sqli_db_switch")){require(__DIR__."sqli_db_switch.class.php");}
class sqli_cols{
    public $col_name = "";
    public $col_type = "";
    public $col_length = "";
    public $col_default = "";
    public $col_charset = "";
}

/**
 * 繼承自sqli_db_switch開發的MySQL操作物件，內含比較危險的操作Function，請小心使用
 * @requires sqli_db_switch 父類別物件
 * @author Lornzo Lee(李赤兔) <a6288678@hotmail.com>
 * @version 2020.7.7
 */
class sqli_db_opration extends sqli_db_switch{
    
    /**
     * @var bool 在創建table的時候是否要加入TABLE IF NOT EXISTS 
     */
    protected $_if_table_not_exists = true;
    
    protected $_create_table_cols = array();
    
    /**
     * 
     * @param string $cols_name 欄位名稱
     * @param int $length 欄位資料長度
     * @param string $type 可以為空或是BINARY , UNSIGNED
     * @param bool $is_null
     */
    public function addColWithInt(string $cols_name,int $length,string $type="",bool $is_null=false){
        $result = "`".$cols_name."` int(".$length.")";
        $result .= !empty($type)?" ".$type:"";
        "NOT NULL AUTO_INCREMENT";
    }
    
    public function createTable(string $table_comment){
        "CREATE TABLE IF NOT EXISTS `lornzo_test` (
  `no` int(11) NOT NULL AUTO_INCREMENT,
  `lornzo` varchar(255) NOT NULL,
  PRIMARY KEY (`no`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='測試資料表';
COMMIT;";
        
        
        "CREATE TABLE IF NOT EXISTS `lornzo_test` (
  `no` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tesst` int(11) UNSIGNED DEFAULT 0 COMMENT '123',
  `lornzo` varchar(255) NOT NULL,
  PRIMARY KEY (`no`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='測試資料表';
COMMIT;
";
    }
    
    /**
     * 清空整張資料表
     * @return bool
     */
    public function truncateTable(){
        return !empty($this->_table)?$this->doQuery("TRUNCATE ".$this->_table.";"):false;
    }
    
    /**
     * 刪除整張資料表
     * @return bool
     */
    public function dropTable(){
        return !empty($this->_table)?$this->doQuery("DROP TABLE IF EXISTS ".$this->_table.";"):false;
    }
    
    /**
     * 取得所有可以操作的資料庫
     * @return array
     */
    public function getDatabases(){
        $dbs = mysqli_fetch_all($this->doQuery("SHOW DATABASES;"),MYSQLI_ASSOC);
        $result = array();
        if(!empty($dbs)){foreach($dbs as $db){if(!empty($db)){foreach($db as $name){$result[] = $name;}}}}
        return $result;
    }
    
    /**
     * 取得所有資料庫裡面的表名
     * @param bool $use_db_as_key
     * @return array
     */
    public function getTables(){
        $db_tables = mysqli_fetch_all($this->doQuery("SHOW TABLES;"),MYSQLI_ASSOC);
        $result = array();
        if(!empty($db_tables)){
            foreach($db_tables as $tables){
                if(!empty($tables)){
                    foreach($tables as $db_name => $table_name){
                            $result[] = $table_name;
                    }
                }
            }
        }
        return $result;
    }
}
?>