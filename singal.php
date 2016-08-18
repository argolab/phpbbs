<?php

require_once('include/config.php');
require_once('include/utils.php');
require_once('include/class-session.php');
require_once('include/class-router.php');
require_once('include/meekrodb.php');
require_once('include/class-manager.php');
require_once('include/class-board.php');
require_once('include/class-section.php');
require_once('include/class-post.php');

$rules = require("include/singal_urls.conf.php");
$router = new Router($rules, 'api');
$router->go_request_uri();
