<?php

function a_post_reply($board) {
	global $user;

	if (!$user->islogin()) {
		echo 'Not Logined';
		return;
	}
	if (!$user->has_post_perm($board))
	{
		echo 'Permission Denied';
		return;
	}
	if (!isset($_POST['title']) || !isset($_POST['content'])) {
		echo 'Invalid Arguments';
		return;
	}

	/* Ban out campus ip */
	if (etc_check_outcampus_ip($_SERVER["REMOTE_ADDR"])) {
		echo "系统维护中，校外IP将暂停发贴功能 ";
		return ;
	}

	$arg['title'] = trim(urldecode($_POST['title']));
	$arg['content'] = urldecode($_POST['content']);
	$arg['title'] = preg_replace('/[\x00-\x1F\x7F]/', ' ', $arg['title']);
	$arg = utf82gbk($arg);

	if (etc_keyword_check($arg['content']) || etc_keyword_check($arg['title'])) {
		echo 'Content Not Allowed';
		return;
	}

	if (isset($_POST['articleid'])) {
		$arg['articleid'] = 'M.' . $_POST['articleid'] . '.A';
	}
    $arg['userid'] = $user->userid();
	$arg['fromaddr'] = $user->from();
	$arg['board'] = $board;    
	
	$filename = ext_simplepost($arg);
	if ($filename) {
		ext_mark_read($user->userid(), $board, $filename);
		ext_update_lastpost($board);
        trace_report(" posted " . $board . " " . $filename);
		echo '1';
	}
	echo '0';
}

?>
