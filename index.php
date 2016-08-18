<?php

/* FirePHP for Development  */
/* Redirect PHP error & exception to Firebug Console */
require_once("common/FirePHPCore/fb.php");

/* $p = new FirePHP(); */
/* $p->registerErrorHandler(); */
/* $p->registerExceptionHandler(); */
/* Useful Usage: */
/* FB::log('Log message'); */
/* FB::info('Info message'); */
/* FB::warn('Warn message'); */
/* FB::error('Error message'); */

/* 初始化，将产生一些全局变量，请查看common/init.php */
require_once("common/init.php");
main_init();

/* 使用简洁url的路由功能 */
if (!isset($router)) {
    $router = new Router;
	$router->init();
}

?>
