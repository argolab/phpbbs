<?php
/* bbsanc.php */
/* 阅读精华区文章 */


if (!isset($_GET['path'])) exit;

$path = $_GET['path'];

$content = ext_annfile($path);

$tpl->loadTemplate('bbsanc.html');
echo $tpl->render(array('content' => $content));

?>
