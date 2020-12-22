<?php
namespace cws;
# 自動タグ付け命令、頻繁に変えるため分離した
if (!isset($cws_load)) $cws_load = array();
$cws_load['autotag'] = false;
include_once('cws.php');

// 更新日を付与してhtmlの出力(改_20191107)
function set_autotag(...$data_list){
    $default_opt = array('text_style'=>false, 'text_write'=>true, 'create'=>true, 'output'=>true, 'add_date'=>false);
    $local_set = null;
    $index_array = function($var) { return \is_numeric($var) && $var >= 0; };
    $not_index_array = function($var) { return !\is_numeric($var); };
    $define = array();
    $local_set_attr = function(&$list, $arg_opt, $all_attr = false) 
    use (&$local_set, &$local_set_attr, &$define) {
        $out_list = array();
        foreach ($list as $key => $var) {
            $key_switch = $key;
            if ($all_attr) {
                $key_switch = 'attr';
            } else {
                if (is_numeric($key)) continue;
            }
            switch($key_switch) {
                case 'attr': case 'attribute': case 'themes': case 'data-themes':
                    $attr = get_ref($list, $key, true, '');
                    if (is_array($attr)) {
                        $out_list += $local_set_attr($attr, $arg_opt, true, $define_mode);
                    } else {
                        if ($key === 'attr') $key = '';
                        if (is_numeric($key)) {
                            $key = $var; $attr = '';
                        }
                        $out_list[$key] = $attr;
                    }
                break;
                default:
                    if (is_array($var)) {
                        unset($list[$key]);
                        $var['tag'] = $key;
                        $local_set($var, $arg_opt);
                    } else {
                        if (is_numeric($key)) {
                            $out_list[$var] = '';
                        } else {
                            $out_list[$key] = $var;
                        }
                    }
                break;
            }
        }
        return $out_list;
    };
    $out_list = array();
    $local_set = function($data_list, $arg_opt)
    use (&$local_set, &$out_list, &$index_array, &$not_index_array, &$local_set_attr, &$define) {
        $data_type = gettype($data_list);
        if ($data_type === 'array') {
            $lopt = get_ref($data_list, -1, true, array());
            if (gettype($lopt)==='array') {
                $opt = array_merge($arg_opt, $lopt);
            } else {
                $opt = $arg_opt;
            }
            $def_list = get_ref($data_list, 'define', true, array());
            if (gettype($def_list)==='array') {
                $defunction = function(&$data) use (&$defunction, &$define) {
                    foreach($data as $key => $var) {
                        if (is_array($var)) {
                            $defunction($var);
                        } else {
                            $define[$key] = $var;
                        }
                    }
                };
                $defunction($def_list);
            }
            if (count(array_filter($data_list, $not_index_array, ARRAY_FILTER_USE_KEY)) === 0) {
                foreach(array_filter($data_list, $index_array, ARRAY_FILTER_USE_KEY) as $value) {
                    $local_set($value, $opt);
                }
                return;
            }
            $data = $data_list;
        } else {
            if (empty($data_list)) return;
            $data = array($data_list);
            $opt = $arg_opt;
        }

        $tag = '';
        $inner = '';
        switch (count($data)) {
            case 1:
            $title = get_ref($data, 'title', true, false);
            if ($title) {
                $tag = 'title';
                $inner = $title;
            }
        }

        $d_value = get_ref($data, 0, true, '');
        switch ($d_value) {
            case 'viewport-w':
                $data['content'] = 'width=device-width,initial-scale=1';
            case 'viewport':
                $tag = 'meta';
                $data['name'] = 'viewport';
            break;
            case 'utf-8': case 'charset':
                $tag = 'meta';
                $data['charset'] = 'utf-8';
            break;
            case 'title':
                $tag = 'title';
                $data['inner'] = get_val($define, 'title', '');
            break;
            case 'description':
                $tag = 'meta'; $data['name'] = 'description';
                $data['content'] = get_val($define, 'description', '');
            break;
            case 'manifest':
                if (get_val($define, 'sw_flag', true)) {
                    $tag = 'link'; $data['rel'] = 'manifest';
                    $data['href'] = get_val($define, 'manifest', '/manifest.json');
                }
            break;
            case 'theme': case 'theme-color':
                $tag = 'meta'; $data['name'] = 'theme-color';
                $data['content'] = get_val($define, 'theme-color', '#FFF');
            break;
            case 'sw':
                if (get_val($define, 'sw_flag', true)) {
                    $tag = 'script'; $data['type'] = 'text';
                    $data['type'] = 'text/javascript';
                    $url = get_val($define, 'sw', null);
                    $scope = get_val($define, 'sw-scope', null);
                    $scope = is_null($scope) ? '' : ",{scope:'$scope'}";
                    if (is_null($url)) {
                        $url = get_val($define, 'sw-url', '/sw.js');
                        $data['inner'] = "if('serviceWorker' in navigator){"
                            ."addEventListener('load', function(){navigator.serviceWorker.register('$url'$scope).then(function(){console.log('Service Worker Registered');});});}";
                    } else {
                        $data['src'] = $url;
                    }
                }
            break;
            case 'icon':
                $tag = 'link'; $rel = 'icon'; $src = get_val($define, 'icon', '/favicon.ico');
            break;
            case 'app':
            case 'mobile':
                $app_title = get_val($define, array('app-title', 'title'), null);
                $app_image = get_val($define, array('app-icon', 'image', 'icon'), null);
                $local_set(array(
                    array('tag' => 'meta', 'name' => 'mobile-web-app-capable',
                        'content' => get_val($define, 'app', 'yes')),
                    isset($app_title) ? array('tag' => 'meta',
                        'name' => 'apple-mobile-web-app-title', 'content' => $app_title) : null,
                    isset($app_image) ? array('tag' => 'meta',
                        'name' => 'apple-touch-icon', 'content' => $app_image) : null,
                    ), $opt);
            break;
            case 'app-config':
                $tag = 'meta'; $data['name'] = 'msapplication-config';
                $data['content'] = get_val($define, array('app-config', 'image', 'icon'), '');
            break;
            case 'og':
                $title = get_val($define, array('og:title', 'title'), '');
                $description = get_val($define, array('og:description', 'description'), '');
                $url = get_val($define, array('og:url', 'url'), $_SERVER['REQUEST_URI']);
                $image = get_val($define, array('og:image', 'image'), null);
                $local_set(array(
                    array('tag' => 'meta', 'property' => 'og:title', 'content' => $title),
                    array('tag' => 'meta', 'property' => 'og:description', 'content' => $description),
                    isset($url) ? array('tag' => 'meta', 'property' => 'og:url', 'content' => get_fullurl($url)) : null,
                    isset($image) ? array('tag' => 'meta', 'property' => 'og:image', 'content' => get_fullurl($image)) : null,
                ), $opt);
                return;
            break;
            case 'twitter':
                $card = get_val($define, array('twitter:card', 'card'), 'summary');
                $site = get_val($define, array('twitter:site', 'site'), '');
                $creator = get_val($define, array('twitter:creator', 'creator'), '');
                $local_set(array(
                    array('tag' => 'meta', 'property' => 'twitter:card', 'content' => $card),
                    array('tag' => 'meta', 'property' => 'twitter:site', 'content' => $site),
                    array('tag' => 'meta', 'property' => 'twitter:creator', 'content' => $creator),
                ), $opt);
                return;
            break;
            case '':
            break;
            default:
                $data['src'] = $d_value;
            break;
        }
        $tag = get_ref($data, 'tag', true, $tag);
        $rel = '';
        $type = '';
        $elm = '';
        $src = get_ref($data, 'src', true, '');
        $inner .= get_ref($data, 'inner', true, '');

        $data += $local_set_attr($data, $opt);

        $ps = strpos($src, '?');
        $p = $ps ? substr($src, 0, $ps) : $src;
        $dp = get_docpath($p);
        $ext = get_ref($data, 'ext', true, '');
        if (empty($ext)) $ext = pathinfo($p, PATHINFO_EXTENSION);
        $ext = mb_strtolower($ext);
        // themeデータに切り替わる
        if (isset($data['data-theme'])) {
            $theme = $data['data-theme'];
        } elseif(isset($data['theme'])) {
            $theme = $data['theme'];
        }
        if (!empty($theme)) {
            if (!empty($src)) $data['src_data'] = $src;
            if (!empty($data[$theme])) { $src = $data[$theme]; }
            else { $theme = "data-$theme"; if (!empty($data[$theme])) $src = $data[$theme]; }
        }
        $direct = false;
        // txtファイルはテキストデータとして直接返される
        if ($opt['text_write']) {
            $fl_txt = false; $fl_br = false;
            if ($ext==='txt') {
                $fl_txt = true;
                $direct = true;
                $element = null;
            } elseif ($opt['text_style']) {
                $fl_br = true;
                if ($ext==='js') {
                    $fl_txt = true; $tag = 'script'; $src = '';
                } elseif ($ext==='css') {
                    $fl_txt = true; $tag = 'style'; $src = '';
                }
            }
            if ($fl_txt && $opt['create'] && $dp !== '') {
                $content = file_get_contents($dp);
                if ($fl_br) $content = "\n$content\n";
                $inner .= $content;
            }
        }
        if ($direct) {
            $out_list[] = array('element'=>$element, 'content'=>$inner,'path'=>$p, 'get_docpath'=>$dp);
            if ($opt['output']) { echo($inner); }
        } else {
            if (!empty($src) && $opt['add_date']) {
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
                            if (!empty($src)) $data['src'] = $src;
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
                    // $src = get_fullurl($src);
                    break;
                    case '':
                    switch ($tag) {
                        case 'a':;
                        break;
                        case '':
                        $inner .= $src;
                        if (count($data) > 0 || $inner !== '') {
                            $tag = 'span';
                        }
                        else {
                            return;
                        }
                        break;
                        default:
                        $inner .= $src;
                        break;
                    }
                    break;
                    default:
                    $tag = ($tag === '') ? 'a' : $tag;
                }
                if (empty($data['rel'])) {
                    if ($rel !== '') $data['rel'] = $rel;
                } else {
                    $rel = $data['rel'];
                }
                switch ($tag) {
                    case 'a':
                    if (empty($data['href'])) $data['href'] = $src;
                    break;
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
                    case 'link': case 'input': case 'meta':
                    $close_elem = false; $close_slash = false; break;
                    case 'img':
                    $close_elem = false; $close_slash = true; break;
                    default:
                    $close_elem = true; $close_slash = false; break;
                }
                $attr = join_attr(' ', '"', $data);
                if ($attr !== '') $attr = " $attr";
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
function convert_to_br(string $str, bool $leaven = false){
    $br = '<br/>' . ($leaven ? "\n" : '');
    return preg_replace('/(\r\n|\r|\n)/', $br, $str);
}
function escape_to_br(string $str){
    return preg_replace_callback('/(\\\\?)(\<br\/\>|$)/', function($m) {
        if ($m[1] === '') {
            return $m[2];
        } else {
            return '';
        }
    }, $str);
}
// parse_urlのhost側を組み立てる
function join_parsed_host($parsed_url) {
    return (isset($parsed_url['scheme']) ? $parsed_url['scheme'].'://' : '')
    .(isset($parsed_url['user']) ? $parsed_url['user'].(
        (isset($parsed_url['pass']) ? ':'.$parsed_url['pass'] : '')
    ).'@' : '')
    .(isset($parsed_url['host']) ? $parsed_url['host'] : '')
    .(isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '');
}
// parse_urlのpath側を組み立てる
function join_parsed_path($parsed_url) {
    return (isset($parsed_url['path']) ? $parsed_url['path'] : '')
    .(isset($parsed_url['query']) ? '?'.$parsed_url['query'] : '')
    .(isset($parsed_url['fragment']) ? '#'.$parsed_url['fragment'] : '');
}
function get_fullurl(string $path = '', &$internal = false){
    global $cws;
    $parse = \parse_url($path);
    if (empty($parse['host'])) {
        if (isset($parse['path'])) {
            $path = $parse['path'];
            if (strpos($path, '/') === 0) {
                $dir = $cws->basehost;
            } else {
                $dir = $cws->url_dir;
            }
        } else {
            $dir = $cws->url;
        }
        $path = join_parsed_path($parse);
        if (substr($dir, -1) === '/') $dir = substr($dir, 0, strlen($dir) - 1);
        $path = $dir.$path;
        $internal = true;
    }
    return $path;
}
function asc_to_char(string $str, bool $decode = false){
    global $cws;
    $str = str_replace('+', ' ', $str);
    foreach ($cws->url_rg as $rg) {
        $pattern = '%'.dechex(ord($rg));
        $str = str_ireplace($pattern, $rg, $str);
    }
    if ($decode) $str = urldecode($str);
    return $str;
}
function char_to_asc(string $str, bool $encode = false){
    global $cws;
    $str = str_replace(' ', '+', $str);
    if ($encode) $str = urlencode($str);
    foreach ($cws->url_rg as $rg) {
        $pattern = $rg;
        $rpl = '%'.dechex(ord($rg));
        $str = str_replace($pattern, $rpl, $str);
    }
    return $str;
}
// URLエンコード→予約文字のみデコード
function char_to_asc2(string $str){
    $str = asc_to_char(urlencode($str));
    return $str;
}
function convert_to_href_decode(string $str){
    global $cws;
    $callback_quat = function($m){
        $str = $m[1].char_to_asc2($m[2]).$m[3];
        return $str;
    };
    $str = preg_replace_callback('/(href.*\=.*\')(.*)(\')/', $callback_quat, $str);
    $str = preg_replace_callback('/(href.*\=.*\")(.*)(\")/', $callback_quat, $str);
    return $str;
}
function brackets_loop($text, ...$loop_func) {
    if (\preg_match_all('/([^\[\]]*)([\[\]])/', $text, $match_slice)) {
        $ret_text = '';
        $edit_text = '';
        $end_text = '';
        $count = 0;
        if (\preg_match('/([^\[\]]*)$/', $text, $match_slice2)) { $end_text = $match_slice2[1]; }
        for ($i = 0; $i < count($match_slice[0]); $i++) {
            $match = array($match_slice[0][$i], $match_slice[1][$i], $match_slice[2][$i]);
            $esc_bks = false;
            if (preg_match('/\\\\+$/', $match[1], $m_bks)){
                if ((strlen($m_bks[0]) % 2) === 1) $esc_bks = true;
                if ($esc_bks) {
                    $match[1] = preg_replace('/\\\\$/', '', $match[1]);
                } else {
                    $match[1] = preg_replace('/\\\\{2}$/', '\\', $match[1]);
                }
                $match[1] .= $match[2];
                $match[2] = '';
                $match[0] = $match[1].$match[2];
            }
            if ($esc_bks) {
                if ($count > 0) {
                    $edit_text .= $match[0];
                } else {
                    $ret_text .= $match[0];
                }
            } else {
                if ($match[2] === '[') {
                    if ($count++ === 0) {
                        $ret_text .= $match[1];
                        $edit_text = '';
                    } else {
                        $edit_text .= $match[0];
                    }
                } else {
                    if (--$count <= 0) {
                        $count = 0;
                        $edit_text .= $match[1];
                        $out_str = $edit_text;
                        foreach ($loop_func as $func) {
                            $out_str = $func($out_str);
                        }
                        if ($out_str !== '') {
                            if ($out_str !== null) $ret_text .= $out_str;
                        } else {
                            $ret_text .= "[$edit_text]";
                        }
                    } else {
                        $edit_text .= $match[0];
                    }
                }
            }
        }
        $text = $ret_text.$end_text;
    }
    return $text;
}
function tagesc_re($value) {
    if (empty($value)) return '//';
    $_q_f = substr($value, 0, 1);
    $_q_s = preg_replace('/[\?\|\[\]\/\\\\\(\)]/', '\\$0', \addslashes($value));
    if ($_q_f === '#') {
        $ret = '/(\s)('.$_q_s.')([\s#\<])/i';
    } else {
        $ret = '/(.*?)('.$_q_s.')/i';
    }
    return $ret;
}
function tagesc_callback($search_re, $text, ...$loop_func) {
    return __tagesc_callback($search_re, $text, $loop_func);
}
function do_tag_escout($intag, $outtag = 'span') {
    return preg_replace('/^(\<)?([^\s\>]+)(\s?)(.*)(\>)$/', "$1$outtag$5$4$3", $intag); 
}
function __tagesc_callback($search_re, $text, $loop_func = null, $permission = array(), &$linkable = false) {
    $tag_permission = array('script'=>false, 'style'=>false, 'other'=>false);
    if (\is_array($permission))
        foreach($permission as $key => $value) { $tag_permission[$key] = $value; }
    $recall = is_array($loop_func);
    $tag_char = '';
    if (!$recall && is_null($loop_func)) {
        $loop_func = function($m, $text){ return $text; };
    } elseif ($recall) {
        // var_dump($text);
    }
    if (empty($search_re)) $search_re = tagesc_re($search_re);
    $callback_tagesc = function($m) use (&$tag_char, &$loop_func, $search_re, $recall, $tag_permission, &$linkable) {
        preg_match_all('/([^\<\>]*)(\\\\)?([\<\>]?)/',$m[0], $m1);
        $text = '';
        $cur = 0;
        $str = '';
        $stock = '';
        $c_i = count($m1[0]);
        for ($i = 0; $i < $c_i; $i++) {
            $str = $m1[1][$i];
            $esc = $m1[2][$i];
            $check = $m1[3][$i];
            if ($tag_char === '') {
                if ($esc === '') {
                    $cur = $i;
                    if ($str !== '' && $stock !== '') {
                        $str = $stock.$str;
                        $stock = '';
                    }
                    if ($check !== '') {
                        if ($check !== '>' || $tag_char !== '') {
                            $tag_char = $check;
                        } else {
                            $stock .= $check;
                            $check = '';
                            if ($str === '') continue;
                        }
                    }
                    if (preg_match('/\S/',$str)) {
                        if ($recall) {
                            foreach ($loop_func as $func) {
                                $str = __tagesc_callback($search_re, $str, $func, $tag_permission, $linkable);
                            }
                            $text .= $str.$esc.$check;
                        } else {
                            $text .= $loop_func($m, $str, $linkable).$esc.$check;
                        }
                    } else {
                        $text .= $str.$esc.$check;
                    }
                }
                continue;
            } elseif ($check === '>') {
                $tag_check = mb_strtolower(preg_replace('/^([^\s]*).*$/', '$1', $str));
                switch ($tag_check) {
                    case 'a':
                        $linkable = true;
                    break;
                    case '/a':
                        $linkable = false;
                    break;
                    case 'script': case '/script':
                        if (!$tag_permission['script']) $m1[0][$i] = do_tag_escout($m1[0][$i]);
                    case 'style': case '/style': case 'link':
                        if (!$tag_permission['style']) $m1[0][$i] = do_tag_escout($m1[0][$i]);
                    case 'title': case '/title': case 'meta':
                        if (!$tag_permission['other']) $m1[0][$i] = do_tag_escout($m1[0][$i]);
                    break;
                    default:
                    break;
                }
                if ($tag_char === '<') {
                    $tag_char = '';
                }
            } elseif($tag_char === $check) {
                if ($m1[1][$i] === '') $tag_char = '';
            }
            $text .= $m1[0][$i];
        }
        if ($stock !== '') {
            $text .= $stock;
        }
        return $text;
    };
    $text = preg_replace_callback($search_re, $callback_tagesc, $text);
    return $text;
}
// ハッシュタグの自動リンクとはてな記法の自動リンクとハイライトの自動付与
// ここで無名関数を後で入れることでループが完成する
// デフォルトでは結果として生成されたHTMLを出力する
// set_autolink($arr, 'text', $_REQUEST['q']);
function set_autolink($arr = array(), $arg_g_opt = array(), $loop_func = null){
    global $cws;
    $g_opt = array('arr_text' => 'text', 'arr_after_text' => 'after_text', 'arr_before_text' => 'before_text',
        'arr_htmlsp' => array('htmlsp', 'htmlspecialchars'), 'htmlsp' => true, 'autoplay' => false,
        'request_key' => 'q', 'response_key' => 'id', 'br_leaven' => false,
        'reply_re' => '/(^|\s)(@)(\w+)([\:]?)/', 'reply_link' => '$0', 'reply_link_re' => null,
        'response_re' => '/(^|\s)(>>)(\w+)([\:]?)/u', 'response_link' => '$0', 'response_link_re' => null,
        'hashtag_re' => '/(^|\s)#([^\s\<#]*)/'
    );
    if (!\is_null($arg_g_opt)){
        if (\is_array($arg_g_opt)) {
            $g_opt = array_merge($g_opt, $arg_g_opt);
        } else {
            $g_opt['arr_text'] = $arg_g_opt;
        }
    }
    $g_opt['request_key'] = get_val($g_opt, 'request_key', 'q');
    $request_key = $g_opt['request_key'];
    
    $request_q = get_val($g_opt, 'q', get_val($_REQUEST, $request_key, ''));
    $highlight_q = "$request_q " . get_val($g_opt, 'highlight_q', '');
    $highlight_q = preg_replace('/[.+*?(){}\^$|\[\]\\\\]/', '', preg_replace('/^\s+|\s+$|(\s)\s*/', '$1', $highlight_q));
    $out_html_list = array();
    if ($loop_func === null) $loop_func = function($text, $var) use (&$out_html_list) { $out_html_list[] = $text; };
    $_q_str = $request_q;
    $_q = !empty($request_q);
    $_hq = !empty($highlight_q);
    $_hq_str = $_hq ? $highlight_q : '';
    $_q_str_l = (preg_match('/^\s*$/', $_hq_str) ? array() : explode(' ', $_hq_str));
    $_q_str_l = array_unique($_q_str_l);
    $_q_str_l_f = array_flip($_q_str_l);
    $_q_str_e = urlencode($_q_str);
    $_q_join = "?$request_key=" . ($_q ? $_q_str_e.'+' : '');
    $_q_str_l_s = array();
    $_q_str_l_u = array();
    $data_origin = '';
    $title = '';
    $type = '__default__';
    $target = '__default__';
    $class = '';
    $style = '';
    $align_mode = '';
    $heading_mode = '';
    $opt = array();
    $get_mult_opt = function($key, $default) use (&$opt, &$g_opt) {
        return get_val(get_mult($key, $g_opt, $opt), $default);
    };
    if ($_hq) foreach($_q_str_l as $value) {
        $_q_str_l_s[] = tagesc_re($value);
        $_q_str_l_u[] = urlencode($value);
    }

    $callback_search = function($m, $text) use (&$_hq, &$_q_str_l_s, &$request_key, &$g_opt) {
        $callback_1 = function($m) use (&$request_key) {
            $text = $m[0];
            $m2_1 = substr($m[2], 0, 1);
            $inner = substr($m[2], 1);
            if ($inner === '') $m2_1 = '';
            switch ($m2_1) {
                case '#':
                    return $m[1].'<a class="tag" href="?'.$request_key.'=%23'.$inner.'">['.$inner.']</a>'.$m[3];
                case '@':
                    $inner = substr($m[2], 1);
                    return $m[1].'<a class="to" href="?'.$request_key.'=@'.$inner.'">[@'.$inner.']</a>'.$m[3];
                break;
                default:
                    switch ($m[2]) {
                        case 'AND': case 'OR': case 'NOT': case '-':
                        break;
                        default:
                            $text = $m[1].'<span class="highlight">'.$m[2].'</span>';
                        break;
                    }
                break;
            }
            return $text;
        };
        if ($_hq) foreach($_q_str_l_s as $key => $value) {
            $_q_str_s = $_q_str_l_s[$key];

            if (preg_match_all('/([^\<\>]*)(\\\\)?([\<\>]?)/',$text, $m3)) {
                $text_m3 = '';
                for ($m3_i = 0; $m3_i < count($m3[0]); ++$m3_i) {
                    if ($m3[3][$m3_i] !== '>') {
                        $text_m3 .= preg_replace_callback($_q_str_s, $callback_1, $m3[1][$m3_i]).$m3[3][$m3_i];
                    } else {
                        $text_m3 .= $m3[0][$m3_i];
                    }
                }
                $text = $text_m3;
            };
        }
        return $text;
    };
    $class_reset = function()
    use (&$data_origin, &$target, &$title, &$type, &$class, &$style, &$internal, &$opt, $g_opt, &$get_mult_opt){
        $data_origin = ''; $title = ''; $type = '__default__'; $target = '__default__';
        $class = ''; $style = ''; $opt = array(); $internal = false;
    };
    $set_link = function($mturl, string $sub = '')
    use (&$class_reset, &$data_origin, &$target, &$title, &$type, &$class, &$style, &$internal, &$opt, $g_opt, &$get_mult_opt){
        global $cws;
        if (is_array($mturl)) { $str = $mturl[1]; } else { $str = $mturl; }
        $sub_empty = empty($sub);
        if ($sub_empty) $sub = &$str; 
        $host = parse_url($str, PHP_URL_HOST);
        $internal = (is_null($host)) || ($host === $_SERVER['HTTP_HOST']);
        if ($type === '__default__') {
            $type = '';
            if ($internal) {
                if (preg_match($cws->image_re, $str)) {
                    $type = 'image';
                } elseif (preg_match($cws->video_re, $str)) {
                    $type = 'video';
                } elseif (preg_match($cws->audio_re, $str)) {
                    $type = 'audio';
                }
            }
        }
        if ($target === '__default__') {
            if ($internal) {
                if ($sub_empty) {
                    switch($type) {
                    case 'image': case 'video': case 'audio':
                        $target = '_blank';
                    break;
                    default:
                        $target = '';
                    break;
                    }
                } else {
                    $target = '';
                }
            } else { $target = '_blank'; }
        }
        if ($target === '_blank') { $relno = ' rel="noopener noreferrer"'; } else { $relno = ''; }
        if ($target !== '') $target = ' target="'.$target.'"';
    
        if ($title === '') {
            $url_len = intval($get_mult_opt('url_len', 256));
            $title = substr($str, 0, $url_len);
            if ($str !== $title) {
                $title = mb_substr($title, 0, mb_strlen($title) - 1).'…';
            }
        }
        $title = str_replace('%20', ' ', $title);
        $ext = substr($str, strrpos($str, '.') + 1);
        $description = get_val($opt,'description', false);

        $onclick_script = '';
        if (isset($opt['confirm'])) {
            $onclick_script = 'return confirm("'.$opt['confirm'].'");';
        }
        if ($onclick_script !== '') $onclick_script = " onclick='$onclick_script'";
        $return_text = '';
        $object = false;
        $loop = false;
        $media_type = '';
        switch($type) {
            case 'image':
                $style .= ' display:inline-block; text-align:center;';
            break;
        }
        $add_style = ($style === '') ? '' : ' style="'.$style.'"';
        switch($type) {
            case 'image':
                $img_tag = '<img alt="'.$title.'" src="'.$str.'" data-origin="'.$data_origin.'">';
                $media_type = 'image';
                if (isset($g_opt['link_image'])){
                    $return_text = $g_opt['link_image']($img, array('src'=>$src, 'title'=>$title, 'target'=>$target, 'relno'=>$relno));
                } else {
                    if ($description) {
                        $return_text = '<img alt="'.$str.'" src="'.$str.'" data-origin="'.$data_origin.'">';
                        $return_text = $return_text.'<p>'.$title.'</p>';
                    } else {
                        $return_text = '<img alt="'.$title.'" src="'.$str.'" data-origin="'.$data_origin.'">';
                    }
                    $return_text = '<a href="'.$sub.'"'.$target.$relno.' class="'.$class.'"'.$add_style.$onclick_script.'>'.$return_text.'</a>';
                }
            break;
            case 'movie': case 'video':
                $media_type = 'video';
                $object = $get_mult_opt('object', false);
                if (!$object) {
                    $controls = $get_mult_opt('controls', true);
                    $return_text = '<video '.(($controls)?'controls':'').' class="'.$class.'"'.$add_style.' data-origin="'.$data_origin.'">'
                    .'<source src="'.$str.'">'
                    .'<a href="'.$sub.'"'.$target.$relno.'>'.$title.'</a>'
                    .'</video>';
                    if ($description) { $return_text = "<p>$return_text</p><p style=\"text-align: center;\">$title</p>"; }
                } else {
                    $loop = false;
                }
            break;
            case 'audio':
                $media_type = 'audio';
                $object = $get_mult_opt('object', false);
                if (!$object) {
                    $controls = $get_mult_opt('controls', true);
                    $autoplay = $get_mult_opt('autoplay', false);
                    $loop = $get_mult_opt('loop', true);
                    $muted = $get_mult_opt('muted', false);
                    $preload = $get_mult_opt('preload', false);
                    $return_text = '<audio '.(($controls)?'controls ':'')
                    .''.(($autoplay)?'autoplay ':'').''.(($loop)?'loop ':'')
                    .''.(($muted)?'muted ':'').''.(($preload)?'preload ':'')
                    .'class="'.$class.'"'.$add_style.' data-origin="'.$data_origin.'">'
                    .'<source src="'.$str.'">'
                    .'<a href="'.$sub.'"'.$target.$relno.'>'.$title.'</a>'
                    .'</audio>';
                    if ($description) { $return_text = "<p>$return_text</p><p>$title</p>"; }
                } else {
                    $loop = true;
                }
            break;
            case 'inline':
                $charset = $get_mult_opt('charset', 'utf-8');
                $inline_len = $get_mult_opt('inline_len', 4096);
                $a_charset = ($charset === '') ? '' : (' charset="'.$charset.'"');
                $data = file_get_contents(get_docpath($str));
                $data = mb_convert_encoding($data, $charset, "sjis, auto");
                $data = \htmlspecialchars($data, ENT_QUOTES);
                $sub_data = substr($data, 0, $inline_len);
                if ($data !== $sub_data) { $data = mb_substr($sub_data, 0, mb_strlen($sub_data) - 1).'…'; }
                $return_text = 
                '<a href="'.$sub.'"'.$target.$relno.' class="'.$class.'"'.$a_charset.' data-origin="'.$data_origin.'">'.$title."</a></br>"
                ."<pre class='inline text' data-origin='$data_origin'>$data</pre>";
            break;
            default:
                $charset = $get_mult_opt('charset', '');
                $a_charset = ($charset === '') ? '' : (' charset="'.$charset.'"');
                $return_text = '<a href="'.$sub.'"'.$target.$relno.' class="'.$class.'" style="'.$style.'"'.$a_charset.$onclick_script.' data-origin="'.$data_origin.'">'.$title.'</a>';
            break;
        }
        if ($object) {
            $controls = $get_mult_opt('controls', true);
            $autoplay = $get_mult_opt('autoplay', false);
            $loop = $get_mult_opt('loop', $loop);
            $return_text = '<object type="'.$media_type.'" class="'.$class.'"'.$add_style.' data-origin="'.$data_origin.'">'
            .'<param name="src" value="'.$str.'">'
            .'<param name="autoplay" value="'.(($autoplay)?'true':'false').'">'
            .'<param name="loop" value="'.(($loop)?'true':'false').'">'
            .'<param name="controls" value="'.(($controls)?'true':'false').'">'
            .'<a href="'.$str.'"'.$target.$relno.'>'.$title.'</a>'
            .'</object>';
        }
        $text_align = get_val($opt,'text-align', '');
        if ($text_align !== '') {
            $return_text = "<div style='text-align:$text_align'>$return_text</div>";
        }
        $class_reset();
        return $return_text;
    };
    // $loop_funcを引数に渡してからくる関数郡
    $url_pattern = $cws->url_pattern;
    $callback_url = function($m, $text) use (&$url_pattern, &$set_link, &$g_opt) {
        $text = preg_replace_callback($url_pattern, function($m) use (&$url_pattern, &$set_link){
            $text =  preg_replace_callback('/^(.*)$/', $set_link, $m[0]);
            return $text;
        }, $text);
        return $text;
    };
    $oul_tag_list = array();
    // 独自記法です、一部だけはてな記法そのものになってます
    $callback_cwrule = function($m, $text, $linkable = false)
     use (&$class_reset, &$data_origin, &$set_link, &$title, &$type, &$target, &$class,
            &$style, &$internal, &$callback_url, &$align_mode, &$heading_mode, &$oul_tag_list, &$opt) {
        if ($linkable) {
            return $m[0];
        }
        $cwrule_func = function($get_str)
        use (&$class_reset, &$data_origin, &$set_link, &$title, &$type, &$target, &$class, &$style, &$callback_url, &$oul_tag_list, &$opt) {
            $set_link_flag = true;
            $add_tag_list = array();
            $data_origin = "[$get_str]";
            if (preg_match('/^(.*\:\/\/[^\:\/]*.[^\:]*)(.*)$/', $get_str, $om)) {
                $get_str = $om[1];
                $t = $om[2];
            } elseif (preg_match('/^([^\:]*)(.*)$/', $get_str, $om)) {
                $get_str = $om[1];
                $t = $om[2];
            } else {
                $t = $get_str;
            }
            switch ($get_str) {
                case 'b': case 'i':
                    $add_tag_list[] = $get_str;
                    $set_link_flag = false;
                break;
                case 's': case 'e': case 'span':
                    $add_tag_list[] = 'span';
                    $set_link_flag = false;
                break;
                case '.':
                    $set_link_flag = false;
                break;
            }
            $title = '';
            $type = '';
            $style = '';
            $class = '';
            if (preg_match_all('/(\:)([^\:]*)/', $t, $om)) {
            $om_2l = $om[2];
            $c_om_2l = count($om_2l);
            $om_2l[] = '';
            $value_l = array();
            $value = '';
            $linkoption_add_enable = true;
            for($i = 0; $i < $c_om_2l; $i++) {
                $om_2l[$i] = preg_replace_callback('/\\\\(.)/', function ($bsm) {
                    switch($bsm[1]) {
                        case '\\':
                            return '\\';
                        break;
                        case 'e':
                            return '';
                        break;
                        default:
                            return $bsm[0];
                        break;
                    }
                }, $om_2l[$i]);
                $rest_0 = substr($om_2l[$i], -1);
                if ($rest_0 === '\\') {
                    preg_match('/\\\\+$/', $om_2l[$i], $om2);
                    if (strlen($om2[0]) % 2 === 1){
                        $value_l[] = substr($om_2l[$i], 0, strlen($om_2l[$i]) - 1);
                        continue;
                    } else {
                        $value_l[] = $om_2l[$i];
                    }
                } else {
                    $value_l[] = $om_2l[$i];
                }
                $value = implode(':', $value_l);
                $plane_flag = false;
                $value_spl = \explode(',', $value);
                foreach($value_spl as $mono_spl) {
                    if ($linkoption_add_enable && preg_match('/^\s*(\#|[^\d\=]+)[\s:\=]*(\d*)(.*)$/', $mono_spl, $swm)){
                        $equal_f = strpos($swm[0], '=') !== false;
                        $add_style = array();
                        $add_class =  array();
                        switch ($swm[1]) {
                            case '[]':
                                $linkoption_add_enable = false;
                            break;
                            case 'i': case 'b':
                                $add_tag_list[] = $swm[1];
                            break;
                            case 's': case 'span':
                                $add_tag_list[] = 'span';
                            break;
                            case 'e':
                            break;
                            case 'left': case 'right': case 'none':
                                $add_style[] = 'float:'.$swm[1].';';
                            break;
                            case 'text-left': case 'text-right': case 'text-center':
                                $swm[1] = \substr($swm[1], 5);
                            case 'center':
                                $opt['text-align'] = $swm[1];
                            break;
                            case 'w':
                                $numstr = strval(intval($swm[2]));
                                if (empty($swm[3])) { $unit = 'px'; } else { $unit = $swm[3]; } 
                                $add_style[] = 'width:'.$numstr.$unit.';';
                            break;
                            case 'h':
                                $numstr = strval(intval($swm[2]));
                                if (empty($swm[3])) { $unit = 'px'; } else { $unit = $swm[3]; } 
                                $add_style[] = 'height:'.$numstr.$unit.';';
                            break;
                            case 'auto':
                                $add_style[] = 'width:auto; height:auto;';
                            break;
                            case 'small':
                                $get_str = preg_replace('/([^\/]+)\.([^#\?]+)/', 'thumb/$1_s.$2', $get_str);
                            break;
                            case 'style':
                                $add_style[] = $swm[3];
                            break;
                            case '#':
                                $add_style[] = 'color:'.$swm[0].';';
                            break;
                            case 'c': case 'color':
                                $add_style[] = 'color:'.$swm[3].';';
                            break;
                            case 'charset':
                                $opt['charset'] = $swm[3];
                            break;
                            case 'cls': case 'class':
                                $add_class[] = $swm[3];
                            break;
                            case 'title':
                                $title = $swm[3];
                            break;
                            case 'target':
                                $target = $swm[3];
                            break;
                            case 'd': case 'description':
                                $opt['description'] = true;
                            break;
                            case '?': case 'confirm':
                                $opt['confirm'] = $swm[3];
                            break;
                            case 'object': case 'controls': case 'loop':
                            case 'muted': case 'autoplay': case 'preload':
                            case 'no-object': case 'no-controls': case 'no-loop':
                            case 'no-muted': case 'no-autoplay': case 'no-preload':
                                $opt_str = $swm[1];
                                $no = strpos($swm[1], 'no-')===0;
                                $opt_set = $swm[2].$swm[3];
                                if (preg_match('/\S/', $opt_set)) $opt_set = '1';
                                if ($no) {
                                    $opt_str = substr($opt_str, 3);
                                    $opt[$opt_str] = is_true($opt_set);
                                } else {
                                    $opt[$opt_str] = !is_true($opt_set);
                                }
                            break;
                            default:
                                if (preg_match('/^(m|margin|p|padding)\s*($|-(.+)$)/', $swm[1], $mpm)) {
                                    $mp_attr = ($mpm[1][0] === 'p') ? 'padding' : 'margin';
                                    if (empty($mpm[2])) {
                                        $numstr = preg_replace('/(\d+)(\s|$)/', '$1px$2', $swm[2].$swm[3]);
                                        $add_style[] = $mp_attr.':'.$numstr.';';
                                    } else {
                                        if (empty($swm[3])) { $unit = 'px'; } else { $unit = $swm[3]; }
                                        $numstr = strval(intval($swm[2]));
                                        switch ($mpm[3]) {
                                            case 't': case 'top':
                                                $add_style[] = $mp_attr.'-top:'.$numstr.$unit.';';
                                            break;
                                            case 'v': case 'vertical':
                                                $add_style[] = $mp_attr.'-top:'.$numstr.$unit.';';
                                            case 'b': case 'bottom':
                                                $add_style[] = $mp_attr.'-bottom:'.$numstr.$unit.';';
                                            break;
                                            case 'l': case 'left':
                                                $add_style[] = $mp_attr.'-left:'.$numstr.$unit.';';
                                            break;
                                            case 'h': case 'horizontal':
                                                $add_style[] = $mp_attr.'-left:'.$numstr.$unit.';';
                                            case 'r': case 'right':
                                                $add_style[] = $mp_attr.'-right:'.$numstr.$unit.';';
                                            break;
                                        }
                                    }
                                } else {
                                    switch ($value) {
                                        case 'image': case 'movie': case 'video': case 'audio': case 'text': case 'application': case 'inline':
                                            $type = $value;
                                        break;
                                        default:
                                            $plane_flag = true;
                                        break;
                                    }
                                }
                            break;
                        }
                        $style .= (($style === '') ? '' : ' ') . implode(' ', $add_style);
                        $class .= (($class === '') ? '' : ' ') . implode(' ', $add_class);
                    } else {
                        $plane_flag = true;
                    }
                    if ($plane_flag) {
                        if ($title === '') {
                            $title = $value;
                        } else {
                            $target = $value;
                        }
                    }
                }
                $value_l = array();
            } }
            $text = str_replace(' ', '%20', $get_str);
            $subtext = '';
            if (preg_match('/^([^\[]*)\[(.*)\]([^\]]*)$/', $text, $mtbrk)) {
                $text = $mtbrk[1].$mtbrk[3];
                $subtext = $mtbrk[2];
            }
            if ($set_link_flag) {
                if ($text !== '') {
                    $text = $set_link($text, $subtext);
                } else {
                    $class_reset();
                }
            } else {
                $tag = array_shift($add_tag_list);
                if (empty($tag)) {
                    $text = $title;
                } else {
                    $in_style = ($style === '') ? '' : ' style="'.$style.'"';
                    $text = '<'.$tag.' class="'.$class.'"'.$in_style.'>'.$title.'</'.$tag.'>';                    
                }
                $class_reset();
            }
            foreach ($add_tag_list as $add_tag) {
                $text = "<$add_tag>$text</$add_tag>";
            }
            return $text;
        };
        $text = brackets_loop($text, $cwrule_func);
        $li_loop = false;
        $heading_re = '/^(\s*)([*%+\-\\\\]+)(.*)$/m';
        $text = preg_replace_callback($heading_re, function($m) use (&$align_mode, &$heading_mode, &$oul_tag_list, &$li_loop) {
            $space_len = strlen($m[1]);
            $retval = $m[3]; $tail = '';
            if (preg_match('/^(.*)([\\\\\s]+)$/', $retval, $m2)) {
                $retval = $m2[1]; $tail = $m2[2];
            }
            $retarr = array('', $retval, '');
            $mnb_tag = 0;
            if (strlen($m[1]) > 0) {
                if (substr($m[1], -1) === ' ') $m[1] = substr($m[1], 0, $space_len - 1);
            } else {
                $strqty = get_strqty($m[2], false);
                if ((isset($strqty['+']) || isset($strqty['-'])) && preg_match('/([+\-])[^+\-]*$/', $m[2], $m_plmi)) {
                    $strqty[($m_plmi[1] === '+' ? '+' : '-')] = get_val($strqty, '+', 0) + get_val($strqty, '-', 0);
                    unset($strqty[($m_plmi[1] === '+' ? '-' : '+')]);
                }
                foreach($strqty as $symbol_key => &$symbol_len) {
                    $tag = ''; $style = ''; $class = ''; $oul_tag = '';
                    $b_close = false; $open = true; $close = true;
                    switch ($symbol_key) {
                        case '*':
                            switch ($symbol_len % 3) {
                                case 1: $tag = 'h3'; break;
                                case 2: $tag = 'h4'; break;
                                case 0: $tag = 'h5'; break;
                            }
                            if ($symbol_len >= 4) {
                                $b_close = ($heading_mode !== '');
                                $open = ($tag !== $heading_mode);
                                $close = !($b_close || $open);
                                if ($open && !$close) {
                                    $style = 'display: block;';
                                    $heading_mode = $tag;
                                    if ($retarr[1] === '' && $tail === '') $tail  = '\\';
                                } else {
                                    $heading_mode = '';
                                }
                            }
                        break;
                        case '%':
                            $tag = 'div';
                            switch ($symbol_len % 3) {
                                case 1: $align_str = 'center'; break;
                                case 2: $align_str = 'right'; break;
                                case 0: $align_str = 'left'; break;
                                default: $align_str = ''; break;
                            }
                            if ($align_str !== '') $style .= 'text-align:'.$align_str;
                            if ($symbol_len >= 4) {
                                $b_close = ($align_mode !== '');
                                $open = ($align_str !== $align_mode);
                                $close = !($b_close || $open);
                                if ($open && !$close) {
                                    $align_mode = $align_str;
                                    if ($retarr[1] === '' && $tail === '') $tail  = '\\';
                                } else {
                                    $align_mode = '';
                                }
                            }
                        break;
                        case '+': // ol -> liタグ設定
                        case '-': // ul -> liタグ設定
                            $tag = 'li';
                            $li_loop = true;
                            $li_pos = count($oul_tag_list);
                            $li_push_tag = (($symbol_key === '+') ? 'ol' : 'ul');
                            $pop_limit = $li_pos - $symbol_len;
                            if ($pop_limit >= 0 && $li_push_tag !== $oul_tag_list[$symbol_len - 1]) {
                                $pop_limit++;
                            }
                            if ($pop_limit > 0) {
                                for ($li_i = 0; $li_i < $pop_limit; $li_i++) {
                                    $li_pop_tag = array_pop($oul_tag_list);
                                    $oul_tag .= '</'.$li_pop_tag.'>';
                                }
                                $li_pos = count($oul_tag_list);
                            }
                            if ($li_pos < $symbol_len) {
                                for ($li_i = $li_pos; $li_i < $symbol_len; $li_i++) {
                                    array_push($oul_tag_list, $li_push_tag);
                                    $oul_tag .= '<'.$li_push_tag.'>';
                                }
                            }
                            $li_pos = $symbol_len;
                        break;
                        case '\\':
                            $tag = ''; $mnb_tag = true;
                        break;
                    }
                    if ($style !== '') $style = " style='$style'";
                    $tmp_tag = $tag !== '';
                    $mnb_tag |= $tmp_tag;
                    if ($tag !== '') {
                        $retarr[0] = $retarr[0] . $oul_tag . ($b_close ? "</$tag>" : '') . ($open ? "<$tag$style$class>" : '');
                        $retarr[2] = ($close ? "</$tag>" : '') . $retarr[2];
                    }
                }
            }
            $retval = implode('', $retarr).$tail;
            if ($mnb_tag) {
                return $retval;
            } else {
                return $m[1].$m[2].$retval;
            }
        }, $text);
        if (!$li_loop) {
            $li_pos = count($oul_tag_list);
            for ($li_i = 0; $li_i < $li_pos; $li_i++) {
                $li_pop_tag = array_pop($oul_tag_list);
                $text = '</'.$li_pop_tag.'>' . $text;
            }
        }
        return $text;
    };
    $hashtag_re = get_val($g_opt, 'hashtag_re', '');
    $add_symbol = count($_q_str_l_f) !== 0;
    $callback_tag = function($m, $text) use (&$hashtag_re, &$_q_join, &$_q_str_l_f, &$add_symbol, $request_key) {
        $text = preg_replace_callback($hashtag_re, function($m) use (&$_q_join, &$_q_str_l_f, &$add_symbol, $request_key){
            $tag = $m[2];
            $tag_hash = '#'.$m[2];
            $brackets_flag = isset($_q_str_l_f[$tag_hash]);
            $tag_value = ($add_symbol && $brackets_flag) ? "[$tag]" : $tag_hash;
            $tag = str_replace('+', '%2b', $tag);
            $tag_href = "?$request_key=%23$tag";
            $plus_href = "$_q_join%23$tag";
            $add_flag = $add_symbol && ($tag_href !== $plus_href) && !$brackets_flag && !isset($_q_str_l_f['-'.$tag_hash]);
            return $m[1].'<a class="tag" href="'.$tag_href.'">'.$tag_value.'</a>'
            .($add_flag ? ('<a class="add" href="'.$plus_href.'">＋</a>') : '');
        }, $text);
        return $text;
    };
    $reply_re = get_val($g_opt, 'reply_re', '');
    $g_opt['reply_link_re'] = get_val($g_opt, 'reply_link_re', "/^(\?)($request_key\=)(.*)$/");
    $callback_reply = function($m, $text) use (&$reply_re, &$g_opt) {
        $text = preg_replace_callback($reply_re, function($m) use (&$g_opt){
            $reply_query = '?' . $g_opt['request_key'] . '=@';
            if (is_callable($g_opt['reply_link'])) {
                $reply_link = preg_replace_callback($g_opt['reply_link_re'], $g_opt['reply_link'], $reply_query.$m[3]);
            } else {
                $reply_link = preg_replace($g_opt['reply_link_re'], $g_opt['reply_link'], $reply_query.$m[3]);
            }
            $to_str = $m[2].$m[3];
            $after = $m[4] == ':' ? '' : $m[4];
            return $m[1].'<a class="reply" href="'.$reply_link.'">'.$to_str.'</a>'.$after;
        }, $text);
        return $text;
    };
    $response_re = get_val($g_opt, 'response_re', '');
    $g_opt['response_key'] = get_val($g_opt, 'response_key', 'id');
    $response_key = $g_opt['response_key'];
    $g_opt['response_link_re'] = get_val($g_opt, 'response_link_re', "/^(\?)($response_key\=)(.*)$/");
    $callback_response = function($m, $text) use (&$response_re, &$g_opt) {
        $text = preg_replace_callback($response_re, function($m) use (&$g_opt){
            $response_query = '?' . $g_opt['response_key'] . '=';
            if (is_callable($g_opt['response_link'])) {
                $response_link = preg_replace_callback($g_opt['response_link_re'], $g_opt['response_link'], $response_query.$m[3]);
            } else {
                $response_link = preg_replace($g_opt['response_link_re'], $g_opt['response_link'], $response_query.$m[3]);
            }
            $to_str = '&gt;&gt;'.$m[3];
            $after = $m[4] == ':' ? '' : $m[4];
            return $m[1].'<a class="response" href="'.$response_link.'">'.$to_str.'</a>'.$after;
        }, $text);
        return $text;
    };
    $tag_char = '';
    $func_list = array();
    
    // $g_optにcb_beforeかcb_afterを定義することで先、後にやる補正を決めることができる
    // function($m, $text)という形式にすること
    if (get_val($g_opt, 'cb_before', null) !== null) $func_list[] = $g_opt['cb_before']; 
    
    $cb_bitnot_cwrule = get_val($g_opt, 'cbn_cwrule', false);
    $cb_bitnot_url = get_val($g_opt, 'cbn_url', false);
    $cb_bitnot_htnurl = true && !$cb_bitnot_cwrule && !$cb_bitnot_url;

    $cb_bit_default_cwrule = true && !$cb_bitnot_url;
    $do_callback_cwrule = get_val($g_opt, 'cbf_cwrule', $cb_bit_default_cwrule);
    if ($do_callback_cwrule) $func_list[] = $callback_cwrule;

    $cb_bit_default_url = true && !$cb_bitnot_cwrule;
    if (get_val($g_opt, 'cbf_url', $cb_bit_default_url)) $func_list[] = $callback_url;

    if (get_val($g_opt, 'cbf_tag', $cb_bitnot_htnurl)) $func_list[] = $callback_tag;
    if (get_val($g_opt, 'cbf_reply', $cb_bitnot_htnurl)) $func_list[] = $callback_reply;
    if (get_val($g_opt, 'cbf_response', $cb_bitnot_htnurl)) $func_list[] = $callback_response;
    if (get_val($g_opt, 'cbf_search', $cb_bitnot_htnurl)) $func_list[] = $callback_search;

    if (get_val($g_opt, 'cb_after', null) !== null) $func_list[] = $g_opt['cb_after'];

    $permission = get_val($g_opt, 'permission', array());
    
    if (!\is_array($arr)) $arr = array($g_opt['arr_text'] => $arr);

    $g_arr_htmlsp = get_val($g_opt, 'arr_htmlsp', '');
    $br_leaven = get_val($g_opt, 'br_leaven', false);
    $g_htmlspecialchars = get_val($g_opt, $g_arr_htmlsp, true);
    foreach($arr as $var) {
        $htmlspecialchars = $g_htmlspecialchars && get_val($var, $g_arr_htmlsp, true);
        $text = get_val($var, $g_opt['arr_before_text'], '') . get_val($var, $g_opt['arr_text'], '') . get_val($var, $g_opt['arr_after_text'], '');
        $text = convert_to_href_decode($text);
        if ($htmlspecialchars) $text = htmlspecialchars($text);
        if ($do_callback_cwrule) {
            $br_esc = chr(27);
            $s_out = chr(14);
            $s_in = chr(15);
            $comment_out = false;
            // ul, liタグの改行はCSS側で調整する
            for ($i_rep = 0; $i_rep < 2; $i_rep++) {
                $text = preg_replace_callback('/(^|\n)([+\-][^\n]*|>>|<<|\*\/|\/\*)(\n|$)/m', function($m)
                use (&$br_esc, &$s_out, &$s_in, &$comment_out) {
                    switch ($m[2]) {
                        case '>>':
                            $m[2] = '<blockquote>';
                        break;
                        case '<<':
                            $m[2] = '</blockquote>';
                        break;
                        case '/*':
                            $m[2] = $s_out;
                            $comment_out = true;
                        break;
                        case '*/':
                            $m[2] = $s_in;
                            $comment_out = false;
                        break;
                    }
                    if (mb_substr($m[2], -1) === $br_esc) {
                        return $m[1].$m[2].$m[3];
                    } else {
                        return $m[1].$m[2].$br_esc;
                    }
                }, $text);
            }
            if ($comment_out) {
                $text .= $s_in;
                $comment_out = false;
            }
            $not_s_in = "[^$s_in]";
            $text = preg_replace("/$s_out$not_s_in*$s_in/m", '', $text);
        };
        $text = convert_to_br($text, $br_leaven);
        if ($do_callback_cwrule) {
            $text = str_replace($br_esc, "\n", preg_replace("/$br_esc$/", "\n\\\n", $text));
        };
        $text = __tagesc_callback('/.*/', $text, $func_list, $permission);
        if ($align_mode !== '') { $text .= '</div>'; $align_mode = ''; }
        if ($heading_mode !== '') { $text .= "</$heading_mode>"; $heading_mode = ''; }
        $text = escape_to_br($text);
        $text = preg_replace('/^\s+|\s+$/', '', $text);
        $loop_func($text, $var);
    }
    return $out_html_list;
}
    // if (preg_match('/\s$/', $str)) {
    //     var_dump($str);
    // }
// set_autolinkのクラス版、ループは-1から開始するため、
// while($autolink->next()){}でループを回すことができます
class AutoLink {
    static private $default_g_opt = array('autoplay'=> false, 'htmlspecialchars' => true);
    private $i = 0;
    private $length = 0;
    private $while = false;
    public function get_index(){return $i;}
    function get_length() { return $this->length; }
    function is_last() { return ($this->length-1) === $this->i; }
    public function get_while() { return $while; }
    private $arr = null;
    private $key_text = 'text';
    private $hold = false;
    private $text = '';
    public $g_opt = null;
    function get_text() {
        if ($this->hold) { $this->set_autolink(); $this->hold = false; }
        return $this->text;
    }
    function get_value() { return $this->arr[$this->i]; }
    function set_autolink() {
        $this_text = &$this->text;
        set_autolink(array($this->arr[$this->i]), $this->g_opt,
        function($text, $var) use (&$this_text) { $this_text = $text; });
    }
    function set_arr($array = array()) {
        if (!is_array($array)) { $array = array($key_text => $array); }
        $this->arr = $array;
        $this->length = count($this->arr);
    }
    public function current(){
        if ($this->i < 0) $this->i = 0;
        $this->while = $this->i < $this->length;
        if ($this->while) {
            $this->hold = true;
            return $this->i;
        } else {
            return false;
        }
    }
    public function next(){
        $this->while = $this->i < ($this->length - 1);
        if ($this->while) {
            ++$this->i; $this->hold = true;
        }
        return $this->while;
    }
    public function back(){
        $this->while = $this->i > 0;
        if ($this->while) {
            --$this->i; $this->hold = true;
        }
        return $this->while;
    }
    public function reset(){
        $this->i = -1;
        return $this->while = true;
    }
    static function create($arr = array(), $g_opt = null){
        if (is_null($g_opt)) $g_opt = self::$default_g_opt;
        return new self($arr, $g_opt);
    }
    function __construct($arr = array(), $g_opt = null){
        if (is_null($g_opt)) $g_opt = self::$default_g_opt;
        $this->g_opt = $g_opt;
        if (isset($this->g_opt['arr_text'])) $this->key_text = $this->g_opt['arr_text'];
        $this->set_arr($arr);
        $this->i = -1;
    }
    function __destruct() {
    }
}
?>