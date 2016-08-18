<?php

function mailbox($start) {
	global $tpl;
	global $user;

    $user->set_stat(STAT_RMAIL);
	if (!$user->islogin()) {
		return;
	}

    $list_num = 20;
	$total = ext_count_mail($user->userid());
    $used_size = get_size(ext_used_mail_size($user->userid()));
    $total_size = get_size(get_total_size());
    
	if ($start == null || $start > $total) {
		$start = $total - $list_num + 1;        
	}
    if($start <=0) $start = 1;
	
	$mail_list = ext_list_mail($user->userid(), $start, $list_num);

	foreach ($mail_list as &$mail) {
		/* timestamp to date */
		$t = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		if ($mail->filetime < $t) {
			$mail->filetime = date('M d', $mail->filetime);
		} else {
			$mail->filetime = date('H:i', $mail->filetime);
		}
	}
    $prev = $start - $list_num;
    if($prev<=0) $prev=1;    
    $next = $start + $list_num;
    if($next > $total)  $next=$start;
        /*$prev = $start > 0 ? ($start - 20 < 0 ? 0 : $start - 20) : -1;
          $next = $start + 20 < $total ? $start + 20 : -1; */
	$tpl->loadTemplate("standard/list_mail.html");
	echo $tpl->render(
		array("total" => $total,
		      "from" => $start ,
		      "to" => $start + count($mail_list) - 1,
		      "mail" => $mail_list,
              "used_size" => $used_size,
              "total_size" => $total_size,
		      "prev" => $prev,
		      "next" => $next));
	return;
}

function delete_mail() {

	global $user;
	
	if (!$user->islogin()) {
		echo "���ȵ�½";
		return;
	}
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		echo "�������";
		return;
	}
	if (!isset($_POST['indexes']) || !is_array($_POST['indexes'])) {
		echo "��������";
		return;
	}

        /* ����ò����bug�� ���ɾ����ô.DIR�е�index����ı� */    
        /*foreach ($_POST['indexes'] as &$index) {
		if (!ext_del_mail($user->userid(), $index)) {
			echo "ɾ����" . $index . "���ż�ʧ�ܣ�ֹͣ";
			return;
		}
        }*/
    $res = ext_del_mail($user->userid(), $_POST['indexes']);
    
	echo $res ? "ɾ���ʼ��ɹ�" : "ɾ���ʼ�ʧ��"; 
}


function merge_mail() {

	echo "�˹���δ���";
	return;
}


function mark_as_read_mail() {
	global $user;
	
	if (!$user->islogin()) {
		echo "���ȵ�½";
		return;
	}
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		echo "�������";
		return;
	}
	if (!isset($_POST['indexes']) || !is_array($_POST['indexes'])) {
		echo "��������";
		return;
	}
	
	foreach ($_POST['indexes'] as &$index) {
		ext_mark_read_mail($user->userid(), $index);
	}
	
	echo "�����ɹ�";
	return;
}

function send_mail($index = "")
{
    global $user;
	global $tpl;

    if(!$user->islogin()) {
        echo "���ȵ�¼";
        return ;
    }
    
    $user->set_stat(STAT_SMAIL);
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        if (!isset($_POST["title"]) || !isset($_POST["receiver"])
            || !isset($_POST["content"])) {
            m_exit("��������ȷ");
        }
        
        $title = preg_match('!\S!u', $_POST['title']) ?
		utf82gbk($_POST['title']) : $_POST['title'];
                
        $title = trim($title);
        $title = preg_replace('/[\x00-\x1F\x7F]/', ' ', $title);
        if ($title == '') $title = 'û����';
        
        $content = preg_match('!\S!u', $_POST['content']) ?
		utf82gbk($_POST['content']) : $_POST['content'];
            
        $arg = array("title" => $title,
                     "from" => $user->userid(),
                     "to" => $_POST["receiver"],
                     "content" => $content);

        if (isset($_POST["articleid"])) {
            $arg["articleid"] = (int)$_POST["articleid"];
        }
        
            /* ����ʼ�������С */
        $used_size = ext_used_mail_size($user->userid());
        $total_size = get_total_size();        
        if($used_size + strlen($content) + strlen($title) > $total_size) {
            echo "�����������������Ͻ������^_^";
            return ;
        }
        $res = ext_send_mail($arg);
        if ($res && isset($_POST["filename"])) {
            ext_mark_replied($user->userid(), $_POST["filename"]);
        }
        
        echo $res ? "���ͳɹ�" : "����ʧ��";
        return ;
    } else {

        if($index != "") {
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
        } else {
            $mail = array();
        }

        $tpl->loadTemplate('standard/forms/sendmail_form.html');
        echo $tpl->render($mail);
    }
}

function read_mail($index)
{
    global $user;
	global $tpl;
	if (!$user->islogin()) {
		echo "���ȵ�½";
	}

    $user->set_stat(STAT_RMAIL);
    $content = ext_read_mail($user->userid(), $index, 1);

    $tpl->loadTemplate("standard/read_mail.html");
    echo $tpl->render(array("content" => $content,
                       "index" => $index));
	return;
}
?>
