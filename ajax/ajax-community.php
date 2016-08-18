<?php

require_once("common/functions.php");
require_once("common/config.php");

function ajax_topten()
{
    $top = etc_top_ten();
    ajax_success($top);
    return;
}

function ajax_tips()
{
    $files = glob(PHPBBS_HOME . '/tips/M.*');
    if($files){
        $file = array_rand($files);
        ajax_success(file_get_contents($files[$file]));
    }
    else{
        ajax_success(false);
    }
}

function ajax_brithdaywish()
{
    $birthday = etc_birthday_today();
    ajax_success($birthday);
    return;
}

function www_get()
{
    ajax_success_utf8(etc_get_www());
    return ;
}

function www_set()
{
    global $user;
    ajax_assert_login();
    ajax_assert_POST();
    ajax_assert_param($_POST, array('data'));
    ajax_assert($user->hasperm(PERM_SYSOP), "Permission deny", 403);
    
    trace_report("Set site www ");
    if(etc_set_www($_POST['data']))
    {
        ajax_success(true);
    }
    else
    {
        ajax_error_code(404, 'Failed to save etc.');
    }

}

function ajax_get_etc_target()
{
    global $user;
    ajax_assert_login();
    ajax_assert($user->hasperm(PERM_SYSOP), "Permission deny", 403);

    include "etc/etc_target.php";

    ajax_success_utf8(array('desc' => $etc_target_desc));
}

function ajax_get_etc()
{
    global $user;
    ajax_assert_login();
    ajax_assert_param($_GET, array('target'));
    ajax_assert($user->hasperm(PERM_SYSOP), "Permission deny", 403);
    
    include "etc/etc_target.php";

    $target = $_GET['target'];
    if(!isset($etc_target[$target]))
    {
        ajax_error_code('0', 'No such etc target.');
    }

    $filename = BBSHOME . '/etc/' . $etc_target[$target];
    trace_report('get etc target [' . $target . ']');

    ajax_success_utf8(array('data' => file_get_contents($filename)));
}

function ajax_update_page()
{
    global $user;
    ajax_assert_login();
    ajax_assert_param($_POST, array("path", "content"));
    ajax_assert($user->hasperm(PERM_SYSOP), "Permission deny", 403);

    if(strpos($_POST['path'], '..') !== false)
    {
        ajax_error('Wrong file path.');
        return;
    }

    if(strpos($_POST['path'], '_') !== false)
    {
        if($userid != 'SYSOP')
        {
            ajax_error('Readonly file path.');
            return;
        }
    }
    
    $fh = fopen(PHPBBS_HOME . '/phplog/update_page', 'a');
    $userid = $user->userid();
    $path = PHPBBS_HOME . '/page/' . $_POST['path'];
    fwrite($fh, "U: [$userid] : $path\n");
    fclose($fh);

    file_put_contents($path, $_POST['content']);

    ajax_success('success.');
}

function ajax_test()
{
    global $user;
    ajax_success('BBSAPI 0.99\nWELCOME');
}

function ajax_refresh()
{
    global $user;
    $arr = array('success' => true);
    if(!$user->islogin())
    {
        $arr['status'] = 'ULI';
        $arr['data'] = array(array('您还没有登录',
                                   'http://bbs.sysu.edu.cn'));
    }
    else
    {
        $arr['status'] = 'UN';
        if(ext_check_mail($user->userid()))
        {
            $arr['status'] = 'NN';
            $arr['data'] = array(array('您有新的邮件', 'http://bbs.sysu.edu.cn/n/index.html#!mail'));
        }        
        else
        {
            $arr['status'] = 'UN';
        }
    }
    header("Content-type: application/json");
    echo json_encode($arr);
    die();
}

function ajax_update_etc()
{
    global $user;
    ajax_assert_login();
    ajax_assert_param($_POST, array('target'));
    ajax_assert($user->hasperm(PERM_SYSOP), "Permission deny", 403);

    include "etc/etc_target.php";

    $target = $_POST['target'];
    if(!isset($etc_target[$target]))
    {
        ajax_error_code('0', 'No such etc target.');
    }

    $filename = BBSHOME . '/etc/' . $etc_target[$target];
    trace_report('Set etc target [' . $target . ']');

    rename($filename, $filename . '.old');
    file_put_contents($filename, $_POST['content']);

    ajax_success('update etc success.');
    
}

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

function www_home2()
{
    global $user;
    $activeboard = fjdb_get(BBSHOME . '/etc/phpactiveboard');
    $topten = gbk2utf8(etc_top_ten());
    $recommend = fjdb_get(BBSHOME . '/etc/phprecommend');
    // TODO -- update boards info for every user
    $boards = fjdb_get(BBSHOME . '/etc/phpgoodbrds');
    foreach($boards as $type => &$val)
    {
        $list = array();
        foreach($boards[$type] as &$l)
        {
            $tmp = new Board($l);
            if($tmp && $tmp->is_vail()) array_push($list, $tmp);
        }
        beautify_board($list);
        $val = gbk2utf8($list);
    }
    $www = etc_get_www();
    if($www['widgets'])
    {
        array_push($www['widgets'],
                   array('title' => '广告',
                         'type' => 'imglist',
                         'clsname' => 'unstyled imglist',
                         'list' => etc_get_ads()));
    }
    if($user->islogin())
    {
        $fav = Board::boards_from_fav();
        $fav = array_filter($fav, "board_perm_filter");
        $fav = array_values($fav); /* rebuild keys */
        beautify_board($fav);
        $fav = gbk2utf8($fav);
    }
    else
    {
        $fav = false;
    }
    ajax_success_utf8(array('activeboard' => $activeboard,
                            'fav' => $fav,
                            'topten' => $topten,
                            'recommend' => $recommend,
                            'goodboards' => $boards,
                            'www' => $www));
}

function www_home()
{
     $birthday = etc_birthday_today();
     $www = etc_get_www();
     $topten = etc_top_ten();
 
     $all_boards = all_boards_sec();

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
         $www['rempost'] = array_reverse(gbk2utf8($remlist->list));
     }

     if($www['widgets'])
     {
         array_push($www['widgets'],
                    array('title' => '广告',
                          'type' => 'imglist',
                          'clsname' => 'unstyled imglist',
                          'list' => etc_get_ads()));
     }

    $activeboard = fjdb_get(BBSHOME . '/etc/phpactiveboard');

     ajax_success_utf8(array("topten" => gbk2utf8($topten),
                             "birthday" => gbk2utf8($birthday),
                             "boardnav" => gbk2utf8($all_boards),
                             "activeboard" => $activeboard,
                             "www" => $www));
        
     return;
     
}

?>
