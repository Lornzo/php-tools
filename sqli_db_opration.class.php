<?php
if(!class_exists("sqli_db_switch")){require(__DIR__."sqli_db_switch.class.php");}


/**
 * 繼承自sqli_db_switch開發的MySQL操作物件，內含比較危險的操作Function，請小心使用
 * @requires sqli_db_switch 父類別物件
 * @author Lornzo Lee(李赤兔) <a6288678@hotmail.com>
 * @version 2020.7.7
 */
class sqli_db_opration extends sqli_db_switch{
    
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
    
    
    public function showDatabases(){
        
        
    }
    
    public function showTables(){
        $db_tables = mysqli_fetch_arr($this->doQuery("SHOW TABLES;"),MYSQLI_ASSOC);
        $result = array();
    }
}
?>