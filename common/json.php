<?php


function json_get_sections() {
	$secs = ext_getsections();
	echo json_encode(gbk2utf8($secs));
	return;
}

function json_get_boards($sec_code) {
	$all_boards = ext_getboards($sec_code);
	$boards = array_filter($all_boards, "board_perm_filter");
	$boards = array_values($boards); /* rebuild keys */
	echo json_encode(gbk2utf8($boards));
	return;
}

function json_get_mail($index) {
	global $user;
	if (!$user->islogin()) {
		echo "false";
		return;
	}
	$mail = ext_read_mail($user->userid(), $index, 1);
	echo json_encode(gbk2utf8($mail));
}

function json_get_quote_post($board, $filename) {
	global $user;

	/* if (!$user->has_post_perm($board)) { */
	/* 	echo "false"; */
	/* 	return; */
	/* } */
	$content = ext_quote_post($board, $filename);
	echo json_encode(gbk2utf8($content));
}

?>
