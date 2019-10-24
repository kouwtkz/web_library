<?php
# アクセスログも含めて簡単にデータベースに接続できるようにするためのクラス
# cldbから流用、mysqliからPDOベースに書き換えた
namespace cws;
/*
    # 以下がデータベース接続するための設定となる
    cws\Cwdb::$db_servise = 'mysql';
	cws\Cwdb::$db_name = '';
	cws\Cwdb::$db_user = '';
	cws\Cwdb::$db_pass = '';
    cws\Cwdb::$db_host = '';
*/
if (isset($preg_ignore_ip)) { Cwdb::$preg_ignore_ip = $preg_ignore_ip; }
if (isset($access_reboot)) { Cwdb::$access_reboot = $access_reboot; }
if (isset($cookie_reboot)) { Cwdb::$cookie_reboot = $cookie_reboot; }
if (isset($flag_session)) { Cwdb::$flag_session = (bool)$flag_session; }
if (isset($flag_log)) { Cwdb::$flag_log = (bool)$flag_log; }

class Cwdb{
    private $access_id = "";   # セッションID_時刻の35進数をアクセスID
    public static $access_cookie_date = "+30 day";  # cookie標準の保存日付
    public static $access_reboot = false;   # ログを再びとるかどうか
    public static $cookie_reboot = false;   # クッキーをリセットするかどうか
    public static $ignore_access_cookie = "ignore_access_log_checked"; # 無視するクッキーの要素
    public static $preg_ignore_ip = "/ip/";     # ログ残す際に無視するipアドレス、正規表現
    public $pdo = null;                         # PDOのコネクトオブジェクト
    public static $table_log = "access_log";    # ログを残すときのテーブル名
    private $use_table_log = "";        # 上で設定したテーブルの確定名
    public static $flag_session = true; # セッションを使用するかどうかのフラグ
    public static $flag_log = true;     # アクセスログを残すかのフラグ
    public static $err_msg = "";        # エラーメッセージ入れるとこ
    public static $exp_err = true;      # SQL実行時にもエラーの代わりに例外を投げるように設定
    public static $fth_asc = true;      # デフォルトのフェッチモードを連想配列形式に設定 
    public static $err_dump = false;    # エラー時に出力するかどうか
    public static $db_host = "log.db"; # データベースのホスト、ファイル名かリンクかIPアドレス
    public static $db_user = "";    # ログインするユーザー名
    public static $db_pass = "";    # ログインするときのパスワード
    public static $db_name = "";    # 使うデータベースの名前
    public static $db_servise = 'sqlite';   # 使用するデータベース、標準でsqliteにした
    private $cr_servise = "";       # 現在使っているデータベースの種類
    private $temp_servise = "";     # STATICなデータベースから一時保存する
    public static $db_charset = "utf8mb4";  # 扱うときの文字型です
    public static $db_collate = ""; # 照合順序(とりあえず)
    static function connect_static(){
        $servise = self::$db_servise;
        $host = self::$db_host;
        $dbname = self::$db_name;
        $user=self::$db_user;
        $pass = self::$db_pass;
        $charset = self::$db_charset;
        switch(mb_strtolower($servise)){
            case 'sqlite': case '0':
                $cnct = 'sqlite:'.$host;
                break;
            case 'mysql': case '1':
                $cnct = 'mysql:host='.$host.';dbname='.$dbname.';charset='.$charset;
                break;
            default: $cnct = null;
        }
    
        if ($cnct!==null) {
            try{
                $pdo = new \PDO($cnct, $user, $pass);
                if (self::$exp_err) { $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); }
                if (self::$fth_asc) { $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC); }
            } catch(\Exception $e) {
                $pdo = null;
            }
        } else {
            $pdo = null;
        }
        return $pdo;
    }
    function execute($sql) {
        $dbh = $this->pdo;
        try{                
            $sth = $dbh->prepare($sql);
            $sth->execute();
            self::$err_msg = '';
        } catch(\Exception $e) {
            self::$err_msg = join('; ', $e->errorInfo);
            if (self::$err_dump) { \var_dump($sql . "\n" . self::$err_msg); };
            $sth = null;
        }
        return $sth;
    }
    function execute_all($sql) {
        $sth = $this->execute($sql);
        $result = $sth->fetchAll();
        return $result;
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
        $this->pdo = self::connect_static();
        $this->cr_servise = self::$db_servise;
        $this->temp_servise = $this->cr_servise;
        return $this->pdo;
    }
    function disconnect() {
        $this->pdo = null;
        $this->cr_servise = '';
        return $this->pdo;
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
    # STATICを一時的にローカルなものにする
    private function static_local_begin(){
        $this->temp_servise = self::$db_servise;
        self::$db_servise = $this->cr_servise;
    }
    private function static_local_end(){
        self::$db_servise = $this->temp_servise;
        $this->temp_servise = $this->cr_servise;
    }
    function session_begin(){
        if (self::$flag_session) {
            if (!isset($_SESSION)) { session_start(); }
            if (!is_null($this->pdo) && self::$flag_log) {
                $this->static_local_begin();
                $this->use_table_log = self::$table_log;
                $table = $this->use_table_log;
                if (!$this->exists($table)) {
                    $sql = "CREATE TABLE `$table` (
                        `ID` " . self::set_inc() . ",
                        `access_id` " . self::set_text(60) . ",
                        `ip_address` " . self::set_text(60) . ",
                        `user_agent` " . self::set_text() . ",
                        `access_date` " . self::set_timestamp() . ",
                        `document_root` " . self::set_text(255) . ",
                        `script_name` " . self::set_text(255) .
                        self::set_inc_foot() . "
                        )";
                    $this->execute($sql);
                }
                if (self::$access_reboot) { unset($_SESSION[$this->access_id]); }
                if (self::$cookie_reboot) {
                    self::set_cookie(self::$ignore_access_cookie, '', 0, "/");
                }
                if (!isset($_COOKIE[self::$ignore_access_cookie])) {
                    if (isset($_SESSION["access_id"])) {
                        $access_id =  $_SESSION["access_id"];
                    } elseif (isset($_COOKIE["access_id"])) {
                        $access_id = $_COOKIE["access_id"];
                    } else {
                        $access_id = '';
                    }
                    if ($access_id == '') {
                        $access_id =  session_id()."_".base_convert(time(), 10, 36);
                    }
                    $this->access_id = $access_id;
                    $scnm = $_SERVER["SCRIPT_NAME"];
                    $sql = "SELECT count(*) FROM `access_log` WHERE 
                     `access_id` = '$access_id' AND
                     `script_name` = '$scnm'";
                    $stmt = $this->execute($sql);
                    if (is_null($stmt))
                        $row = 0;
                    else
                        $row = intval($stmt->fetchColumn());
                    if ($row === 0){
                        $addr = $_SERVER["REMOTE_ADDR"];
                        $dcrt = $_SERVER["DOCUMENT_ROOT"];
                        if (!preg_match(self::$preg_ignore_ip, $addr)) {
                            $user_agent = $_SERVER["HTTP_USER_AGENT"];
                            $sql = "INSERT INTO `$table` (`ip_address`,`user_agent`,`access_id`,`document_root`,`script_name`)
                             VALUES ('$addr', '$user_agent', '$access_id','$dcrt','$scnm')";
                            $this->execute($sql);
                        } else {
                            self::set_cookie(self::$ignore_access_cookie, 1, "+3 year");
                        }
                    }
                    $_SESSION[$this->access_id] = $access_id;
                    if (!isset($_COOKIE["access_id"])) {
                        self::set_cookie("access_id", $access_id, "today");
                    }
                }
                $this->static_local_end();
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
    function __destruct() {
        $this->disconnect();
    }
    static function set_inc($primary = true){
        $servise = self::$db_servise;
        switch(mb_strtolower($servise)){
            case 'sqlite': case '0':
                $txt = 'INTEGER ';
                if ($primary) $txt .= ' NOT NULL PRIMARY KEY';
                $txt .= ' AUTOINCREMENT';
                break;
            case 'mysql': case '1':
                $txt = 'INT';
                if ($primary) $txt .= ' NOT NULL PRIMARY KEY';
                $txt .= ' AUTO_INCREMENT';
                break;
            default:
                $txt = 'INTEGER';
        }
        return $txt;
    }
    static function set_inc_foot($index_name = 'ID'){
        $servise = self::$db_servise;
        switch(mb_strtolower($servise)){
            case 'sqlite': case '0':
                $txt = '';
                break;
            case 'mysql': case '1':
                $txt = ", INDEX($index_name)";
                break;
            default:
                $txt = '';
        }
        return $txt;
    }
    static function set_bit($bits = 1){
        $servise = self::$db_servise;
        switch(mb_strtolower($servise)){
            case 'sqlite': case '0':
                $txt = 'INTEGER';
                break;
            case 'mysql': case '1':
                if ($bits < 0) {
                    $txt = 'BOOL';
                } else {
                    $txt = "BIT($bits)";
                }
                break;
            default:
                $txt = 'INTEGER';
        }
        return $txt;
    }
    static function set_int($int_size = 4){
        $servise = self::$db_servise;
        switch(mb_strtolower($servise)){
            case 'sqlite': case '0':
                $txt = 'INTEGER';
                break;
            case 'mysql': case '1':
                if ($int_size < 2) {
                    $txt = 'TINYINT';
                } else if ($int_size < 3) {
                    $txt = 'SMALLINT';
                } else if ($int_size < 4) {
                    $txt = 'MEDIUMINT';
                } else if ($int_size < 8) {
                    $txt = 'INT';
                } else {
                    $txt = 'BIGINT';
                }
                break;
            default:
                $txt = 'INTEGER';
        }
        return $txt;
    }
    static function set_real($float_size = 8){
        $servise = self::$db_servise;
        switch(mb_strtolower($servise)){
            case 'sqlite': case '0':
                $txt = 'REAL';
                break;
            case 'mysql': case '1':
                if ($float_size <= 8) {
                    $txt = 'FLOAT';
                } else {
                    $txt = 'DOUBLE';
                }
                break;
            default:
                $txt = 'FLOAT';
        }
        return $txt;
    }
    static function set_numeric($int_size = 4){
        $servise = self::$db_servise;
        switch(mb_strtolower($servise)){
            case 'sqlite': case '0':
            case 'mysql': case '1':
            default:
            $txt = 'NUMERIC';
        }
        return $txt;
    }
    static function set_text($len = -2){
        $servise = self::$db_servise;
        switch(mb_strtolower($servise)){
            case 'sqlite': case '0':
                $txt = 'TEXT';
                break;
            case 'mysql': case '1':
                if ($len <= -2) {
                    $txt = 'LONGTEXT';
                } else if ($len < 0) {
                    $txt = 'TEXT';
                } else {
                    $txt = "VARCHAR($len)";
                }
                break;
            default:
                $txt = 'TEXT';
        }
        return $txt;
    }
    static function set_brob($len = -2){
        $servise = self::$db_servise;
        switch(mb_strtolower($servise)){
            case 'sqlite': case '0':
                $txt = 'NONE';
                break;
            case 'mysql': case '1':
                if ($len >= 0) {
                    $txt = "BROB($len)";
                } else if ($len >= -1) {
                    $txt = 'TINYBROB';
                } else if ($len >= -2) {
                    $txt = 'BROB';
                } else if ($int_size < -3) {
                    $txt = 'MEDIUMBROB';
                } else {
                    $txt = 'LONGBROB';
                }
                break;
            default:
                $txt = 'NONE';
        }
        return $txt;
    }
    static function set_time($dateonly = false, $notnull = true){
        $servise = self::$db_servise;
        switch(mb_strtolower($servise)){
            case 'sqlite': case '0':
                $txt = 'TEXT';
                break;
            case 'mysql': case '1':
                if ($dateonly) {
                    $txt = 'DATE';
                } else {
                    $txt = 'DATETIME';
                }
                break;
            default:
                $txt = 'TEXT';
        }
        if ($notnull) { $txt .= ' NOT NULL'; }
        return $txt;
    }
    static function set_timestamp(){
        $servise = self::$db_servise;
        switch(mb_strtolower($servise)){
            case 'sqlite': case '0':
                $txt = "TEXT NOT NULL DEFAULT (DATETIME('now', 'localtime'))";
                break;
            case 'mysql': case '1':
                $txt = 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
                break;
                default:
                $txt = 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        }
        return $txt;
    }    
}
?>