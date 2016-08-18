<?php
require_once("common/functions.php");
require_once('include/meekrodb.php');
require_once('include/config.php');

/*
 * Login 
 * @param $userid, $passwd
 * @return {success: "1", data: "..."}
 *         {success: "", error: "..."}
 */
function ajax_login() 
{
    global $user;

    ajax_assert_POST();    
    ajax_assert_param($_POST, array("userid", "passwd"));

    $userid = $_POST["userid"];
    $passwd = $_POST["passwd"];

    ajax_assert(ctype_alpha($userid), "Invalid userid", 303);
    
    $urec = ext_get_urec($userid);
    if (!$urec) ajax_error("No such user", 307);
    
    $user->login($userid, $passwd, true);

    if ($user->islogin()) ajax_success("Login success");
    else ajax_error("Login wrong password", 308);

    return ;
}

/*
 * Logout
 * @param $userid, $passwd
 * @return {success: "1", data: "..."}
 *         {success: "", error: "..."}
 */
function ajax_logout() 
{
    global $user;
    ajax_assert_login();
    $user->logout();
    
    if ($user->islogin())   ajax_error("Logout failed", 309);
    else ajax_success("Logout success.");
}

/*
 * Get user info
 * @param $userid
 * @return {success: "1", data: <user_info object>}
 *         {success: "", error: ""}
 */
function ajax_user_query()
{
    global $user;

    ajax_assert_param($_GET, array("userid"));
    $userid = $_GET["userid"];
    ajax_assert(ctype_alpha($userid), "Invalid userid", 303);
    $user->set_stat(STAT_QUERY);
    $urec=ext_get_urec($userid);

    $uinfo=ext_get_uinfo($userid);

    if(count($urec) == 0) 
        ajax_error("User not found.", 307);
    
    $user_info = array();
    $user_info['numlogins'] = $urec['numlogins'];
    $user_info['numposts'] = $urec['numposts'];

    $user_info['userid'] = $urec['userid'];
    $user_info['username'] = $urec['username'];
    $user_info['usertitle'] = $urec['usertitle'];

    $user_info['life_value'] = intval(count_life_value($urec));      
    $user_info['has_mail'] = ext_check_mail($urec['userid']);
    $user_info['lastlogout'] = $urec['lastlogout'] ;
                         //date("M d Y", $urec['lastlogout']);
    $user_info['lastlogin'] = $urec['lastlogin'] ;
                         //date("M d Y",$urec['lastlogin']);
    $user_info['stay'] = stay_time($urec['stay']);
    $user_info['constellation'] = get_constellation($urec['birthmonth'], $urec['birthday']);
    $user_info['male'] = $urec['gender'] ==77 ? true : false;
    $user_info['plan'] = ext_get_whole_file($urec['userid'], "plans", 0); /* raw content */
    $user_info['signature'] = ext_get_whole_file($urec['userid'], "signatures", 0); /* raw content */

    /* 对sysop，呈现真实姓名 */
    if($user->hasperm(PERM_SYSOP)) {
        $user_info['realname'] = $urec['realname'];
    }

    //    if(strlen($user_info['plan']) <= 25)unset($user_info['plan']);    
    if($uinfo) {
        $user_info['online'] = true;
        $user_info['mode'] = ModeType($uinfo['mode']);
    }

    $userid = $urec['userid'];

    if(file_exists(BBSHOME . '/0Announce/personal/' . strtoupper($userid[0]) . '/' . $userid))
    {
        $user_info['has_works'] = true;
    }

    $user_info = gbk2utf8($user_info);

    $title_file = user_home_path($userid) . '/title';
    if(file_exists($title_file))
    {
        $filearray = explode("\n", file_get_contents($title_file));
        foreach($filearray as &$t)
        {
            $t = explode(" ", $t);
        }
        $user_info['title'] = $filearray;
    }
    
    ajax_success_utf8($user_info);
    return ;
}

function ajax_update_user_title()
{
    global $user;
    ajax_assert_POST();
    ajax_assert_login();

    if($user->hasperm(PERM_SYSOP))
    {
        ajax_assert_param($_POST, array('userid', 'content'));
        file_put_contents(user_home_path($_POST['userid']) . '/title',
                          $_POST['content']);
        ajax_success('Update success.');
    }

    ajax_error('Perm error.');
    return;
}

/*
 * Update userinfo
 * @param: (all optional)
 *      - passwd: If change passwd, old-passwd and confirm-passwd should be provided.
 *          - old-passwd: The old passwd.
 *          - confirm-passwd: Should be the same with passwd.(The new passwd)
 *      - username
 *      - realname
 *      - gender: 'M' or 'F'
 *      - address
 *      - email
 *      - birthyear: 50-99
 *      - birthmonth: 1-12
 *      - birthday: 1-31
 *      - plan: Personal description.
 *      - signature 
 *      - www: Other web settings. 
 *      - avatar: upload FILE
 * 
 * @return {success: "1", data: ""} 
 *         {success: "", error: ""}
 */
function ajax_user_update()
{

    global $user;

    ajax_assert_POST();
    ajax_assert_login();

    $user->set_stat(STAT_USERDEF);

    // Update passwd
    if (array_key_exists("passwd", $_POST))  {
        ajax_assert_param($_POST, array("old-passwd", "confirm-passwd")); 
        if (!ext_checkpassword($user->userid(), $_POST['old-passwd']))
            ajax_error2("Old passwd not match.", 317); 

        if ($_POST['passwd'] == "") 
            ajax_error2("New passwd can not be empty.", 318);

        if ($_POST['confirm-passwd'] != $_POST['passwd'])
            ajax_error2("Confirm passwd not match.", 302);
        $md5passwd = ext_igenpass($user->userid(), $_POST["passwd"]);
        $res = ext_update_urec($user->userid(), array("passwd" => $md5passwd));
    
        if (!$res) ajax_error2("Update passwd failed.", 319);

        unset($_POST['passwd']);
        unset($_POST['old-passwd']);
        unset($_POST['confirm-passwd']);
    }
    
    // Update urec 
    $urec_attrs = array("username", "realname", "gender", "email", "address", "birthyear",
                        "birthmonth", "birthday");
    $urec = array();
    if (isset($_POST["gender"]))  {
        if ($_POST['gender'] != 'M' && $_POST['gender'] != 'F')
            ajax_error2("Gender should be M or F", 305);
        $urec['gender'] = ($_POST['gender'] == "M") ? 77 : 70;
    }
    foreach (array("birthyear", "birthmonth", "birthday") as $bir) {
        if (isset($_POST[$bir]))
            $urec[$bir] = intval($_POST[$bir]);
    }

    foreach (array("username", "realname") as $attr) {
        if (isset($_POST[$attr]))
            $urec[$attr] = conv2gbk($_POST[$attr]);
    }
    if (count($urec)) {
        $res = ext_update_urec($user->userid(), $urec); 
        if (!$res) ajax_error2("Update urec info error.", 320);
    }

    // Update plan/signature/www
    if (isset($_POST["plan"])) {
        $plan = conv2gbk($_POST['plan']);
        $res = ext_set_whole_file($user->userid(), "plans", $plan);
        if (!$res) 
            ajax_error2("Update plan fail.", 321);
    }
    if (isset($_POST["signature"])) {
        $signature = conv2gbk($_POST['signature']);
        $res = ext_set_whole_file($user->userid(), "signatures", $signature);
        if (!$res) 
            ajax_error2("Update signature fail.", 322);
    }
    
    //www hanged.
    //

    // avatar

    if (isset($_FILES["avatar"])) {
        $errcode = $_FILES["avatar"]["error"];
        if ($errcode == 4) goto update_last;
        else if ($errcode != 0 ) {
            ajax_error2("Upload avatar error ". $errcode, 323);
        }

        $file = $_FILES["avatar"]; 
        /* if (!in_array($file["type"], array("image/jpeg"))) { */
        /*     ajax_error2("Avatar only accept .jpg", 324); */
        /* } */
        $res = save_avatar($file, $user->userid());
        if (!$res) ajax_error2("Save avatar fail.", 325);
    }

update_last:
    trace_report("Update userinfo");
    if(isset($_POST['r_html']))
    {
        // Set this header to fix download bug in IE
        header("Content-type: text/html");
        $data = Array("success" => true,
                      "data" => "Update success.");
        if(isset($_FILES['avatar']))
        {
            $data['avatar'] = true;
        }
        echo json_encode($data);
        die();
    }
    else
    {
        ajax_success("Update success");
    }
    return ;
}

/*
 * Get user setting
 */
function ajax_user_get_setting()
{
    global $user;
    ajax_assert_login();
    ajax_success(etc_get_user_setting($user->userid()));
}

/*
 * Update user setting number
 * @param:
 *    update: dict
 *
 * @return {success: "1", data: ""}
 *         { success: "", error: ""}
 */
function ajax_user_update_setting()
{
    global $user;

    ajax_assert_POST();
    ajax_assert_login();

    ajax_assert_param($_POST, array("update"));

    $user->set_stat(STAT_USERDEF);
    $d = etc_get_user_setting($user->userid());
    $maybe = array('no_hint_mail', 'no_hint_fav');
    foreach($_POST['update'] as $k => $v)
    {
        if(!in_array($k, $maybe))
            ajax_error("Unvailed key field. $k");
        if(!is_numeric($v))
            ajax_error('New value should be number.');
        $d[$k] = $v;
    }
    etc_set_user_setting($user->userid(), $d);
    ajax_success('update success.');
}

/*
 * Get user record info. 
 * @param None
 * @return {success: "1", data: <urec ojbect>}
 *         {success: "", error: ""}
 */
function ajax_user_info()
{
    global $user;
    $user->islogin();
    ajax_assert_login();

    $urec = ext_get_urec($user->userid());

    if (!$urec) ajax_error("Get user info failed.", 307);

    if(!($urec['userlevel'] & PERM_WELCOME))
    {
        $urec['notauth'] = true;
    }
    
    //unset  the secret attrs    
    foreach (array("passwd", "userlevel", "notedate", "noteline", "reginfo", "userdefine", "flag") as $banattr) {
        unset($urec[$banattr]);
    }

    // get_whole_file: 0 raw text, 1 with html tag
    $urec['plan'] = ext_get_whole_file($user->userid(), "plans", 0);
    $urec['signature'] = ext_get_whole_file($user->userid(), "signatures", 0);
    $urec['web_stay'] = $_SESSION['lastrefresh'] - $_SESSION['logintime'];
    /* if(defined('SYSU_IP_LIST')){ */
    /*     $urec['ban_post'] = !check_outcampus_ip(); */
    /* } */
    ajax_success($urec);
    return ;
}

/*
 * Get faviorite board list.
 * @param no param
 * @return {success: "1", data: [<board object>, ...]}
 *         {success: "", error: "No fav board"}
 */
function ajax_getfav()
{
    global $user;
    ajax_assert_login();

    $boards = Board::boards_from_fav();
	$boards = array_filter($boards, "board_perm_filter");
	$boards = array_values($boards); /* rebuild keys */
    beautify_board($boards);
    
    if ($boards) ajax_success($boards);
    else ajax_error("No fav board", 314);
    return ;
}


/*
 * Add fav board
 * @param $boardname
 * @return  {success: "1", data: "..."}
 *          {success: "", error: "..."}
 */

function ajax_addfav()
{
    global $user;
    ajax_assert_POST();
    ajax_assert_param($_POST, array("boardname"));
    ajax_assert_login();

    $boardname = $_POST['boardname'];

	$board = new Board($boardname);
    if (!$board || $user->has_read_perm($board) == false) {
        ajax_error("Board not exist.", 401);
		return;
	}
	
	$boards = Board::boards_from_fav();
	$boards = array_filter($boards, "board_perm_filter");
    
	$fav_boards = array();
    foreach ($boards as $b) {
        if ($b->filename == $board->filename) {
            ajax_success("Already added");
            return;
        }
        $fav_boards[] = $b->filename;
    }

	$fav_boards[] = $board->filename;
	
    $res = ext_addfavboards($user->userid(), $boardname);
    if ($res) ajax_success("Add successed"); 
    else ajax_error("Add failed", 315);
}

/*
 * Delete fav board 
 * @param  $boardname
 * @return  {success: "1", data: "..."}
 *          {success: "", error: "..."}
 */
function ajax_delfav()
{
    global $user;
    ajax_assert_POST();
    ajax_assert_param($_POST, array("boardname"));
    ajax_assert_login();

    $boardname = $_POST['boardname'];
	$board = new Board($boardname);
    if (!$board || $user->has_read_perm($board) == false) {
        ajax_error("Board not exists", 401);
		return;
	}

    $res = ext_delfavboards($user->userid(), $boardname);
    if ($res) ajax_success("Delete successed"); 
    else ajax_error("Delete failed", 316);

}

function ajax_setfav()
{
    global $user;
    ajax_assert_POST();
    ajax_assert_login();
    ajax_assert_param($_POST, array('boards'));

    $boards = array_unique($_POST['boards']);
    $ret = '';
    $fav = array();
    foreach($boards as $b)
    {
        if($user->has_read_perm($b))
            $ret .= "$b\n";
    }
    file_put_contents(user_home_path($user->userid()) . '/.goodbrd',
                      $ret);
    ajax_success('success');
}

/*
 * Get friend list.
 * @param none
 * @return {success: "1", data: [{id: "", exp: ""}, ...]}
 */
function ajax_friend()
{
    global $user;  
    
    ajax_assert_login();
    $overrides = ext_get_override($user->userid(), "friends");

    if(count($overrides)) {
        foreach($overrides as &$over) {
            $uinfo = ext_get_uinfo($over->id);
            if($uinfo) {
                $over->mode = ModeType($uinfo['mode']);
            }
        }
    }

    ajax_success($overrides);    
}

/*
 * Add a friend.
 * @param $id: userid 
 *        $exp: comment or explain of this friend.
 * @return {success: "1", data: ...}
 */
function ajax_add_friend() 
{
    global $user;
    
    ajax_assert_POST();
    ajax_assert_login();
    ajax_assert_param($_POST, array("id", "exp"));
    
    $id = $_POST["id"];  
    $exp = $_POST["exp"];
    ajax_assert($id != "", "Id can not be empty.", 310);

    
    $res=ext_add_override($user->userid(),$id, $exp, "friends"); /*  add to override */

    if($res == 0) {
        //TODO: Message will use mysql.
        //do_atuser(array($_POST['userid']), "argo", "argo", "f");
    }

    switch ($res) {
        case 0: ajax_success("Add success"); 
        case 1: ajax_error("Already added", 311); 
        case 2: ajax_error("This userid not exists", 307);
        case 3: ajax_error("The number of friends over limit.", 312);
        default: ajax_error("Add failed."); 
    }
}

/*
 * Del a friend;
 * @param $userid
 * @return {success: "1", data: ...}
 *         {success: "", error: ...}
 */
function ajax_del_friend()
{
    global $user;
    
    ajax_assert_POST();
    ajax_assert_login();
    ajax_assert_param($_POST, array("userid")); 

    $userid = $_POST["userid"];
    if ($userid == "") ajax_error("Userid can not be empty", 310);

    $res=ext_del_override($user->userid(), $userid , "friends"); /* del override */

    switch ($res) {
        case 0: ajax_success("Del success.");
        case 1: ajax_error("userid not in friend list", 313);
        default: ajax_error("Del failed.");
    }
}

function ajax_register2()
{
    ajax_assert_POST();
    ajax_assert_param($_POST, array('netid', 'password', 'userid', 'email'));
    if(pydo("check_netid", array("netid" => $_POST['netid'],
                                 "passwd" => $_POST['password'])))
    {
        if(check_multi_register2($_POST['netid']))
        {
            ajax_error_utf8('此Netid注册过多，注册失败。');
            return;
        }
        $para = array('userid' => $_POST['userid'],
                      'pass1' => $_POST['password'],
                      'pass2' => $_POST['password'],
                      'username' => '这家伙还没起昵称',
                      'year' => 1996,
                      'month' => 1,
                      'day' => 1,
                      'gender' => 0,
                      'email' => $_POST['email']);
        $ret = pydo('guess_userinfo', array("netid" => $_POST['netid'],
                                            "passwd" => $_POST['password']));
        if($ret)
        {
            $para['dept'] = '0 ' . $ret[0];
            $para['address'] = '0 ' . $ret[0] . ' ' . $ret[1];
            $para['realname'] = '0 ' . $ret[2];
        }
        else
        {
            $para['dept'] = '0 000000';
            $para['address'] = '0 000000';
            $para['realname'] = '0 000000';
        }
        $para_r = $para;
        $para = utf82gbk($para);
        $ch = curl_init("http://localhost/bbsdoreg");
        curl_setopt($ch, CURLOPT_HEADER, 1);    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
        curl_setopt($ch, CURLOPT_POST, 1);    
        curl_setopt($ch, CURLOPT_POSTFIELDS, $para);
        $ret = curl_exec($ch);    
        curl_close($ch);
        preg_match('/<ul class="search">\r\n([^<]*)<br\/>/', $ret, $right);
        if($right && $right[1]){
            $urec = ext_get_urec($_POST['userid']);
            $urec['userlevel'] |= (PERM_WELCOME | PERM_DEFAULT);
            $res = ext_update_urec($urec['userid'],
                                   array('userlevel' => $urec['userlevel']));
            global $user;
            $user->login($_POST['userid'], $_POST['password'], true);
            report_register($_POST['netid'], $para_r['dept'], $para_r['address'],
                            $para_r['realname'], $_POST['email']);
            if($res){
                trace_report("Auth success. Netid approch.");
                ajax_success(array('msg' => 'Register success.'));
            }
            else
            {
                ajax_error('Registered but not auth.', 330);
            }
            return;
        }
        else{
            preg_match('/<ul class=\"search\"><li>([^<]*)<\/li>/',
                       $ret, $wrong);
            ajax_error_utf8(gbk2utf8($wrong[1]));
        }    
    }
    else
    {
        ajax_error_utf8('netid或者密码错误.');
    }
}

function ajax_self_inv()
{
    ajax_assert_login();
    global $user;
    $data = DB::queryOneColumn('newuserid', "SELECT newuserid FROM Inviter WHERE inviter=%s LIMIT 50", $user->userid());
    $count = DB::queryFirstRow("SELECT count(newuserid) FROM Inviter WHERE inviter=%s", $user->userid());
    ajax_success_utf8(array('totalnum' => $count['count(newuserid)'],
                            'newuserid' => $data));
}

function ajax_register3()
{
    ajax_assert_POST();
    ajax_assert_param($_POST, array('userid', 'password', 'email'));
    if(!isset($_SESSION['netid']))
    {
        ajax_error_utf8('错误的netid！');
        return;
    }
    $netid = $_SESSION['netid'];
    if(check_multi_register2($netid))
    {
        ajax_error_utf8('此Netid注册过多，注册失败。');
        return;
    }
    $para = array('userid' => $_POST['userid'],
                  'pass1' => $_POST['password'],
                  'pass2' => $_POST['password'],
                  'username' => '这家伙还没起昵称',
                  'year' => 1996,
                  'month' => 1,
                  'day' => 1,
                  'gender' => 0,
                  'dept' => '0 000000',
                  'address' => '0 000000',
                  'realname' => '0 000000',
                  'email' => $_POST['email']);
    $para_r = $para;
    $para = utf82gbk($para);
    $ch = curl_init("http://localhost/bbsdoreg");
    curl_setopt($ch, CURLOPT_HEADER, 1);    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
    curl_setopt($ch, CURLOPT_POST, 1);    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $para);
    $ret = curl_exec($ch);    
    curl_close($ch);
    preg_match('/<ul class="search">\r\n([^<]*)<br\/>/', $ret, $right);
    if($right && $right[1]){
        $urec = ext_get_urec($_POST['userid']);
        $urec['userlevel'] |= (PERM_WELCOME | PERM_DEFAULT);
        $res = ext_update_urec($urec['userid'],
                               array('userlevel' => $urec['userlevel']));
        unset($_SESSION['netid']);
        global $user;
        $user->login($_POST['userid'], $_POST['password'], true);
        report_register($netid, $para_r['dept'], $para_r['address'],
                        $para_r['realname'], $_POST['email']);
        if($res){
            trace_report("Auth success. Netid approch.");
            if(isset($_SESSION['inviter']))
            {
                $urec2 = ext_get_urec($_SESSION['inviter']);
                if($urec2)
                {
                    DB::insert('Inviter',
                               array('inviter' => $urec2['userid'],
                                     'newuserid' => $urec['userid']));
                }
            }
            ajax_success(array('msg' => 'Register success.'));
        }
        else
        {
            ajax_error('Registered but not auth.', 330);
        }
        return;
    }
    else{
        preg_match('/<ul class=\"search\"><li>([^<]*)<\/li>/',
                   $ret, $wrong);
        ajax_error_utf8(gbk2utf8($wrong[1]));
    }
}

function ajax_register()
{
    ajax_assert_POST();
    $para = utf82gbk($_POST);
    $ch = curl_init("http://localhost/bbsdoreg");
    curl_setopt($ch, CURLOPT_HEADER, 1);    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
    curl_setopt($ch, CURLOPT_POST, 1);    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $para);
    $ret = curl_exec($ch);    
    curl_close($ch);
    preg_match('/<ul class="search">\r\n([^<]*)<br\/>/', $ret, $right);
    if($right && $right[1]){
        ajax_success('Register success.');
    }
    else{
        preg_match('/<ul class=\"search\"><li>([^<]*)<\/li>/', $ret, $wrong);
        ajax_error_utf8(gbk2utf8($wrong[1]));
    }    
}


/*
 * 验证信息，有两种方式
 * # 用 netid 验证 ajax_auth_netid
 *  1.  浏览器跳转到https://cas.sysu.edu.cn/cas/login?service=<service-url>上
 *      其中service-url是bbs的验证页面url 后面会用到
 *      按目前设计，这个<service-url>应该设置为http://<bbs-site>/ajax/auth/netid/
 *  2. 用户在cas页面验证netid，如果成功，会将用户跳转到:
 *     <service-url>?ticket=<the-ticket>
 *     然后后台获取ticket
 *  3. 后台根据ticket，发请求到:
 *      https://cas.sysu.edu.cn/cas/validate?service=<service-url>&ticket=<the-ticket>
 *      然后如果返回yes，那么表明这个netid是有效的。
 *
 *  @param: $auth_type: netid
 *          $ticket 
 *
 * #. 校友信息验证（2008年前毕业的校友使用）ajax_auth_info
 *  1. 毕业年份，真实姓名，专业（这个要先根据毕业年份ajax取(misc提供获取接口)，用户select来选择）
 *     出生年月，学号
 *     需要以上信息都匹配到bbs_home/auth/1995_2008 
 *     中的资料（但譬如资料里没有学号，那么可以不匹配）
 * @param: $auth_type: info
 *         $year: 毕业年份19xx
 *         $realname: 真实姓名
 *         $major: 专业
 *         $birthyear: 1900-1999
 *         $birthmonth: 1-12
 *         $birthday: 1-31
 *         $student_id: 学号
 *
 * #. 邮箱验证(未实现）
 *  ;;TODO
 * #. 人肉验证（辛苦版大= =）
 *  ;;TODO
 */
function ajax_auth_info()
{
    global $user;
    
    ajax_assert_POST();
    ajax_assert_login();
    
    $urec = ext_get_urec($user->userid());
    if($urec['userlevel'] != PERM_BASIC) 
        ajax_error("You have already auth.", 326);

    ajax_assert_param($_POST, array("year", "realname", "major", 
        "birthyear", "birthmonth", "birthday", "student_id")); 
    $arr = array();
    $arr["year"] = $_POST["year"];
    $arr["realname"] = $_POST["realname"];
    $arr["major"] = $_POST["major"];
    $arr["birthyear"] = $_POST["birthyear"];
    $arr["birthmonth"] = $_POST["birthmonth"];
    $arr["birthday"] = $_POST["birthday"];
    $arr["student_id"] = $_POST["student_id"];
    //$arr["phone"] = $_POST["phone"];
    $chk = check_confirm_info($arr);

    if ($chk["result"] == "yes") {
        $urec = ext_get_urec($user->userid());
        $urec['userlevel'] |= (PERM_WELCOME | PERM_DEFAULT);
        $urec['reginfo'] = $chk['reginfo'];
        $res = ext_update_urec($user->userid(), array('userlevel' => $urec['userlevel'],
            'reginfo' => $urec['reginfo']));
        if($res) {
            //            ext_security_report($user->userid(), conv2gbk("使用校友验证激活 ".
            //   $user->userid() . " 的帐号(ajax接口)"), " ");
            trace_report("Auth success. Info approch.");
            ajax_success("Auth success!");
        }
    } else ajax_error($chk["result"]);
}       

function ajax_auth_netid()
{
    global $user;
    ajax_assert_login();
    ajax_assert_param($_GET, array("ticket"));
    $http_host = $_SERVER["HTTP_HOST"]; 
    $service_url =  "http://" . $http_host . "/ajax/auth/netid/";
    $ticket = $_GET["ticket"];
    $url = "https://cas.sysu.edu.cn/cas/validate?service=" . $service_url . "&ticket=";
    $url .= $ticket; 
    
    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);

    if (strstr($data, "yes")) {
        $urec = ext_get_urec($user->userid());
        //防过多注册
        $netid = substr($data, 4);
        $netid = substr($netid, 0, strlen($netid)-1);
        if(check_multi_register($netid)) {
            ajax_error("此NetID注册过多，激活失败。", 327);
        }
        $urec['userlevel'] |= (PERM_WELCOME | PERM_DEFAULT);
        $res = ext_update_urec($user->userid(), array('userlevel' => $urec['userlevel']));
        if($res)  {

            trace_report("Auth success. Netid approch.");
            ajax_success("激活成功，现在您可以畅游逸仙时空了！");

            //            ext_security_report($user->userid(), "使用NetID激活" . $user->uerid() . "的帐号(ajax接口)", " "); 
            
        } else ajax_error("未知原因，激活失败", 328);


    } else  ajax_error("NetID激活失败", 328);

}

function generateRandomString($length = 20) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function ajax_gen_invcode()
{
    global $user;
    ajax_assert_POST();
    ajax_assert_login();
    if($_POST['password'] != 'argo1996!!!')
    {
        ajax_error('Wrong password.');
    }
    for($i = 0; $i < 10; $i++)
    {
        $code = generateRandomString();
        if(file_exists("invcode/".$code) || file_exists("invcode/=".$code))
        {
            continue;
        }
        file_put_contents("invcode/".$code, $user->userid().'|');
        echo $code . "<br/>";
    }
}

function ajax_auth_invcode()
{
    global $user;
    ajax_assert_login();
    ajax_assert_POST();
    ajax_assert_param($_POST, array("invcode"));
    $urec = ext_get_urec($user->userid());
    if($urec['userlevel'] != PERM_BASIC) 
        ajax_error("You have already auth.", 326);
    $code = $_POST['invcode'];
    if(!ctype_alnum($code))
    {
        ajax_error("Wrong invitation code.");
    }
    if(file_exists("invcode/=".$code))
    {
        ajax_error("This code has been used.");
    }
    if(!file_exists("invcode/".$code))
    {
        ajax_error("No such invitation code.");
    }
    file_put_contents("invcode/".$code, $user->userid()."\n", FILE_APPEND);
    $first_char = substr(strtoupper($user->userid()), 0, 1);
    file_put_contents(BBSHOME . "/home/" . $first_char . "/" . $user->userid()
                      . "/invcode", $code);
    rename("invcode/".$code, "invcode/=".$code);
    $urec = ext_get_urec($user->userid());
    $urec['userlevel'] |= (PERM_WELCOME | PERM_DEFAULT);
    $res = ext_update_urec($user->userid(),
                           array('userlevel' => $urec['userlevel']));
    if($res) {
        //ext_security_report($user->userid(),
        //                    conv2gbk("使用验证码试激活 ".
        //                            $user->userid() . " 的帐号(ajax接口)"),
        //                   " ");
        trace_report("Auth success. Info approch.");
        ajax_success("Auth success!");
    }
    ajax_error("Unkown error.");
}
    
?>
