<?php
require_once("etc.php");
require_once("log.php");

/*************** Encode/Decode *********************/

function pydo($cmd, $params)
{
    ob_start();
    system(PHPBBS_HOME . '/pydo.py ' . $cmd . ' '
           . escapeshellarg(json_encode($params)), $retcode);
    $res = ob_get_clean();
    $ret = json_decode($res);
    if($ret)
        return $ret->data;
}

function utf82gbk($data) {
	if (is_array($data)) {
		return array_map('utf82gbk', $data);
	}
	if (is_object($data)) {
		return array_map('utf8gbk', get_object_vars($data));
	}
	return @iconv('utf-8','gbk//IGNORE', $data);
}

/* json_encode �Ĳ���ֻ����utf8���룬����һ��null */
function gbk2utf8($data) {
	if (is_array($data)) {
		return array_map('gbk2utf8', $data);
	}
	if (is_object($data)) {
		return array_map('gbk2utf8', get_object_vars($data));
	}
	return @iconv('gbk','utf-8//IGNORE', $data);
}

function convert_title($title) 
{
    $title = conv2gbk($title);

	$title = trim($title);
	$title = preg_replace('/[\x00-\x1F\x7F]/', ' ', $title);

    if ($title == '') $title = 'null';

    return $title;
}


/*************  For ajax/json  ********************/

/* json encode after transfering to utf8 */
function _json_encode($data) 
{
    header("Content-type: application/json");
    return json_encode(gbk2utf8($data));
}

function ajax_error2($errmsg, $code = 0)
{
    $arr = array("success" => false, "error" => $errmsg, "code" => $code);
    
    trace_report("AjaxError: $code: $errmsg"); 
    header("Content-type: text/html");
    echo json_encode(gbk2utf8($arr));
    die();
}

function ajax_error($errmsg, $code = 0)
{
    $arr = array("success" => false, "error" => $errmsg, "code" => $code);
    
    trace_report("AjaxError: $code: $errmsg"); 
    header("Content-type: application/json");
    echo json_encode(gbk2utf8($arr));
    die();
}

function ajax_error_utf8($errmsg, $code = 0)
{
    $arr = array("success" => false, "error" => $errmsg, "code" => $code);
    trace_report("AjaxError: $code: $errmsg");
    header("Content-type: application/json");
    echo json_encode($arr);
    die();
}

function ajax_error_code($code, $msg)
{
    $arr = Array("success" => false,
                 "status" => $code,
                 "error" => $msg);
    header("Content-type: application/json");
    echo json_encode(gbk2utf8($arr));
    die();
}

function ajax_success($data) 
{
    $arr = Array("success" => 1, "data" => $data);
    header("Content-type: application/json");
    echo json_encode(gbk2utf8($arr));
    die();
}

function ajax_success_utf8($data)
{ 
    header("Content-type: application/json");
    echo json_encode(Array("success" => 1,
                           "data" => $data));
    die();
}

function ajax_assert($res, $failmsg = "assert fail", $failcode = 0)
{
    if (!$res) {
        ajax_error($failmsg, $failcode);
        die();
    }
}

function ajax_assert_POST()
{
    if (request_method() != 'POST')  {
        ajax_error("Only accept POST method.", 101);
    }
}

function ajax_assert_login()
{
    global $user;
    ajax_assert($user->islogin(), "Please login first", 301);
}

function ajax_assert_param($arr, $params)
{
    $not_found = "";
    foreach($params as $p) {
        if (!isset($arr[$p])) $not_found .= $p . ", ";
    }
    
    if ($not_found) ajax_error("Param error: " . $not_found . " not found.", 102);
}

function ajax_assert_board($boardname)
{
    $pattern = "/\w{2,16}/";
    if (!preg_match($pattern, $boardname)) 
        ajax_error("boardname format not correct.", 401);
}

function ajax_assert_filename($filename)
{
    $pattern = "/M\.\d{9,10}\.A/";
    if (!preg_match($pattern, $filename))
        ajax_error("filename format not correct.", 501);
}


/****************** End ajax ***********************/

function request_method()
{
    return $_SERVER["REQUEST_METHOD"];
}

/* ����Ȩ�޹��˰����б�, ��array_filterʹ�� */
function board_perm_filter($b) {
	global $user;
	return $user->has_read_perm($b);
}

/* ��html�������������ȡ��maxlen���ȵ�ժҪ */
function get_digest_html($content, $maxlen = 280)
{
    $ret = '';
    $len = 0;
    preg_match_all('/(.*?)<br \/>/', $content, $matchs);
    for($i=0; $i < count($matchs[1]); $i++)
    {
        //if($len >= $maxlen)  {
        //    break;
        //}
        //else if($len + strlen($matchs[1][$i]) < $maxlen){
        //    $len += strlen($matchs[1][$i]);
        //    $ret .= $matchs[0][$i] . "|\n";
        //}
        $ret .= $matchs[0][$i];
        if(strlen($ret) > $maxlen) break;
    }
    $ret .= "</font>";
    return $ret; 
}

function user_home_path($userid)
{
    return BBSHOME. '/home/' . substr(strtoupper($userid), 0, 1) . '/' . $userid;
}

function get_avatar_path($userid, $type = "jpg")
{
    $first_char = substr(strtoupper($userid), 0, 1);
    $path = BBSHOME . "/home/" . $first_char . "/" . $userid . "/" . $userid . "." . $type;
    return $path;
}

function get_attach_path($boardname, $filename)
{
    $path = BBSHOME . "/attach/"  . $boardname . "/" . $filename;
    return $path;
}

function save_avatar($file, $userid) 
{
    $tmp_file = $file["tmp_name"];
    $origname = $file["name"];
    //$type_arr = explode("/", $file["type"]);
    //$type = $type_arr[1];
    
    $path = get_avatar_path($userid);
    if (file_exists($tmp_file)) {

        if(function_exists("imagecreatefromjpeg"))
        {
            /* MAY SUPPORT IMG CUT */
            /* Remember to compile gd.so for php first. 
             * Make sure gd.so support jpeg.
             * If "imagecreatefromjpeg" function not found, follow this:
             *  - Install libjpeg first
             *  - Compile gd.so extension in /path/to/php_source_code/ext/gd/
             *  - Set he libjpeg.so path:  ./configure --with-jpeg-dir=/path/to/jpeg_lib/
             *  - make .
             *  - Import gd.so when start the php-cgi or php-fpm
             */
            list($width, $height) = getimagesize($tmp_file);
            /* Compress to 72 x 72 px */
            $new_width = AVATAR_WIDTH;
            $new_height = AVATAR_HEIGHT;
            $image = @imagecreatefromjpeg($tmp_file);
            if(!$image)
                return false;                
            $image_p = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($image_p, $image, 0, 0, 0, 0, 
                               $new_width, $new_height, $width, $height);
        
            imagejpeg($image_p, $path);

            imagedestroy($image_p);
            imagedestroy($image);
        }
        else
        {
            $content = file_get_contents($tmp_file);
            file_put_contents($path, $content);
        }
        return true;
    }
    return false;
}


function my_array_key_exists($key, $arr) 
                                            {
	if (!is_array($arr))
		return false;
	return array_key_exists($key, $arr);
}

function ispicture($type) {
    $pics = array("jpeg", "gif", "png",  "bmp");
    foreach($pics as $pic)
    {
        if(strcasecmp($type, $pic) == 0) return true;
    }
    return false;
}


function get_signature()
{
    global $user;

    $signature = "";
    $sig_arr = ext_get_signatures($user->userid());

    $total = intval(count($sig_arr)/6);
    if(count($sig_arr) % 6) $total++;

    $which = rand(0, $total-1); 

    $index = $which*6;
    while($index < $which*6+6 && $index< count($sig_arr)) {
        $signature .= "\n" . $sig_arr[$index];
        $index ++;
    }
    return $signature;
}


function get_myface($userid)
{
        /*fix me , ad hoc */
    $first_char = substr(strtoupper($userid), 0, 1);
    $facepath = BBSHOME . "/home/" . $first_char . "/" . $userid . "/attach/" . $userid ;
    if(file_exists($facepath)) {
        return $userid ;
    }
    return ;
}

function check_attach($board)
{
    global $user;
    if (isset($_FILES["attach"])) {
        $attach = $_FILES["attach"];

        if(! ($board->flag & BRD_ATTACH)) {
            return false;
        }

        if ($attach["error"] >0) { 
            if ($attach['error'] == 4) return 4;
            return false;
        }

        /* TODO�����type�����Ƿ���ϰ���Ҫ�� */
        //$type = explode("/", $attach["type"]);

        return true;
    }
    return 4;
}


function show_last_time($old)
{
    $now=time();
    $today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    $thisyear =mktime(0, 0, 0, 1, 1, date('Y'));

    if ($now - $old < 12*3600 && $now-$old >= 60*60){
        return   intval(($now-$old)/60/60) . " Сʱǰ";
    }else  if ($now - $old >60 && $now - $old <60*60) {
        return  intval(($now - $old)/60) . " ����ǰ";
    }else if($now - $old <= 60){
        return ($now - $old) . " ��ǰ";
    }
    if($old < $thisyear ) {
        return date('o M d H:i', $old);
    } else if($old < $today - 86400)   {
        return date('m��d�� H:i', $old);
    } else if($old >=$today - 86400 && $old<$today) {
        return  "���� " .  date('H:i', $old);
    } else {
        return  "���� " . date('H:i', $old);
    }
}
function stay_time($stay)
{
    $ret = '';
    $y = intval($stay / (86400*365));
    $stay -= $y * (86400*365);
    $m = intval($stay / (86400*30));
    $stay -= $m * (86400*30);
    $d = intval($stay / 86400);
    $stay -= $d * 86400;
    $h = intval($stay / 3600);
    $stay -= $h * 3600;
    $mi = intval($stay / 60);
    if($y > 0) $ret .= $y . '�� ';
    if($m > 0) $ret .= $m . '�� ';
    if($d > 0) $ret .= $d . '�� ';
    if($h > 0) $ret .= $h . 'ʱ ';
    if($mi > 0) $ret .= $mi . '�� ';
    return $ret;
}
/* �����ռ����� */
function get_total_size($userid=null)
{
    if(is_null($userid))
    {
        global $user;
        $userid = $user->userid();
    }
    $urec = ext_get_urec($userid);
    $lifeval = count_life_value($urec);
        /* 999 ��������512M�ռ䣡��~~ */
    if($lifeval >= 999) return 512*1024*1024 ;
    
        /*������32M��ۣ�354�������ⶥ256M�� */
    if($lifeval >= 364) return 256*1024*1024;
    else return 32*1024*1024 + $lifeval * 224 / 364 * 1024 * 1024 ; 
}


/* ��Ϣ��ʾ���� */
/* $type��ʾ��ʾ�����ͣ�@��ʾ@�ᵽ��r��ʾ�ظ�����, fΪ�Ӻ�����ʾ
   $arr ��Ҫ��ʾ���û�id����
 */

function do_atuser($arr, $board, $filename, $type) 
{
    global $user;
    if(count($arr) > 10) {
        echo "��קק��������@̫���û���(<10)��";
        return ;
    }
    foreach($arr as $userid)
    {
            //ext_is_user_exist������ʵ��userid����Ϊ�д�Сд�����⣩
        if($userid=ext_is_user_exist($userid)) {
            ext_add_msg($user->userid(), $userid, $board, $filename, $type); 
        }
    }
}

/* վ�Ѽ��� */
function get_user_level($user)
{
    $ret = 'δ����';
    $permlist = array(PERM_LOGINOK, PERM_BOARDS, PERM_SYSOP, PERM_XEMPT);
    $permname = array('����', '����', '��վվ��', '�����˺�');
    for($i=0; $i<4; $i++)
    {
        if($user->hasperm($permlist[$i])) $ret = $permname[$i];
    }
    if($user->hasperm(PERM_LOGINOK) && !$user->hasperm(PERM_POST))
        $ret = "ȫվ����ing";    
    return $ret;
}

function get_size($filesize)
{
    if($filesize > 1024*1024)
        return round($filesize/1024/1024, 1) . "M";
    else if ($filesize > 1024)
        return round($filesize/1024, 1) . "K";
    else if ($filesize >0)
        return $filesize . "B" ;
}

function conv2gbk($code)
{
    return  preg_match('!\S!u', $code) ? utf82gbk($code) : $code;
}

function count_life_value(&$urec)
{
    $i=(time() - $urec['lastlogin'])/60;
    if(($urec['userlevel'] & PERM_XEMPT))
        return 999;
    if($urec['numlogins'] <= 3 && !($urec['userlevel'] & PERM_WELCOME))
        return (15*1440-$i)/1440;
    if(!($urec['userlevel'] & PERM_LOGINOK))
        return (30*1440-$i)/1440;
    if($urec['stay']>1000000)
        return (365*1440-$i)/1440;
	if ($urec['userlevel'] & PERM_SUICIDE)
		return (3 * 1440 - $i) / 1440;
    return (120*1440-$i)/1440;
}
function get_constellation($month, $day)
{
    $date=$month*100+$day;
    if($month<1 || $month>12 || $day<1 || $day>31) return "����";
    if($date<121 || $date>=1222) return "Ħ����";
    if($date<219) return "ˮƿ��";
    if($date<321) return "˫����";
    if($date<421) return "ĵ����";
    if($date<521) return "��ţ��";
    if($date<622) return "˫����";
    if($date<723) return "��з��";
    if($date<823) return "ʨ����";
    if($date<923) return "��Ů��";
    if($date<1024) return "�����";
    if($date<1123) return "��Ы��";
    if($date<1222) return "������";
    return NULL;
}
function ModeType($mode)
{
    switch ($mode & ~STAT_WWW) {        
        case STAT_IDLE:  return ""; 
        case STAT_NEW:  return "��վ��ע��"; 
        case STAT_LOGIN:  return "���뱾վ"; 
        case STAT_DIGESTRACE:  return "���������"; 
        case STAT_MMENU:  return "��ѡ��"; 
        case STAT_ADMIN:  return "������ѡ��"; 
        case STAT_SELECT:  return "ѡ��������"; 
        case STAT_READBRD:  return "һ����ɽС"; 
        case STAT_READNEW:  return "����������"; 
        case STAT_READING:  return "Ʒζ����"; 
        case STAT_POSTING:  return "�ĺ��ӱ�"; 
//    case STAT_MAIL:  return "�����ż�"; 
        case STAT_SMAIL:  return "�����Ÿ�"; 
        case STAT_RMAIL:  return "�����ż�"; 
        case STAT_TMENU:  return "����ѡ��"; 
        case STAT_LUSERS:  return "��������:)"; 
        case STAT_FRIEND:  return "Ѱ�Һ���"; 
        case STAT_MONITOR:  return "̽������"; 
        case STAT_QUERY:  return "��ѯ����"; 
        case STAT_TALK:  return "����"; 
        case STAT_PAGE:  return "����"; 
        case STAT_CHAT1:  return "���ʻ�����"; 
        case STAT_CHAT2:  return "���Ⱥ���"; 
        case STAT_CHAT3:  return "Chat3"; 
        case STAT_CHAT4:  return "Chat4"; 
        case STAT_LAUSERS:  return "̽������"; 
        case STAT_XMENU:  return "ϵͳ��Ѷ"; 
        case STAT_VOTING:  return "ͶƱ��..."; 
        case STAT_EDITUFILE:  return "�༭���˵�"; 
        case STAT_EDITSFILE:  return "����ϵͳ��"; 
        case STAT_ZAP:  return "����������"; 
        case STAT_SYSINFO:  return "���ϵͳ"; 
        case STAT_DICT:  return "�����ֵ�"; 
        case STAT_LOCKSCREEN:  return "��Ļ����"; 
        case STAT_NOTEPAD:  return "���԰�"; 
        case STAT_GMENU:  return "������"; 
        case STAT_MSG:  return "��ѶϢ"; 
        case STAT_USERDEF:  return "�Զ�����"; 
        case STAT_EDIT:  return "�޸�����"; 
        case STAT_OFFLINE:  return "��ɱ��.."; 
        case STAT_EDITANN:  return "���޾���"; 
        case STAT_LOOKMSGS:  return "�쿴ѶϢ"; 
        case STAT_WFRIEND:  return "Ѱ������"; 
        case STAT_WNOTEPAD:  return "���߻���"; 
        case STAT_BBSNET:  return "BBSNET";
        case STAT_WINMINE:  return "����ɨ��"; 
        case STAT_FIVE:  return "��ս������"; 
                //   case STAT_WORKER:  return "������"; 
        case STAT_PAGE_FIVE:  return "��������"; 
        default:  return "��˷����";
    }
    
}

function show_information($message, $direct = "/main/")
{
    global $tpl;
    $tpl->loadTemplate("standard/show_information.html");
    echo $tpl->render(array("message" => $message,
                            "direct" => $direct));
}


function check_multi_register($netid)
{
    $res = ext_count_register($netid);
    if($res >= 3) return true;
    else return false;
}

function check_multi_register2($netid)
{
    $res = ext_count_register($netid);
    if($res >= 3) return true;
    else return false;
}

function bad_user_id($userid)
{
    chdir(BBSHOME);

    $arr = file("etc/bad_id");
    
    if(count($arr))  {
        foreach($arr as $pattern)   
        {
            if(strstr($userid, "#")) continue;
            $pattern = substr($pattern, 0, strlen($pattern)-1);
            if(fnmatch($pattern, $userid))  {
                return true;
            }
        }
    }
    return false;
}

function get_dept($year)
{
    chdir(BBSHOME . "/auth/" . $year);
    $res = array();

    $res = file("dept"); 

    //eliminate the newline  
    foreach($res as &$r) 
    {
        $r = substr($r, 0, strlen($r)-1);
    }
    return $res;
}

function check_confirm_info($arr)
{
    chdir(BBSHOME . "/auth/" . $arr["year"]); 
    $farr = file($arr["year"]);
    foreach($farr as $f) 
    {
        $info = explode(";", $f); 
        $info[0] = gbk2utf8($info[0]);
        $info[1] = gbk2utf8($info[1]);
        if($arr['realname'] != $info[0]) continue;
        if($arr['major'] != $info[1]) continue;
        if($info[2] != "" && $info[2] != $arr['student_id']) continue;
        $da = explode("-",$info[3]);
        if(isset($da[0]) && $arr['birthyear'] != $da[0]) continue;
        if(isset($da[1]) && intval($arr['birthmonth']) != intval($da[1])) continue;
        if(isset($da[2]) && intval($arr['birthday']) != intval($da[2])) continue;
        
        if(ext_count_register($f, $arr['realname']) >= 3) 
        {
            return array("result" => "���ļ����û����Ѿ��������ƣ�����ʧ��", "reginfo" => "");
        }
        return array("result" => "yes", "reginfo" => ext_igenpass($arr['realname'], $f));
    }
    return array("result" => "�Ҳ���ƥ������", "reginfo" => "");
}

/*
 * Transfer 'a.b.c.d' to int 
 */
function ip_str2int($ip)
{
	$result = 0;
	$arr = explode(".", $ip);
	if ( count($arr) != 4) return 0;
	for ($i = 0; $i < 4; $i++) {
		$result = $result * 256 + intval($arr[$i]);
	}
	return $result;
}

function all_boards_sec()
{
    $secs = ext_getsections();
    if(!$secs) 
    {
        return 202;
    }
    foreach($secs as $s)
    {
        $boards = ext_getboards($s->seccode);
        $boards = array_filter($boards, "board_perm_filter");
        $boards = array_values($boards); /* rebuild keys */
        foreach ($boards as $board) {
            unset($board->level);
            unset($board->flag);
            $board->boardname = $board->filename;
            unset($board->filename);
            unset($board->total_toady);
            $board->title = substr($board->title, 11);
        }
        $s->boards = $boards;
    }
    return $secs;
}

function beautify_board($boards)
{
    global $user;

    foreach($boards as &$board) {

		/* ����ʱ����ʾ */
        //		$board->lastpost = show_last_time($board->lastpost);
        unset($board->level);
        unset($board->flag);

        /* ��ȡ������������ */
		$ret = $board->get_post_list(0, 21, 0);
        $count = -1;
        if (isset($ret->list) && count($ret->list) > 0) {
            $ret->list = array_reverse($ret->list);
			$board->lastpostfile = $ret->list[0]->title;
			$board->lastfilename = $ret->list[0]->filename;
            $board->lastauthor = $ret->list[0]->owner;
            foreach($ret->list as &$p)
            {
                if($p->unread)
                {
                    ++$count;
                }
                else
                {
                    break;
                }
            }
		}
        $board->unreadn = $count;
        $board->boardname = $board->filename;
        $board->unread = ($count != -1);
        unset($board->filename);
	}
}

function check_outcampus_ip()
{
    if(!file_exists(SYSU_IP_LIST)) return true;

    // Convert ipv6 to ipv4
    $ipi = ip2long(substr($_SERVER['REMOTE_ADDR'], 7));

    $text = file(SYSU_IP_LIST);
    foreach($text as &$l)
    {
        $l = split(' ', $l);
        if(($ipi >= ip2long($l[0])) &&
           ($ipi <= ip2long(rtrim($l[1]))))
            return true;
    }
    return false;
}

function get_ip_from_lastline($signature)
{
    $lines = explode("\n", rtrim($signature));
    $lines = $lines[count($lines)-1];
    preg_match('[FROM: (\d+.\d+.\d+.\d+)]', $lines, $matchs);
    if($matchs){
        return $matchs[1];
    }else{
        return false;
    }
}

function get_address($ip)
{
    if(!file_exists(ADDRESS_LIST)) return false;

    // Convert ipv6 to ipv4
    $ipi = ip2long($ip);

    $text = file(ADDRESS_LIST);
    foreach($text as &$l)
    {
        $l = explode(' ', $l);
        if(($ipi >= ip2long($l[0])) &&
           ($ipi <= ip2long(rtrim($l[1]))))
            return trim($l[2]);
    }
    return false;
}

function do_simple_post($userid, $boardname, $title, $content)
{
    global $user;
    return ext_simplepost(array('userid' => $userid,
                                'board' => $boardname,
                                'fromaddr' => $user->from(),
                                'anonymous' => 0,
                                'title' => utf82gbk($title),
                                'content' => utf82gbk($content),
                                "articleid" => '',
                                "signature" => '',
                                "reply_notify" => 0,
                                "attach_tmpfile" => '',
                                "attach_origname" => '',
                                'attach_type' => ''));
}

function report_register($netid, $dept, $address, $realname, $email)
{
    global $tpl;
    global $user;
    $tpl->loadTemplate('report/register.html');
    $datetime = new DateTime();
    $userid = $user->userid();
    $ret = $tpl->render(array('userid' => $userid,
                              'netid' => $netid,
                              'dept' => $dept,
                              'address' => $address,
                              'from' => $user->from(),
                              'email' => $email,
                              'time' => $datetime,
                              'realname' => $realname));
    do_simple_post($userid, 'syssecurity', gbk2utf8('���� ' . $userid . ' ���ʺ�'), $ret);
}

?>
