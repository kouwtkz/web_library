<?php
# アクセスログも含めて簡単にデータベースに接続できるようにするためのクラス
/*
    # 以下がデータベース接続するための設定となる
	Cldb::$db_name = '';
	Cldb::$db_user = '';
	Cldb::$db_pass = '';
	Cldb::$db_host = '';
*/
if (isset($preg_ignore_ip)) { Cldb::$preg_ignore_ip = $preg_ignore_ip; }
if (isset($access_reboot)) { Cldb::$access_reboot = $access_reboot; }
if (isset($cookie_reboot)) { Cldb::$cookie_reboot = $cookie_reboot; }
if (isset($flag_session)) { Cldb::$flag_session = (bool)$flag_session; }
if (isset($flag_log)) { Cldb::$flag_log = (bool)$flag_log; }

class Cldb{
    private $access_id = "access_id";
    public static $access_reboot = false;   # ログを再びとるかどうか
    # セッションID_時刻の35進数をアクセスIDとしてるため、同一セッションでも異なる値にできる
    public static $cookie_reboot = false;   # クッキーをリセットするかどうか
    public $ignore_access_cookie = "ignore_access_log_checked"; # 無視するクッキーの要素
    public static $preg_ignore_ip = "/ip/";     # ログ残す際に無視するipアドレス、正規表現
    public $conn = null;                        # mysqliのコネクトオブジェクト
    public static $table_log = "access_log";    # ログを残すときのテーブル名
    private $use_table_log = "";        # 上で設定したテーブルの確定名
    public static $msg_err = "";        # エラーメッセージ入れるとこ
    public static $flag_session = true; # セッションを使用するかどうかのフラグ
    public static $flag_log = true;     # アクセスログを残すかのフラグ
    public static $db_host = "";    # データベースのホスト、ローカルならIPアドレス
    public static $db_user = "";    # ログインするユーザー名
    public static $db_pass = "";    # ログインするときのパスワード
    public static $db_name = "";    # 使うデータベースの名前
    public static $db_charset = "utf8mb4";  # 扱うときの文字型です
    public static $db_collate = ""; # 照合順序(とりあえず)
    static function connect_static(){
        $tmp_cnct = mysqli_connect(self::$db_host, self::$db_user, self::$db_pass, self::$db_name);
        $tmp_cnct->set_charset(self::$db_charset);
        mysqli_set_charset($tmp_cnct, self::$db_charset);
        if (!$tmp_cnct) {
            self::$msg_err = mysqli_error();
            $tmp_cnct = null;
        }
        return $tmp_cnct;
    }
    function execute($sql) {
        return mysqli_query($this->conn, $sql);
    }
    function execute_all($sql) {
        return mysqli_fetch_all($this->execute($sql));
    }
    function exists($table, $column = "") {
        if ($column === "") {
            $sql = "SELECT 1 FROM $table LIMIT 1;";
        } else {
            $sql = "SELECT $column FROM $table LIMIT 1;";
        }
        $result = $this->execute($sql);
        return (bool)$result;
    }
    function connect() {
        $this->conn = self::connect_static();
        return $this->conn;
    }
    static function escape($param){
        $param = preg_replace("/(\'|\\\\)/","$1$1",$param);
        return $param;
    }
    static function set_cookie($name, $value = "1", $time = null, $dir = "/"){
        if (ctype_digit($time) && $time <= 0) {
            $time = time() - 42000;
            unset($_COOKIE[$name]);
        } else {
            if (is_null($time)) {   # デフォルトで雑に30日後まで
                $time = time() + (30 * 24 * 60 * 60);
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
    function session_begin(){
        if (self::$flag_session) {
            if (!isset($_SESSION)) { session_start(); }
            if (!is_null($this->conn) && self::$flag_log) {
                $this->use_table_log = self::$table_log;
                $table = $this->use_table_log;
                if (!$this->exists($table)) {
                    $sql = "CREATE TABLE `$table` (
                        `ID` bigint(20) UNSIGNED AUTO_INCREMENT NOT NULL UNIQUE,
                        `$this->access_id` varchar(60),
                        `ip_address` varchar(60),
                        `user_agent` longtext,
                        `access_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `document_root` varchar(255),
                        `script_name` varchar(255),
                        INDEX(ID)
                        )";
                    $this->execute($sql);
                }
                if (self::$access_reboot) { unset($_SESSION[$this->access_id]); }
                if (self::$cookie_reboot) {
                    self::set_cookie($this->ignore_access_cookie, '', 0, "/");
                }

                if (!isset($_COOKIE[$this->ignore_access_cookie])) {
                    if (isset($_SESSION[$this->access_id])) {
                        $access_id =  $_SESSION[$this->access_id];
                    } elseif (isset($_COOKIE[$this->access_id])) {
                        $access_id = $_COOKIE[$this->access_id];
                    } else {
                        $access_id =  session_id()."_".base_convert(time(), 10, 36);
                    }
                    $scnm = $_SERVER["SCRIPT_NAME"];
                    $sql = "SELECT 1 FROM `access_log` WHERE 
                     `$this->access_id` = '$access_id' AND
                     `script_name` = '$scnm'";
                    if (($this->execute($sql))->num_rows === 0) {
                        $addr = $_SERVER["REMOTE_ADDR"];
                        $dcrt = $_SERVER["DOCUMENT_ROOT"];
                        if (!preg_match(self::$preg_ignore_ip, $addr)) {
                            $user_agent = $_SERVER["HTTP_USER_AGENT"];
                            $sql = "INSERT INTO `$table` (`ip_address`,`user_agent`,`$this->access_id`,`document_root`,`script_name`)
                             VALUES ('$addr', '$user_agent', '$access_id','$dcrt','$scnm')";
                            $this->execute($sql);
                        } else {
                            self::set_cookie($this->ignore_access_cookie, 1, null, "/");
                        }
                    }
                    $_SESSION[$this->access_id] = $access_id;
                    if (!isset($_COOKIE[$this->access_id])) {
                        self::set_cookie($this->access_id, $access_id, "today");
                    }
                }
            }
        }
    }
    function session_connect(){
        $this->connect();
        $this->session_begin();
    }
    function __construct(){
        $this->session_connect();
    }

}
?>