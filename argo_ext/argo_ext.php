<?php
if (php_sapi_name() == "cli") {
	dl("argo_ext.so");
}

if (isset($_GET['user']) && isset($_GET['pw'])) {
	$ans = ext_checkpassword($_GET['user'], $_GET['pw']);
	echo "login " . (($ans == true) ? "success." : "failed") . "<br />";
	ext_login();
}

if (isset($_GET['article']))
	ext_readarticle("sysop", "M.1249924401.A");

$test = ext_getsections();
print_r($test);
?>
