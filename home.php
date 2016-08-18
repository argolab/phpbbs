<?php

function handle_home()
{
    global $user;

    if(!$user->islogin())
    {
	header("Content-Type:text/html; charset=utf-8");
        include "template/home.html";
    }
    else
    {
        header("HTTP/1.1 302 Found");
        header("Location: /n/index.html#!home");
    }
}

?>
