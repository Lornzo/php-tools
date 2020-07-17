<?php
if(!class_exists("sqli_db_opration")){require(__DIR__."/sqli_db_opration.class.php");}
/**
 * 把Schema整個MySQL化，但繼承了db_opration，所以要小心使用
 * @requires sqli_db_opration.class.php 父類別物件
 * @author Lornzo Lee(李赤兔) <a6288678@hotmail.com>
 * @version 2020.7.7
 */
class sqli_db_schema extends sqli_db_opration{
    
}
?>