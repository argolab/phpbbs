<?php
require_once('common/functions.php');
require_once('common/etc.php');

function do_post($command, $board, $articleid) {
	global $user;

	if (!$user->islogin()) {
		echo "���ȵ�½";
		return;
	}    
	if (etc_check_outcampus_ip($_SERVER["REMOTE_ADDR"])) {
		echo "ϵͳά���У�У��IP����ͣ�������� ";	
		return ;
	}

    $www = $user->www();
    if(isset($www['lastpost']) && time(null) - intval($www['lastpost']) <=3)  { //��ˢ�档����
        echo "����ʱ�����(<=3s)��Ҫ�����term�ɣ�";
        $www['lastpost'] = strval(time(null));
        ext_set_www($user->userid(), $www);
        return ;
    }
    $www['lastpost'] = strval(time(null));
    ext_set_www($user->userid(), $www);
    
	if (!$user->has_post_perm($board)) {
		echo "����Ȩ�ڱ��淢������";
		return;
	}

	if ($_SERVER["REQUEST_METHOD"] != "POST" ||
	    !isset($_POST["title"]) || !isset($_POST["content"])) {
		echo "�������";
		return;
        }
    
    if($command == "reply") {
        $fh = ext_getfileheader($board->filename, $articleid);
        if($fh->flag & FILE_NOREPLY) {
            echo "�����²��ܻظ�";
            return ;
        }        
    }
	/* �� ajax post �ı���� utf-8 ת���� gbk */
	// mb_detect_encoding($_POST['title'], 'UTF-8', true) ?
    
	$title = preg_match('!\S!u', $_POST['title']) ?
		utf82gbk($_POST['title']) : $_POST['title'];
	
	$title = trim($title);
	$title = preg_replace('/[\x00-\x1F\x7F]/', ' ', $title);

	if ($title == '') {
		echo "���ⲻ��Ϊ��";
		return;
	}

	$content = preg_match('!\S!u', $_POST['content']) ?
		utf82gbk($_POST['content']) : $_POST['content'];
    
        /* ����ǩ���� */
    $signature = get_signature();
        /* ������ */    
    $attach_ok = check_attach($board);
    if(!$attach_ok) return ;
    if($attach_ok === 4) $attach_ok = 0;
        /* ������  */ 
    if(isset($_POST['anonymous']) && $_POST['anonymous'] == "on") {
        $anony = 1;
    } else $anony = 0;
        /*  �ظ����� */
    if(isset($_POST['reply-notify']) && $_POST['reply-notify'] == "on") {
        $reply_notify = 1;
    } else $reply_notify = 0;
    
    // $res Ϊ����/����֮����ļ��� M.123423525.A
    if($command == 'edit') {

        if($attach_ok) $attach = $_FILES["attach"];
        else $attach = array();

        $res = $board->edit_post($user, $title, $content, $articleid,
            $signature,
            $anony,
            $reply_notify,
            $attach);
        $msg = $res ? '�޸ĳɹ�' : '�޸�ʧ��';
    } else {
        /* ��articleidΪ�մ����Ƿ�����������Ϊ�ظ�  $articleid="M.123456789.A"*/

        if($attach_ok) $attach = $_FILES["attach"];
        else $attach = array();

        $res = $board->new_post($user, $title, $content, $articleid,
            $signature,
            $anony,
            $reply_notify,
            $attach);
        $msg = $res ? "����ɹ�" : '����ʧ��';
    }

        /* ����@gcc ���Ȱ����ò��������Ȼ���ȡ��������@uerid �Ĳ��֣�Ȼ����֪ͨ�û� */
    $matches = array();
    $newcontent = preg_replace('/:\s.*/', ' ', $content);    
    preg_match_all('/@([a-zA-Z]{2,12})/', $newcontent , $matches);
    do_atuser($matches[1], $board->filename, $res, "@"); 

        /* ���ظ�����������Ҫ���ѵģ���@����֮~ */
    if($command == "reply")  {
            if($fh->flag & FILE_REPLYNOTIFY) {
                do_atuser(array($fh->realowner), $board->filename, $res, "r");
            }
    }
	echo $msg;
	return;
	
}

function do_copy($boardname, $articleid)
{
    global $user;
    global $tpl;

    if (!$user->islogin()) {
		echo "���ȵ�½";
		return;
	}

    if(!isset($_POST['boardname'])) {
        echo "��������";
        return ;
    }
    
    if($_POST['boardname'] == "" || !ext_board_header($_POST['boardname']))
    {
        echo "û�����������";
        return ;            
    }
    
    $title = preg_match('!\S!u', $_POST['title']) ?
		utf82gbk($_POST['title']) : $_POST['title'];
        
    $title = trim($title);
	$title = preg_replace('/[\x00-\x1F\x7F]/', ' ', $title);
    
    $board = new Board($_POST['boardname']);
    
    if(!$user->has_read_perm($boardname)) {
        echo "����Ȩת�ر�����";
        return ;
    }
	if (!$user->has_post_perm($board)) {
		echo "����Ȩת�ص��ð�";
		return;
	}    
    
    $post = new Post($boardname, $articleid);
    if(! $post->userid) {
        echo "�޷�ת����ƪ����==b, �뵽BugReport�²�";
        return ;
    }
    
    if(strncmp($post->title,"[ת��]", 6))
        $post->title = "[ת��]" . $post->title;

    $post->rawcontent = "\033[1;37m�� ��������ת���� \033[32m $boardname \033[37m������ ��\n �� ԭ����\033[32m $post->userid \033[37m ������ ��\033[m\n\n" . $post->rawcontent;

    $res = $board->new_post($user,
                            $post->title,
                            $post->rawcontent,
                            "", //articleid
                            "",  //signature
                            array()); 
    
    echo  $res ? 'ת�سɹ�' : 'ת��ʧ��';
}

/*command=new/edit/reply/copy ������command��ͬ����ͬ���� */
function post_form($command, $boardname, $articleid = "") {
    global $user;
	global $tpl;

    if(!$user->islogin()) {
        echo "���ȵ�¼";
        return ;
    }
	$board = new Board($boardname);

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if($command == "copy") {
            do_copy($boardname, $articleid);
            return ;
        }
        do_post($command, $board, $articleid);
        return;
    }

    $user->set_stat(STAT_POSTING);
    if($command == "edit") { /* �޸����� */
        $post = new Post($boardname, $articleid);
		$title = $post->title;
		$quote = $post->rawcontent;
    } else if($command == "reply") { /*����ǻظ������������ģʽ*/
		$post = new Post($boardname, $articleid);
        if(substr($post->title, 0, 4) != "Re: ") $post->title = "Re: " . $post->title;
		$title = $post->title;
		$quote = "\n\n" . ext_quote_post($boardname, $articleid);
	} else if ($command == "new") {
		$title = "";
		$quote = "";
	} else if($command == "copy") { /* ת�� */
        $tpl->loadTemplate('standard/forms/copypost_form.html');
        echo $tpl->render(array('board' => $boardname,
                                                 'articleid' => $articleid));
        return ;
    }
	
	$tpl->loadTemplate('standard/forms/post_form.html');
	echo $tpl->render(
		array('board' => $board,
		      'title' => $title,
		      'content' => $quote,
		      'articleid' => $articleid,
              'allow_attach' => ($board->flag & BRD_ATTACH),// �ж�������Ƿ���ϴ��ļ�
              'anonymous' => $board->flag & ANONY_FLAG, //�ж��Ƿ���������
              'command' => $command 
              ));
	return;	
}

?>

