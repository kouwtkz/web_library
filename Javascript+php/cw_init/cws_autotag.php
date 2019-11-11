<?php
namespace cws;
# 自動タグ付け命令、頻繁に変えるため分離した
$cws_autotag_enable = true;
include_once('cws.php');

// 更新日を付与してhtmlの出力(改_20191107)
function set_autotag(...$data_list){
    $local_set = null;
    $index_array = function($var) { return \is_numeric($var) && $var >= 0; };
    $not_index_array = function($var) { return !\is_numeric($var); };
    $local_set_attr = function (&$list, $arg_opt, $all_attr = false) 
    use (&$local_set, &$local_set_attr) {
        $out_list = array();
        foreach ($list as $key => $var) {
            $key_switch = $key;
            if ($all_attr) {
                $key_switch = 'attr';
            } else {
                if (is_numeric($key)) continue;
            }
            switch($key_switch) {
                case 'attr': case 'data':
                $attr = getref($list, $key, true, '');
                if (is_array($attr)) {
                    $out_list += $local_set_attr($attr, $arg_opt, true);
                } else {
                    if ($key === 'attr') $key = '';
                    if (is_numeric($key)) {
                        $key = $var; $attr = '';
                    }
                    $out_list[$key] = $attr;
                }
                ;
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
    $default_opt = array('write_text'=>true, 'create'=>true, 'output'=>true, 'add_date'=>true);
    $local_set = function ($data_list, $arg_opt) use (&$local_set, &$out_list, &$index_array, &$not_index_array, &$local_set_attr) {
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
            if (empty($data_list)) return;
            $data = array($data_list);
            $opt = $arg_opt;
        }
        $tag = '';
        $inner = '';
        switch (count($data)) {
            case 1:
            $title = getref($data, 'title', true, false);
            if ($title) {
                $tag = 'title';
                $inner = $title;
            }
        }

        $d_value = getref($data, 0, true, '');
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
            case '':
            break;
            default:
            $data['src'] = $d_value;
            break;
        }
        $tag = getref($data, 'tag', true, $tag);
        $rel = '';
        $type = '';
        $elm = '';
        $src = getref($data, 'src', true, '');
        $inner .= getref($data, 'inner', true, '');

        $data += $local_set_attr($data, $opt);

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