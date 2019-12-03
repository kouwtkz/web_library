<?php
namespace cws;
if (!isset($cws_load)) $cws_load = array();
$cws_load['require'] = false;
// ini_set('display_errors', "On");

// $cws_jump_to = '/hogehoge';
// $cws_jump_trigger = '/\/redirect/';

if(!isset($cws_jump_to)) $cws_jump_to = '';
if(!isset($cws_jump_not_preg)) $cws_jump_not_preg = '';
if(!isset($cws_jump_trigger)) $cws_jump_trigger = '';
if(!isset($cws_jump_replace) || $cws_jump_replace === '') $cws_jump_replace = $cws_jump_to.'$0';
if(!isset($cws_jump_redirect_port)) $cws_jump_redirect_port = '';
if(!isset($cws_jump_redirect_host)) $cws_jump_redirect_host = '';

$cws_jump_replace = server_ireplace($cws_jump_replace);
if (!isset($cws_localhost_preg)) $cws_localhost_preg = '/192\.168|127/';
$cws_local_mode = preg_match($cws_localhost_preg, $_SERVER['REMOTE_ADDR']);

$cws_auto_preg = '/^([\/\#\~])(.*)([\/\#\~])([smui]?)$/';   

function server_ireplace(string $str){
    foreach (array(
        'REQUEST_SCHEME', 'HTTP_HOST', 'DOCUMENT_ROOT', 'SERVER_NAME', 'SERVER_ADDR',
        'SERVER_PORT', 'REMOTE_ADDR', 'REQUEST_URI', 'SCRIPT_NAME', 'QUERY_STRING'
    ) as $key){
        $rep = '%{'.$key.'}';
        if (isset($_SERVER[$key])) {
            $str = str_ireplace($rep, $_SERVER[$key], $str);
        }
    }
    return $str;
}
(function(){
    global $cws_jump_to, $cws_jump_not_preg, $_jump_mode;
    $doc = '/'.$_SERVER["HTTP_HOST"].$_SERVER['DOCUMENT_ROOT'];
    if ($cws_jump_not_preg === '') {
        $_jump_mode = ($cws_jump_to !== '') ? (strpos($doc, $cws_jump_to) === false) : false;
    } else {
        $_jump_mode = ($cws_jump_to !== '') ? !preg_match($cws_jump_not_preg, $doc) : false;
    }
})();
function path_auto_base(string $path = '', bool $redirect = false){
    global $_jump_mode, $cws_jump_trigger, $cws_jump_replace, $cws_jump_not_trigger;
    if ($_jump_mode && ($cws_jump_trigger !== '')) {
        if (preg_match($cws_jump_trigger, $path) && !preg_match($cws_jump_not_trigger, $path) ) {
            $path = preg_replace($cws_jump_trigger, $cws_jump_replace, $path);
        }
    }
    return $path;
}
function path_auto_doc(string $path = ''){
    $doc = $_SERVER['DOCUMENT_ROOT'];
    $path = path_auto_base($path, false);
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
function debug($flag = true) {
    ini_set('display_errors', $flag?"On":"Off");
}
if(isset($cws_debug_mode) && $cws_debug_mode) {
    debug(true);
}
/* cws\debug(true); */
?>