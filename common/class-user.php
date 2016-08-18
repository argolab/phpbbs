<?php

/* Debug it. */

class User {
	
	protected $islogin;
	protected $userid;
	protected $utmpid;	/* if login */
	protected $fromaddr;
	protected $www;
	protected $lastpost;
    public $userec;
	
	/* 初始化中判断登陆状态或自动登陆，并设置用户相关的cookies */
	/* $autologin给当前用户使用，用于自动登陆 */

    function resolve_userec()
    {
        if(is_null($this->userec))
        {
            $this->userec = ext_get_urec($this->userid);
            return $this->userec;
        }
        return false;
    }

    function ul()
    {
        $this->resolve_userec();
        return $this->userec['userlevel'];
    }

	/* 判断登陆状态 */
	public function islogin() {
		return $this->islogin;
	}
    
    function islogin2()
    {
        if(isset($_SESSION['userid']))
        {
            $userid = $_SESSION['userid'];
            $utmpid = $_SESSION['utmpid'];
            if(!$this->_login($userid, $utmpid))
            {
                $this->_login($userid);
            }
        }
        return $this->islogin;
    }

	public function userid() {
		return $this->userid;
	}

	public function from() {
		return $this->fromaddr;
	}

	public function userlevel() {
        $this->resolve_userec();
		return $this->userec['userlevel'];
	}

    public function init_nologin()
    {
        $this->resolve_userec();
    }
    
	public function www() {
		if(count($this->www) == 0) {
			return $this->loadwww();
		} else {
			return $this->www;
		}
	}
    
	public function loadwww() {
		$this->www = ext_get_www($this->userid);
		return $this->www;
	}

	public function set_stat($status) {
		if (!$this->islogin)
			return;
		ext_update_utmp($this->utmpid, array("mode" => STAT_WWW | $status));
	}

	public function set_invisible($invisible) {
		if (!$this->islogin)
			return;
		$i = ($invisible == true) ? 1 : 0;
		ext_update_utmp($this->utmpid, array("invisible" => $i));
	}


	public function update_total_post($board) {

		if (is_string($board)) {
			$board = ext_board_header($board);
		}
		
		if ($board->flag & JUNK_FLAG) {			
			return;
		}

        $this->resolve_userec();
        $this->userec['numposts']++;

		ext_update_urec($this->userid, array("numposts" =>
                                             $this->userec['numposts']));
		 
	}

	public function has_BM_perm($board) {

		if (is_string($board)) {
			$board = new Board($board);
		}
		
		if ($this->hasperm(PERM_BLEVELS))
			return true;
		if (!$this->hasperm(PERM_BOARDS))
			return false;

        if($board && is_array($board->BM))
        {
            foreach($board->BM as $bm) {
                if (!strcasecmp($bm, $this->userid())) return true;
            }
        }

		return false;

	}

	public function has_post_perm($board) {
        
        if (!$board) return false;
		if (is_string($board)) {
			$board = ext_board_header($board);
		}

		if ($this->islogin == false)
			return false;
		if ($this->has_read_perm($board) == false)
			return false;

		/* 未激活id不可发帖 */ 
		if (($board->level & PERM_POSTMASK) == false)
			if (!$this->hasperm(PERM_WELCOME))
				return false;

		if (!strcasecmp($board->filename, DEFAULTBOARD))
			return true;

		/* 检查是否为只读版 */
		if ($board->flag & BRD_READONLY)
			return false;

		if ($this->hasperm(PERM_SYSOP))
			return true; 

		/* 推荐版不能发文 */
		if (!strcmp($board->filename, DEFAULTRECOMMENDBOARD) &&
		    !$this->has_BM_perm($board)) {
			return false;
		}

		/* 达到文章上限 */
		if (!$this->has_BM_perm($board) && !($board->flag & NOPLIMIT_FLAG)) {
			$num = ext_brctotalpost($board->filename, 0);
			if ((!($board->flag & BRD_MAXII_FLAG) && $num >= MAX_BOARD_POST) ||
			    (($board->flag & BRD_MAXII_FLAG) && $num >= MAX_BOARD_POST_II))
				return false;
		}

		/* added by freestyler 08.06.23
		   先检查版面属性，再检查POST权，让一些未获得POST权的用户
		   依然可以在某些特定版发文，这类用户包括未通过注册的和被
		   封全站的。目前代码对这两类用户不作区分 */
		if ($board->level & PERM_POSTMASK)
			return $this->hasperm(($board->level & ~PERM_NOZAP) & ~PERM_POSTMASK);

		
		if (!$this->hasperm(PERM_POST)) return false;

		/* fixme: 封禁用户 */
		/* if in denylist, false */
		/* sprintf(filename, "boards/%s/.DENYLIST", board); */
		$dhlist = ext_get_denyheader($board->filename);
		if(count($dhlist)) {
			foreach($dhlist as &$dh) {
				if($dh->blacklist == $this->userid) return false;
			}
		}

		return true;
	}

	public function has_read_perm($board) {

		if (is_string($board)) {
			$board = ext_board_header($board);
			if(!$board) return false;
        }
        
		if ($board->filename[0] < ' ' || $board->filename[0] > 'z') {
			return false;
		}

		if ($board->parent != 0) return false;

		if (($board->flag & BRD_RESTRICT)
		    && !ext_is_in_restrict_board($this->userid, $board->filename))
			return false;

		/* 暂对所有非版主权限屏蔽校内与半开放版面 */
		/*if (($board->flag & BRD_INTERN) && !$this->hasperm(PERM_BOARDS)) {
			return false;
		}
		if (($board->flag & BRD_HALFOPEN) && !$this->hasperm(PERM_BOARDS)) {
        return false;
        }*/

        //处理校内版和版开放版面
        if(! ext_in_validate_ip_range("etc/auth_host", $this->fromaddr)) { 
            if($board->flag & BRD_INTERN)  {
                if(!$this->hasperm(PERM_SYSOP)
                   && !$this->hasperm(PERM_BOARDS)
                   && !$this->hasperm(PERM_INTERNAL))
                    return false;
            }
            if($board->flag & BRD_HALFOPEN) {
                if(!$this->hasperm(PERM_SYSOP)
                   && !$this->hasperm(PERM_WELCOME))
                    return false;
            }
        }
        
		/* fixme: intern board  */
		/* if ( !validate_ip_range(fromhost) ) { */
		/* 	/\* 校内版面 *\/ */
		/* 	if( x->flag & BRD_INTERN )   { */
		/* 		if(!user_perm(user, PERM_SYSOP)  */
		/* 		&& !user_perm(user, PERM_BOARDS) */
		/* 		&& !user_perm(user, PERM_INTERNAL) )  */
		/* 	        	return 0; */
		/* 	} */
		/* 	/\* freestyler: 半开放版面 *\/ */
		/* 	if ( x->flag & BRD_HALFOPEN ) { */
		/* 		if( !user_perm(user, PERM_SYSOP) */
		/* 	 	 && !user_perm(user, PERM_WELCOME))  */
		/* 			return 0; */
		/* 	} */
		/* } */

		if ($board->level == 0) return true;
		if ($board->level & (PERM_POSTMASK | PERM_NOZAP)) return true;
		if ($this->hasperm(PERM_BASIC) == false)  return false;
		if ($this->hasperm($board->level)) return true;

		return false;

	}

	/* $perm is constant defined in extension, ext_permissions.h */
	public function hasperm($perm) {
        $this->resolve_userec();
		return $this->userec['userlevel'] & $perm;
	}
    
    function __construct($userid = "guest", $login_session=false)
    {
        
        $this->islogin = false;
        $this->userid = $userid;
        $this->utmpid = -1;
        $this->fromaddr = $_SERVER['REMOTE_ADDR'];
        $this->userec = null;

		if (strncmp($this->fromaddr, '::ffff:', 7) == 0) {
			$this->fromaddr = substr($this->fromaddr, 7);
		}

        if($login_session)
        {
            if(isset($_SESSION['userid']))
            {
                $userid = $_SESSION['userid'];
                $utmpid = $_SESSION['utmpid'];
                if(!$this->_login($userid, $utmpid))
                {
                    $this->_login($userid);
                }
            }
        }        
    }

    private function update_stay_info()
    {
        if(isset($_SESSION['lastrefresh']) &&
           isset($_SESSION['logintime']))
        {
            $this->resolve_userec();
            $stay = $_SESSION['lastrefresh'] - $_SESSION['logintime'];
            $this->userec['stay'] = $this->userec['stay']+$stay;
            $this->userec['lastlogout'] = $_SESSION['lastrefresh'];
            ext_update_urec($this->userid,
                            array("lastlogout" => $_SESSION['lastrefresh'],
                                  "stay" => $this->userec['stay']));
        }
    }

	public function login($userid, $passwd){
		if (!ext_checkpassword($userid, $passwd, 0))
			return false;
        if($this->_login($userid))
        {
            ext_kick_multi($userid);
            return true;
        }
    }
    
	public function logout() {
		if ($this->islogin) {
			ext_remove_utmp($this->userid, $this->utmpid);
            $this->update_stay_info();
            unset($_SESSION['lastrefresh']);
            unset($_SESSION['logintime']);
			trace_report(" logout ");
		}
		$this->islogin = false;
		$this->www = array();
        $this->utmpid = -1;
		$this->_update_session();
	}

    private function _login($userid, $utmpid=null)
    {
        if(is_null($utmpid))
        {
            $utmpid = ext_insert_utmp($userid, $this->fromaddr);
			if ($utmpid < 0) {
				trace_report("Attach & insert utmp failed. Clear cookie.");
				goto login_fail;
			}
            $userec = ext_get_urec($userid);
            $this->userid = $userid = $userec['userid'];
            /* 停权 */
            if (!($userec['userlevel'] & PERM_BASIC)) {
                goto login_fail;
            }
            /* 咸鱼翻生 */
            if ($userec['userlevel'] & PERM_SUICIDE) {
                $userec['userlevel'] &= ~PERM_SUICIDE;
                $userec['userlevel'] |= PERM_MESSAGE;
                $userec['userlevel'] |= PERM_SENDMAIL;
                ext_update_urec($userec['userid'],
                                array("userlevel" => $userec['userlevel']));
            }            
            $this->userec = $userec;
            $now = time();
            $arg = array('lastlogin' => $now,
                         'lastlogout' => $now + 30,
                         'numlogins' => $userec['numlogins'] + 1,
                         'lasthost' => $this->fromaddr);
            ext_update_urec($userec['userid'], $arg);
            $this->userec = $userec = array_merge($userec, $arg);

            $this->update_stay_info();
            
            $_SESSION['lastrefresh'] = $_SESSION['logintime'] = time();
        }
        else
        {
            $uinfo = ext_attach_utmp($userid, $utmpid, $this->fromaddr);
            if(!is_array($uinfo))
            {
				trace_report("Attach & insert utmp failed. Clear cookie.");
				goto login_fail;
            }
            $userid = $uinfo['userid'];
            $_SESSION['lastrefresh'] = time();
        }
		
        $this->islogin = true; 
        $this->userid = $userid;
        $this->utmpid = $utmpid;
        $this->_update_session();
        
		trace_report(" login success utmpid " . $this->utmpid);
		return true;


    login_fail :
        $this->islogin = false;
        $this->_update_session();
        return false;
        
    }

    private function _update_session()
    {
        if($this->islogin)
        {
            $_SESSION['userid'] = $this->userid;
            $_SESSION['utmpid'] = $this->utmpid;
        }
        else
        {
            unset($_SESSION['userid']);
            unset($_SESSION['utmpid']);
        }
    }

}

?>
