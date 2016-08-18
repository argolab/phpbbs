<?php

header('Content-Type: text/html; charset=UTF-8');

include_once('config.php');
include_once('saetv2.ex.class.php' );

function auth()
{

    $o = new SaeTOAuthV2( WB_AKEY , WB_SKEY ); 
    $code_url = $o->getAuthorizeURL( WB_CALLBACK_URL );
    
    global $user;
    if($user->islogin())
    {
        header("Location:$code_url");
    }
    else
    {
        echo '请先登录！';
    }
    
}

function token()
{
    global $user;
    if($user->islogin())
    {
    
        $o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
	$token = null;

        if (isset($_GET['code'])) {
            $keys = array();
            $keys['code'] = $_GET['code'];
            $keys['redirect_uri'] = WB_CALLBACK_URL;
            try {
                $token_get = $o->getAccessToken( 'code', $keys ) ;
            } catch (OAuthException $e) {
            }
        }

        if (isset($token_get)) {
	    $token = $token_get['access_token'];
            file_put_contents(user_home_path($user->userid()) . '/weibo_token',
                              $token);
            $c = new SaeTClientV2( WB_AKEY , WB_SKEY , $token);
            $uid_get = $c->get_uid();
	    if(isset($uid_get['error'])){
	        include('templates/acc_failed.php');
		return;
	    }
            $uid = $uid_get['uid'];
            file_put_contents(user_home_path($user->userid()) . '/weibo_uid',
                              $uid);
            include('templates/acc_success.php');
        }
        else
        {
            include('templates/acc_failed.php');
        }
    }
    else
    {
        include('templates/acc_failed.php');
    }
}

?>
