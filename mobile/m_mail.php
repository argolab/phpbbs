<?php
require_once("m_misc.php");


function _m_send_mail() {
	global $user;
	global $tpl;
	
	if (!isset($_POST["title"]) || !isset($_POST["receiver"])
	    || !isset($_POST["content"])) {
		m_exit("参数不正确");
	}
	$title = trim($_POST['title']);
	$title = preg_replace('/[\x00-\x1F\x7F]/', ' ', $title);
	if ($title == '') $title = '没主题';
	$arg = array("title" => $title,
		     "from" => $user->userid(),
		     "to" => $_POST["receiver"],
		     "content" => $_POST["content"]);

	if (isset($_POST["articleid"])) {
		$arg["articleid"] = (int)$_POST["articleid"];
	}

	$res = ext_send_mail($arg);
	if ($res && isset($_POST["filename"])) {
		ext_mark_replied($user->userid(), $_POST["filename"]);
	}
	return $res;
}

function m_list_mail($start) {
	global $user;
	global $tpl;
	if (!$user->islogin()) {
		m_exit("未登陆。。");
	}

	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if ($user->hasperm(PERM_SENDMAIL) && _m_send_mail()) {
			$tpl->set(array('msg' => "寄信成功！"));
		} else {
			$tpl->set(array('msg' => "寄信失败！"));
		}
	}
	
	$total = ext_count_mail($user->userid());
	if ($start == null || $start > $total) {
		$start = $total > 20 ? $total - 20 : 0;
	}
	
	$mail_list = ext_list_mail($user->userid(), $start, 20);

	foreach ($mail_list as $mail) {
		/* timestamp to date */
		$t = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		if ($mail->filetime < $t) {
			$mail->filetime = date('M d', $mail->filetime);
		} else {
			$mail->filetime = date('H:i', $mail->filetime);
		}
	}
	$prev = $start > 0 ? ($start - 20 < 0 ? 0 : $start - 20) : -1;
	$next = $start + 20 < $total ? $start + 20 : -1;
	$tpl->loadTemplate("mobile/m_listmail.html");
	echo $tpl->render(array("mail" => $mail_list, "prev" => $prev, "next" => $next));
	return;
}

/* 回复收件箱中的第index封信件，index为null时为撰写新信件 */
function m_send_mail($index) {
	global $user;
	global $tpl;
	if (!$user->islogin()) {
		m_exit("未登陆。。");
	}
	if ($index == null) {
		$mail['reply'] = false;
	} else {
		$mail = ext_quote_mail($user->userid(), $index);
		if ($mail) {
			$mail['reply'] = true;
			if (strncmp($mail['title'], "Re: ", 4)) {
				$mail['title'] = 'Re: ' . $mail['title'];
			}
		} else {
			$mail = array();
			$mail['reply'] = false;
		}
	}

	$tpl->loadTemplate("mobile/m_sendmail.html");
	echo $tpl->render($mail);
	return;
}

function m_checkmail()
{
       global $user;
    if(!$user->islogin()) {
        echo "请先登录";
        return ;
    }
    
	if (ext_check_mail($user->userid())) {
		echo 'mail';
		return;
	}
    echo 0;
}
?>
