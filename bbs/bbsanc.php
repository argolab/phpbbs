<?php
/* bbsanc.php */
/* �Ķ����������� */


if (!isset($_GET['path'])) exit;

$path = $_GET['path'];

$content = ext_annfile($path);

$tpl->loadTemplate('bbsanc.html');
echo $tpl->render(array('content' => $content));

?>
