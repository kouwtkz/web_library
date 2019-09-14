<?php
namespace cws;
require_once($_SERVER['DOCUMENT_ROOT']."/common/cw_init/cws.php");
// 画像URLのsrcとthumbnailの割り振り関数
function filter_thumbnail($array, $thumb_suffix = "_tmb"){
    $dir = '';
    $filter_func = function ($value) use ($thumb_suffix, &$dir){
        // dirだけ→以後のデフォルトへ登録、dirとsrcどちらも→一時的に変更
        $dir_cur = (isset($value->dir)?$value->dir:$dir);
        $dir = (isset($value->src)?$dir:$dir_cur);
        $tmb = (isset($value->thumbnail)?$value->thumbnail:'');
        $path = (isset($value->src)?$value->src:'');
        $path = get_eachpath($path, $dir_cur);
        if ($path == '') return false;
        $value->src = $path;
        if ($tmb == '') {
            $tmb = get_eachpath($path, '', '', '_tmb', '', false);
            $value->thumbnail = $tmb;
        }
        return $value;
    };
    return array_filter($array, $filter_func);
}
function filter_all_gallery($keyword = "", $page = 1, $max = 9) {
    global $gallery;
    return filter_all($gallery, $keyword, $page, $max);
}
?>