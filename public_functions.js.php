<?php
/**
 * 這裡有一些常用的，不必將之物件化的Function(PHP印JS)
 */

/**
 * 輸出訊息後回到上一個頁面
 * @param type $msg
 */
function errback($msg){
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"><script>";
    echo "alert('$msg');";
    echo "history.go(-1);";
    echo "</script>";
}

/**
* 送出訊息後，轉跳到指定的位置
* @param type $msg
* @param type $loc
* @param type $target
*/
function msggo($msg,$loc, $target = "self"){
       echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"><script>";
       echo "alert('$msg');";
       echo "".$target.".location.href='$loc';";
       echo "</script>";
}
?>