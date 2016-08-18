<?php

require_once("error.php");

function section($index=0) {
    global $user;

    $user->set_stat(STAT_SELECT);
    
	if (!is_numeric($index)) {
		trigger_error('param error', E_USER_ERROR);
		return;
	}
    
	if ($index > 9 || $index < 0) {
		$index = 0;
	}
	global $tpl;
	$secs = etc_section_list();
    
	$sec = $secs[$index]; 
   
	$boards = Board::boards_from_seccode($sec['seccode']);
	$boards = array_filter($boards, "board_perm_filter");
	$boards = array_values($boards); /* rebuild keys */
	foreach($boards as &$board) {
		$ret = $board->get_post_list($board->total, 1, 0);       
		if (isset($ret->list) && count($ret->list) > 0) {
			$board->lastpostfile = $ret->list[0]->title;
			$board->lastfilename = $ret->list[0]->filename;
			$board->lastauthor = $ret->list[0]->owner;
			$board->lastpost = date('o-m-d H:i', $board->lastpost);
		}
	}
    if($user->islogin()) {
        $www=$user->www();        
    } else $www = "";
	$tpl->loadTemplate('standard/section.html');
	echo $tpl->render(array('board_list'=>$boards,
                            'www' => $www));
}




?>
