<?php
require_once("functions.php");

function a_allboards() {
    global $user;

    $allboards = ext_get_allboards();
    $retstr = "[";
    foreach($allboards as $board)
    {
        if($user->has_read_perm($board))  $retstr .= "'" . $board . "'," ;
    }
    $retstr .= "]"; 
    echo $retstr;
          
	return;
}

function a_read_post($bname, $filename) {
	global $tpl;
	global $user;

	$user->set_stat(STAT_READING);
	$board = new Board($bname);
	if ($user->has_read_perm($board) == false) {
		echo "Permission Denied";
		return;
	}

	$post=new Post($bname,$filename);    
	if($post->userid == "" )     {
		$ah = ext_getfileheader($bname, $filename);
		if($ah) {
            $urec = ext_get_urec($ah->owner);
			$post->userid = $ah->owner;
			$post->board = $bname;
			$post->board = $bname;
			$post->username = $urec['username'];            
			$post->post_time = show_last_time($ah->filetime);
			$post->rawcontent = $post->content = ext_read_post($bname, $filename);
			$post->title = $ah->title;
		} else {
			echo "文章不存在";
			return ;
		}
	}
    $post->post_time = show_last_time($post->post_time);

        /* 根据是否有删贴/改帖权力来显示按钮 */
    $realowner = $post->userid;
    if($board->flag & ANONY_FLAG)  //匿名则要判断是否是真正的发贴人
    {
        $ah = ext_getfileheader($bname, $filename);
        $realowner = $ah->realowner;
    }
    $has_bm_perm = $user->has_BM_perm($bname);
	if($has_bm_perm || $user->userid() == $realowner) $perm_del = 1;
	else $perm_del = 0;
                                                                           
	$tpl->loadTemplate('ajax/post_entry.html');
	
	$nodot_id=str_replace(".","-",$filename); /* M-123456789-A ，删帖回帖使用的标签id*/

        /* 头像 */
	$myface = get_myface($post->userid); /* return {userid} */
	if($myface)  $post->myface = '/attach/' . $post->userid . '/' . $myface;
    
        /* 分享内容的摘要 */
        /*if(strlen($post->rawcontent) > 0) $content_digest = "";
	else {
		$content_digest = $post->userid . '(' . $post->username .  ')：' . $post->rawcontent;
		$content_digest = preg_replace('/[\x00-\x1F\x7F]/', ' ', $content_digest);
		$content_digest = htmlspecialchars_decode($content_digest);
		$content_digest = preg_replace('["]', '\"', $content_digest);
        }    */
	ext_mark_read($user->userid(), $bname, $filename);
	echo $tpl->render(array('post' =>$post,
                            'board' => $bname,
                            'articleid' => $filename,
                            'nodot_id' => $nodot_id,
                            'post_title' => $post->title,
                                /*'content_digest' => $content_digest, */
                            'perm_del'  => $perm_del,
                            'has_bm_perm' =>$has_bm_perm,
                            'login' => $user->islogin()
                            ));
	return;
}

//返回这篇文章的下一篇文章的filename
function a_next_post($boardname, $filename)
{
    $fh = ext_getfileheader($boardname, $filename);
    if(!$fh) {
        echo "null";
        return ;
    }
    
    $board = new Board($boardname);
    $ret = $board->get_post_list($fh->index+1, 1, 0); //获取下一篇
    
    if(!$ret || $ret->total == 0) {
        echo "null";
        return ;
    }
    echo $ret->list[0]->filename;
}

//返回这篇文章的上一篇文章的filename
function a_prev_post($boardname, $filename)
{
    $fh = ext_getfileheader($boardname, $filename);
    if(!$fh || $fh->index == 1) {
        echo "null";
        return ;
    }

    $board = new Board($boardname);
    $ret = $board->get_post_list($fh->index-1, 1, 0); //获取上一篇
    
    if(!$ret || $ret->total == 0) {
        echo "null";
        return ;
    }
    echo $ret->list[0]->filename;
}

function a_topic_list($boardname, $filename)
{
	global $user;

	$board = new Board($boardname);
	if ($user->has_read_perm($board) == false) {
		echo "Permission Denied";
		return;
	}
    
	$files = ext_gettopicfiles($boardname, $filename);

	$msg = "";
	$users = "";
	foreach($files as &$file) {
		if($users != "") $users .= ":" . $file->userid;
		else $users .= $file->userid;
		if($msg !="") $msg .= ":" . $file->filename;
		else $msg .= $file->filename;
	}
	echo $msg . "&" . $users;
}

function a_addfav($boardname)
{
	global $user;
	if (!$user->islogin()) {
		echo "请先登陆";
		return;
	}

	$board = new Board($boardname);
	if (!$board || $user->has_read_perm($board) == false) {
		echo "版块不存在";
		return;
	}
	
	$boards = Board::boards_from_fav();
	$boards = array_filter($boards, "board_perm_filter");

	if (count($boards) > 50) {
		echo "超过最大预定数量(<=50)";
		return;
	}

	$fav_boards[] = array();
        foreach ($boards as $b) {
		if ($b->filename == $board->filename) {
			echo "你已经收藏此版块";
			return;
		}
		$fav_boards[] = $b->filename;
        }
	
	$fav_boards[] = $board->filename;
	
        //$res = Board::change_fav_boards($fav_boards);
    $res = ext_addfavboards($user->userid(), $boardname);
	echo $res ? '收藏成功' : '收藏失败';
}

function a_delfav($boardname)
{
	global $user;
	if (!$user->islogin()) {
		echo "请先登陆";
		return;
	}

	$board = new Board($boardname);
	if (!$board || $user->has_read_perm($board) == false) {
		echo "版块不存在";
		return;
	}

    $res = ext_delfavboards($user->userid(), $boardname);
    echo $res ? '删除成功' : '删除失败';
}
?>
