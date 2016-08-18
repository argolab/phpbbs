<?php

require_once("common/functions.php");

function ajax_get_sections() 
{
    $secs = ext_getsections();
    if ($secs) ajax_success($secs);
    else ajax_error("No section.", 201);
	return;
}

?>
