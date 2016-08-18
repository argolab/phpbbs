<?php
require_once("common/functions.php");
require_once("common/etc.php"); 

function ajax_get_errorcode()
{
    $error_code_mapping =  etc_get_errorcode();

    ajax_success($error_code_mapping);
}

function ajax_get_profession()
{
    ajax_assert_param($_GET, array("graduate_year"));
    
    chdir(BBSHOME);
    $year = intval($_GET["graduate_year"]);
    if ($year < 1995 || $year > 2008)
        ajax_error("graduate_year invalid. Should in 1995-2008", 701);

    $dept = get_dept($year);
    ajax_success($dept);
}

?>
