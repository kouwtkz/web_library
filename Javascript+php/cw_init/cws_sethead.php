<?php
namespace cws;
# 自動ヘッダー関数、頻繁に変えるため分離した
if (!isset($cws_load)) $cws_load = array();
$cws_load['sethead'] = false;
include_once('cws.php');

function set_header(...$args) {
    if (count($args) === 0) $args = array(1);
    $default_charset = 'utf-8';
    $charset = $default_charset;
    $local_set_header = function($args) use (&$local_set_header, $charset) {
        foreach($args as $k => $opt) {
            if (is_array($opt)) { $local_set_header($opt); continue; }
            $headstr = null;
            $set_ext = true;
            switch (mb_strtolower($k)) {
                case 'char': case 'charset':
                    $charset = $opt; continue 2;
            }
            switch (mb_strtolower($opt)) {
            case '1': case 'text': case 'txt': case 'conf': case 'plane': case 'php': case 'cgi': case 'py':
                $headstr = 'text/plane; charset='.$charset; break;
            case '2': case 'json':
                $headstr = 'application/json; charset='.$charset; break;
            case '3': case 'script': case 'javascript':
                $headstr = 'text/javascript; charset='.$charset; break;
            case '4': case 'css':
                $headstr = 'text/css; charset='.$charset; break;
            case '5': case 'html': case 'htm':
                $headstr = 'text/html; charset='.$charset; break;
            case '6': case 'pdf':
                $headstr = 'application/pdf'; break;
            case '8': case 'image': case 'png': case 'apng':
                $headstr = 'image/png'; break;
            case '9': case 'jpeg': case 'jpg':
                $headstr = 'image/jpeg'; break;
            case '10': case 'gif':
                $headstr = 'image/gif'; break;
            case '11': case 'svg':
                $headstr = 'image/svg+xml'; break;
            case '16': case 'audio': case 'mp3': case 'aac': case 'm4a':
                $headstr = 'audio/*'; break;
            case '17': case 'video': case 'movie': case 'mov': case 'mp4': case 'ani':
                $headstr = 'video/*'; break;
            case '18': case 'ogg':
                $headstr = 'application/ogg'; break;
            case '20': case 'wav':
                $headstr = 'audio/wav'; break;
            case '101': case 'acao': case 'access control':
                $set_ext = false; $headstr = 'Access-Control-Allow-Origin: *'; break;
            default: $headstr = 'application/octet-stream'; break;
            }
            if ($headstr!==null) {
                if ($set_ext) $headstr = 'Content-Type: '.$headstr;
                header($headstr);
            }
        }
    };
    $local_set_header($args);
}
function download($dir, $filename){
    global $cws;
    // memory check
    $limitsize = $cws->limitsize;
    $filesize = filesize($dir.$filename);
    if ($limitsize < $filesize * 2) {
        return false;
    }
    $data = file_get_contents($dir.$filename);
    if ($data !== false) {
        set_headfile($dir, $filename);
        echo($data);
        return true;
    } else {
        return false;
    }
}
function set_headfile(string $path_or_name = '', string $filename = '', $download = false) {
    $path_or_name = get_docpath($path_or_name);
    if ($path_or_name==='') {$download = '';}
    $name = '';
    switch(gettype($download)){
        case 'boolean':
            if ($download) {
                $download = 'attachment';
            } else {
                $download = 'inline';
                if($filename===''){ $filename = basename($path_or_name); }
                set_header(pathinfo($filename, PATHINFO_EXTENSION));
            }
            break;
        case 'object':
            $download = 'form-data'; break;
            if ($path_or_name!=='') {$name = ' ;name="'.$name.'"';}
        default: $download = '';
    }
    if ($filename!=='') {$filename = ' ;filename="'.$filename.'"';}
    if ($download !== '') {
        header('Content-Disposition: '.$download.$name.$filename);
        if ($path_or_name !== '') {readfile($path_or_name);}
    }
}
?>