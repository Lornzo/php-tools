<?php
if(!class_exists("sqli_db_switch")){require(__DIR__."sqli_db_switch.class.php");}

class sqli_cols{
    public $col_name = "";
    public $col_type = "";
    public $col_length = 0;
    public $col_default = "";
    public $col_charset = "utf8";
    public $col_attributes = "";
    public $col_is_null = false;
    public $col_comment = "";
    
    /**
     * 重置所有的變數
     * @return $this
     */
    public function reset(){
        $this->col_name = "";
        $this->col_type = "";
        $this->col_length = 0;
        $this->col_default = "";
        $this->col_charset = "utf8";
        $this->col_attributes = "";
        $this->col_is_null = false;
        $this->col_comment = "";
        return $this;
    }
    
    /**
     * 1.設定欄位名稱
     * @param string $name 欄位名稱
     * @return $this
     */
    public function setColName(string $name){
        $this->col_name = $name;return $this;
    }
    
    /**
     * 2.設定欄位的資料類別
     * @param string $type
     */
    public function setColType(string $type){
        $this->col_type = in_array($type,$this->getAllColType())?$type:"";return $this;
    }
    
    /**
     * 3.設定欄位屬性
     * @param string $attr_name BINARY,UNSIGNED,UNSIGNED ZEROFILL,on update CURRENT_TIMESTAMP 四選一
     * @return $this
     */
    public function setColAttributes(string $attr_name){
        $this->col_attributes = in_array($attr_name,$this->getAllColType())?$attr_name:"";return $this;
    }
    
    /**
     * 4.設定欄位的長度
     * @param mixed $length
     */
    public function setColLength($length ){
        
    }
    
    /**
     * 取得所有的欄位屬性
     * @return array
     */
    public function getAllColAttributes(){
        return array("BINARY","UNSIGNED","UNSIGNED ZEROFILL","on update CURRENT_TIMESTAMP");
    }
    
    /**
     * 取得所有Mysql的欄位類別
     * @return array
     */
    public function getAllColType(){
        return array("tinyint","smallint","mediumint","int","bigint","decimal","float","double","real","bit","boolean","serial","date","datetime","timestamp","time","year","char","varchar","tinytext","text","mediumtext","longtext","binary","varbinary","tinyblob","blob","mediumblob","longblob","enum","set","geometry","point","linestring","polygon","multipoint","multilinestring","multipolygon","geometrycollection","json");
    }
    
    public function getColTypeLength(string $type_name="",string $col_attr=""){
        switch ($col_attr){
            case "":
                break;
            default:
                $types = array("tinyint"=>array(),"smallint","mediumint","int","bigint","decimal","float","double","real","bit","boolean","serial","date","datetime","timestamp","time","year","char","varchar","tinytext","text","mediumtext","longtext","binary","varbinary","tinyblob","blob","mediumblob","longblob","enum","set","geometry","point","linestring","polygon","multipoint","multilinestring","multipolygon","geometrycollection","json");
                break;
        }
        return empty($type_name)?$types:!empty($types[$type_name])?$types[$type_name]:array();
    }
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