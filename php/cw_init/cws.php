<?php
namespace cws;
class server{
    function __construct($thisfile = __FILE__){
        $t = $this;
        $t->method = (count($_REQUEST) === 0) ? '' : $_SERVER['REQUEST_METHOD'];
        $t->scheme = getval($_SERVER, 'REQUEST_SCHEME', '');
        $t->basehost = $t->scheme.'://'.getval($_SERVER, 'HTTP_HOST', '');
        $t->path = preg_replace("/\?.+$/",'',getval($_SERVER, 'REQUEST_URI', ''));
        $t->url = $t->basehost.$t->path;
        $t->root = $_SERVER['DOCUMENT_ROOT'];
        $t->pathlist = explode('/', $t->path);
        $t->php_path = str_replace($t->root,'',str_replace('\\','/',__FILE__));
        $t->php_dir = getdir($t->php_path);
        $t->ref_url = getval($_SERVER, 'HTTP_REFERER', "");
        $t->ref_domain = getdomain($t->ref_url);
        $t->ref_basehost = getbasehost($t->ref_url);
        $t->ref_dir = getdir($t->ref_url);
        $t->ht_head = false;
        $t->ht_body = false;
    }
    function head($opt = 0, string $charset='utf-8', $optarg=array()){
        if (!($this->ht_head)) {
            echo("<html>\n<head>\n");
            echo('<meta http-equiv="Content-Type" content="text/html" charset="'.$charset.'"/>'."\n");
            if($opt&1){echo('<meta name="viewport" content="width=device-width,initial-scale=1">'."\n");}
            $link = setlinkdata($this->php_dir."/server.js", null, 7);
            if($link!=null){
                jsrun(
                    'cws.php_path = "'.$this->php_path.'";'
                );
            };
        }
        $this->ht_head = true;
    }
    function body ($opt = 0) {
        if (!($this->ht_body)) {
            echo("</head>\n<body>\n");
        }
        $this->ht_body = true;
    }
}
$cws = new server();
function getdomain(string $url){
    preg_match('/\/\/.*?\//', $url, $base);
    return (count($base)===0)?"":mb_substr($base[0],2,-1);
}
function getbasehost(string $url){
    preg_match('/^.*?\/\/.*?\//', $url, $base);
    return (count($base)===0)?"":mb_substr($base[0],0,-1);
}
function getpathname(string $url){
    $basehost = getbasehost($url);
    $url = mb_substr($url, mb_strlen($basehost));
    return $url;
}
function getdir(string $url){
    preg_match('/^.*?\./', $url.'.', $base);
    preg_match('/^.*\//', $base[0], $base);
    return (count($base)===0)?"":preg_replace('/\/+$/','/',$base[0]);
}
function title($title_str){
    echo("<title>$title_str</title>\n");
}
function sethead_file(string $path_or_name = '', string $filename = '', $download = false) {
    $path_or_name = docpath($path_or_name);
    if ($path_or_name==='') {$download = '';}
    $name = '';
    switch(gettype($download)){
        case 'boolean':
            if ($download) {
                $download = 'attachment';
            } else {
                $download = 'inline';
                if($filename===''){ $filename = basename($path_or_name); }
                sethead_type(pathinfo($filename, PATHINFO_EXTENSION));
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

function sethead_type($opt = 1, $charset='utf-8') {
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
    default: $headstr = 'application/octet-stream'; break;
    }
    if ($headstr!==null) {header('Content-Type: '.$headstr);}
}

// 存在しない場合は標準の場合はnullを返す
function getval($ary, string $key, $nullval = null) { return (isset($ary[$key])) ? $ary[$key] : $nullval; }
// /から始まる相対パスを変換、そして存在するパスじゃないとき空文字列で返す
function docpath(string $argpath, $_blank = true) {
    global $cws;
    if (strncmp($argpath, '/', 1) === 0) {
        $cpath = $cws->root.$argpath;
    } elseif (preg_match("/\//", $argpath)) {
        $cpath = $argpath;
    } elseif ($argpath !== '') {
        $cpath = $cws->refparent.$argpath;
    } else {
        $cpath = '';
    }
    if ($cpath !== '' && file_exists($cpath)) {
        return $cpath;
    } else {
        return $cpath ? '' : $cpath;
    }
}
// 更新日を返してクエリ化する
// あとtxtファイルはtxtとして返される（直接変換する）
// opt|4はElement要素の出力を同時に行うかどうか
function setlinkdata($gpath, $tag=null, $opt = 3){
    $bid = getval($tag, 'id');
    if($bid!=null){unset($tag['id']);}
    $btag = '';
    switch(gettype($tag)){
    case 'array':
        $keys = array_keys($tag);
        for ($i = 0;$i < count($keys);++$i) {
            $val = $tag[$keys[$i]];
            if(gettype($val)=='array'){$val=implode(' ',$val);}
            $btag = $btag.' '.$keys[$i].'="'.$val.'"';
        }
        break;
    case 'string':
        $btag = ' '.$tag;
    }
    $lpath = $gpath;
    if(gettype($gpath) === 'string'){ $lpath = array($lpath); }
    if(gettype($lpath) === 'array'){
        $keys = array_keys($lpath);
        for ($i = 0;$i < count($keys);++$i) {
            $p = preg_replace("/\?.+$/", '', $lpath[$keys[$i]]);
            $dp = docpath($p);
            $ext = mb_strtolower(pathinfo($p, PATHINFO_EXTENSION));
            if (($opt&1)&&($ext=='txt')) {
                if ($opt & 2) {
                    if ($dp != '') {
                        $lpath[$keys[$i]] = array('element'=>null, 'content'=>file_get_contents($dp),'path'=>$p, 'docpath'=>$dp);
                        if ($opt & 4) {echo($lpath[$keys[$i]]);}
                    }
                }
            } else {
                if ($dp !== '') {
                    $mdate = filemtime($dp);
                } else {
                    $mdate = 0;
                }
                if ($mdate !== 0) {$elm = $p.(preg_match("/\?/", $p) ? '&' : '?').'v='.$mdate;} else {$elm = $p;}
                if ($opt & 2) {
                    $addtag = (($bid!=null)?(' id="'.$bid.(count($keys)>1)?$i:''.'"'):'').$btag;
                    switch ($ext) {
                        case 'css': $elm = '<link rel="stylesheet"'.$addtag.' href="'.$elm.'" type="text/css" media="all">'; break;
                        case 'js': $elm = '<script type="text/javascript"'.$addtag.' src="'.$elm.'"></script>'; break;
                        case 'png': case 'jpg': case 'jpeg': case 'gif': case 'tiff': case 'bmp':
                            $elm = '<img'.$addtag.($opt&1?' alt="'.$keys[$i]:'').'" src="'.$elm.'" />'; break;
                        default : $elm = '';
                        var_dump($elm);
                    }
                    if (($opt & 4)&&($elm!='')){echo($elm."\n");}
                    $lpath[$keys[$i]] = array('element'=>$elm, 'content'=>null,'path'=>$p, 'docpath'=>$dp);
                }
            }
        }
        if(gettype($gpath) === "string"){ $lpath = $lpath[$keys[0]]; }
        return $lpath;
    }
    return ($opt&2)?array():null;
}
function jsrun($str, $onLoadDelete = false){
    $id = '__aft_delete__';
    $idelm = $onLoadDelete?' id="'.$id.'"':'';
    $runstr = '<script'.$idelm.'>'.$str
        .($onLoadDelete?';document.getElementById("'.$id.'").outerHTML = "";':'')
        .'</script>'."\n";
    echo($runstr);
}
// 最初の文字が[か{であるならば、JSON文字列
// そうでなければパスとみなす
function json_read($target_json)
{
    $jsonstr = '';
    if (preg_match('/^[ \n]*[{\[]/', $target_json)) {
        $jsonstr = $target_json;
    } else {
        $target_json = docpath($target_json);
        if (file_exists($target_json)) {
            $jsonstr = file_get_contents($target_json);
            $jsonstr = mb_convert_encoding($jsonstr, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
        }
    }
    if ($jsonstr !== '') {
        return json_decode($jsonstr, true);
    } else {
        return [];
    }
}
function json_out($json, $pretty = false)
{
    try {
        switch (gettype($json)) {
    case 'array':
    $opt = JSON_UNESCAPED_UNICODE | (($pretty) ? JSON_PRETTY_PRINT : 0);

    return json_encode($json, $opt);
    case 'object': return;
    default: return $json;
    }
    } catch (Exception $e) {
        return '';
    }
}
function connect($servise='sqlite',$host='test.db',$dbname='',$user='',$pass='',$charset='utf8'){
    switch(mb_strtolower($servise)){
    case 'sqlite': case '0':
        $cnct = 'sqlite:'.docpath($host);
        break;
    case 'mysql': case '1':
        $cnct = 'mysql:host='.$host.';dbname='.$dbname.';charset='.$charset;
        break;
    default: $cnct = null;
    }

    if ($cnct!==null) {
        try{
            $pdo = new \PDO($cnct, $user, $pass);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $pdo = null;
        }
    } else {
        $pdo = null;
    }
    return $pdo;
}
function debug($flag = true) {
    ini_set('display_errors', $flag?"On":"Off");
}
?>