<?php



function m_read_normal($boardname, $filename) {
	global $tpl;
	global $user;
	$board = new Board($boardname);
	if ($user->has_read_perm($board) == false) {
		m_exit("¶ÁÈ¡Ê§°Ü");
	}
	$files = ext_gettopicfiles($board->filename, $filename);    
	$tpl->loadTemplate('mobile/m_read.html');
	echo $tpl->render(array("board" => $board, "files" => $files));
}

function m_ajax_get($boardname, $filename) {
    global $tpl;
	global $user;
	$board = new Board($boardname);
	if ($user->has_read_perm($board) == false) {
		m_exit("¶ÁÈ¡Ê§°Ü");
	}
	$files = ext_read_post($board->filename, $filename);
    if($files) {
        ext_mark_read($user->userid(), $boardname, $filename);
    }
    echo $files;
}
?>
