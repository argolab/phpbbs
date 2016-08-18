<?php
require_once("common/functions.php");

function read_message($start = 0)
{
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
                $mh->when = show_last_time($mh->when);
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
        $res->list = array_reverse($res->list);
    } else {
        $res->list = array();
        $prev = 0;
        $next = 0;
    }
    $tpl->loadTemplate("standard/list_message.html");
    echo $tpl->render(array("mlist" => $res->list,
                            "prev" => $prev,
                            "next" => $next));
}


function a_message_markread($index)
{
    global $user;
    
    if(!$user->islogin()) {
        echo "请先登录";
        return ;
    }

    if(ext_message_markread($user->userid(), intval($index))) {
        echo "mark success";
    } else {
        echo "mark fail";
    }    
}

/* 定时检查各类未处理信息，稀有信息优先级最高 */
function checkall()
{
    global $user;
    if(!$user->islogin()) {
        echo "请先登录";
        return ;
    }
    
	if (ext_check_mail($user->userid())) {
		echo 'm';
		return;
	}
    
    $mlist = ext_get_msglist($user->userid(), 0, 1);
    if($mlist->total>0) {
        $mh = $mlist->list[0];
        if(!($mh->flag & FILE_READ))  {
            echo $mh->type;
            return ;
        }
    }

    echo "none";
}
?>