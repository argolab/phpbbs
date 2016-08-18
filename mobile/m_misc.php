<?php

function m_exit($error_msg) {
	global $tpl;
	$tpl->loadTemplate('mobile/m_error.html');
	echo $tpl->render(array("msg" => "·ÃÎÊ³ö´í", "error" => $error_msg));
	exit;
}

function m_data() {
	global $tpl;
	$tpl->loadTemplate('mobile/m_data.html');
	echo $tpl->render();
}


function m_about() {
	global $tpl;
	$tpl->loadTemplate('mobile/m_about.html');
	echo $tpl->render();
}
?>
