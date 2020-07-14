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
    public $_table_cols = array();
    
    public $col_name = "";
    public $col_type = "";
    public $col_length = 0;
    public $col_default = "";
    public $col_charset = "utf8";
    public $col_attributes = "";
    public $col_is_null = false;
    public $col_comment = "";
    
    /**
     * 設定這一張Table的名稱
     * @param string $table_name
     */
    public function setTableName(string $table_name){
        $this->_table_name = $table_name;return $this;
    }
    
    public function addTableCol(string $col_name,string $col_type){}
    
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