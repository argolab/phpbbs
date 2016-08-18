<?php

require_once("error.php");
require_once("common/functions.php");
/* 注释注释注释 */

/* start 0 is the last  , type =1  为文摘 */
function get_post_list($boardname, $start, $type) {
	global $user;
	$user->set_stat(STAT_READNEW);
                    
	$board = new Board($boardname);
        /* permission */
	if (!$user->has_read_perm($board)) {
		return NULL;
	}

        /* number list per page */
	$www = $user->www();
	if(isset($www['t_lines'])) $list_num = $www['t_lines'];    
	else $list_num=20;
    
	$ret = $board->get_post_list($start, $list_num, $type);

	$start=$start ? $start : $ret->total-$list_num+1;
	if($start<=0)  $start=1;
    
	$prev = $start - $list_num;
	if($prev<=0) $prev=1;    
	$next = $start + $list_num;
	if($next > $ret->total)  $next=$start;

        /* G.12345457.A  ==> M.1234567.A */
	if($type == 1 && count($ret->list) > 0) {
		foreach($ret->list as &$post)
		{
			$post->filename = str_replace("G", "M", $post->filename);
		}
    }
    foreach ($ret->list as &$post) {
        if ($post->unread == 0)
            $post->mark = strtolower($post->mark);
        if ($post->mark == 'n') $post->mark = '';
        $post->update = show_last_time($post->update);
    }
    
	$data = array(
		"board" => $board,
		"plist" => $ret->list,    
		"prev" => $prev,
		"next" => $next,
        "islogin" => $user->islogin() ? '1' : '0'
		);

	return $data;
}

function list_post_normal($boardname, $start=0) {
	global $tpl;

	$data = get_post_list($boardname, $start, 0);
	$data['isnormal'] = true;
   
	$tpl->loadTemplate('standard/list_post_all.html');
	echo $tpl->render($data);
}

function list_post_digest($boardname, $start=0) {
	global $tpl;
	$data = get_post_list($boardname, $start, 1);
	$data['isdigest'] = true;
   
	$tpl->loadTemplate('standard/list_post_all.html');
	echo $tpl->render($data);
}

function list_post_topic($boardname, $start=0) {
	global $tpl;
	global $user;
    
	$board=new Board($boardname);
    
	if (!$user->has_read_perm($board)) {
		return NULL;
	}

	$www = $user->www();
	if(isset($www['t_lines'])) $list_num = $www['t_lines'];
	else $list_num=20;
    
	$ret=$board->get_topic_list($start, $list_num);
    
	$start=$start ? $start : $ret->total - $list_num+1;
	if($start<=0)  $start=1;
    
	$prev = $start - $list_num;
	if($prev<=0)  $prev = 1;    
	$next = $start + $list_num;
	if($next > $ret->total)  $next = $start;

	$data = array(
		"board" => $board,
		"plist" => $ret->list,
		"prev" => $prev,
		"next" => $next,
		"istopic" => true,
        "islogin" => $user->islogin() ? '1' : '0'
		);
	$tpl->loadTemplate('standard/list_post_all.html');
	echo $tpl->render($data);
}

function delete_post($boardname,$filename)
{
	global $user;
    
	if (!$user->islogin()) {
		echo "请先登陆";
		return;
	}
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		echo "请求错误";
		return;
	}
	if (!isset($_POST['board']) || !isset($_POST['filename'])) {
		echo "参数错误";
		return;
	}

	$board=new Board($boardname);

    $res = $board->delete_post($filename);
    echo $res ? "删除成功" : "删除失败";
    return ;
}

function clear_unread($boardname)
{
	global $user;
     
	if (!$user->islogin()) {
		echo "请先登陆";
		return;
	}

	$board = new Board($boardname);     
	if ($user->has_read_perm($board) == false) {
		echo "版块不存在";
		return;
	}

	$ret = $board->get_post_list(0, BRC_MAXNUM, 0);

	$total = 0;
	foreach($ret->list as &$file) {
		if(ext_mark_read($user->userid(), $boardname, $file->filename))
			$total ++;
	}
	echo "已清除";
}

function list_fav_boards()
{
	global $tpl;
	global $user;
	if (!$user->islogin()) {
		echo "你需要登陆才能查看收藏夹！";
		return ;
	}
	$boards = Board::boards_from_fav();
	$boards = array_filter($boards, "board_perm_filter");
	$boards = array_values($boards); /* rebuild keys */
	foreach($boards as &$board) {
		/* 更改时间显示 */
		$board->lastpost = show_last_time($board->lastpost);
		/*$t = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		  if ($board->lastpost < $t) {
		  $board->lastpost = date('m-d', $board->lastpost);
		  } else {
		  $board->lastpost = date('H:i', $board->lastpost);
		  } */
		/* 获取版面最新文章 */
		$ret = $board->get_post_list($board->total, 1, 0);
		if (isset($ret->list) && count($ret->list) > 0) {
			$board->lastpostfile = $ret->list[0]->title;
			$board->lastfilename = $ret->list[0]->filename;
			$board->lastauthor = $ret->list[0]->owner;
		}
	}

	$www = $user->islogin()? $user->www() : "";
	
	$tpl->loadTemplate('standard/fav_boards.html');
	echo $tpl->render(array("boards" => $boards,
				"www" => $www));
}

?>

