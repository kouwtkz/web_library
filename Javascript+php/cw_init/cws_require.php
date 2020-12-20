<?php
namespace cws;
if (!isset($cws_load)) $cws_load = array();
if (!isset($cws_local_transfer)) {
    $cws_local_transfer = true;
}
$cws_load['require'] = false;
// ini_set('display_errors', "On");

if(!isset($cws_doc)) $cws_doc = $_SERVER['DOCUMENT_ROOT'];

function extr_dir($dir) {
    return preg_replace('/\/[^\/]*\.[^\/]*$/', '', $dir);
}

function auto_mkdir($dir) {
    $dir_s = '';
    $dir = extr_dir($dir);
    foreach (explode('/', $dir) as $s) {
        $dir_s .= $s.'/';
        if (!file_exists($dir_s)) mkdir($dir_s);
    }
}
function auto_rmdir($dir, $command = true) {
    $dir = get_docpath(get_dir($dir));
    if (!is_dir($dir)) return;
    $php_os = $command ? PHP_OS : '';
    switch ($php_os) {
        case 'Linux':
            exec("rm -r $dir");
        break;
        default:
            $files = array_diff(scandir($dir), array('.','..'));
            foreach ($files as $file) {
                if (is_dir("$dir/$file")) {
                    auto_rmdir("$dir/$file", false);
                } else {
                    unlink("$dir/$file");
                }
            }
            return rmdir($dir);
        break;
    }
}
function path_auto_doc(string $path = '', $auto_make = true){
    global $cws_doc;
    $doc = $cws_doc;
    if (strpos($path, '/') === 0 && strpos($path, $doc) !== 0) {
        $path = $doc.$path;
    }
    if ($auto_make) { auto_mkdir($path); }
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
function get_docpath(string $path = '', bool $return_blank = false, bool $auto_make = false) {
    $path = path_auto_doc($path, $auto_make);
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