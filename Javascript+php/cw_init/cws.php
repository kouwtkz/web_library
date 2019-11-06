<?php
/*
// cws.phpと組み合わせて以下のように定義する (cws\はget_mdateなどの前に、ケースに応じて)
namespace cws;
require_once($_SERVER['DOCUMENT_ROOT']."/common/cw_init/cws.php");
<script type="text/javascript" src="/common/cw_init/cws.js?<?php echo(get_mdate('/common/cw_init/cws.js')); ?>"></script>
*/
namespace cws;
// $cws_debug_mode = true;
if(!isset($cws_require_enable) && !$cws_require_enable) include_once('cws_require.php');
class server{
    function __construct($thisfile = __FILE__){
        $t = $this;
        $t->method = (count($_REQUEST) === 0) ? '' : $_SERVER['REQUEST_METHOD'];
        $t->scheme = getval($_SERVER['REQUEST_SCHEME'], '');
        $t->basehost = $t->scheme.'://'.getval($_SERVER['HTTP_HOST'], '');
        $t->path = preg_replace("/\?.+$/",'',getval($_SERVER['REQUEST_URI'], ''));
        $t->url = $t->basehost.$t->path;
        $t->pathlist = explode('/', $t->path);
        $t->php_path = str_replace($_SERVER['DOCUMENT_ROOT'],'',str_replace('\\','/',__FILE__));
        $t->php_dir = getdir($t->php_path);
        $t->ref_url = getval($_SERVER, 'HTTP_REFERER', "");
        $t->ref_domain = getdomain($t->ref_url);
        $t->ref_basehost = getbasehost($t->ref_url);
        $t->ref_dir = getdir($t->ref_url);
        $t->ht_head = false;
        $t->ht_body = false;
        $t->prefix_l_list = array(
            "K" => 1, "M" => 2, "G" => 3, "T" => 4, "P" => 5
        );
        $limit = mb_strtoupper(ini_get('memory_limit'));
        preg_match("/(\d*)([A-Z])/", $limit, $m);
        $t->limitsize = $m[1] * pow(1024, getval($t->prefix_l_list[$m[2]], 0));
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
                set_headtype(pathinfo($filename, PATHINFO_EXTENSION));
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
function set_headtype($opt = 1, $charset='utf-8') {
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
function getval($val_or_array, $key_or_nullval = null, $nullval = null) {
    if (is_array($val_or_array)){
        return (empty($val_or_array[$key_or_nullval])) ? $nullval : @$val_or_array[$key_or_nullval];
    }
    else{
        return (is_null($val_or_array) ? $key_or_nullval : $val_or_array);
    }
}
//  配列用の値チェック、自動削除も可能
function getref(array &$array, $key, $do_unset = false, $empty_val = null, $empty_set = false) {
    if (empty($array[$key])) {
        if ($empty_set && !$do_unset) $array[$key] = $empty_val;
        return $empty_val;
    } else {
        $retval = $array[$key];
        if ($do_unset) unset($array[$key]);
        return $retval;
    }
    return (empty($array[$key_or_nullval])) ? $nullval : @$val_or_array[$key_or_nullval];
}
// クエリを足して配列で返す
function get_include_query($query, $new = array(), bool $query_only = false){
    switch (gettype($query)) {
        case 'array':
            $query_array = $query;
            break;
        case 'string':
            $query_array = array();
            if (!$query_only) {
                $query = \parse_url($query, PHP_URL_QUERY);
            }
            \parse_str($query, $query_array);
            break;
        default:
            $query_array = array();
    }
    switch (gettype($new)) {
        case 'array':
            $new_array = $new;
            break;
        case 'string':
            $new_array = array();
            \parse_str($new, $new_array);
            break;
        default:
            $new_array = array();
    }
    return array_merge($query_array, $new_array);
}
// ファイル名の両端の名前のファイルが存在するかチェックする
function get_eachpath(string $path, string $dir = '', string $head = '',
    string $foot = '', string $ext = '', bool $return_blank = true){
    $path = ($path) ? $dir.$path : $path;
    $info = pathinfo($path);
    $eachpath = (isset($info['dirname']) ? ($info['dirname']).'/':'')
    .(isset($info['filename']) ? $head.$info['filename'].$foot : '')
        .($ext ? '.'.$ext : (isset($info['extension']) ? ('.'.$info['extension']) : ''));
    $check = get_path($eachpath);
    return $check ? $eachpath : ($return_blank ? '' : $path);
}
// 更新日のクエリだけ返す
function get_mdate($path, $char = 'v') {
    $path = get_docpath($path);
    if ($path !== '') {
        $mdate = filemtime($path);
    } else {
        $mdate = 0;
    }
    if ($mdate !== 0) {return $char.'='.$mdate;} else {return '';}
}
// クエリやアトリビュートの連結関数
function join_attr(string $separator, string $bracket, ...$query){
    $char_array = array();
    $local_join = function($local_query, $value_to_key = false) use (&$char_array, &$local_join, $bracket){
        foreach ($local_query as $k => $v) {
            if (is_array($v)) {
                $local_join($v);
                continue;
            } else {
                if ($value_to_key) {
                    $char_array[] = $v;
                } elseif($v === '') {
                    if ($k !== '') $char_array[] = $k;
                } else {
                    $char_array[] = $k.'='.$bracket.$v.$bracket;
                }
            }
        }
    };
    $local_join($query, true);
    return implode($separator, $char_array);
}

// 更新日を付与してhtmlの出力(改_20191107)
function set_linkdata(...$data_list){
    $index_array = function($var) { return \is_numeric($var) && $var >= 0; };
    $not_index_array = function($var) { return !\is_numeric($var); };
    $out_list = array();
    $default_opt = array('write_text'=>true, 'create'=>true, 'output'=>true, 'add_date'=>true);
    $local_set = function ($data_list, $arg_opt) use (&$local_set, &$out_list, $index_array, $not_index_array) {
        $data_type = gettype($data_list);
        if ($data_type === 'array') {
            $lopt = getref($data_list, -1, true, array());
            if (gettype($lopt)==='array') {
                $opt = array_merge($arg_opt, $lopt);
            } else {
                $opt = $arg_opt;
            }
            if (count(array_filter($data_list, $not_index_array, ARRAY_FILTER_USE_KEY)) === 0) {
                foreach(array_filter($data_list, $index_array, ARRAY_FILTER_USE_KEY) as $value) {
                    $local_set($value, $opt);
                }
                return;
            }
            $data = $data_list;
        } else {
            $data = array($data_list);
            $opt = $arg_opt;
        }
        $d_value = getref($data, 0, true, '');
        switch (gettype($d_value)){
            case 'array':
            return;
            break;
        }
        switch ($d_value) {
            case 'viewport':
            $data['name'] = 'viewport';
            $data['tag'] = 'meta';
            break;
            default:
            $data['src'] = $d_value;
            break;
        }
        $rel = '';
        $type = '';
        $elm = '';
        $src = getref($data, 'src', true, '');
        $attr = getref($data, 'attr', true, '');
        $tag = getref($data, 'tag', true, '');
        $inner = getref($data, 'inner', true, '');
        $ps = strpos($src, '?');
        $p = $ps ? substr($src, 0, $ps) : $src;
        $dp = get_docpath($p);
        $ext = mb_strtolower(pathinfo($p, PATHINFO_EXTENSION));
        // txtファイルはテキストデータとして直接返される
        if ($opt['write_text']&&($ext=='txt')) {
            if ($opt['create']) {
                if ($dp != '') {
                    $out_list[] = array('element'=>null, 'content'=>file_get_contents($dp),'path'=>$p, 'get_docpath'=>$dp);
                    if ($opt['output']) {echo($src);}
                }
            }
        } else {
            if ($opt['add_date']) {
                if ($dp !== '') {
                    if (\file_exists($dp)) {
                        $mdate = filemtime($dp);
                    } else
                        $mdate = 0;
                } else {
                    $mdate = 0;
                }
                if ($mdate !== 0) {$src = $src.($ps ? '&' : '?').'v='.$mdate;}
            }
            if ($opt['create']) {
                switch ($ext) {
                    case 'css':
                    $tag = ($tag === '') ? 'link' : $tag;
                    $rel ='stylesheet';
                    switch ($tag) {
                    }
                    break;
                    case 'js':
                    $tag = ($tag === '') ? 'script' : $tag;
                    switch ($tag) {
                        case 'script':
                        $data['src'] = $src;
                        $data['type'] = 'text/javascript';
                        break;
                    }
                    case 'png': case 'jpg': case 'jpeg': case 'gif': case 'tiff': case 'bmp':
                    switch ($tag) {
                        case 'link':
                        $rel = 'icon'; break;
                        case '':
                        $tag = 'img';
                        default:
                        break;
                    }
                    break;
                    case 'ico':
                    $tag = ($tag === '') ? 'link' : $tag;
                    $rel = 'icon';
                }
                if (empty($data['rel'])) {
                    if ($rel !== '') $data['rel'] = $rel;
                } else {
                    $rel = $data['rel'];
                }
                switch ($tag) {
                    case 'img':
                    if (empty($data['alt'])) {
                        $data['alt'] = count($out_list);
                    }
                    $data['src'] = $src;
                    break;
                    case 'link':
                    switch ($rel) {
                        case 'stylesheet';
                        $data['href'] = $src;
                        $data['type'] = 'text/css';
                        break;
                        case 'icon': case 'shortcut icon':
                        $data['rel'] = 'shortcut icon';
                        $data['href'] = $src;
                        if ($ext !== 'ico') {
                            if (empty($data['type'])) {
                                $type = 'image/'.$ext;
                                $data['type'] = $type;
                            } else {
                                $type = $data['type'];
                            }
                        }
                        break;
                    }
                    break;
                }
                    switch ($tag) {
                        case 'link': case 'input':
                        $close_elem = false; $close_slash = false; break;
                        case 'img':
                        $close_elem = false; $close_slash = true; break;
                        default:
                        $close_elem = true; $close_slash = false; break;
                    }
                    $attr = join_attr(' ', '"', $attr, $data);
                    $char_close_slash = ($close_slash) ? ' /' : '';
                    $char_close_elem = ($close_elem) ? "</$tag>" : '';
                    $elm = "<$tag$attr$char_close_slash>$inner$char_close_elem";
                if ($opt['output']) echo $elm."\n";
                $out_list[] = array('element'=>$elm, 'tag'=>$tag, 'attr'=>$attr, 'content'=>$inner, 'path'=>$p, 'get_docpath'=>$dp);
            }
        }
    };
    $local_set($data_list, $default_opt);
    return $out_list;
}
function jsrun($str, $onLoadDelete = false){
    $id = '__aft_delete__';
    $idelm = $onLoadDelete?' id="'.$id.'"':'';
    $runstr = '<script'.$idelm.'>'.$str
        .($onLoadDelete?';document.getElementById("'.$id.'").outerHTML = "";':'')
        .'</script>'."\n";
    echo($runstr);
}
function delete_until_bracket($str){
    return preg_replace("/^[^\[\{]*/", "", $str);
}
function delete_since_comment($str){
    return preg_replace("/[\n][^\'\"\n]*[\/\/].*/", "", $str);
}
function delete_since_last_semicolon($str){
    return preg_replace("/[\;\s]*$/", "", $str);
}
function json_read_one($target_json, $assoc = false, $flag_force_json = false){
    $jsonstr = '';
    if ($flag_force_json) {
        $jsonstr = $target_json;
    } else {
        $target_json = get_docpath($target_json);
        if (file_exists($target_json)) {
            $jsonstr = file_get_contents($target_json);
            $jsonstr = mb_convert_encoding($jsonstr, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
        }
    }
    $jsonstr = delete_since_last_semicolon(delete_since_comment(delete_until_bracket($jsonstr)));
    if ($jsonstr !== '') {
        return json_decode($jsonstr, $assoc);
    } else {
        return [];
    }
}
// 最初の文字が[か{、改行が存在するならばJSON文字列、そうでなければパスとみなす
// assocはtrueならばArray、falseならばstdClassとして取り出す
// force_jsonが有効な場合、強制的にJSONとして読み込む
// 最初のブラケットよりも前の文字列、ダブルクォーテーションよりも手前のコメントアウトは自動削除される
// 配列が二階層の場合はプロパティモードで読み込む、jsonとして読み込まれるのは 0, json, value, hrefの優先度順
function json_read($target, $assoc = false, $force_json = false)
{
    /*
    $targetが文字列 → 単一の出力
    $targetが配列 → マージする
    マージする際に、デコードされたものが
    配列の場合はそのまま一つにまとめる、オブジェクトの場合は別の親配列を生成する
    */
    $out = [];
    $merge_enable = is_array($target);
    // ループさせるために配列にいれる
    if (!$merge_enable) $target = [$target];
    foreach ($target as $value){
        $property_mode = is_array($value);
        // 自動的に取り出す
        if ($property_mode){
            if (isset($value[0])){
                $use_json = $value[0]; unset($value[0]);
            } elseif(isset($value["json"])){
                $use_json = $value["json"]; unset($value["json"]);
            } elseif(isset($value["value"])){
                $use_json = $value["value"]; unset($value["value"]);
            } elseif(isset($value["href"])){
                $use_json = $value["href"]; unset($value["href"]);
            } else {
                $use_json = "";
            }
        }
        else {
            $use_json = $value;
        }
        $flag_force_json = $force_json || strpos($use_json, "\n") !== false || preg_match('/^[\s]*[{\[]/', $use_json);
        $result = json_read_one($use_json, $assoc, $flag_force_json);
        if ($property_mode) {
            $href = $flag_force_json ? "" : $use_json;
            $result = array("href" => $href, "value" => $result);
            $result = array_merge($value, $result);
        }
        if ($merge_enable){
            if ($property_mode || !is_array($result)){
                $result = [$result];
            }
            $out = array_merge($out, $result);
        } else {
            $out = $result;
            break;
        }
    }
    return $out;
}
function json_stringfy($json, $pretty = false)
{
    $write = "";
    try {
        switch (gettype($json)) {
        case 'array':
        case 'object':
            $opt = JSON_UNESCAPED_UNICODE | (($pretty) ? JSON_PRETTY_PRINT : 0);
            $write = json_encode($json, $opt);
            break;
        default:
            $write = $json;
            break;
        }
    } catch (\Exception $e) {}
    return $write;
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
// キーワード検索、デフォルトでよく使いそうなメンバ変数を取得
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
            if (isset($v->text)) $str .= " $v->text";
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
// 上記のデフォルト設定時の検索
function filter_all($array ,$keyword = "", $page = 1, $max = 9) {
    return filter_page(filter_keyword(filter_exclusion($array), $keyword), $page, $max);
}
// 汎用日付フォーマットへ変換
function datetostr_default($date = null){
    $default = "Y-m-d\TH:i:s";
    if (!is_numeric($date)) 
        if (is_null($date))
            return date($default);
        else
            $date = strtotime($date);
    return date($default, $date);
}
// 日付だけの状態から日時絞り込み用の数を生成
function date_since($date = null){
    if (!is_numeric($date))
        $date = strtotime(getval($date, "2000-01-01"));
    return strtotime(date("Y-m-d", $date));
}
function date_until($date = null){
    if (!is_numeric($date))
        $date = strtotime(getval($date, datetostr_default()));
    return strtotime(date("Y-m-d", $date)."+1 day -1 second");
}

function convert_to_br(string $str){
    return str_replace("\n", '<br/>', $str);
}
?>