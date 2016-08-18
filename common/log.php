<?php

function trace_report($mesg)
{
    global $user;

	if (!is_object($user)) { 
		if (isset($_COOKIE["userid"])) $userid = $_COOKIE["userid"]; 
		else $userid = "guest";                                     
	} else $userid = $user->userid();
	
    chdir(BBSHOME);

    if (!is_object($user)) {
        if (isset($_COOKIE["userid"])) $userid = $_COOKIE["userid"]; 
        else $userid = "guest";
    } else  $userid = $user->userid();
 
    $file = fopen("phplog/trace", "a");
    flock($file, LOCK_EX);
    fputs($file, $userid . " " . date("Y-m-d H:i:s ") . " " . $_SERVER['REMOTE_ADDR']
 . " ". $mesg . "\n");
    flock($file, LOCK_UN);
    fclose($file);
}

?>
