<?php
namespace cws;
# 検索関数
if (!isset($cws_load)) $cws_load = array();
$cws_load['search'] = false;
include_once('cws.php');

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
        if (empty($this->hook)) $this->hook = array();
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
?>