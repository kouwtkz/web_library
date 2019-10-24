<?php
namespace cws;
# クッキー用命令
class Cookie{
    public static $access_cookie_date = "+30 day";  # cookie標準の保存日付    
    static function set($name, $value = "1", $time = null, $dir = "/"){
        if (ctype_digit($time) && $time <= 0) {
            $time = time() - 42000;
            unset($_COOKIE[$name]);
        } else {
            if (is_null($time)) {
                $time = strtotime(self::$access_cookie_date);
            } elseif ($time === "today") {  # 今日までの日付でクッキーを設置
                $time = strtotime(date("y-m-d",strtotime("+1 day"))) - 1;
            } elseif (preg_match("/([\+\-]?\d*)\s*(\w*)/" , $time, $m)){
                $sub = sprintf("%+d", ((int)$m[1] + 1));
                # 正規表現で +1 day といった型を補足してn日後の処理を行う
                # uyearとumonthとudayで今年まで、今月まで、今日まで、という処理を行う
                if (preg_match("/year|month|day/", $m[2])) {
                    $time = strtotime(date("y-m-d",strtotime($time))) - 1;
                } elseif (preg_match("/uyear/", $m[2])) {
                    $time = strtotime(date("y",strtotime("$sub year"))) - 1;
                } elseif (preg_match("/umonth/", $m[2])) {
                    $time = strtotime(date("y-m",strtotime("$sub month"))) - 1;
                } elseif (preg_match("/uday/", $m[2])) {
                    $time = strtotime(date("y-m-d",strtotime("$sub day"))) - 1;
                } else {
                    $time = strtotime($time) - 1;
                }
            }
            $_COOKIE[$name] = $value;
        }
        setcookie($name, (string)$value, $time, $dir);
    }
}
?>