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
     * @var array 這一個Table的所有欄位及其屬性
     */
    protected $_table_cols = array();
    
    /**
     * @var array 這一個Table的所有的索引
     */
    protected $_table_indexes = array();
    
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
     * 新增欄位1. 設定要新增的欄位名稱
     * @param string $name 欄位名稱
     * @return $this
     */
    public function setAddColName(string $name){
        $this->_add_col_name = $name;return $this;
    }
    
    public function addColToTable(){
        $this->addColToTableByParameter($this->_add_col_name, $this->_add_col_type, $this->_add_col_length, $this->_add_col_default, $this->_add_col_charset, $this->_add_col_attr, $this->_add_col_null, $this->_add_col_ai, $this->_add_col_comment);
        $this->resetAddCol();
        return $this;
    }
    
    /**
     * 新增欄位到Table裡面
     * @param string $col_name 欄位名稱
     * @param string $col_type 類型
     * @param mixed $col_length 長度/值
     * @param string $col_default 預設值
     * @param string $col_charset 編碼與排序
     * @param string $col_attr 屬性
     * @param bool $col_is_null 空值(NULL)
     * @param bool $col_ai A_I
     * @param string $col_comment 備註
     * @return $this
     */
    public function addColToTableByParameter(string $col_name,string $col_type,$col_length,string $col_default,string $col_charset,string $col_attr,bool $col_is_null,bool $col_ai,string $col_comment){
        $this->_table_cols[$col_name] = array("name"=>$col_name,"type"=>$col_type,"length"=>$col_length,"attribute"=>$col_attr,"is_null"=>$col_is_null,"default"=>$col_default,"auto_increment"=>$col_ai,"comment"=>$col_comment,"charset"=>$col_charset);
        return $this;
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
        $this->_add_col_charset = "";
        $this->_add_col_attr = "";
        $this->_add_col_null = false;
        $this->_add_col_ai = false;
        $this->_add_col_comment="";
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
     * @param mixed $length 是數字就設數字，enum的話就設array
     */
    public function setColLength($length){
        $this->col_length = is_array($length)?"'".implode("','", $length)."'":$length;
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
    
    /**
     * 取出mysql資料型別的上限
     * @param string $type_name
     * @param string $col_attr
     * @return type
     */
    public function getColTypeLength(string $type_name="",string $col_attr=""){
        switch ($col_attr){
            case "":
                break;
            default:
                $types = array(
                    "tinyint"=>array("length"=>"number","min"=> -128,"max" => 127),
                    "smallint"=>array("length"=>"number","min"=>-32768,"max"=>32767),
                    "mediumint"=>array("length"=>"number","min"=>-8388608,"max"=>8388607),
                    "int"=>array("length"=>"number","min"=>"","max"),"bigint","decimal","float","double","real","bit","boolean","serial","date","datetime","timestamp","time","year","char","varchar","tinytext","text","mediumtext","longtext","binary","varbinary","tinyblob","blob","mediumblob","longblob","enum","set","geometry","point","linestring","polygon","multipoint","multilinestring","multipolygon","geometrycollection","json");
                break;
        }
        return empty($type_name)?$types:!empty($types[$type_name])?$types[$type_name]:array();
    }
}

?>