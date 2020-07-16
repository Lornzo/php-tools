<?php
if(!class_exists("sqli_db_switch")){require(__DIR__."/sqli_db_switch.class.php");}
if(!class_exists("sqli_db_table")){require(__DIR__."./sqli_db_table.class.php");}
/**
 * 繼承自sqli_db_switch開發的MySQL操作物件，內含比較危險的操作Function，請小心使用
 * @requires sqli_db_switch.class.php 父類別物件
 * @requires sqli_db_table.class.php 建立Table的必要物件
 * @author Lornzo Lee(李赤兔) <a6288678@hotmail.com>
 * @version 2020.7.7
 */
class sqli_db_opration extends sqli_db_switch{
    
    protected function _getColQuery(string $col_name,string $col_type,$col_length){
        $query = "`".$col_name."` ".$col_type."(";
        $query .= is_array($col_length)?"'".implode("','", $col_length)."'":$col_length;
        $query .= ")";
        
    }
    
    protected function _buildCreateColsQuerys(array $table_data){
        $result = array();
        if(!empty($table_data["cols"])){
            foreach($table_data["cols"] as $col_name => $cold_data){
                $result[] = "";
            }
        }
        return $resutl;
    }
    
    public function createTable(sqli_db_table $table,bool $drop_exists_table=false){
        $table_data = $table->getTableWithArray();
        print_r($table_data);
        if(!empty($table_data["name"]) && !empty($table_data["engine"]) && !empty($table_data["charset"])){
            if($drop_exists_table){$this->dropTable($table_data["name"]);}
            $query = "CREATE TABLE IF NOT EXISTS `".$table_data["name"]."`(";
            $query .= implode(",", $this->_buildCreateColsQuerys($table));
            $query .= ") ENGINE=".$table_data["engine"]." DEFAULT CHARSET=".$table_data["charset"];
            $query .= !empty($table_data["comment"])?" COMMENT='".$table_data["comment"]."'":"";
            $query .= ";";
            $this->doQuerys(array($query,"COMMIT;"));
        }
        
        
//        "CREATE TABLE IF NOT EXISTS `lornzo_test` (
//  `no` int(11) NOT NULL AUTO_INCREMENT,
//  `lornzo` varchar(255) NOT NULL,
//  PRIMARY KEY (`no`)
//) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='測試資料表';
//COMMIT;";
//        
//        
//        "CREATE TABLE IF NOT EXISTS `lornzo_test` (
//  `no` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
//  `tesst` int(11) UNSIGNED DEFAULT 0 COMMENT '123',
//  `lornzo` varchar(255) NOT NULL,
//  PRIMARY KEY (`no`)
//) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='測試資料表';
//COMMIT;
//";
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
     * @param string $table_name 要刪除的資料表名稱
     * @return bool
     */
    public function dropTable(string $table_name){
        return !empty($table_name)?$this->doQuery("DROP TABLE IF EXISTS ".$table_name.";"):false;
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