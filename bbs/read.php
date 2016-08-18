<?php

function read_post($boardname, $filename) {
	global $tpl;
	global $user;
	$board = new Board($boardname);
	if ($user->has_read_perm($board) == false) {
		return;
	}
        //$files = ext_gettopicfiles($board->filename, $filename);    /* 返回obj的数组 */
	$fh = ext_getfileheader($board->filename, $filename);
    $files = array();
    if(isset($fh)) {
        $file->filename = $fh->filename;
        $file->userid = $fh->owner;
        $files []=$file;
    }
     
	$tpl->loadTemplate('standard/read_post_all.html');
	echo $tpl->render(array("board" => $board,
                            "files" => $files,
                            "exist" => count($files) > 0,
                            "post_title" => isset($fh)?$fh->title : '',
                            "islogin" => $user->islogin() ? '1' : '0',
                            "www" => $user->www()));
	return;
}

function read_digest($boardname, $start) {
	global $user;
	$board = new Board($boardname);
	if ($user->has_read_perm($board) == false) {
		return;
	}
	echo $board->read_post_digest($start);
}

/* fixme or not? 载入文章列表后再ajax得到文章内容，不利于搜索引擎优化。 */
function read_topic($boardname, $filename) {    
	global $tpl;
	global $user;
	$board = new Board($boardname);
	if ($user->has_read_perm($board) == false) {
		return;
	}
	$files = ext_gettopicfiles($board->filename, $filename);    /* 返回obj的数组 */
	$post_title = "";
	if($files[count($files)-1]) {
		$ah = ext_getfileheader($board->filename, $files[count($files)-1]->filename);
		$post_title = $ah->title;
	}
	$tpl->loadTemplate('standard/read_post_all.html');
	echo $tpl->render(array("board" => $board,
                            "files" => $files,
                            "exist" => count($files) > 0,
                            "islogin" => $user->islogin() ? '1' : '0',
                            "post_title" => $post_title,
                            "www" => $user->www()));
}

?>
