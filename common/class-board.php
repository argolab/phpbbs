<?php

require_once("functions.php");
class Board {


        /* no perm filter */
	static public function boards_from_seccode($seccode) {
		$board_headers = ext_getboards($seccode);
       
		$boards = array();
		foreach($board_headers as $board) {
			$boards[] = new Board($board);
		}
		return $boards;
	}

        /* no perm filter */
	static public function boards_from_fav() {
		global $user;
		$board_headers = ext_getfavboards($user->userid());
		$boards = array();
        if($board_headers) 
        {
            foreach($board_headers as $board) {
                $boards[] = new Board($board);
            }
        }
		return $boards;
	}

    static public function change_fav_boards($fav_boards)
    {
        global $user;
        $res = ext_changefavboards($user->userid(), $fav_boards);
        return $res; 
    }

    public function is_vail()
    {
        return property_exists($this, "filename");
    }
    
	public function __construct($board) {
		global $user;
        if(!$board)
        {
            return false;
        }
		if (is_string($board)) {
			$board = ext_board_header($board);
		}
        if(!$board) {
            return;                                 
        }
        $this->flag = $board->flag;        
        
		if (!is_object($board)) {
                /* 只接受来自 php_func_board.c 的 stdClass */
                throw new Exception("invalid parameter for Board");
		}
            /* 复制所有 stdClass 的变量 */
		foreach($board as $var=>$value) {
			$this->$var = $value;
		}

            //分割BM-____-,,        
        $arr_bm = array();
        $token = strtok($board->BM, ",: ;&()\n");
		while ($token) {
            $arr_bm []= $token;
			$token = strtok(",: ;&()\n");
		}
        $this->BM = $arr_bm;
        
            /* 分割title */
		$this->seccode = $this->title[0];
		$this->type = substr($this->title, 1, 7);
		$this->title = substr($this->title, 10);

		$sec_list = etc_section_list();
		for ($i = 0; $i < count($sec_list); $i++) {
			if ($this->seccode == $sec_list[$i]['seccode']) {
				$this->secnum = $i;
				break;
			}
		}

        /* 未读标记 */
		if ($user->islogin()) {
			$res = ext_is_read($user->userid(), $board->filename, array($board->lastpost));
			$this->unread = $res[0] ? false : true;
		} else {
			$this->unread = false;
		}
		
	}
    
    public function mark_flag($flag, $unread)
    {
        $mark = 'N';
        if ($flag & FILE_DIGEST)  $mark = 'G';
        if ($flag & FILE_MARKED) $mark = 'M';
        if (($flag & FILE_MARKED) && ($flag & FILE_DIGEST)) $mark = 'B';
        if ($flag & FILE_ATTACHED) $mark .= '@';
        if ($flag & FILE_NOREPLY) $mark = 'X';
        //if ($unread == '0') $mark = strtolower($mark);
        //$mark = str_replace('n', ' ', $mark);
        return $mark;
    }
    
    /* type:0 普通 1 文摘 2 同主题 */    
	public function get_post_list($start, $count, $type=0) {
		global $user;
        
        if ($type <= 1) { 
            // normal | digest
            $ret =  ext_getpostlist($this->filename, $start, $count, $type);
        } else { 
            // topic
            $ret = ext_gettopiclist($this->filename, $start, $count);
        }

		if (isset($ret->list) && count($ret->list)>0) {			
			if ($user->islogin()) {
				$files = array();
                if($type <= 1){
                    foreach ($ret->list as $post_header) {
                        $files[] = $post_header->filename;
                    }
                }else{
                    foreach ($ret->list as $post_header) {
                        $files[] = 'M.' . $post_header->update . '.A';
                    }
                }
				$read_list = ext_is_read($user->userid(), $this->filename, $files); //返回1表示已读
                    /* 返回的是逆序的 =。= */
				$total = count($read_list);
				for ($i = 0; $i < $total; $i++) {
					$ret->list[$i]->unread = $read_list[$total - $i - 1] ? '0' : '1'; //
				}
			}
            foreach ($ret->list as &$post_header) {
                $post_header->update =$post_header->update;
                    /* h2o template中不能用位运算。。 */
                if (!$user->islogin())  $post_header->unread = '1';
                if ($type == 1) $post_header->flag |= FILE_DIGEST;
                $post_header->mark = $this->mark_flag($post_header->flag, $post_header->unread);
			}
            return $ret;
		}
        else
        {
            return null;
        }
	}

    public function get_topic_list($start,$count)
    {
        return $this->get_post_list($start, $count, 2);
    }
    
    public function new_post($user, $title, $content,$articleid="",
                             $signature = "",
                             $anony = 1,
                             $reply_notify = 0,
                             $attach = NULL) 
    {
        if ($attach && count($attach)) {
            $attach_tmpfile = $attach["tmp_name"]; 
            $attach_origname = $attach["name"];
            $attach_type = $attach["type"];
        } else  {
            $attach_tmpfile = $attach_origname = $attach_type = "";
        }

        $arg = array(
            "userid" => $user->userid(),
            "board" => $this->filename,
            "fromaddr" => $user->from(),
            'anonymous' => $anony ? $this->flag & ANONY_FLAG : 0,
            "title" => $title,
            "content" => $content,
            "articleid" => $articleid,
            "signature" => $signature,
            "reply_notify" => $reply_notify,
            "attach_tmpfile" => $attach_tmpfile,
            "attach_origname" => $attach_origname,
            "attach_type" => $attach_type
                     );
        $res = ext_simplepost($arg);
        if ($res) {
            ext_mark_read($user->userid(), $this->filename, $res);
            ext_update_lastpost($this->filename);
            $user->update_total_post($this);
            
             /* $urec = ext_get_urec($user->userid()); */
             /* $nurec = array('numposts' => $urec['numposts'] +1); */
             /* ext_update_urec($user->userid(), $nurec); */

             trace_report(" posted " . $this->filename . " " . $res);
            return $res;
        }
        return false;
    }
    
    public function edit_post($user, $title, $content, $articleid,
                              $signature,
                              $anony,
                              $reply_notify,
                              $attach) 
    {
        global $user;
        
        if (count($attach)) {
            $attach_tmpfile = $attach["tmp_name"]; 
            $atttach_origname = $attach["name"];
            $attach_type = $attach["type"];
        } else  {
            $attach_tmpfile = $attach_origname = $attach_type = "";
        }

        $header=ext_getfileheader($this->filename,$articleid);
        
        if($user->has_BM_perm($this->filename) || $user->userid()==$header->realowner)
        {
            $arg = array(
            "userid" => $user->userid(),
            "board" => $this->filename,
            "fromaddr" => $user->from(),
            'anonymous' => $anony ? $this->flag & ANONY_FLAG : 0,
            "title" => $title,
            "content" => $content,
            "articleid" => $articleid,
            "signature" => $signature,
            "reply_notify" => $reply_notify,
            "attach_tmpfile" => $attach_tmpfile,
            "attach_origname" => $attach_origname,
            "attach_type" => $attach_type
                            );
            $res=ext_editpost($arg);
            trace_report('log1' . json_encode($arg) . ']');
            if ($res) {
                ext_update_lastpost($this->filename);
                return $articleid;
            } else {
                return null;
            }
        } else {
            echo "您无权修改该文章 ";
            return null;
        }
        return null;
    }

    public function delete_post($filename)
    {
        global $user;

        $header=ext_getfileheader($this->filename,$filename);
        
        if($user->has_BM_perm($this->filename) || strcasecmp($user->userid(), $header->realowner) ==0)
        {
            $res=ext_delete_post($this->filename,$filename,$user->userid());

            trace_report(" delete " . $this->filename . " " . $filename);
            
            return $res? true : false; 
        }else {
            return false;
        }
    }
    
    public function read_post($filename) {
        global $user;
		
        $content = ext_read_post($this->filename, $filename);
        if ($content && $user->islogin()) {
            ext_mark_read($user->userid(), $this->filename, $filename);
        }

        return $content;
    }

    public function read_post_digest($start) {
        global $user;
        $content = ext_read_digest($this->filename, $start);
        return $content;
    }


}

?>
