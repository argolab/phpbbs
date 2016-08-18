<?php

require_once('include/setup.php');

function at_user($content, $owner, $boardname, $filename)
{
    global $user;
    $matches = array();
    $newcontent = preg_replace('/:\s.*/', ' ', $content);
    preg_match_all('/@([a-zA-Z]{2,20})/', $newcontent , $matches);
    $matches = $matches[1];
    if(count($matches) > 10){
        $matches = array_slice($matches, 0, 10);
    }
    foreach($matches as $userid){
        if($userid=ext_is_user_exist($userid)){
            ext_add_msg($owner, $userid, $boardname, $filename, '@'); 
        }
    }
}

class LegacyPostManage 
{
    static function gen_summary($content)
    {
        // max len is 150 for database scheme
        if(mb_strlen($content, 'utf-8') <= 140)
            return $content;
        return mb_substr($content, 0, 140, 'utf-8') . '…';
    }
    

    private function get_signature()
    {
        $userid = $this->customer;
        $signature = "";
        $sig_arr = ext_get_signatures($userid);
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

    function sync_new($filename, $boardname)
    {
        $fh = ext_getfileheader($boardname, $filename);
        if(!$fh)
            return false;
        $arr = ext_post_content_classify($boardname, $filename);
        DB::insert('Topic',
                   array('author' => $arr['userid'],
                         'posttime' => new DateTime(),
                         'title' => cc($arr['title']),
                         'boardname' => $boardname,
                         'fileid' => $fh->filetime));
        $tid = DB::insertId();
        DB::insert('Post',
                   array('topicid' => $tid,
                         'replyid' => 0,
                         'author' => $arr['userid'],
                         'posttime' => new DateTime(),
                         'title' => cc($arr['title']),
                         'content' => cc($arr['rawcontent']),
                         'summary' => self::gen_summary(cc($arr['rawcontent'])),
                         'boardname' => $boardname,
                         'filename' => $fh->filename,
                         'signature' => cc($arr['rawsignature']),
                         'usersign' => $arr['username'],
                         'flag' => $fh->flag));
        $pid = DB::insertId();
        DB::insert('TopicPart',
                   array('author' => $arr['userid'],
                         'topicid' => $tid,
                         'flag' => PARTTOPIC_AUTHOR));
        update_topic_score($tid);

        at_user($arr['rawcontent'], $arr['userid'], $boardname,
                $fh->filename);
        
    }

    function sync_cross($filename, $boardname)
    {
        $fh = ext_getfileheader($boardname, $filename);
        if(!$fh)
            return false;
        $arr = ext_post_content_classify($boardname, $filename);
        DB::insert('Topic',
                   array('author' => $arr['userid'],
                         'posttime' => new DateTime(),
                         'title' => cc($arr['title']),
                         'boardname' => $boardname,
                         'fileid' => $fh->filetime));
        $tid = DB::insertId();
        DB::insert('Post',
                   array('topicid' => $tid,
                         'replyid' => 0,
                         'posttime' => new DateTime(),
                         'author' => $arr['userid'],
                         'title' => cc($arr['title']),
                         'content' => cc($arr['rawcontent']),
                         'summary' => self::gen_summary(cc($arr['rawcontent'])),
                         'boardname' => $boardname,
                         'filename' => $fh->filename,
                         'signature' => cc($arr['rawsignature']),
                         'usersign' => cc($arr['username']),
                         'flag' => $fh->flag));
        $pid = DB::insertId();
        DB::insert('TopicPart',
                   array('author' => $arr['userid'],
                         'topicid' => $tid,
                         'flag' => PARTPOST_CROSS));
        update_topic_score($tid);                 
    }
    
    function sync_reply($filename, $boardname, $toreply)
    {
        $fh = ext_getfileheader($boardname, $filename);        
        if(!$fh)
            return false;
        $arr = ext_post_content_classify($boardname, $filename);
        

        at_user($arr['rawcontent'], $arr['userid'], $boardname,
                $fh->filename);
        
        $ret = DB::queryFirstRow("SELECT postid, filename, topicid, replyid, author FROM Post "
                                 . " WHERE filename=%s AND"
                                 . "       boardname=%s",
                                 $toreply, $boardname);
        if(!$ret)
            return false;
        ext_add_msg($arr['userid'], $ret['author'], $boardname,
                    $filename, 'r');

        DB::insert('Post',
                   array('topicid' => $ret['topicid'],
                         'replyid' => $ret['postid'],
                         'posttime' => new DateTime(),
                         'author' => $arr['userid'],
                         'title' => cc($arr['title']),
                         'content' => cc($arr['rawcontent']),
                         'summary' => self::gen_summary(cc($arr['rawcontent'])),
                         'boardname' => $boardname,
                         'filename' => $filename,
                         'signature' => cc($arr['rawsignature']),
                         'usersign' => cc($arr['username']),
                         'flag' => $fh->flag));
        $pid = DB::insertId();
        $lastpart = DB::queryFirstRow('SELECT * FROM TopicPart'
                                      . ' WHERE topicid=%d AND author=%s',
                                      $ret['topicid'], $arr['userid']);
        
        if(is_null($lastpart))
        {
            DB::insert('TopicPart',
                       array('topicid' => $ret['topicid'],
                             'flag' => PARTTOPIC_REPLY,
                             'author' => $arr['userid']));
            DB::query('UPDATE Topic SET replynum = replynum +1 '
                      . '               , replyusernum = replyusernum + 1'
                      . '               , lastupdate = %t'
                      . ' WHERE topicid=%d', new DateTime(), $ret['topicid']);
        }
        else
        {
            if(($lastpart['flag'] & PARTTOPIC_REPLY)>=PARTTOPIC_REPLY)
                DB::query('UPDATE Topic SET replynum = replynum +1 '
                          . '               , lastupdate = %t'
                          . ' WHERE topicid=%d', new DateTime(), $ret['topicid']);
            else
                DB::query('UPDATE Topic SET replynum = replynum +1 '
                          . '               , replyusernum = replyusernum + 1'
                          . '               , lastupdate = %t'
                          . ' WHERE topicid=%d', new DateTime() , $ret['topicid']);
            DB::query('UPDATE TopicPart SET flag=flag|%d '
                      . ' WHERE topicid=%d AND author=%s',
                      PARTTOPIC_REPLY, $ret['topicid'], $arr['userid']);
        }
        update_topic_score($ret['topicid']);        
    }

    function sync_update($filename, $boardname, $key, $value)
    {
        if($key == 'content')
        {
            $arr = ext_post_content_classify($boardname, $filename);
            $setter = "content=%s";
            $val = cc($value);
        }
        else if($key == 'digest')
        {
            $setter = ($value=='0')?'flag=flag&~%d':'flag=flag|%d';
            $val = FILE_DIGEST;
        }
        else if($key == 'mark')
        {
            $setter = ($value=='0')?'flag=flag&~%d':'flag=flag|%d';
            $val = FILE_MARKED;
        }
        else if($key == 'title')
        {
            $fh = ext_getfileheader($boardname, $filename);
            $setter='title=%s';
            $val = $fh->title;
        }
        else if($key == 'flag')
        {
            $setter='flag=%d';
            $val=$value;
        }else
            return;
        $sql = 'UPDATE Post SET ' . $setter
            . ' WHERE filename=%s AND boardname=%s';
        DB::query($sql, $val, $filename, $boardname);
    }

    function sync_cancel($filename, $boardname)
    {
        $ret = DB::queryFirstRow('SELECT topicid, replyid FROM Post WHERE'
                                 . ' filename=%s AND boardname=%s',
                                 $filename, $boardname);
        if(!$ret)
            return;
        if($ret['replyid'] == 0)
        {
		DB::delete('Topic', 'topicid=%d', $ret['topicid']);
        }
        DB::update('Post', array('flag' => DB::sqleval('flag=flag &~ %d',
                                                       FILE_DELETED)),
                   'filename=%s AND boardname=%s',
                   $filename, $boardname);
    }

    function sync_delete($filename, $boardname)
    {
    }
                         
}

/**
  * the post manager for NEW mysql
 */
class PostManager extends Manager
{
    public function get_post_list($boardname,$cursor,$limit,$order,$filter)
    {
        //TODO filter
        if ($cursor == NULL)
            $result = DB::query("SELECT postid, topicid, author, title, summary, posttime, vote, boardname, flag, lastupdate FROM `Post` WHERE boardname=%s ORDER BY posttime ".$order." LIMIT %i ",
                                  $boardname,$limit);
        else
            $result = DB::query("SELECT postid, topicid, author, title, summary, posttime, vote, boardname, flag, lastupdate FROM `Post` WHERE boardname=%s AND topicid>%i ORDER BY posttime ".$order." LIMIT %i ",
                                  $boardname,$cursor,$limit);
        $ret = array();
        foreach ($result as $row)
        {
            $row['lastupdate'] = strtotime($row['lastupdate']);
            $ret[] = $row;
        }

        if (isset($ret) && count($ret)>0) 
            return $ret;
        else
            return null;
    }

    public function get_topic_list($boardname,$cursor,$limit,$order)
    {
        if ($cursor == NULL)
            $result = DB::query("SELECT * FROM `Topic` WHERE boardname=%s ORDER BY lastupdate ".$order."  LIMIT %i ",
                                  $boardname,$limit);
        else
            $result = DB::query("SELECT * FROM `Topic` WHERE boardname=%s AND topicid>%i ORDER BY lastupdate ".$order." LIMIT %i ",
                                  $boardname,$cursor,$limit);

        $ret = array();
        foreach ($result as $row)
        {
            $row['lastupdate'] = strtotime($row['lastupdate']);
            $row['posttime'] = strtotime($row['posttime']);
            $ret[] = $row;
        }
        return $ret;
    }

    public function get_post_in_topic($topicid,$cursor,$limit)
    {
        $result = DB::query("SELECT * FROM `Post` WHERE topicid=%i AND topicid>%i ORDER BY posttime LIMIT %i ",
            $topicid,$cursor,$limit);
        $ret = array();
        foreach ($result as $row)
        {
            $row['lastupdate'] = strtotime($row['lastupdate']);
            $ret[] = $row;
        }
        return $ret;
    }

    public function get_topicid_by_postid($postid)
    {
        $row = DB::queryFirstRow("SELECT topicid FROM `Post` WHERE postid=%i",$postid);
        return $row['topicid'];

    }

     public function get_boardname_by_postid($postid)
    {
        $row = DB::queryFirstRow("SELECT boardname FROM `Post` WHERE postid=%i",$postid);
        return $row['boardname'];
    }

    public function get_boardname_by_topicid($topicid)
    {
        $row = DB::queryFirstRow("SELECT boardname FROM `Topic` WHERE topicid=%i",$topicid);
        return $row['boardname'];
    }

    public function get_topicinfo($filename, $boardname, $topicid=null){

    if(!$topicid || $topicid==''){
        $ret = DB::queryFirstRow("SELECT Topic.topicid FROM Topic, Post WHERE Post.filename=%s AND Post.boardname=%s AND Post.topicid=Topic.topicid",
		  		  $filename, $boardname);
	if(!$ret) return;
	$topicid=$ret['topicid'];
				  }
   if(!$topicid) return;
        $ret = DB::queryFirstRow("select Topic.topicid, Topic.author, Topic.title, Topic.vote, Topic.replynum, Topic.boardname, Topic.lastupdate, Topic.posttime, Post.filename from Topic,Post Where Topic.topicid=%d and Post.topicid=%d order by Post.postid limit 1",
	       			 $topicid, $topicid);
	if(!$ret) return;
	$row = DB::queryFirstRow("SELECT * FROM `TopicPart` WHERE author=%s AND topicid=%i",$this->customer,$topicid);
	$ret['f'] = $row['flag'];
	$ret['hasvoted'] = isset($row['flag']) && (($row['flag']&PARTTOPIC_VOTE) >= PARTTOPIC_VOTE);
	return $ret;

				  
	}

    public function get_my_part_topic($userid)
    {
        $result = DB::query("select Topic.topicid,Topic.title,Topic.lastupdate,Topic.boardname,Topic.score,
		  	    Topic.author, Topic.vote, Topic.replynum, Topic.posttime
                            from Topic,TopicPart where TopicPart.author=%s and
                             Topic.topicid = TopicPart.topicid",$userid);
        $ret = array();
        foreach($result as $row)
        {
            $row['lastupdate'] = strtotime($row['lastupdate']);
            $ret[] = $row;
        }
        return $ret;
    }

    public function update_topic_score($topicid)
    {
        update_topic_score($topicid);
        return true;
    }

    public function get_post_by_boardname($boardname, $cursor, $limit)
    {
        $result = DB::query("SELECT Topic.topicid,Topic.title,Topic.lastupdate,Topic.score,Topic.author,Topic.vote,Topic.replynum,Topic.posttime,Post.summary from `Topic` LEFT JOIN `Post` ON Topic.topicid=Post.topicid AND Post.replyid=0 WHERE Topic.boardname=%s  ORDER BY score DESC LIMIT %i OFFSET %i;", $boardname, $limit, $cursor);
        $ret = array();
        foreach($result as $row)
        {
            $row['lastupdate'] = strtotime($row['lastupdate']);
            $row['posttime'] = strtotime($row['posttime']);
            $ret[] = $row;
        }
        return $ret;
    }

    public function get_top_score_topic($cursor,$limit)
    {
        $result = DB::query("SELECT Topic.topicid, Topic.title, Topic.lastupdate, Topic.boardname, Topic.score, Topic.author, Topic.vote, Topic.replynum, Topic.posttime, Post.summary FROM `Topic` LEFT JOIN `Post` ON Topic.topicid=Post.topicid AND Post.replyid=0 ORDER BY score DESC  LIMIT %i OFFSET %i",$limit,$cursor);
        $ret = array();
        foreach($result as $row)
        {
            $row['lastupdate'] = strtotime($row['lastupdate']);
            $row['posttime'] = strtotime($row['posttime']);
            $ret[] = $row;
        }
        return $ret;
    }

    public function vote_topic($userid,$topicid)
    {
        $row = DB::queryFirstRow("SELECT * FROM `TopicPart` WHERE author=%s AND topicid=%i",$userid,$topicid);
        if(isset($row['flag']) && (($row['flag']&PARTTOPIC_VOTE) >= PARTTOPIC_VOTE))
        {
            return false;
        }
        else
        {
            if(isset($row['flag']))
            {
                $flag = $row['flag']|PARTTOPIC_VOTE;
                DB::update('TopicPart',array('flag'=>$flag),"author=%s AND topicid=%i",$userid,$topicid);
            }
            else
            {
                DB::insert('TopicPart',array('author'=>$userid,'topicid'=>$topicid,'flag'=>PARTTOPIC_VOTE));
            }
            
            $ret = DB::queryFirstRow("SELECT boardname, author FROM Topic WHERE topicid=%d", $topicid);
            $rp = DB::queryFirstRow("SELECT filename FROM Post WHERE topicid=%d ORDER BY postid LIMIT 1", $topicid);
            ext_add_msg($userid, $ret['author'], $ret['boardname'], $rp['filename'], 'U');
            
            DB::query("UPDATE `Topic` SET vote=vote+1, lastupdate=%t WHERE topicid=%i", new DateTime(), $topicid);
            $this->update_topic_score($topicid);
            return true;
        }
    }


}
