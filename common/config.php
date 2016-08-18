<?php

define('ANN_FILE', 0x01);              /* 普通文件 */
define('ANN_DIR', 0x02);              /* 普通目录 */
define('ANN_PERSONAL', 0x04);              /* 个人文集目录 */
define('ANN_GUESTBOOK', 0x08);              /* 留言本 */
define('ANN_LINK', 0x10);              /* Local Link */
define('ANN_RLINK', 0x20);              /* Remote Link (unused) */
define('ANN_SELECTED', 0x100);             /* 被选择 */
define('ANN_ATTACHED', 0x200);		  /* 带有附件 */
define('ANN_RESTRICT', 0x010000);          /* 限制性文件/目录 */
define('ANN_READONLY', 0x020000);          /* 只读 (不能修改属性/内容) */

define("AVATAR_HEIGHT", 72);
define("AVATAR_WIDTH", 72);

define("PHPBBS_HOME", dirname(dirname(__FILE__)));

define("SYSU_IP_LIST", BBSHOME . "/etc/sysu_ip.lst");
define("ADDRESS_LIST", PHPBBS_HOME . '/address.lst');
define("API_ERRCODE", "api_errcode.lst");
define("MIN_POST_INTERVAL", 3);

// Remember to change it before release to public.
define("COOKIE_SECRET", "b8e7ae12510bdfb1812e463a7f086122cf37f4f0");


?>
