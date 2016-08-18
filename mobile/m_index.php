<?php

function m_index() {
	global $tpl;
	global $user;
	$top = etc_top_ten();
	if (!$user->islogin()) {
		$tpl->loadTemplate('mobile/m_welcome.html');
		echo $tpl->render();
		return;
	}
	$tpl->loadTemplate('mobile/m_index.html');
	echo $tpl->render(array("top" => $top));
}

?>
