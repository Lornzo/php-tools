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

    /**
     * 建表用的Function
     * @param sqli_db_table $table MySQL資料表的物件
     * @param bool $drop_exists_table 如果已在MySQL存在相同表名的話，要不要先把前面那個表給刪掉
     */
    public function createTable(sqli_db_table $table,bool $drop_exists_table=false){
        $table_data = $table->getTableWithArray();
        print_r($table_data);
        if(!empty($table_data["name"]) && !empty($table_data["engine"]) && !empty($table_data["charset"])){
            if($drop_exists_table){$this->dropTable($table_data["name"]);}
            $query = "CREATE TABLE IF NOT EXISTS `".$table_data["name"]."`(";
            $query .= implode(",", $this->_buildCreateColsQuerys($table_data));
            $query .= ") ENGINE=".$table_data["engine"]." DEFAULT CHARSET=".$table_data["charset"];
            $query .= !empty($table_data["comment"])?" COMMENT='".$table_data["comment"]."'":"";
            $query .= ";";
            $this->doQuerys(array($query,"COMMIT;"));
        }
    }
    
    /**
     * (未完成)把sqli_db_table物件裡面的欄位，組合成MySQL Query (但是還無法指用編碼)
     * @param array $table_data
     * @return array
     */
    protected function _buildCreateColsQuerys(array $table_data){
        $result = array();
        if(!empty($table_data["cols"])){
            foreach($table_data["cols"] as $col_name => $col_data){
                $query = "`".$col_name."` ".$col_data["type"]."(";
                $query .= is_array($col_data["length"])?"'".implode("','", $col_data["length"])."'":$col_data["length"];
                $query .= ")";
                $query .= !empty($col_data["attribute"])?" ".$col_data["attribute"]:"";
                $query .= !empty($col_data["is_null"])?" NULL":" NOT NULL";
                $query .= !empty($col_data["default"])?" DEFAULT '".$col_data["default"]."'":"";
                $query .= !empty($col_data["auto_increment"])?" AUTO_INCREMENT":"";
                $query .= !empty($col_data["comment"])?" '".$col_data["comment"]."'":"";
                $result[] = $query;
            }
        }
        if(!empty($table_data["keys"]["PRIMARY"])){
            $result[] = "PRIMARY KEY (`".implode("`,`", $table_data["keys"]["PRIMARY"])."`)";
        }
        if(!empty($table_data["keys"]["UNIQUE"])){
            foreach($table_data["keys"]["UNIQUE"] as $key_name => $keys){
                $result[] = "UNIQUE KEY `".$key_name."` (`".implode("`,`", $keys)."`)";
            }
        }
        return $result;
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