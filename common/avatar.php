<?php

require_once("common/functions.php");

function user_avatar($userid)
{
    $path = get_avatar_path($userid);

    
    if(!file_exists($path)) {
        $imgfile = dirname(__FILE__) . '/../static/images/avatar/' . rand(0, 13) . '.png';
        if(!@copy($imgfile, $path))
            $path = dirname(__FILE__) . '/../static/images/avatar/default.jpg';
    }
    //trace_report($path);
     
    header("Content-Type: image/jpeg");
    
    $st = stat($path);
    header("Last-Modified: "  . gmdate(DATE_RFC822, $st[10]));            
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        $last_modify = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        if($st[10] == $last_modify) {
            header( "HTTP/1.1 304 Not Modified" );
            return ;
        }
    }

    readfile($path);
}

?>
