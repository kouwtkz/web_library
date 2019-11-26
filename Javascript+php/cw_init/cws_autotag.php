<?php
namespace cws;
# 自動タグ付け命令、頻繁に変えるため分離した
$cws_autotag_enable = true;
include_once('cws.php');

// 更新日を付与してhtmlの出力(改_20191107)
function set_autotag(...$data_list){
    $default_opt = array('text_style'=>false, 'text_write'=>true, 'create'=>true, 'output'=>true, 'add_date'=>false);
    $local_set = null;
    $index_array = function($var) { return \is_numeric($var) && $var >= 0; };
    $not_index_array = function($var) { return !\is_numeric($var); };
    $define = array();
    $local_set_attr = function (&$list, $arg_opt, $all_attr = false) 
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
    $local_set = function ($data_list, $arg_opt)
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
            case 'og':
                $title = get_val($define, 'og:title', get_val($define, 'title', ''));
                $description = get_val($define, 'og:description', get_val($define, 'description', ''));
                $url = get_val($define, 'og:url', get_val($define, 'url', null));
                $image = get_val($define, 'og:image', get_val($define, 'image', null));
                $local_set(array(
                    array('tag' => 'meta', 'property' => 'og:title', 'content' => $title),
                    array('tag' => 'meta', 'property' => 'og:description', 'content' => $description),
                    isset($url) ? array('tag' => 'meta', 'property' => 'og:url', 'content' => get_fullurl($url)) : null,
                    isset($image) ? array('tag' => 'meta', 'property' => 'og:image', 'content' => get_fullurl($image)) : null,
                ), $opt);
                return;
            break;
            case 'twitter':
                $card = get_val($define, 'twitter:card', get_val($define, 'card', 'summary'));
                $site = get_val($define, 'twitter:site', get_val($define, 'site', ''));
                $local_set(array(
                    array('tag' => 'meta', 'property' => 'twitter:card', 'content' => $card),
                    array('tag' => 'meta', 'property' => 'twitter:site', 'content' => $site),
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
?>