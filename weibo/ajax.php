<?php

include_once('config.php');
include_once('saetv2.ex.class.php' );

function check_auth()
{
    ajax_assert_login();
    global $user;
    if($user->islogin())
    {
        $token = @file_get_contents(user_home_path($user->userid())
                                   . '/weibo_token');
        if($token)
        {
            $uid = null;
            try
            {
                $c = new SaeTClientV2(WB_AKEY, WB_SKEY, $token);
                $uid_get = $c->get_uid();
		if(isset($uid_get['error']))
		    throw new OAuthException;
                $uid = $uid_get['uid'];
            } catch (OAuthException $e){
            }
            if($uid)
                ajax_success($uid);
        }
        ajax_success(false);
    }
    else
        ajax_success(false);
}

function use_weibo_avatar()
{
    ajax_assert_login();
//    ajax_assert_POST();
    global $user;
    try
    {
        $token = @file_get_contents(user_home_path($user->userid())
                                   . '/weibo_token');
        if($token)
        {
            $c = new SaeTClientV2(WB_AKEY, WB_SKEY, $token);
            $uid_get = $c->get_uid();
	    if($uid_get['error'])
	        throw new OAuthException;
            $uid = $uid_get['uid'];
            $userdata = $c->show_user_by_id($uid);
            $avatar_url = $userdata['avatar_large'];
            $path = get_avatar_path($user->userid());
            file_put_contents($path, fopen($avatar_url, 'r'));
            return ajax_success(true);
        }
    }catch (OAuthException $e)     {
    }
    ajax_error('Weibo API error.');
}

function update1()
{
    ajax_assert_login();
//    ajax_assert_POST();
    global $user;
    try
    {
        $token = @file_get_contents(user_home_path($user->userid())
                                   . '/weibo_token');
        if($token)
        {
            $c = new SaeTClientV2(WB_AKEY, WB_SKEY, $token);
            $userid = $user->userid();
            $c->update("撒花！我今天正式入住到逸仙时空啦，我的站内id是 $userid 。我在站内等你们哟！ #一个卖萌机器人代发# ");
            return ajax_success(true);
        }
    }catch (OAuthException $e)     {
    }
    ajax_error('Weibo API error.');
}

?>
