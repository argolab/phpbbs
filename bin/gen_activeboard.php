<?php

require_once('bin.php');

function get_activeboard_args($content)
{
    $title_pattern = '/^标题\ *:\ *(.*)$/m';
    $href_pattern = '/^链接\ *:\ *(.*)$/m';
    preg_match($title_pattern, $content, $tm);
    if(!$tm) return false;
    if(!$tm[1]) return false;
    $title = $tm[1];
    preg_match($href_pattern, $content, $cm);
    if(!$cm) return false;
    if(!$cm[1]) return false;
    $href = $cm[1];
    return array("title" => $title,
                 "href" => $href);
}

function activeboard($max)
{
    $boardname = "posterwall";
    $board = new Board($boardname);
    if(!$board->is_vail()) return false;
    $start = ext_getpostlist($boardname, 0, 0, 1);
    if(!$start) return false;
    $start = $start->total;
    $count = 0;
    $limit = 20;
    $push_user = array();
    $ret = array();
    while(($count<$max)&&($start>0))
    {
        $start = $start - $limit;
        if($start < 0)
        {
            $limit = $start + $limit;
            $start = 1;
        }
        $list = $board->get_post_list($start, $limit, 1);
        foreach($list->list as &$pr)
        {
            print "\nHandling ... [" . $pr->filename . ']..';
	    $pr->filename = str_replace("G", "M", $pr->filename);
            $post = new Post($boardname, $pr->filename);
            if(!$post) continue;
            print ' HAS_POST';
	    if($post->userid == "" )
	    {
		$ah = ext_getfileheader($boardname, $pr->filename);
		if($ah)
		{
            	    $urec = ext_get_urec($ah->owner);
	    	    $post->userid = $ah->owner;
		    $post->board = $boardname;
		    $post->username = $urec['username'];
		    $post->post_time = $ah->filetime;
            	    $post->title = $ah->title;
        	} else {
		    continue;
		}
    	    }			    
            $owner = new User($post->userid);
            $owner->init_nologin();
            if(!$owner->hasperm(PERM_BLEVELS | PERM_SYSOP))
            {
                if(array_key_exists($post->userid, $push_user)) continue;
                print ' FIRST_USER';
                $push_user[$post->userid] = true;
            }
            else
                print ' BM_PERM';
            $content = @iconv('gbk','utf-8//ignore',
                              $post->rawcontent);
            $args = get_activeboard_args($content);
            if(!$args) continue;
            print ' RIGHT_PARA';
            if(isset($post->ah->link)){
	        $link = preg_replace('/A\.(\d*)\.A/','$1', $post->ah->link);
	    } else {
	        preg_match('/(http:\/\/argo.sysu.edu.cn\/attach\/.*)\n/', $content, $m);
		if(!isset($m[1])) continue;
		$link = $m[1];
	    }
            $ret[] = array("img" => $link,
                             "href" => $args['href'],
                             "title" => $args['title']);
            ++$count;
            print "   [Done].";
        }
        if($start == 1) break;
    }
    print "\n";
    return $ret;
}

$ret = activeboard(8);
if(!$ret) $ret = '';
fjdb_set(BBSHOME . '/etc/', 'phpactiveboard', $ret);

?>
