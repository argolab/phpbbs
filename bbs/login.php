<?php
function login($message = "") {
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
					header('Location: /main/');
				}
				return;
			}
		}
		$tpl->set(array("msg" => "用户名或密码错误！"));
	}
	$tpl->loadTemplate('standard/login.html');
	echo $tpl->render(array("message" => $message));
}

function logout() {
	global $user;
	$user->logout();
	header("Location: /");
}
?>
