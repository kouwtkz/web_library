<?php
namespace cws;
function cws_db_set_log(DB $instance, $ignore_mode = false){
    $dbi = $instance->dbi;
    $pdo = $dbi->pdo;
    $addr = $_SERVER["REMOTE_ADDR"];
    if (!is_null($pdo) && $dbi->flag_log) {
        $instance->use_table_log = $dbi->table_log;
        $table = $instance->use_table_log;
        if (!$instance->exists($table)) {
            $sql = "CREATE TABLE `$table` (
                `ID` " . $instance->set_inc() . ",
                `access_id` " . $instance->set_text(60) . ",
                `ip_address` " . $instance->set_text(60) . ",
                `user_agent` " . $instance->set_text() . ",
                `access_date` " . $instance->set_timestamp() . ",
                `referer` " . $instance->set_text(255) . ",
                `document_root` " . $instance->set_text(255) . ",
                `script_name` " . $instance->set_text(255) .
                $instance->set_inc_foot() . "
                )";
            $instance->execute($sql);
        }
        if ($dbi->cookie_use) {
            if ($dbi->access_reboot) { unset($_SESSION[$instance->access_id]); }
            if ($dbi->cookie_reboot) {
                Cookie::set($dbi->ignore_access_cookie, '', 0, "/");
            }
        }
        if (!isset($_COOKIE[$dbi->ignore_access_cookie])) {
            if ($dbi->cookie_use) {
                if (isset($_SESSION[$dbi->access_id_cookie])) {
                    $access_id =  $_SESSION[$dbi->access_id_cookie];
                } elseif (isset($_COOKIE[$dbi->access_id_cookie])) {
                    $access_id = $_COOKIE[$dbi->access_id_cookie];
                } else {
                    $access_id = '';
                }
                if ($access_id == '') {
                    if ($dbi->cookie_use)
                    $access_id = base_convert(session_id()."_".time(), 10, 36);
                }
            } else {
                $access_id =  $addr."_".date("Ymd");
            }
            $dbi->access_id = $access_id;
            $scnm = $_SERVER["SCRIPT_NAME"];
            $referer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "";
            $sql = "SELECT count(*) FROM `" . $dbi->table_log . "` WHERE 
                `access_id` = '$access_id' AND
                `script_name` = '$scnm'";
            $stmt = $instance->execute($sql);
            if (is_null($stmt))
                $row = 0;
            else
                $row = intval($stmt->fetchColumn());
            if ($row === 0){
                $dcrt = $_SERVER["DOCUMENT_ROOT"];
                if (!$ignore_mode) {
                    $user_agent = $_SERVER["HTTP_USER_AGENT"];
                    $sql = "INSERT INTO `$table` (`ip_address`,`user_agent`,`access_id`,`referer`,`document_root`,`script_name`)
                        VALUES ('$addr', '$user_agent', '$access_id','$referer','$dcrt','$scnm')";
                    $instance->execute($sql);
                }
            }
            $stmt = null;
            if ($dbi->cookie_use) {
                $_SESSION[$dbi->access_id] = $access_id;
                if (!isset($_COOKIE[$dbi->access_id_cookie])) {
                    Cookie::set($dbi->access_id_cookie, $access_id, "today");
                }
            }
        }
    }
}
?>