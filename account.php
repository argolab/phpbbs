<?php

function register()
{
    header('Content-Type: text/html; charset=UTF-8');
    global $tpl;
    global $user;
    if($user->islogin()) $user->logout();
    $tpl->loadTemplate('account/register.html');
    echo $tpl->render();
}

function sss()
{
    global $user;
    if(!$user->islogin()){
        header("Content-Type:text/html; charset=utf-8");
        echo '请先 <a target="_blank" href="http://argo.sysu.edu.cn">在 argo.sysu.edu.cn 登录</a> 。';
        return;
    }
    if(!isset($_GET['callback'])){
        header("Content-Type:text/html; charset=utf-8");
        echo '错误';
        return;
    }
    $first_char = substr(strtoupper($user->userid()), 0, 1);
    $enc_path = BBSHOME . '/home/' . $first_char . '/' . $user->userid()
        . '/encode';
    if(file_exists($enc_path)){
        $enc = file_get_contents($enc_path);
    }else{
        $validcharacters = "abcdefghijklmnopqrstuxyvwzabcdefghijklmnopqrstuxyvwz+-*#&@!?";
        $enc = mt_rand(0, 10);
        file_put_contents($enc_path, $enc);
    }
    $tf = time();
    $md5str = md5($enc . "$tf");
    $ref = 'http://' . $_GET['callback']
         . '?' . http_build_query(
                   array('m' => $md5str,
                         'u' => $user->userid(),
                         't' => $tf));
    $p = parse_url($ref);
    
    global $tpl;
    header("Content-Type:text/html; charset=utf-8");
    $tpl->loadTemplate('account/sss.html');
    echo $tpl->render(array('host' => $p['host'],
                            'issysu' => (substr($p['host'], -12) ==
                                   '.sysu.edu.cn'),
                            'userid' => $user->userid(),
                            'ref' => $ref));
}

function sssok()
{
    ajax_assert_param($_GET, array('m', 'u', 't'));
    if(!ctype_alpha($_GET['u']))
        ajax_error('w');
    $first_char = substr(strtoupper($_GET['u']), 0, 1);
    $enc_path = BBSHOME . '/home/' . $first_char . '/' . $_GET['u']
        . '/encode';
    if(!file_exists($enc_path))
        ajax_error('w');
    $enc = file_get_contents($enc_path);
    if(md5($enc . $_GET['t']) != $_GET['m'])
        ajax_error('w');
    ajax_success('r');
}

function auth()
{
    /* if($_SERVER['HTTP_HOST'] != 'bbs.sysu.edu.cn'){ */
    /*     header("Location: http://bbs.sysu.edu.cn/account/auth"); */
	/* die(); */
    /* } */
    global $user;
    header('Content-Type: text/html; charset=UTF-8');
    if(!$user->islogin()){
        echo '请先 <a href="http://bbs.sysu.edu.cn">在 bbs.sysu.edu.cn 登录</a> 。';
        return;
    }
    ajax_assert_login();
    global $tpl;
    $tpl->loadTemplate('account/auth.html');
    echo $tpl->render();
}

function auth_info(){
    global $tpl;
    $tpl->loadTemplate('account/auth_info.html');
    echo $tpl->render();
}

function auth_netid2()
{
    global $user;
    ajax_assert_login();
    ajax_assert_param($_POST, array("netid", "password"));
    header("Content-type: text/html; charset=utf-8"); 
    if(pydo("check_netid", array("netid" => $_POST['netid'],
                                 "passwd" => $_POST['password'])))
    {
        $urec = ext_get_urec($user->userid());
        //防过多注册
        $netid = $_POST['netid'];
        if(check_multi_register($netid)) {
            echo "此NetID注册过多，激活失败。";
        }
        $urec['userlevel'] |= (PERM_WELCOME | PERM_DEFAULT);
        $res = ext_update_urec($user->userid(), array('userlevel' => $urec['userlevel'], 'reginfo' => $netid));
        if($res)  {

            trace_report("Auth success. Netid approch.");
            //ext_security_report($user->userid(), "使用NetID激活" . $user->userid() . "的帐号(ajax接口)", " ");

            global $tpl;
            $tpl->loadTemplate('account/success.html');
            echo $tpl->render();
            
        } else echo "未知原因，激活失败，请联系管理员";


    } else echo "NetID激活失败，检查你输入的Netid是否正确。如果没有问题，请联系管理员。";

}

function register_netid_start(){
    global $user;
    if(isset($_GET['nonew'])){
        if(!$user->islogin()){
            header("Content-Type:text/html; charset=utf-8");
            echo '请先使用新版登录！<a target="_blank" href="/">点此登录</a>';
            return;
        }
    }
    $url = 'http://sso.sysu.edu.cn/cas/login?service=http://bbs.sysu.edu.cn/account/netid';
    if(isset($_GET['inviter']))
    {
        $_SESSION['inviter'] = $_GET['inviter'];
        global $tpl;
        header('Content-Type: text/html; charset=UTF-8');
        $tpl->loadTemplate('account/inviter.html');
        echo $tpl->render(array('inviter' => $_SESSION['inviter'],
                                'url' => $url));
    }
    else
    {
        header("Location: $url");
    }
}

function register_netid()
{
    header('Content-Type: text/html; charset=UTF-8');
    $service_url =  "http://bbs.sysu.edu.cn/account/netid";
    $ticket = $_GET["ticket"];
    $url = "https://sso.sysu.edu.cn/cas/validate?service=" . $service_url . "&ticket=";
    $url .= $ticket;
    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    curl_close($ch);
    if (strstr($data, "yes")) {
        $netid = substr($data, 4);
        $netid = substr($netid, 0, strlen($netid)-1);
        if(check_multi_register($netid)) {
            echo "此NetID注册过多，操作失败。";
            return;
        }
        global $user;
        if($user->islogin()){
            $urec = ext_get_urec($user->userid());
            $p = (PERM_WELCOME | PERM_DEFAULT);
            if(($urec['userlevel'] & $p) > $p){
                echo '无需重复激活！';
                return;
            }
            $urec['userlevel'] |= (PERM_WELCOME | PERM_DEFAULT);
            $res = ext_update_urec($urec['userid'],
                                   array(
                                       'userlevel' => $urec['userlevel'],
                                       'reginfo' => $netid));
            if($res){
                trace_report("Auth success. Netid approch.");
            }
            header('Location: /');
        }
        else{
            $_SESSION['netid'] = $netid;
            header('Location: /p/register_n.html');
        }
        return;
    }
    else
    {
        echo '错误的Netid，操作失败。';
        return;
    }
}

function auth_netid()
{
    global $user;
    ajax_assert_login();
    ajax_assert_param($_GET, array("ticket"));
    header('Content-Type: text/html; charset=UTF-8');
    $service_url =  "http://bbs.sysu.edu.cn/account/auth/netid";
    $ticket = $_GET["ticket"];
    $url = "https://sso.sysu.edu.cn/cas/validate?service=" . $service_url . "&ticket=";
    $url .= $ticket;

    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    curl_close($ch);

    if (strstr($data, "yes")) {
        $urec = ext_get_urec($user->userid());
        //防过多注册
        $netid = substr($data, 4);
        $netid = substr($netid, 0, strlen($netid)-1);
        if(check_multi_register($netid)) {
            echo "此NetID注册过多，激活失败。";
        }
        $urec['userlevel'] |= (PERM_WELCOME | PERM_DEFAULT);
        $res = ext_update_urec($user->userid(), array('userlevel' => $urec['userlevel'], 'reginfo' => $netid));
        if($res)  {

            trace_report("Auth success. Netid approch.");
            //ext_security_report($user->userid(), "使用NetID激活" . $user->userid() . "的帐号(ajax接口)", " ");

            global $tpl;
            $tpl->loadTemplate('account/success.html');
            echo $tpl->render();
            
        } else echo "未知原因，激活失败，请联系管理员";


    } else  echo "NetID激活失败，检查你输入的Netid是否正确。如果没有问题，请联系管理员。";

}

?>
