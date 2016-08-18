<?php

require_once('bin.php');

function get_recommend_content($board, $filename)
{
    chdir(BBSHOME);
	$path = 'boards/'  . $board . '/' . $filename;
	if ( !file_exists($path) ) return '';
    $arr = file($path);
    $ret = '';
    $len = 0;
    for($i=8; $i < count($arr) ; $i++)
    {
        if($len +  strlen($arr[$i])  > 280) break;
        $len += strlen($arr[$i]);        
        $ret .= $arr[$i];        
    }
    return ' ' . $ret . '...';
}

$board = new Board("Recommend");
if($board->is_vail())
{
    $remlist = $board->get_post_list(0, 5, 1);
    foreach($remlist->list as &$post)
    {
        $post->filename = str_replace("G", "M", $post->filename);
        $post->content = get_recommend_content("Recommend", $post->filename);
        unset($post->flag);
        unset($post->id);
        unset($post->unread);
        unset($post->mark);
    }
    fjdb_set(BBSHOME . '/etc/', 'phprecommend',
             gbk2utf8(array_reverse($remlist->list)));
}

?>
