<?php

/* 必备文件 */
require_once("config.php");
require_once("functions.php");
require_once('h2o-php/h2o.php');
require_once('class-router.php');
require_once('class-user.php');
require_once('class-board.php');
require_once('class-post.php');
require_once('log.php');


/* function http_init() { */

/* 	global $fromhost; */

/* 	/\* 设置fromhost *\/ */
/* 	if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) */
/* 		$fromhost = $_SERVER['HTTP_X_FORWARDED_FOR']; */
/* 	if ($fromhost == NULL) $fromhost = $_SERVER["REMOTE_ADDR"]; */
/* } */

function main_init() {

	global $user;
	global $tpl;

    $lifeTime = 30 * 24 * 3600; 
    session_set_cookie_params($lifeTime, '/'); 
    session_start();

	/* 产生一个User的实例user，表示当前用户 */
    $user = new User("deadbeef", true);
    
	/* set template engine as global variable */
	$tpl = new H2o('', array('searchpath'=>dirname(__FILE__) . '/../template/'));
	$tpl->set(array('login' => $user->islogin()));
    $tpl->set(array('userid' => $user->userid()));

    //banner 的东东都在这里设定
    $urec = ext_get_urec($user->userid());
    if( !$urec || $urec['userlevel'] === PERM_BASIC) $not_auth = true;
    else $not_auth = false;
    if(!$user->islogin()) $not_auth = false; 

    $tpl->set(array('not_auth' => $not_auth)); //是否已经验证
}


?>
