<?php
/**
 * 用於創建MySQL的Table物件
 * @author Lornzo Lee(李赤兔) <a6288678@hotmail.com>
 * @version 2020.7.7
 */
class sqli_db_table{
    /**
     * @var string 這個Table的名稱
     */
    protected $_table_name = "";
    
    /**
     * @var string 這個Table的Engine 
     */
    protected $_table_engine="MyISAM";
    
    /**
     * @var string 這個Table的編碼與排序 
     */
    protected $_table_charset ="utf8";
    
    /**
     * @var string 這個Table的備註說明 
     */
    protected $_table_comment="";

    /**
     * @var array 這一個Table的所有欄位及其屬性
     */
    protected $_table_cols = array();
    
    /**
     * @var array 這一個Table的所有的索引
     */
    protected $_table_indexes = array("PRIMARY"=>array(),"UNIQUE"=>array());
    
    /**
     * @var string 要新增的位名稱 
     */
    protected $_add_col_name = "";
    
    /**
     * @var string 要新增的欄位類型，需符合MYSQL的欄位類型 
     */
    protected $_add_col_type = "";
    
    /**
     * @var mixed 新增欄位的長度，是數值的話就寫入整數，是enum的話就寫入array 
     */
    protected $_add_col_length = "";
    
    /**
     * @var string 新增欄位的預設值，可以為空
     */
    protected $_add_col_default = "";
    
    /**
     * @var bool 只要$_add_col_default被設定過，就會為true 
     */
    protected $_add_col_default_set = false;
    
    /**
     * @var string 新增欄位的編碼，可為空 
     */
    protected $_add_col_charset = "";
    
    /**
     * @var string 新增欄位的屬性，需符合MYSQL的欄位類型規範Like:"UNSIGNED","BINARY" 
     */
    protected $_add_col_attr = "";
    
    /**
     * @var bool 新增欄位的值是否可以為null 
     */
    protected $_add_col_null = false;

    /**
     * @var bool 這個欄位是否可以是AUTO_INCREMENT
     */
    protected $_add_col_ai = false;
    
    /**
     * @var string 欄位備註 
     */
    protected $_add_col_comment = "";

    /**
     * 設定這一張Table的名稱
     * @param string $table_name
     */
    public function setTableName(string $table_name){
        $this->_table_name = $table_name;return $this;
    }
    
    /**
     * 設定這一張Table的Engine，序設為MyISAM
     * @param string $table_engine
     * @return $this
     */
    public function setTableEngine(string $table_engine){
        $this->_table_engine = !empty($table_engine) && in_array($table_engine,$this->getAllTableEngine())?$table_engine:"MyISAM";return $this;
    }
    
    /**
     * 設定這一張Table的編碼，預設為utf8
     * @param string $table_charset
     * @return $this
     */
    public function setTableCharset(string $table_charset){
        $this->_table_charset = !empty($table_charset)?$table_charset:"utf8";return $this;
    }
    
    /**
     * 設定這一張Table的備註
     * @param string $table_comment
     * @return $this
     */
    public function setTableComment(string $table_comment){
        $this->_table_comment = $table_comment;return $this;
    }
    
    /**
     * 新增欄位1. 設定要新增的欄位名稱
     * @param string $name 欄位名稱
     * @return $this
     */
    public function setAddColName(string $name){
        $this->_add_col_name = $name;return $this;
    }
    
    /**
     * 新增欄位2. 設定要新增的欄位類型
     * @param string $type 必需要是MySQL的欄位類型
     * @return $this
     */
    public function setAddColType(string $type){
        $this->_add_col_type = in_array($type,$this->getAllColType())?$type:"";return $this;
    }
    
    /**
     * 新增欄位3. 設定要新增的柵位長度
     * @param mixed $length 可是是數字(針對數值類型)，也可以是array(針對ENUM)
     * @return $this
     */
    public function setAddColLength($length){
        $this->_add_col_length = $length;return $this;
    }
    
    /**
     * 新增欄位4. 設定要新增欄位的預設值
     * @param mixed $default 只能是數值或是字串
     * @return $this
     */
    public function setAddColDefault($default){
        $this->_add_col_default = is_string($default) || is_numeric($default) ? $default : "";
        $this->_add_col_default_set = true;
        return $this;
    }
    
    /**
     * 新增欄位5. 設定要新增欄位的排序與編碼
     * @param string $charset
     * @return $this
     */
    public function setAddColCharset(string $charset){
        $this->_add_col_charset = $charset;return $this;
    }
    
    /**
     * 新增欄位6. 設定要新增欄位的屬性
     * @param string $attribute
     * @return $this
     */
    public function setAddColAttribute(string $attribute){
        $this->_add_col_attr = in_array($attribute,$this->getAllColAttributes())?$attribute:"";return $this;
    }
    
    /**
     * 新增欄位7. 設定要新增欄位的空值設定
     * @param bool $is_null
     * @return $this
     */
    public function setAddColIsNull(bool $is_null){
        $this->_add_col_null = $is_null;return $this;
    }
    
    /**
     * 新增欄位8. 設定要新增欄位是否要AUTO INCREMENT
     * @param bool $ai
     * @return $this
     */
    public function setAddColAutoIncrement(bool $ai){
        $this->_add_col_ai = $ai;return $this;
    }
    
    /**
     * 新增欄位9. 設定要新增欄位的備註
     * @param string $comment
     * @return $this
     */
    public function setAddColComment(string $comment){
        $this->_add_col_comment = $comment;return $this;
    }
    
    /**
     * 新增欄位10. 最後一步：把整個欄位存到陣列裡面，並且重置剛剛新增欄位1~9的所有變數
     * @return $this
     */
    public function addColToTable(){
        $this->addColToTableByParameter($this->_add_col_name, $this->_add_col_type, $this->_add_col_length, $this->_add_col_default,$this->_add_col_default_set, $this->_add_col_charset, $this->_add_col_attr, $this->_add_col_null, $this->_add_col_ai, $this->_add_col_comment);
        $this->resetAddCol();
        return $this;
    }
    
    /**
     * 新增欄位到Table裡面
     * @param string $col_name 欄位名稱
     * @param string $col_type 類型
     * @param mixed $col_length 長度/值
     * @param string $col_default 預設值
     * @param string $col_default_set 是否有設定過預設值
     * @param string $col_charset 編碼與排序
     * @param string $col_attr 屬性
     * @param bool $col_is_null 空值(NULL)
     * @param bool $col_ai A_I
     * @param string $col_comment 備註
     * @return $this
     */
    public function addColToTableByParameter(string $col_name,string $col_type,$col_length,string $col_default,bool $col_default_set,string $col_charset,string $col_attr,bool $col_is_null,bool $col_ai,string $col_comment){
        $this->_table_cols[$col_name] = array("name"=>$col_name,"type"=>$col_type,"length"=>$col_length,"attribute"=>$col_attr,"is_null"=>$col_is_null,"default"=>$col_default,"default_set"=>$col_default_set,"auto_increment"=>$col_ai,"comment"=>$col_comment,"charset"=>$col_charset);
        return $this;
    }
    
    /**
     * 設定這一張表的PRIMARY KEY，一張表只能有一個PRIMARY規則
     * @param array $col_name
     * @return $this
     */
    public function setTablePrimaryKey(array $col_names){
        $this->_table_indexes["PRIMARY"] = $col_names;return $this;
    }
    
    /**
     * 在Table裡面增加UNIQUE索引規則
     * @param string $key_name 索引名稱，不可重複，如果重複的話，後面的值會直接取代掉之前設定的
     * @param array $key_values 要關連的欄位名稱
     * @return $this
     */
    public function addTableUniqueKey(string $key_name,array $key_values){
        if(!empty($key_name) && !empty($key_values)){$this->_table_indexes["UNIQUE"][$key_name] = $key_values;}
        return $this;
    }
    
    /**
     * 從Table裡面移除UNIQUE索引規則
     * @param string $key_name
     * @return $this
     */
    public function removeTableUniqueKey(string $key_name){
        if(!empty($key_name) && !empty($this->_table_indexes["UNIQUE"][$key_name])){unset($this->_table_indexes["UNIQUE"][$key_name]);}return $this;
    }
    
    /**
     * 清除Table裡面的UNIQUE規責
     * @return $this
     */
    public function clearTableUniqueKey(){
        $this->_table_indexes["UNIQUE"] = array();return $this;
    }
    
    /**
     * 清除Table裡面所有的索引規則
     */
    public function clearTableKeys(){
        $this->_table_indexes = array("PRIMARY"=>array(),"UNIQUE"=>array());
    }

    /**
     * 移除欄位
     * @param string $col_name 剛剛新增的欄位名稱
     * @return $this
     */
    public function removeColFromTable(string $col_name){
        if(!empty($this->_table_cols[$col_name])){unset($this->_table_cols[$col_name]);}return $this;
    }
    
    /**
     * 清空這張Table的所有欄位
     * @return $this
     */
    public function clearColFromTable(){
        $this->_table_cols = array();return $this;
    }

    /**
     * 重置所有的變數
     * @return $this
     */
    public function resetAddCol(){
        $this->_add_col_name = "";
        $this->_add_col_type = "";
        $this->_add_col_length = "";
        $this->_add_col_default = "";
        $this->_add_col_default_set = false;
        $this->_add_col_charset = "";
        $this->_add_col_attr = "";
        $this->_add_col_null = false;
        $this->_add_col_ai = false;
        $this->_add_col_comment="";
        return $this;
    }
    
    /**
     * 重置整個Table物件
     * @return $this;
     */
    public function resetTable(){
        $this->_table_name = "";
        $this->_table_engine = "MyISAM";
        $this->_table_charset = "utf8";
        $this->_table_comment = "";
        $this->resetAddCol();
        $this->clearColFromTable();
        $this->clearTableKeys();
        return $this;
    }
    
    /**
     * 把整個Table用陣列的方式輸出
     * @return array
     */
    public function getTableWithArray(){
        return array(
            "name"=>$this->_table_name,
            "engine"=>$this->_table_engine,
            "charset"=>$this->_table_charset,
            "comment"=>$this->_table_comment,
            "keys"=>$this->_table_indexes,
            "cols"=>$this->_table_cols
        );
    }

    /**
     * 取得所有的Engine類型
     * @return array
     */
    public function getAllTableEngine(){
        return array("CSV","MRG_MyISAM","MEMORY","Aria","MyISAM","SEQUENCE","InnoDB");
    }
    
    /**
     * 取得MySQL所有的索引類型
     * @return type
     */
    public function getAllKeyTypes(){
        return array("PRIMARY","UNIQUE","INDEX","FULLTEXT","SPATIAL");
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
}

?>