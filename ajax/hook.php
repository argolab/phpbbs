<?php

require_once("common/functions.php");
require_once('include/meekrodb.php');
require_once('include/config.php');

function do_hook($hook, $root=false, $bm=false, $read=false){
    global $user;

    if(!file_exists(PHPBBS_HOME . '/hooks/' . $hook . '.sh')){
        goto failed;
    }
    if($root && !$user->hasperm(PERM_SYSOP)){
        goto failed;
    }
    if($bm && !$user->has_BM_perm($bm)){
        goto failed;
    }
    if($read && !$user->has_read_perm($read)){
        goto failed;
    }
    
    system(PHPBBS_HOME . '/hooks/' . $hook . '.sh');

    return;
    
  failed:

    header("Status: 404 Not Found");
    echo "404 错误，哎呀呀，没有这个地址啦~^o^~";
    
}

