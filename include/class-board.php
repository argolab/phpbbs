<?php

class BoardManager extends Manager
{
    
    static private $cache = array();

    private function has_BM_perm($allbms)
    {
        $cp = $this->get_customer_perm();
        return ($cp & PERM_BLEVELS) ||
            (($cp & PERM_BOARDS) && in_array($this->customer, $allbms));
    }

    private function has_read_perm($header)
    {

        if(!$header)
            return false;
        
		if ($header->filename[0] < ' ' || $header->filename[0] > 'z') {
			return false;
		}

		if ($header->parent != 0) return false;

		if (($header->flag & BRD_RESTRICT)
		    && !ext_is_in_restrict_board($this->customer, $header->filename))
			return false;

        $cp = $this->get_customer_perm();

		/* 暂对所有非版主权限屏蔽校内与半开放版面 */
		/*if (($board->flag & BRD_INTERN) && !$this->hasperm(PERM_BOARDS)) {
			return false;
		}
		if (($board->flag & BRD_HALFOPEN) && !$this->hasperm(PERM_BOARDS)) {
        return false;
        }*/

        //处理校内版和版开放版面
        if(! ext_in_validate_ip_range("etc/auth_host",
                                      $this->get_customer_ip())) { 
            if($header->flag & BRD_INTERN)  {
                if(!($cp & PERM_SYSOP)
                   && !($cp & PERM_BOARDS)
                   && !($cp & PERM_INTERNAL))
                    return false;
            }
            if($header->flag & BRD_HALFOPEN) {
                if(!($cp & PERM_SYSOP)
                   && !($cp & PERM_WELCOME))
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

		if ($header->level == 0) return true;
		if ($header->level & (PERM_POSTMASK | PERM_NOZAP)) return true;

        return ($cp & PERM_BASIC) &&
            ($cp & $header->level);
        
    }

    function get_board($boardname)
    {
        $header = ext_board_header($boardname);
        if($this->has_read_perm($header))
            return $this->cook_header($header);
        return false;
    }
    
    function set_board_params($boardname, $newdata, &$errmsg)
    {
        $header = ext_board_header($boardname);
        if($this->has_BM_perm($header))
        {
            $errmsg = "Permissin deny.";
            return false;
        }
        dump_json(BBSHOME . "/etc/board/" . $header->filename,
                  "www", $newdata);
    }
    
    function get_boards_from_sec($num, $sec_manage)
    {
        $seccode = $sec_manage->get_section_by_code($num);
        $headers = ext_getboards($seccode);
        if($headers)
            return array_map($this->_cb('cook_header'),
                             array_filter($this->_cb('has_read_perm'),
                                                     $header));
        return array();
    }
    
    function get_allboards()
    {
        return array_map($this->_cb('cook_header'),
                         array_filter(array_map('ext_board_header',
                                                ext_get_allboards()),
                                      $this->_cb('has_read_perm')));
    }
    
    function get_boards_from_fav()
    {
        $headers = ext_getfavboards($this->customer);
        if($headers)
        {
            return array_map($this->_cb('cook_header'), $header);
        }
        else
            return array();
    }

    private function cook_header($header)
    {
        // need checker
        if($header->BM)
            $bm = explode(',', $header->BM);
        else
            $bm = array();
        $ret = array(
                     "boardname" => $header->filename,
                     "bm" => $bm,
                     "lastpost" => $header->lastpost,
                     "total" => $header->total);
        $ret['seccode'] = $header->title[0];
        //$ret['tag'] = cc(substr($header->title, 2, 4));
        $ret['title'] = cc(substr($header->title, 10));
        if($this->has_BM_perm($header))
            $ret['p_bm'] = true;
        return $ret;
    }
        
}
