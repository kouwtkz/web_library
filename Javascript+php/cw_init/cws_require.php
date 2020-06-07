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
// ドメイン名の取得(ポート含む)
function get_domain(string $str = '') {
    if (filter_var($str, FILTER_VALIDATE_EMAIL)) {
        return explode('@', $str, 2)[1];
    } else {
        preg_match('/\/\/.*?\//', $str, $base);
        return (count($base)===0)?'':mb_substr($base[0],2,-1);
    }
}
function debug(bool $flag = true) {
    ini_set('display_errors', $flag?"On":"Off");
}
if(isset($cws_debug_mode) && $cws_debug_mode) {
    debug(true);
}
/* cws\debug(true); */
?>