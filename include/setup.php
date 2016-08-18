<?php

require_once('include/config.php');
require_once('include/utils.php');
require_once('include/class-session.php');
require_once('include/meekrodb.php');
require_once('include/class-manager.php');
require_once('include/class-board.php');
require_once('include/class-section.php');
require_once('include/class-post.php');

DB::$dbName = 'bbs';
DB::$user = 'bbs';
DB::$password = 'To0Late$';
DB::$host = 'localhost';
DB::$encoding = 'utf-8';

