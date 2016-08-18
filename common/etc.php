<?php
require_once("common/functions.php");
require_once("common/config.php");

/* for operation with BBS_HOME/etc/ */
/* begin with "etc_" is recommended  */

/* 十大 */
function etc_top_ten() {
	chdir(BBSHOME . "/etc");
	$arr = file("posts/http.day");
	foreach ($arr as &$top) {
		$key = array("author", "title", "board", "filename", "time", "num");
		$top = array_combine($key, explode("\t", $top));
	}
	
	return $arr;
}

//ad hoc，增加推荐文章
function etc_add_recom($file, $bname, $filename)
{
    global $user;
    $fh = ext_getfileheader($bname, $filename);
    if(!$fh) {
        echo "推荐失败，文章不存在";
        return ;
    }
    chdir(BBSHOME . "/etc");
    $file = fopen("posts/" . $file, "a");
    flock($file, LOCK_EX);
    fwrite($file , $fh->title . "\t" . $bname . "\t" . $filename . "\t" . $fh->filetime . "\t" . $user->userid() . "\n");
    flock($file, LOCK_UN);
    fclose($file);
    echo "推荐成功";
}

//读取推荐文章，最后的若干篇
function etc_get_recom($file, $num)
{
    chdir(BBSHOME . "/etc");
    if(!file_exists("posts/" . $file)) return array();
	$arr = file("posts/" . $file);
    $newarr = array();
	foreach ($arr as &$top) {
		$key = array("title", "bname", "filename", "time", "recomer");        
		$top = array_combine($key, explode("\t", $top));
        if(ext_getfileheader($top['bname'], $top['filename']))  //筛选那些被删的帖子
            $newarr []= $top;
	}
    $arr = $newarr;
    $ret = array();
    $total = count($arr);
    for($i = $total-1; $i>=0; $i--)
    {
        if($total - $i <=$num) //取最后的若干个 
            $ret []= $arr[$i];
        else break;
    }
        //清除多余的
    
    $solid_lim = 200;
    $soft_lim = 150;
    if($total >= $solid_lim) { //若>solid_lim个则清除到soft_lim个
        $file = fopen("posts/" . $file, "w");
        flock($file, LOCK_EX);
        for($i=0; $i<$total; $i++)
        {
            if($total - $i <= $soft_lim) {
                    //print_r($arr[$i]);            echo "<br />";
                fputs($file, $arr[$i]["title"] . "\t" . $arr[$i]["bname"] . "\t" . $arr[$i]["filename"] . "\t" . $arr[$i]["time"] . "\t" . $arr[$i]["recomer"] );
            }
        }
        flock($file, LOCK_UN);
        fclose($file);
    }
        //
    if(count($ret)) {
        foreach($ret as &$top) {
            $top["time"] = show_last_time($top["time"]);
            $top['recomer'] = rtrim($top['recomer']);
                //$top['recomer'] = str_replace("\n", " ",  $top['recomer']);
        }
    }
    return $ret;
}


/*本周十大*/
function etc_week_ten() {
	chdir(BBSHOME . "/etc");
	$arr = file("posts/http.week");
    $cnt = 0;
    $ret = array();
	foreach ($arr as &$top) {
        $cnt++;
		$key = array("author", "title", "board", "filename", "time", "num");
		$top = array_combine($key, explode("\t", $top));
        $ret[] = $top;
        if($cnt >= 10) break;
	}
    return $ret;
}

/* 检查是否含有关键字 */
function etc_keyword_check($content) {
	chdir(BBSHOME . '/etc');
	$keywords = file_get_contents("filter_words");
	$expr = str_replace("\n", "|", $keywords);
	$expr = trim($expr, "|");
	return preg_match('/' . $expr . '/', $content);
}

function etc_birthday_today() //今日生日之用户
{
    chdir(BBSHOME);
    $arr = file("etc/birthday_today");
    foreach($arr as &$user)
        $user = rtrim($user);
    natcasesort($arr);
    return $arr;
}

//用于wiki的处理
function etc_wiki_list($listdir, $listname)
{    
    chdir(BBSHOME . "/etc");
    if(!is_dir($listdir)) {        
        mkdir($listdir);
    }
    chdir(BBSHOME . "/etc/" . $listdir);
    
	$arr = file($listname); // $listdir / $listname  store the wiki list
    
	foreach ($arr as &$term) {
		$key = array("term_name", "exp"); //name and explanation
		$term = array_combine($key, explode(":", $term));
	}
	return $arr;
}

function etc_set_content($listname, $term_name, $content)
{
    chdir(BBSHOME . "/etc/" . $listname);
    $res = file_put_contents($term_name, $content);
    return $res;
}

function etc_get_content($listname, $term_name)
{
    chdir(BBSHOME . "/etc/" . $listname);
    $res = file_get_contents($term_name);
    return $res;
}

function etc_section_list() {
	/* 可预见的未来不会改变，所以直接像原web一样不分析menu.ini了 */
	/* ext_getsections(); */
	
	static $secs = array(
		array('seccode' => '0', 'secname' => 'BBS 系统'),
		array('seccode' => 'u', 'secname' => '校园社团'),
		array('seccode' => 'z', 'secname' => '院系交流'),
		array('seccode' => 'c', 'secname' => '电脑科技'),
		array('seccode' => 'r', 'secname' => '休闲娱乐'),
		array('seccode' => 'a', 'secname' => '文化艺术'),
		array('seccode' => 's', 'secname' => '学术科学'),
		array('seccode' => 't', 'secname' => '谈天说地'),
		array('seccode' => 'b', 'secname' => '社会信息'),
		array('seccode' => 'p', 'secname' => '体育健身')
		);
	
	return $secs;
	
}

function etc_check_outcampus_ip($ip) 
{
	if ( substr($ip, 0, 7) == "::ffff:") {
		$ip = substr($ip, 7);
	}
	chdir(BBSHOME . "/etc");
	if (!file_exists(SYSU_IP_LIST)) return false;
	$file = fopen(SYSU_IP_LIST, "r");	
	if (!$file) return false;
	
	$ip_cur = ip_str2int($ip);
	while ($arr = fscanf($file, "%s\t%s\n")) {
		if (count($arr) != 2) continue;
		$ip_start = ip_str2int($arr[0]);
		$ip_end = ip_str2int($arr[1]);
		if ($ip_start <= $ip_cur && $ip_cur <= $ip_end)
			return false;
	}
	fclose(file);

	return true;	
}

function etc_get_errorcode()
{
    chdir(BBSHOME . "/etc");
    if (!file_exists(API_ERRCODE)) return array();
    $file = fopen(API_ERRCODE, "r");
    $res = array();
    while ($arr = fscanf($file, "%s\t%s\n")) {
        $res[$arr[0]] = $arr[1];
    }
    fclose($file);
    return $res;
}

function etc_get_ads()
{
    $lines = file(BBSHOME . '/etc/ads');
    foreach($lines as &$ads)
    {
        $ads =explode( ',', $ads );
    }
    return $lines;
}

/* `File as Json ` Database */
function fjdb_get($path)
{
    if(!file_exists($path)) return ;
    $content = file_get_contents($path);
    return json_decode($content, true);
}
function fjdb_set($dir, $key, $data)
{
    if(!is_dir($dir))
    {
        mkdir($dir, 0770, true);
    }
    return file_put_contents($dir . $key, json_encode($data));
}
function fjdb_lget($path)
{
    $path = BBSHOME . '/etc/' . $path;
    if(!file_exists($path)) return null ;
    return file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

function etc_get_board_www($boardname)
{
    return fjdb_get(BBSHOME . '/etc/boards/' . $boardname . '/www');
}
function etc_set_board_www($boardname, $data)
{
    return fjdb_set(BBSHOME . '/etc/boards/' . $boardname,
                    '/www', $data);
}

function etc_get_user_setting($userid)
{
    $res =  fjdb_get(BBSHOME . '/home/' . substr(strtoupper($userid), 0, 1) . '/' . $userid . '/setting.json') ;
    if(is_null($res))
    {
        $res = array();
    }
    return $res;
}
function etc_set_user_setting($userid, $data)
{
    return fjdb_set(BBSHOME . '/home/' . substr(strtoupper($userid), 0, 1) . '/' . $userid . '/' ,  'setting.json', $data);
}
                    
function etc_get_www()
{
    return fjdb_get(BBSHOME . '/etc/www');
}
function etc_set_www($data)
{
    return fjdb_set(BBSHOME . '/etc', '/www', $data);
}
function etc_set_www_gbk($data)
{
    return fjdb_set(BBSHOME . '/etc', '/www', utf82gbk($data));
}

?>
