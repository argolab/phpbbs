<?php
require_once('common/functions.php');
require_once('common/etc.php');

function do_post($command, $board, $articleid) {
	global $user;

	if (!$user->islogin()) {
		echo "请先登陆";
		return;
	}    
	if (etc_check_outcampus_ip($_SERVER["REMOTE_ADDR"])) {
		echo "系统维护中，校外IP将暂停发贴功能 ";	
		return ;
	}

    $www = $user->www();
    if(isset($www['lastpost']) && time(null) - intval($www['lastpost']) <=3)  { //防刷版。。。
        echo "发贴时间过快(<=3s)。要快就用term吧！";
        $www['lastpost'] = strval(time(null));
        ext_set_www($user->userid(), $www);
        return ;
    }
    $www['lastpost'] = strval(time(null));
    ext_set_www($user->userid(), $www);
    
	if (!$user->has_post_perm($board)) {
		echo "你无权在本版发表文章";
		return;
	}

	if ($_SERVER["REQUEST_METHOD"] != "POST" ||
	    !isset($_POST["title"]) || !isset($_POST["content"])) {
		echo "请求错误";
		return;
        }
    
    if($command == "reply") {
        $fh = ext_getfileheader($board->filename, $articleid);
        if($fh->flag & FILE_NOREPLY) {
            echo "本文章不能回复";
            return ;
        }        
    }
	/* 把 ajax post 的编码从 utf-8 转换到 gbk */
	// mb_detect_encoding($_POST['title'], 'UTF-8', true) ?
    
	$title = preg_match('!\S!u', $_POST['title']) ?
		utf82gbk($_POST['title']) : $_POST['title'];
	
	$title = trim($title);
	$title = preg_replace('/[\x00-\x1F\x7F]/', ' ', $title);

	if ($title == '') {
		echo "标题不能为空";
		return;
	}

	$content = preg_match('!\S!u', $_POST['content']) ?
		utf82gbk($_POST['content']) : $_POST['content'];
    
        /* 处理签名档 */
    $signature = get_signature();
        /* 处理附件 */    
    $attach_ok = check_attach($board);
    if(!$attach_ok) return ;
    if($attach_ok === 4) $attach_ok = 0;
        /* 匿名版  */ 
    if(isset($_POST['anonymous']) && $_POST['anonymous'] == "on") {
        $anony = 1;
    } else $anony = 0;
        /*  回复提醒 */
    if(isset($_POST['reply-notify']) && $_POST['reply-notify'] == "on") {
        $reply_notify = 1;
    } else $reply_notify = 0;
    
    // $res 为更新/发表之后的文件名 M.123423525.A
    if($command == 'edit') {

        if($attach_ok) $attach = $_FILES["attach"];
        else $attach = array();

        $res = $board->edit_post($user, $title, $content, $articleid,
            $signature,
            $anony,
            $reply_notify,
            $attach);
        $msg = $res ? '修改成功' : '修改失败';
    } else {
        /* 若articleid为空串则是发新帖，否则为回复  $articleid="M.123456789.A"*/

        if($attach_ok) $attach = $_FILES["attach"];
        else $attach = array();

        $res = $board->new_post($user, $title, $content, $articleid,
            $signature,
            $anony,
            $reply_notify,
            $attach);
        $msg = $res ? "发表成功" : '发表失败';
    }

        /* 处理@gcc ，先把引用部分清掉，然后抽取新内容中@uerid 的部分，然后再通知用户 */
    $matches = array();
    $newcontent = preg_replace('/:\s.*/', ' ', $content);    
    preg_match_all('/@([a-zA-Z]{2,12})/', $newcontent , $matches);
    do_atuser($matches[1], $board->filename, $res, "@"); 

        /* 若回复的文章是需要提醒的，则发@提醒之~ */
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
		echo "请先登陆";
		return;
	}

    if(!isset($_POST['boardname'])) {
        echo "参数错误";
        return ;
    }
    
    if($_POST['boardname'] == "" || !ext_board_header($_POST['boardname']))
    {
        echo "没有这个版啦～";
        return ;            
    }
    
    $title = preg_match('!\S!u', $_POST['title']) ?
		utf82gbk($_POST['title']) : $_POST['title'];
        
    $title = trim($title);
	$title = preg_replace('/[\x00-\x1F\x7F]/', ' ', $title);
    
    $board = new Board($_POST['boardname']);
    
    if(!$user->has_read_perm($boardname)) {
        echo "你无权转载本文章";
        return ;
    }
	if (!$user->has_post_perm($board)) {
		echo "你无权转载到该版";
		return;
	}    
    
    $post = new Post($boardname, $articleid);
    if(! $post->userid) {
        echo "无法转载这篇文章==b, 请到BugReport吐槽";
        return ;
    }
    
    if(strncmp($post->title,"[转载]", 6))
        $post->title = "[转载]" . $post->title;

    $post->rawcontent = "\033[1;37m【 以下文字转载自 \033[32m $boardname \033[37m讨论区 】\n 【 原文由\033[32m $post->userid \033[37m 所发表 】\033[m\n\n" . $post->rawcontent;

    $res = $board->new_post($user,
                            $post->title,
                            $post->rawcontent,
                            "", //articleid
                            "",  //signature
                            array()); 
    
    echo  $res ? '转载成功' : '转载失败';
}

/*command=new/edit/reply/copy ，根据command不同做不同处理 */
function post_form($command, $boardname, $articleid = "") {
    global $user;
	global $tpl;

    if(!$user->islogin()) {
        echo "请先登录";
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
    if($command == "edit") { /* 修改文章 */
        $post = new Post($boardname, $articleid);
		$title = $post->title;
		$quote = $post->rawcontent;
    } else if($command == "reply") { /*如果是回复，则采用引用模式*/
		$post = new Post($boardname, $articleid);
        if(substr($post->title, 0, 4) != "Re: ") $post->title = "Re: " . $post->title;
		$title = $post->title;
		$quote = "\n\n" . ext_quote_post($boardname, $articleid);
	} else if ($command == "new") {
		$title = "";
		$quote = "";
	} else if($command == "copy") { /* 转贴 */
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
              'allow_attach' => ($board->flag & BRD_ATTACH),// 判断这个版是否可上传文件
              'anonymous' => $board->flag & ANONY_FLAG, //判断是否是匿名版
              'command' => $command 
              ));
	return;	
}

?>

