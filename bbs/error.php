<?php

function err($type, $msg) {
	echo $type;
	echo $msg;
}


function info($msg) {
	err("info", $msg);
}

function warn($msg) {
	err("warn", $msg);
}

?>