<?php
namespace cws;
# アクセスログも含めて簡単にデータベースに接続できるようにするためのクラス
# cldbから流用、mysqliからPDOベースに書き換えた
/*
    # 以下がデータベース接続するための設定となる
    cws\DB::$db_servise = 'mysql';
    cws\DB::$db_host = '';
	cws\DB::$db_name = '';
	cws\DB::$db_user = '';
	cws\DB::$db_pass = '';
*/
require_once("cws_cookie.php");

// データベースに接続するための情報
class DBI{
    public $cookie_use = false;      # クッキーを使うか、デフォルトで使わない(IP+日付を使う)
    public $access_reboot = false;   # ログを再びとるかどうか
    public $cookie_reboot = false;   # クッキーをリセットするかどうか
    public $ignore_access_cookie = "ignore_access_log_checked"; # 無視するクッキーの要素
    public $access_id_cookie = "access_id";  # アクセスIDのクッキー名
    public $preg_ignore_ip = "/ip/";     # ログ残す際に無視するipアドレス、正規表現
    public $table_log = "access_log";    # ログを残すときのテーブル名
    public $flag_session = true; # セッションを使用するかどうかのフラグ
    public $flag_log = false;    # アクセスログを残すかのフラグ
    public $err_msg = "";        # エラーメッセージ入れるとこ
    public $exp_err = true;      # SQL実行時にもエラーの代わりに例外を投げるように設定
    public $fth_asc = true;      # デフォルトのフェッチモードを連想配列形式に設定 
    public $err_dump = false;    # エラー時に出力するかどうか
    public $db_host = "log.db"; # データベースのホスト、ファイル名かリンクかIPアドレス
    public $db_user = "";    # ログインするユーザー名
    public $db_pass = "";    # ログインするときのパスワード
    public $db_name = "";    # 使うデータベースの名前
    public $db_servise = 'sqlite';   # 使用するデータベース、標準でsqliteにした
    public $db_charset = "utf8mb4";  # 扱うときの文字型です
    public $db_collate = "";    # 照合順序(とりあえず)
    public $pdo = null;         # PDOのコネクトオブジェクト
    static function set_value_after(&$to_value, &$from_value, $after = null, $after_ins = true){
        $to_value = $from_value;
        if ($after_ins) $from_value= $after_ins;
        return $to_value;
    }
    function global_init(){
        global $cws_cookie_use, $cws_flag_session;
        global $cws_db_servise, $cws_db_host, $cws_db_name, $cws_db_user, $cws_db_pass;
        global $cws_table_log, $cws_flag_log, $cws_err_dump, $cws_preg_ignore_ip;
        global $cws_access_reboot, $cws_cookie_reboot;
        if (isset($cws_cookie_use)) { self::set_value_after($this->cookie_use, $cws_cookie_use, null); }
        if (isset($cws_flag_session)) { self::set_value_after($this->flag_session, $cws_flag_session, null); }
        if (isset($cws_db_servise)) { self::set_value_after($this->db_host, $cws_db_servise, null); }
        if (isset($cws_db_host)) { self::set_value_after($this->db_host, $cws_db_host, null); }
        if (isset($cws_db_name)) { self::set_value_after($this->db_name, $cws_db_name, null); }
        if (isset($cws_db_user)) { self::set_value_after($this->db_user, $cws_db_user, null); }
        if (isset($cws_db_pass)) { self::set_value_after($this->db_user, $cws_db_pass, null); }
        if (isset($cws_table_log)) { self::set_value_after($this->table_log, $cws_table_log, null); }
        if (isset($cws_flag_log)) { self::set_value_after($this->flag_log, $cws_flag_log, null); }
        if (isset($cws_err_dump)) { self::set_value_after($this->err_dump, $cws_err_dump, null); }
        if (isset($cws_preg_ignore_ip)) { self::set_value_after($this->preg_ignore_ip, $cws_preg_ignore_ip, null); }
        if (isset($cws_access_reboot)) { self::set_value_after($this->access_reboot, $cws_access_reboot, null); }
        if (isset($cws_cookie_reboot)) { self::set_value_after($this->cookie_reboot, $cws_cookie_reboot, null); }
    }
    static function create($global_enable = true){
        return new self($global_enable);
    }
    function __construct($global_enable = true){
        if ($global_enable) $this->global_init();
    }
    function __destruct() {
        $this->pdo = null;
    }
    function __clone() {
        $this->pdo = null;
    }
}

class DB{
    private $access_id = "";   # セッションID_時刻の35進数をアクセスID
    public $dbi = null;             # データベースの情報
    public $pdo = null;             # PDOのコネクトオブジェクト
    function execute($sql, ...$param) {
        $dbi = $this->dbi;
        $servise = $dbi->db_servise;
        $dbh = $dbi->pdo;
        try{
            $sth = $dbh->prepare($sql);
            self::bind($param, $sth);
            $sth->execute();
            $dbi->err_msg = '';
        } catch(\Exception $e) {
            $dbi->err_msg = join('; ', $e->errorInfo);
            if ($dbi->err_dump) { \var_dump($sql . "\n" . $dbi->err_msg); 
            };
            $sth = null;
        }
        return $sth;
    }
    function execute_all($sql, ...$param) {
        $sth = $this->execute($sql, $param);
        $result = $sth->fetchAll();
        return $result;
    }
    function exists($table, $column = "") {
        $param = array();
        if ($column === "") {
            $sql = "SELECT 1 FROM `$table` LIMIT 1;";
        } else {
            $sql = "SELECT `$column` FROM `$table` LIMIT 1;";
        }
        $dbi = $this->dbi;
        $tmp_err_dump = $dbi->err_dump;
        $dbi->err_dump = false;
        $result = $this->execute($sql);
        $dbi->err_dump = $tmp_err_dump;
        return (bool)$result;
    }
    function connect($dbi = null) {
        if (is_null($dbi))
            $dbi = $this->dbi;
        else
            $this->dbi = $dbi;
        $servise = $dbi->db_servise;
        $host = $dbi->db_host;
        $dbname = $dbi->db_name;
        $user=$dbi->db_user;
        $pass = $dbi->db_pass;
        $charset = $dbi->db_charset;
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
                if ($dbi->exp_err) { $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); }
                if ($dbi->fth_asc) { $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC); }
            } catch(\Exception $e) {
                $pdo = null;
            }
        } else {
            $pdo = null;
        }
        $dbi->pdo = $pdo;
        $this->pdo = $pdo;
        return $pdo;
    }
    function disconnect() {
        $dbi = $this->dbi;
        $dbi->pdo = null;
        $this->pdo = null;
        return $this->pdo;
    }
    static function bind($param, &$sth){
        foreach ($param as $k => $p) {
            if (is_array($p)) {
                self::bind($p, $sth);
                continue;
            }
            if (is_numeric($k)) $k = intval($k) + 1;
            $sth->bindParam($k, $p);
        }
    }
    static function escape($param){
        $param = preg_replace("/(\'|\\\\)/","$1$1",$param);
        return $param;
    }
    function session_begin(){
        $dbi = $this->dbi;
        $pdo = $dbi->pdo;
        $addr = $_SERVER["REMOTE_ADDR"];
        $ignore_mode = preg_match($dbi->preg_ignore_ip, $addr);
        if ($dbi->flag_session) {
            if ($dbi->cookie_use && !isset($_SESSION)) { session_start(); }
            if (!is_null($pdo) && $dbi->flag_log) {
                $this->use_table_log = $dbi->table_log;
                $table = $this->use_table_log;
                if (!$this->exists($table)) {
                    $sql = "CREATE TABLE `$table` (
                        `ID` " . self::set_inc() . ",
                        `access_id` " . self::set_text(60) . ",
                        `ip_address` " . self::set_text(60) . ",
                        `user_agent` " . self::set_text() . ",
                        `access_date` " . self::set_timestamp() . ",
                        `referer` " . self::set_text(255) . ",
                        `document_root` " . self::set_text(255) . ",
                        `script_name` " . self::set_text(255) .
                        self::set_inc_foot() . "
                        )";
                    $this->execute($sql);
                }
                if ($dbi->cookie_use) {
                    if ($dbi->access_reboot) { unset($_SESSION[$this->access_id]); }
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
                    $stmt = $this->execute($sql);
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
                            $this->execute($sql);
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
        if ($ignore_mode) {
            if ($dbi->cookie_use) {
                Cookie::set($dbi->ignore_access_cookie, 1, "+3 year");
            }
        }
    }
    function session_connect($dbi = null){
        $this->connect($dbi);
        $this->session_begin();
    }
    function set_inc($primary = true){
        $dbi = $this->dbi;
        $servise = $dbi->db_servise;
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
    function set_inc_foot($index_name = 'ID'){
        $dbi = $this->dbi;
        $servise = $dbi->db_servise;
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
    function set_bit($bits = 1){
        $dbi = $this->dbi;
        $servise = $dbi->db_servise;
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
    function set_int($int_size = 4){
        $dbi = $this->dbi;
        $servise = $dbi->db_servise;
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
    function set_real($float_size = 8){
        $dbi = $this->dbi;
        $servise = $dbi->db_servise;
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
    function set_numeric($int_size = 4){
        $dbi = $this->dbi;
        $servise = $dbi->db_servise;
        switch(mb_strtolower($servise)){
            case 'sqlite': case '0':
            case 'mysql': case '1':
            default:
            $txt = 'NUMERIC';
        }
        return $txt;
    }
    function set_text($len = -2){
        $dbi = $this->dbi;
        $servise = $dbi->db_servise;
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
    function set_brob($len = -2){
        $dbi = $this->dbi;
        $servise = $dbi->db_servise;
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
    function set_time($dateonly = false, $notnull = true){
        $dbi = $this->dbi;
        $servise = $dbi->db_servise;
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
    function set_timestamp(){
        $dbi = $this->dbi;
        $servise = $dbi->db_servise;
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
    static function create($dbi = null){
        if (is_null($dbi)) $dbi = new DBI();
        return new self($dbi);
    }
    function __construct($dbi = null){
        if (is_null($dbi)) $dbi = new DBI();
        $this->session_connect($dbi);
    }
    function __destruct() {
        $this->disconnect();
    }
}
?>