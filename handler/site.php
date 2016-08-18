<?php

function api_test()
{
    $ret = DB::queryFirstRow('SELECT "Hello, World" as "1"');
    print_r($ret['1']);
}

?>
    