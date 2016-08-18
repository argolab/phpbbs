<?php
require_once('common/functions.php');


/*
 * Get mailbox information.
 *  - total: Total mail number
 *  - used_size: How much space used. (Bytes)
 *  - total_size: How large of the mail box
 *
 * @param none
 * @return {success: "1", data: {total: xxx, used_size: xxx, total_size: xxx}}
 *         {success: "", error: xxx}
 *
 */
function ajax_mailbox()
{
    global $user;
    ajax_assert_login();    
    
    $total = ext_count_mail($user->userid());
    $used_size = ext_used_mail_size($user->userid());
    $total_size = get_total_size();

    ajax_success(array("total" => $total, "used_size" => $used_size, "total_size" => $total_size));
    return ;
}

/*
 * Get the mailhead list 
 * @param $start : The start index of the mail head list. (For page, start from 1)
 * @return {success: "1", data: [<mail object>, ...] }
 *         {success: "", error: "..."}
 *
 * data[flag] : & 0 ==> is new mail
 *              > 31 ==> has replied
 */ 
function ajax_getmail_list()
{
    global $user;
    
    ajax_assert_login();
    ajax_assert_param($_GET, array("start"));
    
    $start = intval($_GET["start"]);
    
    $list_num = isset($_GET["limit"]) ? (intval($_GET["limit"])) : 20;
    if($list_num < 1 || $list_num > 20)
    {
        $list_num = 20;
    }
    
	$total = intval(ext_count_mail($user->userid()));
    
    if ($start > $total) {
        $start = $total - $list_num + 1;        
    }
    if($start <= 0) $start = 1;

    $mail_list = ext_list_mail($user->userid(), $start, $list_num);

    if($mail_list)
    {
        foreach($mail_list as &$mail){
            /* timestamp to date */
            $mail->filetime = show_last_time($mail->filetime);
            if ($mail->flag & MAIL_REPLY)
                $mail->reply = 1;
            else 
                $mail->reply = 0;
        }
    }
    
    //$prev = $start - $list_num;
    //if ($prev <= 0 ) $prev = 1;    
    //$next = $start + $list_num;
    //if ($next > $total)  $next = $start;
    
    ajax_success($mail_list);

    return ;
}

/*
 * Get the mail. 
 * @param $index is the order of the mail records. (e.g. 1, 2, 3 ..)
 * @return {success: "1", data: {"filename": "...", "title": "...", ...}}  //fileheader
 *         {success: "",  error: ".."}
 */
function ajax_getmail() 
{
    global $user;
    ajax_assert_login();
    ajax_assert_param($_GET, array("index"));
    $index = intval($_GET["index"]);
    
    $mail = ext_read_mail($user->userid(), $index-1, 0); // the 3rd param: 0 raw, 1 with html

    if($mail)
    {
        $header = ext_list_mail($user->userid(), $index, 1);
        $header = $header[0];
        /* timestamp to date */
        $header->filetime = show_last_time($header->filetime);
        if ($header->flag & MAIL_REPLY)
            $header->reply = 1;
        else 
            $header->reply = 0;
        $header->content = $mail;
        ajax_success($header);
    }
    else
        ajax_error("Mail not exists", 601);
    return ;
}

/*
 * Send a mail.
 * @parameter: title, content, receiver must exists
 *             articleid is optional. (The topic id of this mail, cuz mail is a 
 *             conversation.)
 * @return  {success: "1", data: "..."}
 *          {success: "", error: "..."}
 */
function ajax_sendmail() 
{
    global $user;
    ajax_assert_POST(); 
    ajax_assert_login();

    $param = $_POST; 
    ajax_assert_param($param, array("title", "receiver", "content"));

    $title = $_POST['title'];
    $content = $_POST['content'];
    
    $title = convert_title($title); 
    $content = conv2gbk($content);

    $arg = array("title" => $title,
        "from" => $user->userid(),
        "to" => $param["receiver"],
        "content" => $content);
    
    $touserid = $param["receiver"];
    $urec = ext_get_urec($touserid);
    if(!$urec)
    {
        ajax_error('No such user', 307);
        return;
    }
    $touserid = $urec['userid'];

    if (isset($param["articleid"])) {
        $arg["articleid"] = (int)$param["articleid"];
    }

    $used_size = ext_used_mail_size($touserid);
    $total_size = get_total_size();        
    if($used_size + strlen($content) + strlen($title) > $total_size) {
        ajax_error("Mail box is full.", 602);
        return ;
    }

    $path = BBSHOME . '/mail/' . substr(strtoupper($touserid), 0, 1) . '/' . $touserid;
    if(!file_exists($path))
    {
        mkdir($path, 0775);
    }
    $res = ext_send_mail($arg);
    if ($res && isset($arg["articleid"])) {
        ext_mark_replied_mail($user->userid(), $arg["articleid"]-1);
    }
    
    if ($res) ajax_success("Send succeed.");
    else ajax_error("Send failed.", 603);
    return ;
    
}

/* Send noname mail */
function ajax_noname_sendmail() 
{
    global $user;
    ajax_assert_POST();

    $param = $_POST; 
    ajax_assert_param($param, array("title", "receiver", "content"));

    if($param['receiver'] != 'gbtfm')
    {
        ajax_error('Cannot send mail to this id.');
        return;
    }

    if($user->islogin())
        $sender = $user->userid();
    else
        $sender = $param["receiver"];
    
    $title = $_POST['title'];
    $content = $_POST['content'];
    
    $title = convert_title($title); 
    $content = conv2gbk($content) . "\n\n\n\n [IP] " . $_SERVER['REMOTE_ADDR'];;

    $arg = array("title" => $title,
                 "from" => $sender,
                 "to" => $param["receiver"],
                 "content" => $content);

    $used_size = ext_used_mail_size($param["receiver"]);
    $total_size = get_total_size($param["receiver"]);

    if($used_size + strlen($content) + strlen($title) > $total_size) {
        ajax_error("Mail box is full.", 602);
        return ;
    }
    $res = ext_send_mail($arg);
    
    if ($res) ajax_success("1");
    else ajax_error("Send failed.", 603);
    return ;
}

/*
 * Del a mail. 
 * @param $indexes = array(a1, a2, ... an) is a list of the delete mail order.
 * @return {success: "1", data: ""}
 *         {success: "", error: ""}
 */
function ajax_delmail() {

	global $user;
    ajax_assert_POST();
    ajax_assert_login();
    ajax_assert_param($_POST, array("indexes"));

    if(is_string($_POST['indexes']))
        $_POST['indexes'] = explode(',', $_POST['indexes']);
    if(!is_array($_POST['indexes']))
        ajax_error('Delete failed. Wrong param type.');

    $res = ext_del_mail($user->userid(), $_POST['indexes']);

    if ($res)   ajax_success("Delete succeed.");
    else ajax_error("Delete failed.", 604);
}

/*
 * Check if has new mail.
 * @param:  none
 * @return {success: "1", data: 1 | 0}
 *
 */
function ajax_hasnewmail()
{

    global $user;
    ajax_assert_login();
    $ret = '000';
    if(ext_check_mail($user->userid())){
        $ret[0] = '1';
    }
    $list = ext_get_msglist($user->userid(), 0, 1);
    if(isset($list) && isset($list->list[0]) && !($list->list[0]->flag & FILE_READ)){
        $ret[1] = '1';
    }
    $bh = ext_getfavboards($user->userid());
    if($bh){
        foreach($bh as $b){
            $r = ext_is_read($user->userid(),
                             $b->filename,
                             array($b->lastpost));
            if(!$r[0]){
                $ret[2] = '1';
                break;
            }
        }
    }
    ajax_success($ret);
    return;
}

?>
