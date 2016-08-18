<?php

/* This file is mainly use for sync the old data from telnet server
   or websrc server. It will take some name params, reread them from
   file by ext_*, and then insert them into the database. */

function api_update_all_score(){
    update_all_score();
}

function api_changedigest()
{
    $filename = $_POST['f'];
    $boardname = $_POST['b'];
    $value = $_POST['g'];
    $lpmc = new LegacyPostManage(UserSession::get_cookie_user());
    $lpmc->sync_update($filename, $boardname, 'digest', $value);
}


function api_cross()
{
    $filename = $_POST['f'];
    $boardname = $_POST['b'];
    $lpmc = new LegacyPostManage(UserSession::get_cookie_user());
    $lpmc->sync_cross($filename, $boardname);
}

function api_newtopic()
{
    $filename = $_POST['f'];
    $boardname = $_POST['b'];
    $lpmc = new LegacyPostManage(UserSession::get_cookie_user());
    $lpmc->sync_new($filename, $boardname);
}

function api_reply()
{
    $filename = $_POST['f'];
    $boardname = $_POST['b'];
    $toreply = $_POST['f0'];
    $lpmc = new LegacyPostManage(UserSession::get_cookie_user());
    $lpmc->sync_reply($filename, $boardname, $toreply);
}

function api_updatepost()
{
    $filename = $_POST['f'];
    $boardname = $_POST['b'];
    $lpmc = new LegacyPostManage(UserSession::get_cookie_user());
    $lpmc->sync_update($filename, $boardname, 'content', null);
}

function api_changetitle()
{
    $filename = $_POST['f'];
    $boardname = $_POST['b'];
    $lpmc = new LegacyPostManage(UserSession::get_cookie_user());
    $lpmc->sync_update($filename, $boardname, 'title', null);
}

function api_changemark()
{
    $filename = $_POST['f'];
    $boardname = $_POST['b'];
    $value = $_POST['m'];
    $lpmc = new LegacyPostManage(UserSession::get_cookie_user());
    $lpmc->sync_update($filename, $boardname, 'mark', $value);
}

function api_del()  // hook only in action delete
{
    $filename = $_POST['f'];
    $boardname = $_POST['b'];
    $lpmc = new LegacyPostManage(UserSession::get_cookie_user());
    $lpmc->sync_cancel($filename, $boardname);
}

function api_cancelpost()
{
    /* $filename = $_POST['f']; */
    /* $boardname = $_POST['b']; */
    /* $lpmc = new LegacyPostManage(UserSession::get_cookie_user()); */
    /* $lpmc->sync_cancel($filename, $boardname); */
}

function api_test()
{
    $lpmc = new LegacyPostManage(UserSession::get_cookie_user());
    echo 'Hello, World!';
}
