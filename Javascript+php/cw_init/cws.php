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

class hook_class {
    function __construct($value = '', $mode = '', $mode_not = false, $mode_tag = false, $regex = false){
        $this->value = $value;
        $this->mode = $mode;
        $this->mode_not = $mode_not;
        $this->mode_tag = $mode_tag;
        $this->regex = $regex;
    }
}
/*
使い方（関数例）
$search = new cws\search($keyword);
loop{
    $result = $search->get_result($str);
}
*/
class search{
    static function delimiter($v){
        $delimiter = null;
        $braceDelimiters = array('('=>')', '{'=>'}', '['=>']', '<'=>'>');
    
        if (preg_match("/^([^a-zA-Z0-9\\\\]).*([^a-zA-Z0-9\\\\])[a-zA-Z]*$/", $v, $m)){
            // デリミタが正しい組み合わせになっているかをチェック
            [$dummy, $leftDlmt, $rightDlmt] = $m;
            if (isset($braceDelimiters[$leftDlmt]) 
                && $rightDlmt === $braceDelimiters[$leftDlmt] 
                || $leftDlmt === $rightDlmt
            ) {
                $delimiter = $leftDlmt;
            }
        }
        return $delimiter;
    }
    static function escape($v){
        $rep = " $v";
        $rep = preg_replace("/[\\\\][\\\\]/","\\\?", $rep);
        $rep = preg_replace("/[\\\\][\|]/", "\\\:", $rep);
        $rep = preg_replace("/[\\\\][\&]/", "\\\;", $rep);
        $rep = preg_replace("/[\\\\]\ /", "\\\_", $rep);
        $rep = preg_replace("/\|\|/"," OR ", $rep);
        $rep = preg_replace("/\&\&/"," AND ", $rep);
        $rep = preg_replace("/(\s+)\-/", ' NOT ', $rep);
        $rep = preg_replace("/(\s+)\#/", ' TAG ', $rep);
        $rep = preg_replace("/^\s+/", "", $rep);
        return $rep;
    }
    static function revive($v){
        $rep = $v;
        $rep = preg_replace("/[\\\\][\:]/","|", $rep);
        $rep = preg_replace("/[\\\\][\;]/","&", $rep);
        $rep = preg_replace("/[\\\\][\_]/"," ", $rep);
        $rep = preg_replace("/[\\\\][\?]/","\\", $rep);
        return $rep;
    }
    function get_hook(){
        return $this->hook;
    }
    function __construct($keyword = null, $tag_mode = false, $w_mode = false){
        if ($keyword !== null) $this->hook_define($keyword, $tag_mode, $w_mode);
    }
    static function s_search($str, $value){
        
        $l_result = true;
        $filter_func = function($hook) use (&$l_result, &$str){
            if (is_array($hook)){
                $m_result = self::s_search($str, $hook);
            } else {
                $or_trigger = $hook->mode === '|';
                if (is_array($hook->value)) {
                    $m_result = self::s_search($str, $hook->value);
                } else {
                    $hook_val = $hook->value;
                    // 正規表現の分岐
                    if ($hook->regex) {
                        $m_result = preg_match($hook_val, $str);
                    } else {
                        if ($hook->mode_tag) {
                            $hook_val = " $hook_val ";
                        }
                        $m_result = strpos($str, $hook_val);
                    }
                }
                $m_result = $m_result !== false;
                $m_result = ($m_result xor $hook->mode_not);
                if ($or_trigger) {
                    $l_result = ($l_result || $m_result);
                    $or_trigger = false;
                } else {
                    $l_result = ($l_result && $m_result);
                }
            }
        };
        array_filter($value, $filter_func);
        return $l_result;
    }
    function get_result($subject = "", $keywords = null){
        $str = " ".preg_replace("/[\#\s]+/", " ", preg_replace("/[\[](.*)[\]]/", " [ $1 ] ", $subject))." ";
        $result = &$this->result;
        $result = true;
        if ($keywords == null) {
            $keywords = $this->hook;
        } elseif (is_array($keywords)){
            $keywords = $this->hook_define($keywords);
        }
        $filter_func = function($v) use (&$str, &$result){
            $result = ($result && self::s_search($str, $v));
        };
        array_filter($keywords, $filter_func);
        return $result;
    }
    function hook_define($keyword, $tag_mode = false, $w_mode = false){
        $this->hook_list = [];
        $this->hook_mode = "";
        $this->hook_not = false;
        $this->hook_tag = $tag_mode;
        $this->hook_value = [];
        $this->hook = $this->ret_hook_define($keyword, $w_mode);
        $this->hook_list = [];
        return $this->hook;
    }
    private function ret_hook_define($keyword, $w_mode = false){
        $this->hook = [];
        $me = &$this;
        $map_func = function($v) use ($me, $w_mode){
            $ret = self::escape($v);
            $map_func = function($re) use ($me, $w_mode){
                $hook_mode = &$me->hook_mode;
                $hook_list = &$me->hook_list;
                $hook_not = &$me->hook_not;
                $hook_tag = &$me->hook_tag;
                $hook_value = &$me->hook_value;
                switch($re){
                    case "OR":
                        $hook_mode = '|';
                        return null;
                    case "AND":
                        $hook_mode = '&';
                        return null;
                    case "NOT":
                        $hook_not = true;
                        return null;
                    case "TAG":
                        $hook_tag = true;
                        return null;
                    default:
                        $delimiter = self::delimiter($re);
                        $regex = ($delimiter !== null);
                        $add_val = self::revive($re);
                        if ($w_mode && !$regex) {
                            $hw = mb_convert_kana($add_val, 'kvrn');
                            $fw = mb_convert_kana($add_val, 'KVRN');
                            if ($hw !== $fw) {
                                $add_val = [new hook_class($add_val, '', false, $hook_tag),
                                    new hook_class($hw, '|', false, $hook_tag), new hook_class($fw, '|', false, $hook_tag)];
                            }
                        }
                        if ($hook_mode === ''){
                            if ($add_val !== '') {
                                $hook_value = [new hook_class($add_val, '', $hook_not, $hook_tag, $regex)];
                                array_push($hook_list, $hook_value);
                                $hook_not = false;
                                $hook_tag = false;
                                return $hook_value;
                            } else {
                                $hook_not = false;
                                $hook_tag = false;
                                return null;
                            }
                        } else {
                            // 前のリスト増分
                            if ($add_val !== ''){
                                array_push($hook_value, 
                                    new hook_class($add_val, $hook_mode, $hook_not, $hook_tag, $regex));
                                $hook_list[count($hook_list) - 1] = $hook_value;
                            }
                            $hook_mode = '';
                            $hook_not = false;
                            $hook_tag = false;
                            return null;
                        }
                }
            };
            $ret = array_map($map_func, preg_split("/\s/",$ret));
            $ret = array_filter($ret, function($v){
                return $v !== null;
            });
            return $ret;
        };
        array_map($map_func, preg_split("/\s/","$keyword "));
        $keywords = $me->hook_list;
        return $keywords;
    }
}
// ページ送りのフィルター、最後に設定するもの
function filter_page($array, $page = 1, $max = 9) {
    global $filter_count;
    $filter_count = 0;
    $since = ($page - 1) * $max;
    $until = $page * $max - 1;
    $filter_func = function ($value) use ($since, $until){
        global $filter_count;
        $result = ($since <= $filter_count) && ($filter_count <= $until);
        $filter_count++;
        return $result;
    };
    return array_filter($array, $filter_func);
}
// キーワード検索
function filter_keyword($array, $keyword, $array_func = null) {
    $chk = preg_replace("/\s+|[\*]+/", "", $keyword);
    $blank_true = ($chk === "");
    if (!$blank_true) {
        $search = new search($keyword);
    } else {
        $search = null;
    }
    if ($array_func === null) $array_func = function($v){
        if (is_string($v)){
            $str = $v;
        } else {
            $str = "";
            if (isset($v->genre)) $str .= " $v->genre";
            if (isset($v->tag)) $str .= " $v->tag";
            if (isset($v->title)) $str .= " $v->title";
            if (isset($v->subject)) $str .= " $v->subject";
            if (isset($v->value)) $str .= " $v->value";
        }
        return $str;
    };
    $filter_func = function ($value) use (&$search, $blank_true, $array_func){
        if ($blank_true) return true;
        $array_val = " ".$array_func($value)." ";
        $result = $search->get_result($array_val);
        return $result;
    };
    return array_filter($array, $filter_func);
}
// 除外検索（自分用）、デフォルトでsrcやhrefが空の場合は除外する
function filter_exclusion($array, $filter_func = null){
    if ($filter_func === null) $filter_func = function($v){
        $result = true;
        if (isset($v->src)) $result &= ($v->src !== "");
        if (isset($v->href)) $result &= ($v->href !== "");
        return $result;
    };
    return array_filter($array, $filter_func);
}
?>