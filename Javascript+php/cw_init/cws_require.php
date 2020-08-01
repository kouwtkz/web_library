<?php
namespace cws;
if (!isset($cws_load)) $cws_load = array();
$cws_load['require'] = false;
// ini_set('display_errors', "On");

if(!isset($cws_doc)) $cws_doc = $_SERVER['DOCUMENT_ROOT'];

function path_auto_doc(string $path = ''){
    global $cws_doc;
    $doc = $cws_doc;
    if (strpos($path, '/') === 0) {
        $path = $doc.$path;
    }
    return $path;
}
// 存在しない場合は標準の場合はnullを返す
function get_val($val_or_array, $key_or_nullval = null, $nullval = null) {
    if (is_array($val_or_array)){
        if (is_array($key_or_nullval)) {
            foreach ($key_or_nullval as &$value) {
                if (isset($val_or_array[$value])) return @$val_or_array[$value];
            }
            return $nullval;
        } else {
            return (isset($val_or_array[$key_or_nullval])) ? @$val_or_array[$key_or_nullval] : $nullval;
        }
    }
    else{
        return (is_null($val_or_array) ? $key_or_nullval : $val_or_array);
    }
}
// パスの存在チェック、存在しないときは空かパス名のいずれかを返す
function get_path(string $path = '', bool $return_blank = true) {
    $docpath = get_docpath($path, true);
    if ($docpath !== '') {
        return $path;
    } else {
        return $return_blank ? '' : $path;
    }
}
// /から始まる相対パスを変換、存在しないときもファイルパスとして出力する）
function get_docpath(string $path = '', bool $return_blank = false) {
    $path = path_auto_doc($path);
    if ($path !== '' && file_exists($path)) {
        return $path;
    } else {
        return $return_blank ? '' : $path;
    }
}
// ドメイン名の取得(ポートは引数次第)
function get_domain(string $str = '', $port = false) {
    $ret = '';
    if ($str !== '' && filter_var($str, FILTER_VALIDATE_EMAIL)) {
        $ret = explode('@', $str, 2)[1];
        if (!$port) $ret = explode(':', $ret, 2)[0];
    } else {
        if ($str !== '') {
            if (preg_match('/(\w+\:\/\/\/?|^\s*)(\[[^\]]+\]|[\w.]+|[\:\w]*)([\:\d]*)/', $str, $m)) {
                $ret = $m[2] . ($port ? $m[3] : '');
            } else {
                $ret = '';
            }
        } else {
            $ret = $_SERVER['HTTP_HOST'];
        }
    }
    return $ret;
}

function debug(bool $flag = true) {
    ini_set('display_errors', $flag?"On":"Off");
}
if(isset($cws_debug_mode) && $cws_debug_mode) {
    debug(true);
}
/* cws\debug(true); */
?>