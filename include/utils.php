<?php

/* Json */

function json_finish()
{
    die();
}

function utf8_ss($data){
	if (is_array($data)) {
		return array_map('utf82gbk', $data);
	}
	if (is_object($data)) {
		return array_map('utf8gbk', get_object_vars($data));
	}
    $data = @iconv('utf-8','gbk//IGNORE', $data);
    return @iconv('gbk','utf-8//IGNORE', $data);
}

function json_success($data)
{
    header("Content-type: application/json");
    if(array_key_exists('content', $_GET))
    {
        $data['cotent'] = $_GET['content'];
    }
    echo json_encode($data);
}

function json_error($msg, $code=-1)
{
    header("Content-type: application/json");
    if(array_key_exists('content', $_GET))
    {
        $data['cotent'] = $_GET['content'];
    }
    echo json_encode(array('message' => $msg,
                           'code' => $code));
    json_finish();
}

function json_assert($cond, $msg, $code=-1)
{
    if(!$cond)
    {
        header("Content-type: application/json");
        echo json_encode(array('message' => $msg,
                               'code' => $code));
        json_finish();
    }
}

function json_assert_POST()
{
    json_assert($_SERVER["REQUEST_METHOD"] == 'POST',
                'Only accept POST method.', 101);
}

function json_assert_param($arr)
{
    $not_found = "";
    $numargs = func_num_args();
    $arg_list = func_get_args();
    for($i = 1; $i < $numargs; $i++)
        if (!isset($arr[$arg_list[$i]]))
            $not_found .= $arg_list[$i] . ", ";
    json_assert(!$not_found, "Param error: " . $not_found . " not found.", 102);
}

function json_assert_login()
{
    json_assert(UserSession::get_cookie_user()->is_login(),
                "Please login first", 301);
}

function cc($str)
{
    return @iconv('GBK', 'UTF-8//TRANSLIT', $str);
}

function ccc($str)
{
    $str = @iconv('GBK', 'UTF-8//TRANSLIT', $str);
    $str = preg_replace('/[\x00-\x1F\x7F]/', ' ', $str);
    return $str;
}

function dump_json($dir, $key, $data)
{
    if(!is_dir($dir))
        mkdir($dir, 0770, true);
    return file_put_contents($dir . '/' . $key, json_encode($data));
}

function load_json($dir, $key)
{
    $path = $dir . '/' . $key;
    if(!file_exists($path))
        return null;
    return json_decode(file_get_contents($path), true);
}

/* == */

function make_range($cur, $max, $p, &$prev, &$next)
{
    $prev = $cur - $p;
    if($prev < 0) $prev = null;
    $next = $cur + $p;
    if($next > $max) $next = null;
}

/* log */

function debug($mesg)
{
    global $user;

	if (!is_object($user)) { 
		if (isset($_COOKIE["userid"])) $userid = $_COOKIE["userid"]; 
		else $userid = "guest";                                     
	} else $userid = $user->userid();
	
    chdir(BBSHOME);

    if (!is_object($user)) {
        if (isset($_COOKIE["userid"])) $userid = $_COOKIE["userid"]; 
        else $userid = "guest";
    } else  $userid = $user->userid();
 
    $file = fopen("phplog/trace", "a");
    flock($file, LOCK_EX);
    fputs($file, $userid . " " . date("Y-m-d H:i:s ") . " " . $_SERVER['REMOTE_ADDR']
 . " ". $mesg . "\n");
    flock($file, LOCK_UN);
    fclose($file);
}


?>
