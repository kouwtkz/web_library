<?php
namespace cws;
# アクセスログも含めて簡単にデータベースに接続できるようにするためのクラス
# cldbから流用、mysqliからPDOベースに書き換えた
/*
    # 以下がデータベース接続するための設定となる
    cws\DB::$db_service = 'mysql';
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
    private $db_obj = array(
        'host' => 'log.db',      # データベースのホスト、ファイル名かリンクかIPアドレス
        'user' => '',            # ログインするユーザー名
        'pass' => '',            # ログインするときのパスワード
        'name' => '',            # 使うデータベースの名前
        'service' => 'sqlite',   # 使用するデータベース、標準でsqliteにした
        'charset' => 'utf8mb4',  # 扱うときの文字型です
        'collate' => 'utf8mb4_bin'  # 照合順序、絵文字検索のサポート
    );
    private $db_list = null;
    public $db_active = 0;
    public $pdo = null;         # PDOのコネクトオブジェクト
    public $flag_bind_param = false;    # TrueならbindParam、FalseならbindValueを使う
    # bindValueの方が値は確実に反映されるのでデフォルトでfalse、メモリと確実性の兼ね合い
    static function set_value_after(&$to_value, &$from_value, $after_ins = true){
        $to_value = $from_value;
        if ($after_ins) $from_value= $after_ins;
        return $to_value;
    }
    function __get($name){
        $em = explode('_', $name);
        switch($em[0]) {
            case 'db':
                if (count($em) > 1) {
                    if ($em[1] == 'count') {
                        return count($this->db_list);
                    } else {
                        return $this->db_list[$this->db_active][$em[1]];
                    }
                }
            break;
        }
        return '';
    }
    function __set($name, $value){
        $em = explode('_', $name);
        switch($em[0]) {
            case 'db':
                if (count($em) > 1) {
                    if ($em[1] == 'list') {
                        $this->db_list = array();
                        for ($i = 0; $i < count($value); $i++) {
                            $obj = $this->db_obj;
                            foreach ($value[$i] as $lk => $lv) {
                                $obj[$lk] = $lv;
                            }
                            array_push($this->db_list, $obj);
                        }
                    } else {
                        $this->db_list[$this->db_active][$em[1]] = $value;
                    }
                }
            break;
        }
    }
    function global_init($changeable_db = true){
        $change_db = false;
        $local_db_obj = $this->db_obj;
        global $cws_cookie_use, $cws_flag_session;
        global $cws_db_service, $cws_db_host, $cws_db_name, $cws_db_user, $cws_db_pass;
        global $cws_table_log, $cws_flag_log, $cws_err_dump, $cws_preg_ignore_ip;
        global $cws_access_reboot, $cws_cookie_reboot;
        if (isset($cws_cookie_use)) { self::set_value_after($this->cookie_use, $cws_cookie_use); }
        if (isset($cws_flag_session)) { self::set_value_after($this->flag_session, $cws_flag_session); }
        if ($changeable_db) {
            if (isset($cws_db_service)) { $change_db = true; $local_db_obj['service'] = $cws_db_service; }
            if (isset($cws_db_host)) { $change_db = true; $local_db_obj['host'] = $cws_db_host; }
            if (isset($cws_db_name)) { $change_db = true; $local_db_obj['name'] = $cws_db_name; }
            if (isset($cws_db_user)) { $change_db = true; $local_db_obj['user'] = $cws_db_user; }
            if (isset($cws_db_pass)) { $change_db = true; $local_db_obj['pass'] = $cws_db_pass; }
            if ($change_db) { self::set_value_after($this->db_list[0], $local_db_obj); }
        }
        if (isset($cws_table_log)) { self::set_value_after($this->table_log, $cws_table_log); }
        if (isset($cws_flag_log)) { self::set_value_after($this->flag_log, $cws_flag_log); }
        if (isset($cws_err_dump)) { self::set_value_after($this->err_dump, $cws_err_dump); }
        if (isset($cws_preg_ignore_ip)) { self::set_value_after($this->preg_ignore_ip, $cws_preg_ignore_ip); }
        if (isset($cws_access_reboot)) { self::set_value_after($this->access_reboot, $cws_access_reboot); }
        if (isset($cws_cookie_reboot)) { self::set_value_after($this->cookie_reboot, $cws_cookie_reboot); }
    }
    static function create(...$param){
        return new self($param);
    }
    function __construct(...$param){
        $global_enable = true;
        $changeable_db = true;
        $local_db_obj = $this->db_obj;
        $this->construct_loop($param, $global_enable, $local_db_obj, $changeable_db);
        $this->db_list = array($local_db_obj);
        if ($global_enable) $this->global_init($changeable_db);
    }
    private function construct_loop(&$param, &$global_enable, &$local_db_obj, &$changeable_db) {
        foreach ($param as $value) {
            switch (gettype($value)) {
                case 'boolean':
                    $global_enable = $value;
                break;
                case 'array':
                    if (isset($value[0])) {
                        $this->construct_loop($value, $global_enable, $local_db_obj, $changeable_db);
                    } else {
                        $local_db_obj = array_merge($local_db_obj, $value);
                        $changeable_db = false;
                    }
                break;
            }
        }
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
        $service = $dbi->db_service;
        $dbh = $dbi->pdo;
        if ($dbh === null) return null;
        try{
            $sth = $dbh->prepare($sql);
            if (count($param) === 0) {
                if (preg_match('/^\s*SELECT/i', $sql)) {
                    $dbi->pdo->query($sql);
                } else {
                    $dbi->pdo->exec($sql);
                }
            } else {
                self::bind($param, $sth, $dbi->flag_bind_param);
                $sth->execute();
            }
            $dbi->err_msg = '';
        } catch(\Exception $e) {
            $dbi->err_msg = join('; ', $e->errorInfo);
            if ($dbi->err_dump) { \var_dump($sql . "\n" . $dbi->err_msg); };
            $sth = null;
        }
        return $sth;
    }
    function execute_all($sql, ...$param) {
        $sth = $this->execute($sql, $param);
        $result = ($sth !== null) ? $sth->fetchAll() : array();
        return $result;
    }
    function exists($table, $column = "", $value = null) {
        $value_mode = false;
        if ($column === "") {
            $sql = "SELECT 1 FROM `$table` LIMIT 1;";
        } else {
            if (is_null($value)) {
                $sql = "SELECT `$column` FROM $table LIMIT 1;";
            } else {
                $value_mode = true;
                $sql = "SELECT count(*) AS `count` FROM $table WHERE `$column` = ? LIMIT 1;";
            }
        }
        $dbi = $this->dbi;
        $tmp_err_dump = $dbi->err_dump;
        $dbi->err_dump = false;
        if ($value_mode) {
            $result = $this->execute($sql, $value);
            $result = is_null($result) ? false : (intval($result->fetch()['count']) > 0);
        } else {
            $result = $this->execute($sql);
        }
        $dbi->err_dump = $tmp_err_dump;
        return (bool)$result;
    }
    function connect($dbi = null) {
        if (is_null($dbi))
            $dbi = $this->dbi;
        else
            $this->dbi = $dbi;
        for ($i = 0; $i < $dbi->db_count; $i++) {
            $dbi->db_active = $i;
            $service = $dbi->db_service;
            $host = $dbi->db_host;
            $dbname = $dbi->db_name;
            $user=$dbi->db_user;
            $pass = $dbi->db_pass;
            $charset = $dbi->db_charset;
            $collate = $dbi->db_collate;
            switch(mb_strtolower($service)){
                case 'sqlite': case '0':
                    $cnct = 'sqlite:'.$host;
                    break;
                case 'mysql': case '1':
                    $cnct = 'mysql:host='.$host.';dbname='.$dbname.';charset='.$charset.';collage='.$collate;
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
            if (!is_null($pdo)) break;
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
    static function bind($param, &$sth, $flag_bind_param = false){
        foreach ($param as $k => $p) {
            if (is_array($p)) {
                self::bind($p, $sth, $flag_bind_param);
                continue;
            }
            $param_type = \PDO::PARAM_STR;
            switch (gettype($p)){
                case 'integer':
                $param_type = \PDO::PARAM_INT; break;
                case 'boolean':
                $param_type = \PDO::PARAM_BOOL; break;
                case 'NULL': case 'unknown type':
                $param_type = \PDO::PARAM_NULL; break;
            }
            if (is_numeric($k)) $k = intval($k) + 1;
            if ($flag_bind_param) {
                $sth->bindParam($k, $p, $param_type);
            } else {
                $sth->bindValue($k, $p, $param_type);
            }
        }
    }
    static function escape($param){
        $param = preg_replace("/(\'|\\\\)/","$1$1",$param);
        return $param;
    }
    function session_begin(){
        $dbi = $this->dbi;
        $ignore_mode = preg_match($dbi->preg_ignore_ip, $_SERVER["REMOTE_ADDR"]);
        if ($dbi->flag_session) {
            if ($dbi->cookie_use && !isset($_SESSION)) { session_start(); }
            if ($dbi->flag_log) {
                include_once('cws_db_log.php');
                cws_db_set_log($this, $ignore_mode);
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

    function set_primary($not_null = false){
        return ($not_null ? ' NOT NULL' : '') . ' PRIMARY KEY';
    }
    function set_inc($primary = true){
        $dbi = $this->dbi;
        $service = $dbi->db_service;
        switch(mb_strtolower($service)){
            case 'sqlite': case '0':
                $txt = 'INTEGER ';
                if ($primary) $txt .= $this->set_primary(true);
                $txt .= ' AUTOINCREMENT';
                break;
            case 'mysql': case '1':
                $txt = 'INT';
                if ($primary) $txt .= $this->set_primary(true);
                $txt .= ' AUTO_INCREMENT';
                break;
            default:
                $txt = 'INTEGER';
        }
        return $txt;
    }
    function set_inc_foot($index_name = 'ID'){
        $dbi = $this->dbi;
        $service = $dbi->db_service;
        switch(mb_strtolower($service)){
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
    function set_bit($args = null, $default = null){
        if (is_null($args)) $args = array();
        if (is_array($args)) {
            $bits = isset($args['size']) ? $args['size'] : 1;
            $default = isset($args['default']) ? $args['default'] : $default;
        } else {
            $bits = 1;
        }
        $dbi = $this->dbi;
        $service = $dbi->db_service;
        switch(mb_strtolower($service)){
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
        if (!is_null($default)) {
            $default = intval($default);
            $txt .= " DEFAULT $default";
        }
        return $txt;
    }
    function set_int($args = null, $default = null){
        if (is_null($args)) $args = array();
        if (is_array($args)) {
            $int_size = isset($args['size']) ? $args['size'] : 4;
            $default = isset($args['default']) ? $args['default'] : $default;
        } else {
            $int_size = 4;
        }
        $dbi = $this->dbi;
        $service = $dbi->db_service;
        switch(mb_strtolower($service)){
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
        if (!is_null($default)) {
            $default = intval($default);
            $txt .= " DEFAULT $default";
        }
        return $txt;
    }
    function set_real($args = null, $default = null){
        if (is_null($args)) $args = array();
        if (is_array($args)) {
            $float_size = isset($args['size']) ? $args['size'] : 8;
            $default = isset($args['default']) ? $args['default'] : $default;
        } else {
            $float_size = 8;
        }
        $dbi = $this->dbi;
        $service = $dbi->db_service;
        switch(mb_strtolower($service)){
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
        if (!is_null($default)) {
            $default = floatval($default);
            $txt .= " DEFAULT $default";
        }
        return $txt;
    }
    function set_numeric($args = null, $default = null){
        if (is_null($args)) $args = array();
        if (is_array($args)) {
            $int_size = isset($args['size']) ? $args['size'] : 8;
            $default = isset($args['default']) ? $args['default'] : $default;
        } else {
            $int_size = 4;
        }
        $dbi = $this->dbi;
        $service = $dbi->db_service;
        switch(mb_strtolower($service)){
            case 'sqlite': case '0':
            case 'mysql': case '1':
            default:
            $txt = 'NUMERIC';
        }
        if (!is_null($default)) {
            $default = floatval($default);
            $txt .= " DEFAULT $default";
        }
        return $txt;
    }
    function set_text($args = null, $default = null){
        if (is_null($args)) $args = array();
        if (is_array($args)) {
            $len = isset($args['size']) ? $args['size'] : -2;
            $default = isset($args['default']) ? $args['default'] : $default;
        } else if(is_numeric($args)) {
            $len = $args;
        } else {
            $len = -2;
        }
        $dbi = $this->dbi;
        $service = $dbi->db_service;
        switch(mb_strtolower($service)){
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
        if (!is_null($default)) {
            $txt .= " DEFAULT '$default'";
        }
        return $txt;
    }
    function set_brob($args = null, $default = null){
        if (is_null($args)) $args = array();
        if (is_array($args)) {
            $len = isset($args['size']) ? $args['size'] : -2;
            $default = isset($args['default']) ? $args['default'] : $default;
        } else {
            $len = -2;
        }
        $dbi = $this->dbi;
        $service = $dbi->db_service;
        switch(mb_strtolower($service)){
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
        if (!is_null($default)) {
            $txt .= " DEFAULT '$default'";
        }
        return $txt;
    }
    function set_date($notnull = false, $dateonly = true){
        return $this->set_time($notnull, $dateonly);
    }
    function set_datetime($notnull = false, $dateonly = false){
        return $this->set_time($notnull, $dateonly);
    }
    function set_timestamp($default_timestamp = true, $type_timestamp = false){
        return $this->set_time(true, false, $default_timestamp, $type_timestamp);
    }
    function set_time($notnull = false, $dateonly = false, $default_timestamp = false, $type_timestamp = false){
        $dbi = $this->dbi;
        $service = $dbi->db_service;
        $add_default = '';
        switch(mb_strtolower($service)){
            case 'sqlite': case '0':
                $txt = 'TEXT';
                if ($default_timestamp || $type_timestamp)
                        $add_default = " DEFAULT (DATETIME('now', 'utc'))";
            break;
            case 'mysql': case '1':
                if ($dateonly) {
                    $txt = 'DATE';
                } else {
                    $txt = ($type_timestamp) ? 'TIMESTAMP' : 'DATETIME';
                    if ($default_timestamp || $type_timestamp)
                        $add_default = ' DEFAULT CURRENT_TIMESTAMP';
                }
            break;
            default:
                $txt = 'TEXT';
            break;
        }
        if ($notnull) { $txt .= ' NOT NULL'; }
        $txt .= $add_default;
        return $txt;
    }
    function now($localtime = true){
        $dbi = $this->dbi;
        $service = $dbi->db_service;
        switch(mb_strtolower($service)){
            case 'sqlite': case '0':
                if ($localtime)
                    return "DATETIME('now', 'localtime')";
                else
                    return "DATETIME('now', 'utc')";
            break;
            case 'mysql': case '1':
                return 'NOW()';
            break;
            default:
                return 'NOW()';
            break;
        }
        return '';
    }
    function regable($dbi = null){
        if (is_null($dbi)) $dbi = $this->dbi;
        $service = $dbi->db_service;
        switch(mb_strtolower($service)){
            case 'mysql': case '1':
                return true;
            break;
            default:
                return false;
            break;
        }
        return '';
    }
    function collater($i_up_low = false, $i_full_width = false, $sql_collate = true, $db_def = true){
        $dbi = $this->dbi;
        $collate = $dbi->db_collate;
        $return_collate = '';
        if ($db_def) {
            $service = $dbi->db_service;
        } else {
            $service = 'mysql';
        }
        switch(mb_strtolower($service)){
            case 'mysql': case '1':
                if (strpos($collate, 'utf8') !== false) {
                    if (strpos($collate, 'mb4') === false) {
                        if ($i_full_width) {
                            $return_collate = 'utf8_unicode_ci';
                        } else if ($i_up_low) {
                            $return_collate = 'utf8_general_ci';
                        } else {
                            $return_collate = 'utf8_bin';
                        }
                    } else {
                        if ($i_full_width) {
                            $return_collate = 'utf8mb4_unicode_ci';
                        } else if ($i_up_low) {
                            $return_collate = 'utf8mb4_general_ci';
                        } else {
                            $return_collate = 'utf8mb4_bin';
                        }
                    }
                }
            break;
        }
        if ($sql_collate && $return_collate !== '') $return_collate = " COLLATE $return_collate";
        return $return_collate;
    }
    function reg_classify($reg_str = '', $like_str = '', $grob_str = '', $i_up_low = null){
        $dbi = $this->dbi;
        $service = $dbi->db_service;
        $return_type = '';
        $return_collate = '';
        $return_str = '';
        $i_full_width = false;
        switch(mb_strtolower($service)){
            case 'sqlite': case '0':
                if ($grob_str !== '') {
                    $return_type = 'GLOB';
                } else {
                    $return_type = 'LIKE';
                }
            break;
            case 'mysql': case '1':
                if ($reg_str === '' && $like_str !== '') {
                    $return_type = 'LIKE';
                } else {
                    $return_type = 'REGEXP';
                }
            break;
            default:
                $return_type = 'LIKE';
            break;
        }
        switch($return_type){
            case 'REGEXP':
                if (preg_match('/^([^\w\s\\\\])([\d\D]*)([^\w\s\\\\])(\w*)$/', $reg_str, $m)) {
                    if ($m[1] === $m[3] || (strlen($m13 = $m[1].$m[3]) === 2 && (($m13) === '()' || $m13 === '{}' || $m13 === '[]' || $m13 === '<>'))) {
                        $reg_str = $m[2];
                        if (strpos($m[4], 'i') !== false) {
                            $i_up_low = true;
                        }
                        if (strpos($m[4], 'f') !== false) {
                            $i_up_low = true;
                            $i_full_width = true;
                        }
                    }
                }
                $return_str = $reg_str;
            break;
            case 'LIKE':
                $return_str = $like_str;
            break;
            case 'GLOB':
                $return_str = $grob_str;
            break;
        }
        if (!is_null($i_up_low)){
            $return_collate = $this->collater($i_up_low, $i_full_width, true);
        }
        return array($return_type, $return_str, $return_collate);
    }
    static function create(...$param){
        return new self($param);
    }
    function __construct(...$param){
        $dbi = null;
        $dbi_param = array();
        $this->construct_loop($param, $dbi, $dbi_param);
        if (is_null($dbi)) $dbi = new DBI($dbi_param);
        $this->session_connect($dbi);
    }
    private function construct_loop(&$param, &$dbi, &$dbi_param) {
        foreach ($param as $value) {
            switch (gettype($value)) {
                case 'array':
                    if (isset($value[0])) {
                        $this->construct_loop($value, $dbi, $dbi_param);
                    } else {
                        $dbi_param = array_merge($dbi_param, $value);
                    }
                break;
                case 'object':
                    if (is_a($value, 'cws\DBI')) {
                        $dbi = $value;
                    }
                break;
            }
        }
    }
    function __destruct() {
        $this->disconnect();
    }
    // XSS対策用
    static function h($s){
        return htmlspecialchars($s, ENT_QUOTES, 'utf-8');
    }
}
?>