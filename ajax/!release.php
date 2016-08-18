<?php

require_once("common/functions.php");
require_once('include/meekrodb.php');
require_once('include/config.php');

function release_phpbbs(){
    global $user;
    if($user->hasperm(PERM_SYSOP)){
    }else{
        ext_security_report($user->userid(), 'Warning! Danger User want to release!');
        return;
    }

    ajax_assert_POST();
    echo '<pre>';
    system("cd /home/bbs/phpbbs; svn up");
    echo '</pre>';
}

function release(){
    global $user;
    if($user->hasperm(PERM_SYSOP)){
    }else{
        ext_security_report($user->userid(), 'Warning! Danger User want to release!');
        return;
    }

    ajax_assert_POST();
    ajax_assert_param($_POST, array('commit', 'sure'));

    if(!($_POST['commit'] == '!') && !ctype_alnum($_POST['commit'])){
        ajax_error('No such commit');
    }

    if(!($_POST['sure'] == '!') && !ctype_alnum($_POST['sure'])){
        ajax_error('No such commim#s');
    }

    echo '<pre>';

    system('/home/bbs/local/bin/jsbbs_release ' . $_POST['commit'] . ' ' . $_POST['sure'],
           $ret);

    echo '</pre>';

    echo "<br/><pre>Return Value: $ret";
        
}
