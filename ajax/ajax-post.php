<?php
require_once("common/functions.php");
require_once("common/config.php");

require_once('include/setup.php');
        
/*
 * Get post list. According to request type (normal | digest | topic)
 * @param $start = 0, start from the end
 *        $type = normal | digest | topic
 *        $limit = 20
 *
 * @return {success: "1", data: [{<post object>}, {}..]},
 *         {success: "", error: "..."}
 */
function ajax_getpost_list() {
	global $user;
    
    ajax_assert_param($_GET, array("type", "boardname"));    
    $type = $_GET["type"];
    $boardname = $_GET['boardname'];
    $start = intval(isset($_GET['start']) ? $_GET['start'] : 0);

    $arr = array("normal" => 0, "digest" => 1, "topic" => 2);
    if (!isset($arr[$type])) ajax_error("Request type should be in 'normal | digest | topic'", 103);
    $type = $arr[$type];

    $board = new Board($boardname);
    ajax_assert($board, "Board not exists", 401);

    /* permission */
    ajax_assert($user->has_read_perm($board), "Permission deny.", 503);

    /* number list per page */
    $list_num = intval(isset($_GET['limit']) ? $_GET['limit'] : 20);
   
    $ret = $board->get_post_list($start, $list_num, $type);
    
    /*
	$start = $start ? $start : $ret->total - $list_num+1;
	if($start <= 0)  $start = 1;
    
	$prev = $start - $list_num;
	if ($prev <= 0) $prev=1;    
	$next = $start + $list_num;
    
    */

	if(!is_object($ret)||!count($ret->list))
    {
        //ajax_error_code(1, "Overflow index.");
        ajax_error("Overflow index", 504);
    }

    $summarys = array();
    if($type == 2){
        if(count($ret->list)){
            $buf = array();
            foreach($ret->list as &$post){
                $buf[] = '"' . $post->filename . '"';
            }
            $buf = implode(",", $buf);
            $sql = "SELECT filename, summary FROM `Post` WHERE boardname=%s AND filename IN ($buf);";
            $buf = DB::query($sql, $boardname);
            foreach($buf as &$b){
                $summarys[$b['filename']] = $b['summary'];
            }
        }
    }
    if(count($ret->list) > 0) {
        foreach($ret->list as &$post) {
            /* G.12345457.A  ==> M.1234567.A */
            if ($type == 1) 
                $post->filename = str_replace("G", "M", $post->filename);
            $post->mark = '';
            if ($post->flag & FILE_DIGEST) $post->mark .= 'g';
            if ($post->flag & FILE_MARKED) $post->mark .= 'm';
            if ($post->flag & FILE_ATTACHED) $post->mark .= '@';
            if ($post->flag & FILE_NOREPLY) $post->mark .= 'x';
            // unset realowner
            unset($post->realowner);
        }
	}
    unset($board->level); 
    unset($board->flag);

    $list = gbk2utf8($ret->list);
    if(($type == 2)){
        foreach($list as &$post){
            $post["summary"] = @$summarys[$post["filename"]];
        }
    }

    ajax_success_utf8($list);
    return ;
}

/*
 * Add a post.  POST method.
 * @param 
 *  - meta info:
 *        $type = new | reply | update
 *        $boardname
 *        $articleid (need by reply or update, is the post's filename)
 *        $reply_notify
 *        $attach
 *
 *  - content info:
 *        $title
 *        $content
 *
 * @return 
 *
 */
function ajax_addpost() 
{
	global $user;

    ajax_assert_login();
    ajax_assert_POST();
    ajax_assert_param($_POST, array("boardname", "title", "content", "type"));

    /* if(defined('SYSU_IP_LIST')){ */
    /*     if(!check_outcampus_ip()){ */
    /*         ajax_error('Special period, out ip cannot send post.', 405); */
    /*         return; */
    /*     } */
    /* } */

    $board = new Board($_POST["boardname"]);
    
    ajax_assert(($board && $board->is_vail()), "Permission deny", 402);
    ajax_assert($user->has_post_perm($board), "Permission deny", 402);
      
    /* Check frequency */ 
    $www = $user->www();
    if(isset($www['lastpost']) && time(null) - intval($www['lastpost']) <= MIN_POST_INTERVAL)  { //防刷版。。。
        $www['lastpost'] = strval(time(null));
        ext_set_www($user->userid(), $www);
        ajax_error("Toooooo+ fast.", 506);
        return ;
    }
    $www['lastpost'] = strval(time(null));
    ext_set_www($user->userid(), $www);
    
    $type = $_POST["type"];
    
    $articleid = "";
    if($type == "reply") {
        ajax_assert_param($_POST, array("articleid"));
        $articleid = $_POST["articleid"];

        $fh = ext_getfileheader($board->filename, $articleid);
        if($fh->flag & FILE_NOREPLY) {
            ajax_error("Can not reply this post.", 507);
            return ;
        }
    } else if ($type == 'update') {
        ajax_assert_param($_POST, array("articleid"));
        $articleid = $_POST["articleid"];
    }

    $title = convert_title($_POST['title']);
    $content = conv2gbk($_POST['content']);

    /* 处理签名档 */
    /* RANDOM SIGNATURE ? */
    $signature = get_signature();

    $attach_ok = false;

    /* 处理附件 */
    if (isset($_FILES["attach"])) {
        $attach_ok = check_attach($board);
        if ($attach_ok == false){
            ajax_error("Upload attach failed.", 508);
            return;
        }
        if (4 === $attach_ok)  $attach_ok = false;
    }
    else
    {
        $attach_ok = false;
    }
    
    /* 匿名版 anonymous表示发帖者是否想匿名  */
    /* CHECK IF IS REALLY IS AN ANONYMOUS BOARD ?!! */
    if(isset($_POST['anonymous']) && $_POST['anonymous'] == "1") 
        $anony = 1;
    else 
        $anony = 0;

    /*  回复提醒 */
    if(isset($_POST['reply-notify']) && $_POST['reply-notify'] == "1") 
        $reply_notify = 1;
    else 
        $reply_notify = 0;
    
    // $res 为更新/发表之后的文件名 M.123423525.A
    if($type == 'update') {
        if ($attach_ok) $attach = $_FILES["attach"];
        else $attach = array();

        $res = $board->edit_post($user, $title, $content, $articleid,
                                 $signature, $anony, $reply_notify, 
                                 $attach);
    } else {
        /* 若articleid为空串则是发新帖，否则为回复  $articleid="M.123456789.A" */
        if ($attach_ok) $attach = $_FILES["attach"];
        else $attach = array();

        $res = $board->new_post($user, $title, $content, $articleid,
                                $signature, $anony, $reply_notify,
                                $attach);
    }
    
    // TODO: 计划重新设计消息通知, using mysql
    
    /* 处理@请求 ，先把引用部分清掉，然后抽取新内容中@uerid 的部分，然后再通知用户 */
    //$matches = array();
    //$newcontent = preg_replace('/:\s.*/', ' ', $content);    
    //preg_match_all('/@([a-zA-Z]{2,12})/', $newcontent , $matches);
    //do_atuser($matches[1], $board->filename, $res, "@"); 

    /* 若回复的文章是需要提醒的，则发@提醒之~ */
    //if($type == "reply")  {
    //        if($fh->flag & FILE_REPLYNOTIFY) {
    //            do_atuser(array($fh->realowner), $board->filename, $res, "r");
    //        }
    //}
    
    if ($res) ajax_success($res);
    else ajax_error("Post failed.", 509);

	return ;
	
}

/*
 * Delete a post. Using POST method.
 * @return {success: "1", data: "..."}
 *         {success: "", error: "..."}
 */
function ajax_delpost()
{
	global $user;
    
    ajax_assert_login(); 
    ajax_assert_POST();
    ajax_assert_param($_POST, array("boardname", "filename"));

    $boardname = $_POST["boardname"];
    $filename = $_POST["filename"];

	$board=new Board($boardname);

    $res = $board->delete_post($filename);

    if ($res) ajax_success("Delete succeed.");
    else ajax_error("Delete failed", 510);
    return ;
}

/* 
 * Get a post.
 * @param $bname: boardname
 *        $filename: M.xxxxxxx.A
 * @return  {success: "1", data: { filename: "...", title: "...", ...}
 *          {success: "",  error: "..."}
 */
function ajax_getpost() {
	global $user;
    
    ajax_assert_param($_GET, array("boardname", "filename"));
    $boardname = $_GET["boardname"];
    $filename = $_GET["filename"];

	$user->set_stat(STAT_READING);
    $board = new Board($boardname);

    ajax_assert($user->has_read_perm($board), "Permission deny", 402);
    
	$post=new Post($boardname,$filename);    
	if($post->userid == "" )     {
		$ah = ext_getfileheader($boardname, $filename);
		if($ah) {
            $urec = ext_get_urec($ah->owner);
			$post->userid = $ah->owner;
			$post->board = $boardname;

            
			$post->username = $urec['username'];
            // May remove this line
            $post->post_time = $ah->filetime;
            $post->title = $ah->title;
        } else {
            ajax_error("Post not exists.", 505);
			return ;
		}
    }
    
    $post->address = get_address(get_ip_from_lastline($post->rawsignature));
    
    unset($post->content);    
    unset($post->signature);

    $post->post_time = $post->post_time;

    /* 根据是否有删贴/改帖权力来显示按钮 */
    $realowner = $post->userid;
    if($board->flag & ANONY_FLAG)  //匿名则要判断是否是真正的发贴人
    {
        $ah = ext_getfileheader($boardname, $filename);
        $realowner = $ah->realowner;
    }
    $has_bm_perm = $user->has_BM_perm($boardname);
	if($has_bm_perm || $user->userid() == $realowner) $post->perm_del = 1;
	else $post->perm_del = 0;

    ext_mark_read($user->userid(), $boardname, $filename);

    if($post->rawcontent == ''){
        $text = file_get_contents(BBSHOME . '/boards/' . $boardname . '/' . $filename);
        $post->rawcontent = implode("\n", array_slice(explode("\n", $text), 4));
        $post->rawsignature = '';
    }
    
	ajax_success($post);
	return;
}

/* 
 * 返回这篇文章的上一篇/下一篇文章的filename 
 * 根据$direction = prev / next 决定
 * @return {success: "1", data: "M.2435435465.A" }
 *         {success: "", error : ""}
 */
function ajax_nearpost_name()
{
    global $user;
    ajax_assert_param($_GET, array("direction", "boardname", "filename"));
    
    $direction = $_GET["direction"];
    $boardname = $_GET["boardname"];
    $filename = $_GET["filename"];
    ajax_assert($direction == "prev" || $direction == "next", 
                "Direction should be prev | next", 103);

    ajax_assert_board($boardname); 
    ajax_assert_filename($filename); 

    $board = new Board($boardname);

    if (!$user->has_read_perm($board))
        ajax_error("Permission deny", 402);

    $fh = ext_getfileheader($boardname, $filename);
    if(!$fh || ($direction == "prev" && $fh->index == 1)) {
        ajax_error('Post not exists', 505);
        return ;
    }

    if ($direction == 'prev') $offset = -1;
    else $offset = 1;
    $ret = $board->get_post_list($fh->index + $offset, 1, 0);
    
    if(!$ret || $ret->total == 0) {
        ajax_error('Post not exists', 505);
        return ;
    }
    ajax_success($ret->list[0]->filename);
    return ;
}

/* 
 * 和filename同主题文章列表
 * @param $boardname  $filename
 * @return {success: "1", data: ["M.12324788.A", ...]}
 */
function ajax_topiclist_name()
{
	global $user;
    
    ajax_assert_param($_GET, array("boardname", "filename"));

    $boardname = $_GET["boardname"];
    $filename = $_GET["filename"];
    ajax_assert_board($boardname); 
    ajax_assert_filename($filename);

    $board = new Board($boardname);

    ajax_assert($user->has_read_perm($board), "Permission Denied.", 402);
    
	$files = ext_gettopicfiles($boardname, $filename);

    if(!$files)
    {
        ajax_error("Post not exists.", 505);
        return;
    }
    $arr = array();
    
    foreach($files as &$file) {
        array_push($arr, $file->filename);
    }
    
    ajax_success($arr);
    return ;
}

function ajax_get_topic_posts()
{
	global $user;
    
    ajax_assert_param($_GET, array("boardname", "filename"));

    $boardname = $_GET["boardname"];
    $filename = $_GET["filename"];
    ajax_assert_board($boardname); 
    ajax_assert_filename($filename);

    $board = new Board($boardname);

    ajax_assert($user->has_read_perm($board), "Permission Denied.", 402);
    
	$files = ext_gettopicfiles($boardname, $filename);

    if(!$files)
    {
        ajax_error("Post not exists.", 505);
        return;
    }
    $arr = array();

    $user->set_stat(STAT_READING);
    
    foreach($files as &$file) {
        $filename = $file->filename;
        $post=new Post($boardname,$filename);    
        if($post->userid == "" )     {
            $ah = ext_getfileheader($boardname, $filename);
            if($ah) {
                $urec = ext_get_urec($ah->owner);
                $post->userid = $ah->owner;
                $post->board = $boardname;

            
                $post->username = $urec['username'];            
                $post->post_time = $ah->filetime;
                $post->title = $ah->title;
            } else {
                ajax_error("Post not exists.", 505);
                return ;
            }
        }
    
        unset($post->content);
        unset($post->signature);

        $post->post_time = $post->post_time;

        /* 根据是否有删贴/改帖权力来显示按钮 */
        $realowner = $post->userid;
        if($board->flag & ANONY_FLAG)  //匿名则要判断是否是真正的发贴人
        {
            $ah = ext_getfileheader($boardname, $filename);
            $realowner = $ah->realowner;
        }
        $has_bm_perm = $user->has_BM_perm($boardname);
        if($has_bm_perm || $user->userid() == $realowner) $post->perm_del = 1;
        else $post->perm_del = 0;

        ext_mark_read($user->userid(), $boardname, $filename);

        array_push($arr, $post);
    }
    
    ajax_success($arr);
    return ;
}

?>
