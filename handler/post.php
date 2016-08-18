<?php

require_once('include/config.php');
require_once('include/utils.php');
require_once('include/class-session.php');
//require_once('include/class-router.php');
require_once('include/meekrodb.php');
require_once('include/class-manager.php');
require_once('include/class-board.php');
require_once('include/class-section.php');
require_once('include/class-post.php');

DB::$dbName = 'bbs';
DB::$user = 'bbs';
DB::$password = 'To0Late$';
DB::$host = 'localhost';
DB::$encoding = 'utf-8';

function api_topicinfo()
{
	json_assert_param($_GET, 'filename', 'boardname', 'topicid');
	    $user = UserSession::get_cookie_user();
    $pmc = new PostManager($user->get_userid());
        json_success(array(
	'data' => $pmc->get_topicinfo($_GET['filename'],
				      $_GET['boardname'],
				      $_GET['topicid'])
				      ));
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
    
    ajax_assert(($board && $board->is_vail()), "Permission deny 65", 402);
    ajax_assert($user->has_post_perm($board), "Permission deny 66", 402);
      
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
    //preg_match_all('/@([a-zA-Z]{2,20})/', $newcontent , $matches);
    //do_atuser($matches[1], $board->filename, $res, "@"); 

    /* 若回复的文章是需要提醒的，则发@提醒之~ */
    /* if($type == "reply")  { */
    /*     do_atuser(array($fh->realowner), $board->filename, $res, "r"); */
    /* } */
    
    if ($res) {
        $lpmc = new LegacyPostManage(UserSession::get_cookie_user());
        if($type == "new")
        {
            $lpmc->sync_new($res, $board->filename);
        }
        else if($type == "reply")
        {
            $lpmc->sync_reply($res, $board->filename, $articleid);
        }
        else if($type == "update")
        {
            if(isset($_POST['content']))
            {
                $lpmc->sync_update($articleid, $board->filename,
                                   "content", null);
            }
            if(isset($_POST['title']))
            {
                $lpmc->sync_update($articleid, $board->filename,
                                   "title", null);
            }
        }
        ajax_success($res);
    }
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

    if(!$board || !$board->is_vail() || !isset($board->filename)){
        ajax_error('No such board', 511);
        return;
    }        
    
    $res = $board->delete_post($filename);

    if ($res) {
        $lpmc = new LegacyPostManage(UserSession::get_cookie_user());
        $lpmc->sync_cancel($filename, $board->filename);
        $lpmc->sync_delete($filename, $board->filename);
        ajax_success("Delete succeed.");
    }
    else ajax_error("Delete failed", 510);
    return ;
}


function api_get_post_list()
{
    json_assert_param($_GET,'boardname');
    $boardname = $_GET['boardname'];
    if (isset($_GET['cursor']))
        $cursor = intval($_GET['cursor']);
    else
        $cursor = NULL;
    if(isset($_GET['inverse']) && $_GET['inverse'] == 1)
        $order = " ";
    else
        $order = "desc";
    $filter = intval(isset($_GET['_filter']) ? $_GET['_filter'] : 0);
    $limit = intval(isset($_GET['_limit']) ? $_GET['_limit'] : 20);

    $user = UserSession::get_cookie_user();
    $pmc = new PostManager($user->get_userid());

    /**
     * TODO User permission judgment
     *      boardname exist or not
     *      nextpage and prev_page
     */

    $post_list = $pmc->get_post_list($boardname,$cursor,$limit,$order,$filter);
    $count = count($post_list);
    json_success(array(
        'cursor' => $cursor,
        'boardname' => $boardname,
        'count' => $count,
        'items' => $post_list,
        '_nextpage' => '',
        '_prevpage' => '',));
}

function api_get_topic_list()
{
    $user = UserSession::get_cookie_user();
    $pmc = new PostManager($user->get_userid());

    if (isset($_GET['cursor']))
        $cursor = intval($_GET['cursor']);
    else
        $cursor = NULL;
    json_assert_param($_GET,'boardname');
    $boardname = $_GET['boardname'];
    if(isset($_GET['inverse']) && $_GET['inverse'] == 1)
        $order = " ";
    else
        $order = "desc";
    $limit = intval(isset($_GET['_limit']) ? $_GET['_limit'] : 20);

    $topic_list = $pmc->get_topic_list($boardname,$cursor,$limit,$order);
    $count = count($topic_list);
    json_success(array(
        'cursor' => $cursor,
        'boardname' => $boardname,
        'count' => $count,
        'items' => $topic_list,
        '_nextpage' => '',
        '_prevpage' => '',));

}

function api_get_post_in_topic()
{
    $user = UserSession::get_cookie_user();
    $pmc = new PostManager($user->get_userid());

    $cursor = intval(isset($_GET['cursor']) ? $_GET['cursor'] : 0);
    $limit = intval(isset($_GET['_limit']) ? $_GET['_limit'] : 32);
    $topicid = intval(isset($_GET['topicid']) ? $_GET['topicid'] : NULL);
    if($topicid == NULL)
    {
        json_assert_param($_GET,'postid');
        $postid = $_GET['postid'];
        $topicid = $pmc->get_topicid_by_postid($postid);
    }
    $boardname = $pmc->get_boardname_by_topicid($topicid);

    /**
     * TODO User permission judgment
     *      boardname exist or not
     *      nextpage and prev_page
     *      _showtopic
     */

    $post_list = $pmc->get_post_in_topic($topicid,$cursor,$limit);
    $count = count($post_list);
    json_success(array(
        'boardname' => $boardname,
        'count' => $count,
        'items' => $post_list,
        '_nextpage' => '',
        '_prevpage' => '',));

}

function api_get_my_part_topic()
{
    $user = UserSession::get_cookie_user();
    $pmc = new PostManager($user->get_userid());

    $cursor = intval(isset($_GET['cursor']) ? $_GET['cursor'] : 0);
    $limit = intval(isset($_GET['_limit']) ? $_GET['_limit'] : 32);
    $topic_list = $pmc->get_my_part_topic($user->get_userid());

    $count = count($topic_list);
    json_success(array(
        'cursor' => $cursor,
        'count' => $count,
        'items' => $topic_list,
        '_nextpage' => '',
        '_prevpage' => '',));

}

function filter_top_fresh_post($p)
{
    global $user;
    $board = ext_board_header($p['boardname']);
    return !(($board->flag & JUNK_FLAG) >= JUNK_FLAG)
        && $user->has_read_perm($board);
}


function api_board_topic()
{
    ajax_assert_param($_GET, array("boardname"));
    $boardname = $_GET['boardname'];
    $board = ext_board_header($boardname);
    global $user;
    if(!$user->has_read_perm($board)){
        ajax_error('No such board');
    }    
    $user = UserSession::get_cookie_user();
    $pmc = new PostManager($user->get_userid());
    $cursor = intval(isset($_GET['cursor']) ? $_GET['cursor'] : 0);
    $limit = intval(isset($_GET['_limit']) ? $_GET['_limit'] : 32);
    $top_topic = $pmc->get_post_by_boardname($boardname,$cursor,$limit);
    $count = count($top_topic);

    json_success(array(
        'cursor' => $cursor,
        'count' => $count,
        'items' => $top_topic,
        '_nextpage' => '',
        '_prevpage' => '',
    ));
}

function api_get_top_score_topic()
{
    $user = UserSession::get_cookie_user();
    $pmc = new PostManager($user->get_userid());
    $cursor = intval(isset($_GET['cursor']) ? $_GET['cursor'] : 0);
    $limit = intval(isset($_GET['_limit']) ? $_GET['_limit'] : 32);
    $top_topic = $pmc->get_top_score_topic($cursor,$limit);
    $count = count($top_topic);

    if($top_topic)
    {
        $top_topic = array_filter($top_topic, "filter_top_fresh_post");
    }

    json_success(array(
        'cursor' => $cursor,
        'count' => $count,
        'items' => $top_topic,
        '_nextpage' => '',
        '_prevpage' => '',
    ));
}

function api_vote_topic()
{
    $user = UserSession::get_cookie_user();
    $pmc = new PostManager($user->get_userid());
    json_assert_login();
    json_assert_POST();
    json_assert_param($_POST,'topicid');
    $topicid = $_POST['topicid'];
    $status = $pmc->vote_topic($user->get_userid(),$topicid);
    json_success(array(
        'status' => $status,
        'topicid' => $topicid,
    ));
}

