<?php
/**
 * 這裡有一些常用的，不必將之物件化的Function
 */

/**
 * 過濾要進資料庫的字串
 * @param string $str
 * @return string
 */
function dataFilter(string $str){
    return trim(stripslashes(addslashes(htmlspecialchars($str, ENT_QUOTES, 'UTF-8'))));
}
?>