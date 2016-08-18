<?php
require_once("common/functions.php");

function map_to_board($boards)
{
    $res = array();
    foreach($boards as $b)
    {
        $t = new Board($b);
        if($t->is_vail())
            $res[] = $t;            
    }
    return $res;
}

/* Get Boards in index */
function ajax_goodboards()
{
    global $user;
    if(isset($_GET['type']))
    {
        $type = $_GET['type'];
    }
    else
    {
        if($user->islogin())
        {
            $type = 'fav';
        }
        else
        {
            $type = 'recommend';
        }
    }
    $boards = null;
    if($type == 'fav')
    {
        ajax_assert_login();
        $boards = Board::boards_from_fav();
        $boards = array_filter($boards, "board_perm_filter");
        $boards = array_values($boards); /* rebuild keys */
        beautify_board($boards);
    }
    if($type == 'month')
    {
        $boards = map_to_board(fjdb_lget('sysmonthbrd'));
        beautify_board($boards);
    }
    if($type == 'good')
    {
        $boards = map_to_board(fjdb_lget('sysgoodbrd'));
        beautify_board($boards);
    }
    if($type == 'new')
    {
        $boards = map_to_board(fjdb_lget('sysnewpostbrd'));
        beautify_board($boards);
    }
    if($type == 'hot')
    {
        $boards = map_to_board(fjdb_lget('syshotbrd'));
        beautify_board($boards);
    }
    if($boards)
    {
        $www = etc_get_www();
        $activeboard = fjdb_get(BBSHOME . '/etc/phpactiveboard');
        ajax_success_utf8(array('boards' => gbk2utf8($boards),
                                'activeboard' => $activeboard,
                                'www' => $www));
    }
    else
    {
        ajax_error('No Boards');
    }
}

/*
 * 获取所有boardname列表（在权限可见范围内）
 * @return {success: "1", data : ["Film", "water" ...]}
 *         {success: "", error: "..."}
 */ 
function ajax_allboards() {
	$all_boards = ext_get_allboards();
    $boards = array_filter($all_boards, "board_perm_filter");
    $boards = array_values($boards); /* rebuild keys */
    ajax_success($boards);
	return;
}

function ajax_random_boardname()
{
	$all_boards = ext_get_allboards();
    $boards = array_filter($all_boards, "board_perm_filter");
    $boards = array_values($boards); /* rebuild keys */
    $b = array_rand($boards);
    ajax_success($boards[$b]);
    return;
}

function ajax_next_boardname()
{
    global $user;
    if(isset($_GET['boardname'])) $cur = $_GET['boardname'];
    else $cur = false;    
    if($user->islogin())
    {
        $userid = $user->userid();
        $board_headers = ext_getfavboards($user->userid());
        foreach($board_headers as $board)
        {
            if($cur == $board->filename) continue;
            if(!$user->has_read_perm($board)) continue;
            $ret = ext_is_read($userid, $board->filename,
                               array($board->lastpost));
            if($ret && ! ($ret['0']))
            {
                ajax_success($board->filename);
            }
        }
    }
	$all_boards = ext_get_allboards();
    foreach($all_boards as $boardname)
    {
        $board = new Board($boardname);
        if($cur == $board->filename) continue;
        if(!$user->has_read_perm($board)) continue;
        $ret = ext_is_read($userid, $board->filename,
                           array($board->lastpost));
        if($ret && ! ($ret['0']))
        {
            ajax_success($board->filename);
        }
    }
    $boards = array_filter($all_boards, "board_perm_filter");
    $boards = array_values($boards); /* rebuild keys */
    $b = array_rand($boards);
    if($cur == $boards[$b])
    {
        $b = array_rand($boards);
    }
    ajax_success($boards[$b]);
    return;
}

function ajax_boardnotes()
{
    global $user;
    ajax_assert_param($_GET, array("boardname"));
    $boardname = $_GET["boardname"];
    $board = new Board($boardname);  
    if(!$board->is_vail() || !$user->has_read_perm($board))  {
        ajax_error("Board not exists", 401);
        return ;
    }
    $notes = BBSHOME . '/vote/' . $boardname . '/notes';
    if(file_exists($notes))
        ajax_success_utf8(array('content' =>
                                gbk2utf8(file_get_contents($notes)),
                                'lastnotes' => filemtime($notes)));
    else
        ajax_error('This board has not notes now.');
}

/*
 * Get boardhead information
 * @return  {success: "1", data: {boardname: "...", }}
 *          {success: "", error: ""}
 */
function ajax_getboard()
{
    global $user;
    ajax_assert_param($_GET, array("boardname"));
    $boardname = $_GET["boardname"];
    $board = new Board($boardname);  
    if(!$board->is_vail() || !$user->has_read_perm($board))  {
        ajax_error("Board not exists", 401);
        return ;
    }
    unset($board->flag);
    unset($board->level);
    if($user->has_BM_perm($boardname))
    {
        $board->isadmin = true;
    }

    $board = gbk2utf8($board);
    
    $tmp = ext_gettopiclist($boardname, 0, 0);
    if($tmp)
    {
        $board['total_topic'] = $tmp->total;
    }

    $tmp = ext_getpostlist($boardname, 0, 0, 1);
    if($tmp)
    {
        $board['total_digest'] = $tmp->total;
    }

    $data = @file(BBSHOME . '/etc/www_brd_data/' . $boardname,
                  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if($data)
    {
        foreach($data as &$attr)
        {
            $sa = explode("\t", $attr);
            $board[$sa[0]] = $sa[1];
        }
    }

    $notes = BBSHOME . '/vote/' . $boardname . '/notes';
    if(file_exists($notes)){
        $board['notes'] = gbk2utf8(file_get_contents($notes));
    }
    
    ajax_success_utf8($board);
    return ;
}

/*
 * Set board www etc
 * @return {success: "1", data : 1}
 *         {success: "", data: 0}
 *
 */
function ajax_set_board_www_etc()
{
    global $user;
    ajax_assert_login();
    ajax_assert_POST();
    ajax_assert_param($_POST, array('boardname', 'data'));
    $board = new Board($boardname = $_POST["boardname"]);
    ajax_assert($user->has_BM_perm($board), "Permission deny", 403);
    
    trace_report("Set board www " . $board->filename);
    if (etc_set_board_www($boardname, $_POST['data']))
    {
        ajax_success(true);
    }
    else
    {
        ajax_error_code(404, 'Failed to save etc.');
    }
}

/*
 * Get board by sec code.
 * @return {success: "1", data: [ {filename: "...",
 *                                 title: "...",
 *                                 ...}, 
 *                                {...}, ...]}
 *         {success: "", error: ""}
 */
function ajax_getbysec()
{
    ajax_assert_param($_GET, array("sec_code"));
    $sec_code = $_GET["sec_code"];

	$all_boards = ext_getboards($sec_code);
	$boards = array_filter($all_boards, "board_perm_filter");
    $boards = array_values($boards); /* rebuild keys */
    foreach ($boards as &$board) {
        $board = new Board($board->filename);
        unset($board->level);
        unset($board->flag);
    }
    if (count($boards)) ajax_success($boards);
    else ajax_error("Section not exists", 202);
	return;
}

/*
 * Get all board information. Group by section.
 * @param none
 * @return {success: "1", data: [ {seccode: .., 
 *                                  secname: ...,
 *                                  boards: [<board object>]
 *                                  },
 *                                {...},
 *                                ...
 *                              ]}
 */
function ajax_allboards_sec()
{
    $ret = all_boards_sec();
    if($ret == 202)
    {
        ajax_error('No such section.', 202);
    }
    else
    {
        ajax_success(array('all' => $ret,
                           'good' => fjdb_lget('sysgoodbrd')));
    }
}

/*
 * Clear unread mark of current board.
 * @param $boardname
 * @reurn {success: "1", data: "..."}
 *        {success: "", error: "..."}
 */
function ajax_clearunread()
{
    global $user;
    
    ajax_assert_POST();
    ajax_assert_login();
    ajax_assert_param($_POST, array("boardname"));
    $boardname = $_POST["boardname"];

    $board = new Board($boardname);     
    if ($user->has_read_perm($board) == false) {
        ajax_error("Board not exist.", 401);
        return;
    }

    $ret = $board->get_post_list(0, BRC_MAXNUM, 0);

    $total = 0;
    foreach($ret->list as &$file)
    {
        if(ext_mark_read($user->userid(), $boardname, $file->filename))
            $total ++;
    }
    ajax_success("Clear success");
    return ;
}

/*
 * Get readmark index list.
 * @param $boardname
 * @return {success: "1", data: [a1, a2, ...]} // index
 *
 */
function ajax_get_readmark()
{
    global $user;
    ajax_assert_login();
    ajax_assert_param($_GET, array("boardname"));

    $boardname = $_GET["boardname"];
    ajax_assert_board($boardname);
    
    $arr = ext_get_readmark($user->userid(), $boardname, 1);
    
    ajax_success($arr);
}

function ajax_update_bavatar()
{	
    global $user;

    ajax_assert_POST();    
    ajax_assert_param($_POST, array("boardname"));

    if (!$user->has_BM_perm($_POST["boardname"]))
    {
    	ajax_error("No privilege.");
    }

    if (isset($_FILES["attach"])) {
        $errcode = $_FILES["attach"]["error"];
        if ($errcode == 4) ajax_error("Error.");
        else if ($errcode != 0 ) {
            ajax_error2("Upload avatar error ". $errcode, 323);
        }

        $file = $_FILES["attach"];
        $file = $file["tmp_name"]; 
        /* if (!in_array($file["type"], array("image/jpeg"))) { */
        /*     ajax_error2("Avatar only accept .jpg", 324); */
        /* } */
        $path = PHPBBS_HOME . '/avatar_b/' . $_POST["boardname"] . ".jpg";
        /* if (!in_array($file["type"], array("image/jpeg"))) { */
        /*     ajax_error2("Avatar only accept .jpg", 324); */
        /* } */
        
    	if (file_exists($file)) { 
        
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
            	list($width, $height) = getimagesize($file);
            	/* Compress to 72 x 72 px */
            	$new_width = AVATAR_WIDTH;
            	$new_height = AVATAR_HEIGHT;
            	$image = @imagecreatefromjpeg($file);
            	if(!$image)
                	ajax_error("Save b_avatar fail.");;                
            	$image_p = imagecreatetruecolor($new_width, $new_height);
            	imagecopyresampled($image_p, $image, 0, 0, 0, 0, 
                               $new_width, $new_height, $width, $height);
                
            	imagejpeg($image_p, $path);
                
            	imagedestroy($image_p);
            	imagedestroy($image);
         }
         else
         {       
                $content = file_get_contents($file);
                file_put_contents($path, $content);
         }
         
         return ajax_success("Update success.");
      }
      
      ajax_error2("Save avatar fail.", 325);
   }

}
?>
