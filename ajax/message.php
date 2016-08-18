<?php

require_once("common/functions.php");
require_once("common/config.php");

function read_message()
{
    ajax_assert_login();
    $start = intval(isset($_GET['start']) ? $_GET['start'] : 0);
    
    global $user;
    global $tpl;

    $t_lines = 10;

    $res = ext_get_msglist($user->userid(), $start, $t_lines);

    if(isset($res)) {

        $start=$start ? $start : $res->total-$t_lines+1;
        if($start<=0)  $start=1;    
        $prev = $start - $t_lines;
        if($prev<=0) $prev=1;    
        $next = $start + $t_lines;
        if($next > $res->total)  $next=$start;
    
        if(count($res->list) > 0) {
            foreach($res->list as &$mh)
            {
                //$mh->when = show_last_time($mh->when);
                if($mh->flag & FILE_READ) $mh->unread = 0;
                else $mh->unread = 1;
                
                $myface = get_myface($mh->userid); /* return {userid} */
                if($myface)  $mh->myface = '/attach/' . $mh->userid . '/' . $myface;

                if($mh->type == "@") $mh->at = true;
                if($mh->type == "r") $mh->reply = true;
                if($mh->type == "f") $mh->friend = true;
                if($mh->type == "b") $mh->birthday = true;
            }
        }
        $list = array_reverse($res->list);
    } else {
        $list = array();
        $prev = 0;
        $next = 0;
    }
    ajax_success(array("mlist" => $list,
                       "prev" => $prev,
                       "next" => $next));
}


function mark_message_read()
{
    ajax_assert_login();
    $index = intval(isset($_POST['index']) ? $_POST['index'] : 0);
    
    global $user;

    if(ext_message_markread($user->userid(), intval($index))) {
        ajax_success("mark success");
    } else {
        ajax_error("mark fail", 0);
    }    
}
