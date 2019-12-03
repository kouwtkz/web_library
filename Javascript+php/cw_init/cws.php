<?php
/*
// cws.phpと組み合わせて以下のように定義する
namespace cws;
// cws_requireはcwsまでに定義しておきたい要素を集めたもの
require_once($_SERVER['DOCUMENT_ROOT']."/common/cw_init/cws_require.php");
require_once($_SERVER['DOCUMENT_ROOT']."/common/cw_init/cws.php");
// require_once($_SERVER['DOCUMENT_ROOT']."/common/cw_init/cws_db.php");
// require_once($_SERVER['DOCUMENT_ROOT']."/common/cw_init/cws_search.php");
<script type="text/javascript" src="/common/cw_init/cws.js?<?php echo(get_mdate('/common/cw_init/cws.js')); ?>"></script>
*/
namespace cws;
// $cws_debug_mode = true;
if (!isset($cws_load)) $cws_load = array();
$cws_load_default = array(
    'require' => true,
    'autotag' => true,
    'sethead' => true,
    'search' => false,
);
foreach ($cws_load as $key => $load_flag) {
    $cws_load_default[$key] = \boolval($load_flag);
}
if ($cws_load_default['require']) include_once('cws_require.php');
if ($cws_load_default['autotag']) include_once('cws_autotag.php');
if ($cws_load_default['sethead']) include_once('cws_sethead.php');
if ($cws_load_default['search']) include_once('cws_search.php');

class server{
    function __construct($thisfile = __FILE__){
        $t = $this;
        $t->method = (count($_REQUEST) === 0) ? '' : $_SERVER['REQUEST_METHOD'];
        $t->scheme = get_val($_SERVER['REQUEST_SCHEME'], '');
        $t->basehost = $t->scheme.'://'.get_val($_SERVER['HTTP_HOST'], '');
        $t->path = preg_replace("/\?.+$/",'',get_val($_SERVER['REQUEST_URI'], ''));
        $t->url = $t->basehost.$t->path;
        $t->url_dir = $t->basehost.get_dir($t->path);
        $t->pathlist = explode('/', $t->path);
        $t->php_path = str_replace($_SERVER['DOCUMENT_ROOT'],'',str_replace('\\','/',__FILE__));
        $t->php_dir = get_dir($t->php_path);
        $t->ref_url = get_val($_SERVER, 'HTTP_REFERER', "");
        $t->ref_domain = get_domain($t->ref_url);
        $t->ref_basehost = get_basehost($t->ref_url);
        $t->ref_dir = get_dir($t->ref_url);
        $t->ht_head = false;
        $t->ht_body = false;
        $t->prefix_l_list = array(
            "K" => 1, "M" => 2, "G" => 3, "T" => 4, "P" => 5
        );
        $t->url_pattern = '/((?:https?|ftp):\/\/\S+)/';
        $t->image_re = '/\.(gif|png|jpg|jpeg|bmp)$/i';
        $t->video_re = '/\.(mp4|mpg|mpeg|swf|ogv|mov|avi|webm)$/i';
        $t->audio_re = '/\.(wave|wav|ogg|mp3|m4a|aac)$/i';
        $t->url_rg = ["!","#","$","&","'","(",")","*",",","/",":",";","=","?","@","[","]"," "];
        $limit = mb_strtoupper(ini_get('memory_limit'));
        preg_match("/(\d*)([A-Z])/", $limit, $m);
        $t->limitsize = $m[1] * pow(1024, get_val($t->prefix_l_list[$m[2]], 0));
    }
}
$cws = new server();
// ポートも取得するので必要
function get_domain(string $url){
    preg_match('/\/\/.*?\//', $url, $base);
    return (count($base)===0)?"":mb_substr($base[0],2,-1);
}
function get_basehost(string $url){
    preg_match('/^.*?\/\/.*?\//', $url, $base);
    return (count($base)===0)?"":mb_substr($base[0],0,-1);
}
function get_pathname(string $url){
    $basehost = get_basehost($url);
    $url = mb_substr($url, mb_strlen($basehost));
    return $url;
}
function get_dir(string $url){
    preg_match('/^.*?\./', $url.'.', $base);
    preg_match('/^.*\//', $base[0], $base);
    return (count($base)===0)?"":preg_replace('/\/+$/','/',$base[0]);
}
// 存在しない場合は標準の場合はnullを返す
function get_val($val_or_array, $key_or_nullval = null, $nullval = null) {
    if (is_array($val_or_array)){
        return (isset($val_or_array[$key_or_nullval])) ? @$val_or_array[$key_or_nullval] : $nullval;
    }
    else{
        return (is_null($val_or_array) ? $key_or_nullval : $val_or_array);
    }
}
//  配列用の値チェック、自動削除も可能
function get_ref(array &$array, $key, $do_unset = false, $null_val = null, $null_set = false) {
    if (!isset($array[$key])) {
        if ($null_set && !$do_unset) $array[$key] = $null_val;
        return $null_val;
    } else {
        $retval = $array[$key];
        if ($do_unset) unset($array[$key]);
        return $retval;
    }
    return (isset($array[$key_or_nullval])) ? @$val_or_array[$key_or_nullval] : $nullval;
}
function is_true($val, $return_null=false){
    $boolval = ( is_string($val) ? filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : (bool) $val );
    return ( $boolval===null && !$return_null ? false : $boolval );
}
// 複数の配列からキーが存在するか確認する
function get_mult($key, ...$array) { return _get_mult($key, $array); }
function _get_mult($key, $arrays) {
    if (\is_array($arrays)) {
        foreach($arrays as $array) {
            if (isset($array[$key])) return $array[$key];
        }
    }
    return null;
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
                } elseif($k === '') {
                    $char_array[] = $v;
                } else {
                    $char_array[] = $k.'='.$bracket.$v.$bracket;
                }
            }
        }
    };
    $local_join($query, true);
    return implode($separator, $char_array);
}
function join_query(...$query){
    return join_attr('', '&', $query);
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
// 配列などをJSON文字列化する
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
// 配列などをJSON文字列化して出力する
function set_json_stringfy ($json, $pretty = false) { echo json_stringfy($json, $pretty); }
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
        $date = strtotime(get_val($date, "2000-01-01"));
    return strtotime(date("Y-m-d", $date));
}
function date_until($date = null){
    if (!is_numeric($date))
        $date = strtotime(get_val($date, datetostr_default()));
    return strtotime(date("Y-m-d", $date)."+1 day -1 second");
}
function auto_mkdir($dir) {
    $dir_s = '';
    foreach (explode('/', $dir) as $s) {
        $dir_s .= $s.'/';
        if (!file_exists($dir_s)) mkdir($dir_s);
    }
}
?>