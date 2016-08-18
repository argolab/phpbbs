<?php

require_once('include/meekrodb.php');

/* define("AVATAR_HEIGHT", 72); */
/* define("AVATAR_WIDTH", 72); */

/* //define("SYSU_IP_LIST", BBSHOME . "/etc/sysu_ip.lst"); */
/* define("API_ERRCODE", "api_errcode.lst"); */
/* define("MIN_POST_INTERVAL", 3); */

/* //------- const */

/* define("PHPBBS_HOME", dirname(dirname(__FILE__))); */

/* define('ANN_FILE', 0x01);              /\* 普通文件 *\/ */
/* define('ANN_DIR', 0x02);              /\* 普通目录 *\/ */
/* define('ANN_PERSONAL', 0x04);              /\* 个人文集目录 *\/ */
/* define('ANN_GUESTBOOK', 0x08);              /\* 留言本 *\/ */
/* define('ANN_LINK', 0x10);              /\* Local Link *\/ */
/* define('ANN_RLINK', 0x20);              /\* Remote Link (unused) *\/ */
/* define('ANN_SELECTED', 0x100);             /\* 被选择 *\/ */
/* define('ANN_ATTACHED', 0x200);		  /\* 带有附件 *\/ */
/* define('ANN_RESTRICT', 0x010000);          /\* 限制性文件/目录 *\/ */
/* define('ANN_READONLY', 0x020000);          /\* 只读 (不能修改属性/内容) *\/ */

define('TOPIC_DELETE', 0x001);

define('PARTTOPIC_PART', 0x01);
define('PARTTOPIC_AUTHOR', 0x01 | 0x02);
define('PARTTOPIC_REPLY', 0x01 | 0x04);
define('PARTTOPIC_CROSS', 0x01 | 0x08);
define('PARTTOPIC_VOTE', 0x01 | 0x010);

define('PARTPOST_PART', 0x01);
define('PARTPOST_AUTHOR', 0x02 | 0x02);
define('PARTPOST_CROSS',  0x04 | 0x04);
define('PARTPOST_VOTE', 0x01 | 0x08);

// factor for topic score
define('FACTOR_A',1);
define('FACTOR_W', 0.0618);
define('FACTOR_G',1.41);

function update_all_score(){
    DB::query("update Topic SET score = ((2 * replynum + 5 * vote) + L + IFNULL((SELECT bl FROM BoardL WHERE BoardL.boardname=Topic.boardname LIMIT 1), 0)) * 10 / (pow((%d * (CURRENT_TIMESTAMP()-posttime) + (1 - %d) * (CURRENT_TIMESTAMP() - lastupdate))/3600+2, %d)) ORDER by topicid desc LIMIT 2000", (double)FACTOR_W, (double)FACTOR_W, (double)FACTOR_G);
}

function update_topic_score($topicid){
    @DB::query("update Topic SET score = ((2 * replynum + 5 * vote) + L + IFNULL((SELECT bl FROM BoardL WHERE BoardL.boardname=Topic.boardname LIMIT 1), 0)) * 10 / (pow((%d * (CURRENT_TIMESTAMP()-posttime) + (1 - %d) * (CURRENT_TIMESTAMP() - lastupdate))/3600+2, %d)) WHERE topicid=%d", (double)FACTOR_W, (double)FACTOR_W, (double)FACTOR_G, $topicid);
}

DB::$dbName = 'bbs';
DB::$user = 'bbs';
DB::$password = 'To0Late$';
DB::$host = 'localhost';
DB::$encoding = 'utf-8';

?>
