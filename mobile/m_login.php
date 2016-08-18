<?php
function m_login() {
	global $tpl;
	global $user;
	if ($_SERVER['REQUEST_METHOD'] == "POST") {
		$username = trim($_POST["username"]);

		if (ctype_alpha($username)) {
			$password = $_POST["password"];

			$user->login($username, $password, true);
		
			if ($user->islogin()) {
				$user->set_stat(STAT_MOBILE);
				if (isset($_POST['ref']) && !strstr($_POST['ref'], 'login')) {
					header('Location: ' . $_POST['ref']);
				} else {
					header('Location: /m/');
				}
				return;
			}
		}
		$tpl->set(array("msg" => "用户名或密码错误！"));
	}
	if (isset($_SERVER['HTTP_REFERER'])) {
		$tpl->set(array('ref' => $_SERVER['HTTP_REFERER']));
	}
	$tpl->loadTemplate('mobile/m_login.html');
	echo $tpl->render();
}

function m_logout() {
	global $user;
	$user->logout();
	header("Location: /m/");
}
?>
