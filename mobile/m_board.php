<?php

require_once("m_misc.php");
require_once("common/functions.php");

function m_list_boards() {
	global $tpl;
	$secs = etc_section_list();
	foreach ($secs as & $sec) {
		$boards = Board::boards_from_seccode($sec['seccode']);
		$boards = array_filter($boards, "board_perm_filter");
		$boards = array_values($boards); /* rebuild keys */
		foreach($boards as $board) {
			/* 更改时间显示 */
			$t = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
			if ($board->lastpost < $t) {
				$board->lastpost = date('m-d', $board->lastpost);
			} else {
				$board->lastpost = date('H:i', $board->lastpost);
			}
			
		}
		$sec['brds'] = $boards;
	}
	$tpl->loadTemplate('mobile/m_board.html');
	echo $tpl->render(array('secs' => $secs));
}


function m_list_fav_boards() {
	global $tpl;
	global $user;
	if (!$user->islogin()) {
		m_exit("你需要登陆才能查看收藏夹！");
	}
	$boards = Board::boards_from_fav();
	$boards = array_filter($boards, "board_perm_filter");
	$boards = array_values($boards); /* rebuild keys */
	foreach($boards as $board) {
		/* 更改时间显示 */
		$t = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		if ($board->lastpost < $t) {
			$board->lastpost = date('m-d', $board->lastpost);
		} else {
			$board->lastpost = date('H:i', $board->lastpost);
		}
	}

	$tpl->loadTemplate('mobile/m_fav.html');
	echo $tpl->render(array("boards" => $boards));
}


function m_list_posts($bname, $page=0) {
	global $user;
	global $tpl;

	$board = new Board($bname);
	
	/* permission */
	if (!$user->has_read_perm($board)) {
		m_exit("访问错误");
	}
	
	/* page 0 is the last page */
	$page = ($page == 0) ? ceil($board->total / 25) : $page;
	$start = ($page - 1) * 25 + 1;

	$ret = $board->get_post_list($start, 25, 0);
	$prev = $page > 1 ? $page - 1 : false;
	$next = $page * 25 < $board->total ? $page + 1 : false;
	
	foreach($ret->list as &$post) {
		$post->update = show_last_time($post->update);
		$post->unread = $post->unread == 1 ? 0 : 1;
	}
	$data = array(
		"board" => $board,
		"plist" => $ret->list,
		"prev" => $prev,
		"next" => $next,
		);

	$tpl->loadTemplate('mobile/m_listpost.html');
	echo $tpl->render($data);
}

function m_new_post($bname) {
	global $user;
	global $tpl;
	$board = new Board($bname);
	if ($user->islogin() == false) {
		$tpl->set(array("msg" => "请先登陆！"));
		return m_list_posts($board->filename);
	}

	if ($user->has_post_perm($board) == false) {
		$tpl->set(array("msg" => '无权发表文章！'));
		return m_list_posts($board->filename);
	}
	
	if ($_SERVER["REQUEST_METHOD"] == "GET") {
		$tpl->loadTemplate('mobile/m_newpost.html');
		echo $tpl->render(array("board" => $board));
		return;
	}
	
	if (!isset($_POST["title"]) || !isset($_POST["content"])) {
		m_exit("参数不正确！");
	}

	/* Ban out campus ip */
	if (etc_check_outcampus_ip($_SERVER["REMOTE_ADDR"])) {
		m_exit("系统维护中，校外IP将暂停发贴功能 ");	
		return ;
	}

	$title = trim($_POST['title']);
	$title = preg_replace('/[\x00-\x1F\x7F]/', ' ', $title);
	if ($title == '') {
		$msg = '标题不能为空！';
		$tpl->loadTemplate('mobile/m_newpost.html');
		echo $tpl->render(array("board" => $board));
		return;
	}

	if (etc_keyword_check($_POST['content']) || etc_keyword_check($title)) {
		$msg = '文章含有不合适的内容，请重新编辑！';
		$tpl->loadTemplate('mobile/m_newpost.html');
		echo $tpl->render(array("board" => $board));
		return;
	}

	$res = $board->new_post($user, $title, $_POST['content']);
	$msg = $res ? '发表成功！' : '发表失败！';
	$tpl->set(array("msg" => $msg));
	return m_list_posts($board->filename);
}
?>
