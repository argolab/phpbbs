<?php

class UserSession {
	
	protected $islogin;
	protected $userid;
	protected $utmpid;	/* if login */
	protected $fromaddr;
	protected $www;
	protected $lastpost;
    public $userec;
	
	/* 初始化中判断登陆状态或自动登陆，并设置用户相关的cookies */
	/* $autologin给当前用户使用，用于自动登陆 */

    public static function get_cookie_user()
    {
        static $user;
        if(is_null($user))
        {
            $user = new self('guest', true);
        }
        return $user;
    }
    
    function resolve_userec()
    {
        if(is_null($this->userec))
        {
            $this->userec = ext_get_urec($this->userid);
            return $this->userec;
        }
        return false;
    }

    function get_user_level()
    {
        $this->resolve_userec();
        return $this->userec['userlevel'];
    }

	function is_login()
    {
		return $this->islogin;
	}

	function get_userid()
    {
		return $this->userid;
	}

	function get_fromaddr()
    {
		return $this->fromaddr;
	}
    
	function get_www()
    {
        if(is_null($this->www))
            $this->www = ext_get_www($this->userid);
        return $this->www;
	}

	function set_stat($status)
    {
		if (!$this->islogin)
			return;
		ext_update_utmp($this->utmpid, array("mode" => STAT_WWW | $status));
	}

	function set_invisible($invisible)
    {
		if (!$this->islogin)
			return;
		$i = ($invisible == true) ? 1 : 0;
		ext_update_utmp($this->utmpid, array("invisible" => $i));
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

	function login($userid, $passwd){
		if (!ext_checkpassword($userid, $passwd, 0))
			return false;
        if($this->_login($userid))
        {
            ext_kick_multi($userid);
            return true;
        }
    }
    
	function logout() {
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
