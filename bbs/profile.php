<?php
require_once("common/functions.php");
require_once("bbs/login.php");

function upload_face()
{
    global $user;
    
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        if(isset($_FILES["myface"])) {
            if( $_FILES["myface"]["error"] == 4 ) return true;
            if ($_FILES["myface"]["error"] > 0) {
                switch ($_FILES["myface"]["error"]) {
                    case 1: echo "头像大小超过限制" ; return ; 
                    case 2: echo "头像大小超过限制";  return ;
                    case 3: echo "头像上传不完整";  return ;
                    case 5: echo "上传文件大小不能为0";  return ;
                    default: echo "未知错误原因"; return ;
                }
            }
            
            if(filesize($_FILES["myface"]["tmp_name"]) > 200*1024) {
                echo "头像大小超过限制 (<" . intval(200) . "K )" ;
                return ;
            }
            if(!in_array($_FILES["myface"]["type"],
                         array("image/jpeg")))
            {
                echo "头像文件只支持 .jpg  格式";
                return ;
            }
            $ahlist = ext_get_attachlist($user->userid(), 0, -1);
            $total_size = get_total_size();
            $used_size = $_FILES["myface"]["size"];
            $which = 0;
            $index = 0;
            if(isset($ahlist) && count($ahlist->list)) {
                foreach($ahlist->list as &$ah)
                {
                    $index ++;
                    $used_size += $ah->filesize;
                    if($ah->filename == $user->userid())
                        $which = $index;                
                }
            }
                /* 上传新头像，把原来的删去 */
            if($which >0 ) {
                $res = ext_del_attach($user->userid(), array(strval($which)));
            }
            if($used_size > $total_size) {
                echo "您的附件空间已经装不下啦，赶紧去清理吧！ " ;
                return false;
            }

                /* 上传并指定文件名,以用户名作为头像文件，如gcc */
            $res = ext_upload_attach($user->userid(),
                                     $_FILES["myface"]["tmp_name"],
                                     $_FILES["myface"]["name"],
                                     $_FILES["myface"]["type"],
                                     $user->userid());
            if(!$res) {
                echo "上传头像失败";
                return ;
            }
            trace_report(" upload " . $res . " size " . $_FILES["myface"]["size"]);
            return $res;
        } else {
            echo "上传错误";
            return ;
        }
    } else {
        echo "参数错误";
        return ;
    }
}

function profile_setting_info() {	
	global $user;
	global $tpl;

        /* 在js端进行数据合法性检测，这里假设数据合法 */
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
		$urec = array();
		$urec['username'] = conv2gbk(preg_replace('/[\x00-\x1F\x7F]/',' ', $_POST['username']));
		$urec['realname'] =  conv2gbk(preg_replace('/[\x00-\x1F\x7F]/',' ', $_POST['realname']));
		$urec['birthyear'] = intval(intval($_POST['birthyear'])%100);
		$urec['birthmonth'] = intval($_POST['birthmonth']);
		$urec['birthday'] = intval($_POST['birthday']);
		$urec['gender'] = ($_POST['gender'] == "M") ? 77 : 70;
		
            /*    如果没问题则上传到附件仓库，*/
        if (isset($_FILES["myface"]))
            save_avatar($_FILES["myface"]);

        if (!upload_face()) return ;

        $res = ext_update_urec($user->userid(), $urec);
        echo $res? "修改成功" : "修改失败";
        return ;
    }
    $urec = ext_get_urec($user->userid());
        
    $urec['male'] = ($urec['gender'] == 77 )? true : false;
    $urec['firstlogin'] = date('M d H:i  Y', $urec['firstlogin']);
    $urec['lastlogin'] = date('M d H:i  Y', $urec['lastlogin']);
    $urec['lastlogout'] = date('M d H:i  Y', $urec['lastlogout']);
    $urec['stay'] = intval($urec['stay']/60);

    $myface = get_myface($urec['userid']); /* return {userid} */
    if($myface)  $urec['myface'] = '/attach/' . $urec['userid'] . '/' . $myface;
                               
    $tpl->loadTemplate("standard/profile/info_form.html");
    echo $tpl->render(array('urec' => $urec,
                            'years' => range(99, 10, -1),
                            'mons' => range(1, 12),
                            'days' => range(1, 31)));

}

function profile_setting($cmd = "", $start = 0)
{
    global $user;
    global $tpl;

    if(!$user->islogin()){
        echo "请先登录";
        return ;
    }
    $user->set_stat(STAT_USERDEF);
    if($cmd == ""){  /* 显示设置页面 */
        
        $tpl->loadTemplate("standard/profile/settings.html");
        echo $tpl->render();
        
    } else if ($cmd == "friends" || $cmd == "rejects") { /* 设置好友，坏人*/

        $overrides = ext_get_override($user->userid(), $cmd);
        if(count($overrides)) {
            foreach($overrides as &$over) {
                $uinfo = ext_get_uinfo($over->id);
                if($uinfo) {
                    $over->mode = ModeType($uinfo['mode']);
                }                
            }
        }
        $tpl->loadTemplate("standard/list_overrides.html");
        echo $tpl->render(array(
                              "isfriend" => ($cmd == 'friends' ? true : false),
                              "overrides" => $overrides
                                ));
        
    } else if ($cmd == 'info') {  /* 修改个人资料 */
	    return profile_setting_info();
    } else if ($cmd == 'plan') { /* 修改个人说明 */

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            if(!isset($_POST['plan'])) echo "参数错误";
            $plan =  conv2gbk($_POST['plan']) ;
            $res = ext_set_whole_file($user->userid(),"plans", $plan);
            echo $res? "修改成功" : "修改失败";
            return;
        }
            //htmlspecialchars_decode
        $plan = ext_get_whole_file($user->userid(), "plans", 0);   /* no html */
        $tpl->loadTemplate('standard/profile/plan_form.html');
        echo $tpl->render(array('plan' => $plan));
        
    } else if ($cmd == 'signature') { /* 修改签名档 */
        
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            if(!isset($_POST['signature'])) echo "参数错误";
            $signature = ( conv2gbk($_POST['signature']) );
            $res = ext_set_whole_file($user->userid(),"signatures", $signature); 
            echo $res? "修改成功" : "修改失败";
            return ;
        }
        $signature = ext_get_whole_file($user->userid(), "signatures", 0); /* no html */
        $tpl->loadTemplate('standard/profile/signature_form.html');
        echo $tpl->render(array('signature' => $signature));
        
    } else if ($cmd == 'passwd') {  //修改密码，这个需要监控

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!isset($_POST['oldpasswd']) || !isset($_POST['newpasswd']) || !isset($_POST['confirm'])) {
                trace_report(" change password " .  "param_error");
                echo "参数错误";
                return ;
            }
            if(!ext_checkpassword($user->userid(), $_POST['oldpasswd'], 0)) {
                trace_report(" change password " .  "oldpasswd_error");
                echo "旧密码输入错误";
                return ;
            }
            if($_POST['newpasswd'] != $_POST['confirm']) {
                trace_report(" change password " .  "different_error");
                echo "确认密码和新密码不相同";
                return ;
            }
            if($_POST['newpasswd'] == '') {
                trace_report(" change password " .  "empty_error");
                echo "新密码不能为空";
                return ;
            }
            $md5passwd = ext_igenpass($user->userid(), $_POST['newpasswd']);
            $res = ext_update_urec($user->userid(), array('passwd' => $md5passwd));

            trace_report(" change password " .  ($res?"success":"failed"));
            echo $res? "修改成功" : "修改失败"; 
            return ;
        }
        
        $tpl->loadTemplate('standard/profile/passwd_form.html');
        echo $tpl->render();
        
    } else if ($cmd == 'www') {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $res= ext_set_www($user->userid(), $_POST);
            echo $res? '修改成功' : '修改失败';           
            return ;
        }
        
        $www = ext_get_www($user->userid());
        if(!isset($www)) $www=array();
        if(!array_key_exists('t_lines',$www))  $www['t_lines'] = 20;
        if($www['t_lines']<10 || $www['t_lines']>40) $www['t_lines'] = 20;
        
        if(!array_key_exists('link_mode',$www))  $www['link_mode'] = 0;
        if($www['link_mode'] <0 || $www['link_mode']>1) $www['link_mode'] = 0;
        
        if(!array_key_exists('def_mode',$www))  $www['def_mode'] = 0;
        if($www['def_mode'] <0 || $www['def_mode']>1) $www['link_mode'] = 0;
        
        if(!array_key_exists('friend_time',$www))  $www['friend_time'] = 2;
        if($www['friend_time'] <2 || $www['friend_time']>7) $www['link_mode'] = 2;
        
        $user->loadwww();
        
        $tpl->loadTemplate('standard/profile/www_form.html');
        echo $tpl->render(array('www' => $www));
    } else echo "该页面不存在";
}

function profile_query($userid = "")
{
    global $user;
    global $tpl;
    
    if($userid == "") {
        echo "用户名不能为空";
        return ;            
    }
    $user->set_stat(STAT_QUERY);
    $urec=ext_get_urec($userid);
    $uinfo=ext_get_uinfo($userid);
    if(count($urec) == 0){
        echo "不存在该用户";
        return ;
    }
    $urec['life_value'] = intval(count_life_value($urec));      
    $urec['has_mail'] = ext_check_mail($urec['userid']);
    $urec['lastlogout'] = date("M d Y", $urec['lastlogout']);
    $urec['lastlogin'] = date("M d Y",$urec['lastlogin']);
    $urec['stay'] = stay_time($urec['stay']);
    $urec['constellation'] = get_constellation($urec['birthmonth'], $urec['birthday']);
    $urec['male'] = $urec['gender'] ==77 ? true : false;
    $urec['plan'] = ext_get_whole_file($urec['userid'], "plans", 1); /* with html */

        /* 头像 */
    $myface = get_myface($urec['userid']); /* return {userid} */
    if($myface)  $urec['myface'] = '/attach/' . $urec['userid'] . '/' . $myface;
    
        /*对sysop，呈现真实姓名 */
    if($user->hasperm(PERM_SYSOP)) {
        $urec['sysop'] = true;
    }

    if(strlen($urec['plan'])<=25) unset($urec['plan']);    
    if($uinfo) {
        $urec['online'] = true;
        $urec['mode'] = ModeType($uinfo['mode']);
    }
    
    $tpl->loadTemplate('standard/profile.html');
    echo $tpl->render(array('user' => $urec));
}

function merge($feed1, $feed2)
{
    $feed3 = array();
    $idx1 = 0;
    $idx2 = 0;
    while($idx1< count($feed1) || $idx2 < count($feed2))
    {
        if($idx2 == count($feed2) )  { 
            $feed3[]= $feed1[$idx1];
            $idx1++;
        }  else if($idx1 == count($feed1)) {
            $feed3[]=$feed2[$idx2];
            $idx2++;
        } else if ($feed1[$idx1]->filetime > $feed2[$idx2]->filetime) {
            $feed3[]= $feed1[$idx1];
            $idx1++;
        } else {
            $feed3[]=$feed2[$idx2];
            $idx2++;
        }
    }
    return $feed3;
}

function online_friends()
{
    global $user;
    global $tpl;
    
    if(!$user->islogin()) {
        echo "login first";
        return ;
    }
    $user->set_stat(STAT_LAUSERS);
    $olist = ext_get_override($user->userid(), "friends");
    
        /* 好友发文记录，不包括限制版,在mywww中设置持续时间,默认为2天。 */
    $www = ext_get_www($user->userid());
    if(!count($www) || !array_key_exists('friend_time',$www))  $www['friend_time'] = 2;
    
    $recent = $www['friend_time'];
    
    $friend_feed = array();
    if(count($olist)) {
        $frends = array();
        foreach($olist as $over) {
            $friends[]=$over->id;
        }
            //ext_post_stat({userid数组}, 查看的天数， 每个userid最多返回的feed数);
        $friend_feed = ext_post_stat($friends, $recent, 5);
    }
    $filter_friend_feed = array();
    if(count($friend_feed)) {
            foreach($friend_feed as &$ff)  {
            if(!$user->has_read_perm($ff->board)) continue; //不能读到好友在自己不可读的版面文章
            $post = new Post($ff->board, $ff->filename);
            $fh = ext_getfileheader($ff->board, $ff->filename);
            if($fh->owner != $ff->userid)  continue; // 匿名版的匿名贴自动忽略
            $ff->digest = get_digest_html($post->content);
            $ff->post_time = show_last_time($fh->filetime);
            $ff->nodot_id = str_replace(".", "-", $ff->filename);
            $ff->title = $fh->title;
            $myface = get_myface($post->userid); // return {userid} 
            if($myface)  $ff->myface = '/attach/' . $post->userid . '/' . $myface;
            $filter_friend_feed []= $ff;
        }
    }
    $tpl->loadTemplate('standard/list_online_friends.html');
    echo $tpl->render(array(/*'uinfos' => $uinfos, */
                            "friend_feed" => $filter_friend_feed));

}

function add_friend($userid = "") 
{
    global $user;
    global $tpl;
    if(!$user->islogin()) {
        echo "请先登录";
        return ;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if ($_POST['userid']== "") {
            echo "用户名不能为空";
            return ;
        }
        if(isset($_POST['exp'])) $exp = $_POST['exp'];
        else $exp = "";
        $exp = conv2gbk($exp);
        
        $res=ext_add_override($user->userid(),$_POST['userid'] , $exp , "friends"); /* 添加override */
            /* 加好友则提醒对方 */
        if($res == 0) {
            do_atuser(array($_POST['userid']), "argo", "argo", "f");
        }
            /* 0： 成功  1： 重复  2：无该用户 3：超过限制  4：其他错误 */
        switch ($res) {
            case 0: echo "添加成功"; return ;
            case 1: echo "该用户已在好友列表中"; return;
            case 2: echo "不存在该用户"; return;
            case 3: echo  "好友数量已超上限" ; return ;
            default: echo "添加失败"; return ;
        }
        return;
    }
    $tpl->loadTemplate("standard/forms/friend_form.html");
    echo $tpl->render(array('userid' => $userid, 'addfriend' => true));
}
function del_friend($userid = "")
{
    global $user;
    global $tpl;
    if(!$user->islogin()) {
        echo "请先登录";
        return ;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {        
        if ($_POST['userid']== "") {
            echo "用户名不能为空";
            return ;
        }
        $res=ext_del_override($user->userid(),$_POST['userid'] , "friends"); /* 删除override */
            /* 0： 成功  1：找不到该用户 2：好友列表为空  3：其他错误 */
        switch ($res) {
            case 0: echo "删除成功"; return ;
            case 1: echo "好友列表中无该用户"; return;
            case 3: echo  "好友列表为空" ; return ;
            default: echo "删除失败"; return ;
        }
        return;
    }else {
        echo "参数错误" ;        
    }    
}
function add_reject($userid = "")
{
    global $user;
    global $tpl;
    if(!$user->islogin()) {
        echo "请先登录";
        return ;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if ($_POST['userid']== "") {
            echo "用户名不能为空";
            return ;
        }        
        if(isset($_POST['exp'])) $exp = $_POST['exp'];
        else $exp = "";
        $exp = conv2gbk($exp);
        
        $res=ext_add_override($user->userid(),$_POST['userid'] , $exp , "rejects"); /* 添加override */
            /* 0： 成功  1： 重复  2：无该用户 3：超过限制  4：其他错误 */
        switch ($res) {
            case 0: echo "添加成功"; return ;
            case 1: echo "该用户已在坏人列表中"; return;
            case 2: echo "不存在该用户"; return;
            case 3: echo  "超上限啦，哪有那么多怪叔叔- -" ; return ;
            default: echo "添加失败"; return ;
        }
        return;
    }

    $tpl->loadTemplate("standard/forms/friend_form.html");
    echo $tpl->render(array('userid' => $userid, 'addreject' => true));
}
function del_reject($userid = "")
{
    global $user;
    global $tpl;
    if(!$user->islogin()) {
        echo "请先登录";
        return ;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {        
        if ($_POST['userid']== "") {
            echo "用户名不能为空";
            return ;
        }
        $res=ext_del_override($user->userid(),$_POST['userid'] , "rejects"); /* 删除override */
            /* 0： 成功  1：找不到该用户 2：好友列表为空  3：其他错误 */
        switch ($res) {
            case 0: echo "删除成功"; return ;
            case 1: echo "坏人列表中无该用户"; return;
            case 3: echo  "坏人列表为空" ; return ;
            default: echo "删除失败"; return ;
        }
        return;
    }else {
        echo "参数错误" ;        
    }
}
function a_reg($userid) //检查userid是否已被注册
{
    $urec=ext_get_urec($userid);
    if(count($urec) == 0)  echo "not exist";
    else echo "exist";
}
function a_confirm($year)
{
    global $tpl;
         
    $dept = get_dept($year);

    $tpl->loadTemplate("standard/forms/confirm_info_form.html");
    echo $tpl->render(array("dept" => $dept,
                            "year" => $year));
}

function register()
{
    global $tpl;
    global $user;
    if ($user->islogin()) {
        echo "你已经登录，请退出再进行注册";
        return ;
    }
    if($_SERVER['REQUEST_METHOD'] == 'POST') {

        //服务器端还要检查一次，防止越过js检测
        //这里只对userid检查
        if(!isset($_POST['userid'])) {
            echo "参数错误";
            return ;
        }
        $userid =$_POST["userid"];
        //preg_match("/^[a-zA-Z]*$/", $userid, $match); 
        //if(count($match) <1 || $match[0] != $userid)
        if(!ctype_alpha($userid))
        {
            show_information("用户名错误");
            return ;
        }
        // 不可注册id
        if(bad_user_id($userid)) {
            show_information("该用户名不可注册。");
            return ;
        }
        //
        $urec=ext_get_urec($userid);
        if(count($urec)) {
            show_information("该用户已经被注册"); 
            return ;
        }
        //加入用户，给予perm_basic权限

        $urec['userid'] = $_POST['userid'];
        $urec['passwd'] = ext_igenpass($urec['userid'], $_POST['pass1']);
        $urec['username'] = $_POST['username'];
        $urec['realname'] = $_POST['realname'];
        $urec['address'] = $_POST['address'];
        $urec['email'] = $_POST['email'];
        $urec['gender'] = intval($_POST['gender']); // M:77 F:70
        $urec['birthyear'] = intval(intval($_POST['year'])/100);
        $urec['birthmonth'] = intval($_POST['month']);
        $urec['birthday'] = intval($_POST['day']);
        $urec['userlevel'] = PERM_BASIC; 
        $urec['firstlogin'] = $urec['lastlogin'] = time(0); 
        $res = ext_update_urec($urec['userid'], $urec, 'insert');  //如果有第三个参数，就是insert

        if(!$res) {
            show_information("注册失败，请重新注册");
            return ;
        }
        
        //login("注册成功，请重新登录再进行激活");
        $user->login($urec['userid'], $_POST['pass1'], true);      
        header("Location: /auth/");
        //show_information("注册成功！", "/auth/");

    } else { //填表格
        $tpl->loadTemplate("standard/forms/register_form.html");
        echo $tpl->render(); 
    }
}

function auth($type)
{
    global $tpl;  
    global $user;
    if(!$user->islogin()) {
        echo "请登录先啦!";
        return ;
    }
    $urec = ext_get_urec($user->userid());
    if($urec['userlevel'] != PERM_BASIC) {
        echo "已经被激活了，无需再进行激活";
        return ;
    }
    if($type == "") {
        $tpl->loadTemplate("standard/choose_auth_way.html");
        echo $tpl->render(); 
    } else if ($type == "1") {
        auth_by_netid();              
    } else {
        auth_by_confirm();
    }
}

function auth_by_netid()
{
    global $user;
    if(!$user->islogin()) {
        echo "请先登录";
        return ;
    }

    $arr = parse_url($_SERVER['REQUEST_URI']);	
    
    if(isset($arr['query'])) {
        $exp = explode("?",$arr['query']);
        $query = explode('=', $exp[0]);
    }
    
    //todo: !!!!! remember to change it before commit
    if (!isset($arr['query']) || $query[0] != "ticket")  {
        //header("Location: https://cas.sysu.edu.cn/cas/login?service=http://127.0.0.1:8088/auth/1/");
        header("Location: https://cas.sysu.edu.cn/cas/login?service=http://bbs.sysu.edu.cn:874/auth/1/");
    } else {
        //if(count($exp) != 2) {
        //    echo "验证错误,请联系管理员";
        //    return ;
        //}
        //$url = "https://cas.sysu.edu.cn/cas/validate?service=http://127.0.0.1:8088/auth/1/&ticket=";
        $url = "https://cas.sysu.edu.cn/cas/validate?service=http://bbs.sysu.edu.cn:874/auth/1/&ticket=";
        $url .= $query[1];

        $ch = curl_init();   
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch); 

        if(strstr($data, 'yes')) {
            $urec = ext_get_urec($user->userid());
            //防过多注册
            $netid = substr($data, 4);
            $netid = substr($netid, 0, strlen($netid)-1);
            if(check_multi_register($netid)) {
                show_information("此NetID注册过多，激活失败。");
                return ;
            }
            $urec['userlevel'] |= (PERM_WELCOME | PERM_DEFAULT);
            $res = ext_update_urec($user->userid(), array('userlevel' => $urec['userlevel']));
            if($res)  {
                show_information("激活成功，现在您可以畅游逸仙时空了！");
                //todo : syssecurity report 
                ext_security_report($user->userid(), "在新版使用NetID激活" . $user->uerid() . "的帐号", " "); 
                //
            } else show_information("未知原因，激活失败");

        } else {
            //show_information("NetID验证失败，无法激活");
            echo $data;
        }
    } 
}

function auth_by_confirm()
{
    global $tpl;
    global $user;

    if ($_SERVER['REQUEST_METHOD'] == "POST")  {
        $arr = array();
        $arr['realname'] = $_POST['realname'];
        $arr['major'] = $_POST['major'];
        $arr['birthyear'] = $_POST['birth-year'];
        $arr['birthmonth'] = $_POST['birth-month'];
        $arr['birthday'] = $_POST['birth-day'];
        $arr['student_id'] = $_POST['student_id'];
        $arr['email'] = $_POST['email'];
        $arr['phone'] = $_POST['phone'];
        $arr['year'] = $_POST['year'];

        $chk = check_confirm_info($arr);
        if($chk['result'] == 'yes')
        {
            $urec = ext_get_urec($user->userid());
            $urec['userlevel'] |= (PERM_WELCOME | PERM_DEFAULT);
            $urec['reginfo'] = $chk['reginfo'];
            $res = ext_update_urec($user->userid(), array('userlevel' => $urec['userlevel'],
            'reginfo' => $urec['reginfo']));
            if($res) {
                echo "yes";
                //security_report();
                ext_security_report($user->userid(), "新版使用校友验证激活".$user->userid()."的帐号", " ");
            } else echo "系统错误，请联系管理员";
        } else echo $chk['result'];
    } else {
        $tpl->loadTemplate("standard/forms/confirm_form.html");  
        echo $tpl->render();
    } 
}

?>
