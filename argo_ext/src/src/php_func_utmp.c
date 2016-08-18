#include "ext_prototype.h"


/* attach到已有的utmp中，保持在线 */
/* 更好的函数命名似乎应该为ext_utmp_attach */
/* update:返回值改为null或userid, 以更新php中用户名的大小写 */
PHP_FUNCTION(ext_attach_utmp) {

	int uid;
	struct user_info *uinfo;
	struct userec urec;

	char *userid, *fromaddr;
	int ulen, flen;
	long utmpid;
    char buf[256];
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sls", &userid, &ulen, &utmpid, &fromaddr, &flen) == FAILURE)
		WRONG_PARAM_COUNT;
	if (utmpid < 0 || utmpid > MAXACTIVE)
        RETURN_STRING("invalid utmpid", 1);

	chdir(BBSHOME);
	resolve_utmp();
	resolve_ucache();
	uinfo = &(sessionVar()->utmpshm->uinfo[utmpid]);

	
	/* 判断有效用户 */

	if((uid = (getuser(uinfo->userid, &urec))) != uinfo->uid) {
		snprintf(buf, sizeof(buf), "uid != uinfo->uid. %d %d", uid, uinfo->uid);
		RETURN_STRING(buf, 1);
	}

	if (strcasecmp(urec.userid, userid)) {
		snprintf(buf, sizeof(buf), "urec.userid != userid. %s %s", urec.userid, userid);
		RETURN_STRING(buf, 1);
	}
		
	/* 判断有效utmp */
	if (uinfo->active == 0) RETURN_STRING("uinfo->active == 0", 1);
	if ((uinfo->mode & WWW) == 0) RETURN_STRING("uinfo->mode not WWW", 1);
 
	if (uinfo->userid[0] == 0) {
        snprintf(buf, sizeof(buf), "uinfo->userid[0] is NULL. %s", uinfo->userid+1);
        RETURN_STRING(buf, 1);
    }

	//if (strcmp(uinfo->from, fromaddr)) {
    //    snprintf(buf, sizeof(buf), "uinfo->from != fromaddr. %s %s", uinfo->from, fromaddr);
	//	RETURN_STRING(buf, 1);
    //}

	uinfo->idle_time = time(NULL);

	array_init(return_value);
	add_assoc_long(return_value, "active", uinfo->active);
	add_assoc_long(return_value, "uid", uinfo->uid);
	add_assoc_long(return_value, "pid", uinfo->pid);
	add_assoc_long(return_value, "invisible", uinfo->invisible);
	/* sockactive */
	/* sockaddr */
	/* destuid */
	add_assoc_long(return_value, "mode", uinfo->mode);
	add_assoc_long(return_value, "pager", uinfo->pager);
	/* in_chat */
	add_assoc_long(return_value, "fnum", uinfo->fnum);
	add_assoc_long(return_value, "ext_idle", uinfo->ext_idle);
	add_assoc_string(return_value, "chatid", uinfo->chatid, 1);
	add_assoc_string(return_value, "from", uinfo->from, 1);
	add_assoc_long(return_value, "hideip", uinfo->hideip);
	add_assoc_long(return_value, "idle_time", uinfo->idle_time);
	add_assoc_long(return_value, "deactive_time", uinfo->deactive_time);
	add_assoc_string(return_value, "userid", uinfo->userid, 1);
	add_assoc_string(return_value, "realname", uinfo->realname, 1);
	add_assoc_string(return_value, "username", uinfo->username, 1);
    
}


/* 插入用户到utmp中，使登陆状态 */
/* fixme: return code */
PHP_FUNCTION(ext_insert_utmp) {

	struct user_info uinfo;
	struct user_info *uentp;
	struct userec user;
	int uid, i, utmpfd;
	time_t now;
	
	char *userid, *fromaddr;
	int ulen, flen;

	if (ZEND_NUM_ARGS() != 2) WRONG_PARAM_COUNT;
	if (zend_parse_parameters(2 TSRMLS_CC, "ss", &userid, &ulen, &fromaddr, &flen) == FAILURE)
		return;

	chdir(BBSHOME);
	resolve_utmp();
	resolve_ucache();

	uid = getuser(userid, &user);

	if(uid == 0) RETURN_LONG(-1l);

	memset(&uinfo, 0, sizeof(uinfo));

	uinfo.active = 1;
	uinfo.pid = 1;
	uinfo.uid = uid;
	uinfo.idle_time = time(NULL);
	uinfo.mode |= WWW;

	if ((user.userlevel & PERM_LOGINCLOAK) && (user.flags[0] & CLOAK_FLAG))
		uinfo.invisible = YEA;
	
	if (user.userdefine & DEF_FRIENDCALL)
		uinfo.pager |= FRIEND_PAGER;
	if (user.flags[0] & PAGER_FLAG) {
		uinfo.pager |= ALL_PAGER;
		uinfo.pager |= FRIEND_PAGER;
	}
	if (user.userdefine & DEF_FRIENDMSG)
		uinfo.pager |= FRIENDMSG_PAGER;
	if (user.userdefine & DEF_ALLMSG) {
		uinfo.pager |= ALLMSG_PAGER;
		uinfo.pager |= FRIENDMSG_PAGER;
	}

	strlcpy(uinfo.from, fromaddr, sizeof(uinfo.from));
	strlcpy(uinfo.userid, user.userid, sizeof(uinfo.userid));
	strlcpy(uinfo.realname, user.realname, sizeof(uinfo.realname));
	strlcpy(uinfo.username, user.username, sizeof(uinfo.username));
    //	strlcpy(uinfo.from, fromaddr, sizeof(uinfo.from));

	if (user.userdefine & DEF_NOTHIDEIP) uinfo.hideip = 'N';
	if (user.userdefine & DEF_FRIENDSHOWIP) uinfo.hideip = 'F';


	char ULIST[80];
	char genbuf[256];
	gethostname(genbuf, sizeof(genbuf));
	snprintf(ULIST, sizeof(ULIST), "UTMP.%s", genbuf);
	if ((utmpfd = open(ULIST, O_RDWR | O_CREAT, 0600)) == -1)
		RETURN_LONG(-3l);
	
	f_exlock(utmpfd); /* 锁住文件*/
	now = time(NULL);
	resolve_utmp();
	
	int same_request = 0;
	
	for (i = 0; i < USHM_SIZE; i++) { /* reinsert utmp时可能会同时多个insert */
		uentp = &(sessionVar()->utmpshm->uinfo[i]);
		if (uentp->active && !strcmp(uentp->userid, uinfo.userid)
				&& !strcmp(uentp->from, uinfo.from) 
				&& uinfo.idle_time - uentp->idle_time <= 2) {
			same_request = 1;
			break;
		}
	}

	if (!same_request) {
		for (i = 0; i < USHM_SIZE; i++) { /* 查找空闲的slot*/
			uentp = &(sessionVar()->utmpshm->uinfo[i]);
			if ((!uentp->active || !uentp->pid) && uentp->deactive_time + 60 < now)
				break;
		}

		if (i >= USHM_SIZE) {
			close(utmpfd);		// f_unlock(utmpfd);
			RETURN_LONG(-4l);	/* 在线用户达到最大 */
		}
	}
	
	
	sessionVar()->utmpshm->uinfo[i] = uinfo;
	if (!same_request) sessionVar()->utmpshm->usersum++;

	close(utmpfd);		// f_unlock(utmpfd);

	RETURN_LONG((long)i);
}


/* 从utmp中删除用户，即离线 */
PHP_FUNCTION(ext_remove_utmp) {
	struct user_info *uinfo;
	struct userec user;
	int uid;
	int status = -1;

	char *userid;
	int ulen;
	long utmpid;
	
	if (ZEND_NUM_ARGS() != 2) WRONG_PARAM_COUNT;
	if (zend_parse_parameters(2 TSRMLS_CC, "sl", &userid, &ulen, &utmpid) == FAILURE)
		return;

	chdir(BBSHOME);
	resolve_utmp();
	resolve_ucache();
	uid = getuser(userid, &user);

	if(uid == 0)
		RETURN_FALSE;

	uinfo = &(sessionVar()->utmpshm->uinfo[utmpid]);
	if (strcmp(uinfo->userid, user.userid))
		RETURN_FALSE;
	
	uinfo->active = 0;
    sessionVar()->utmpshm->usersum--;
	
	RETURN_TRUE;
}



/* 更改指定utmp的内容，按需求可修改的字段 */
/* 比如目前是mode, invisible, from.. */
PHP_FUNCTION(ext_update_utmp) {

	struct user_info new_info;
	struct user_info *uinfo;
	long tmp;
	char *tmpstr;
	int tmplen;
	
	long utmpid;
	zval *parm;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "la", &utmpid, &parm) == FAILURE)
		WRONG_PARAM_COUNT;
	chdir(BBSHOME);
	resolve_utmp();

	if (utmpid < 0 || utmpid >= USHM_SIZE) {
		RETURN_FALSE;
	}


	uinfo = &(sessionVar()->utmpshm->uinfo[utmpid]);

	new_info = *uinfo;

	if (zval_array_get_long(parm, "mode", 5, &tmp) != -1) {
		new_info.mode = tmp;
	}
	if (zval_array_get_long(parm, "invisible", 10, &tmp) != -1) {
		new_info.invisible = tmp;
	}
	if (zval_array_get_str(parm, "from", 5, &tmpstr, &tmplen) != -1) {
		strlcpy(new_info.from, tmpstr, sizeof(new_info.from));
	}

	*uinfo = new_info;
	
	RETURN_TRUE;
}

/* 删除一个WWW mode(pid == 1)的utmp */
PHP_FUNCTION(ext_kick_multi) {

	struct user_info *uentp;
	int i;
	
	char *userid;
	int ulen;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &userid, &ulen) == FAILURE)
		WRONG_PARAM_COUNT;

	chdir(BBSHOME);
	resolve_utmp();

	for (i = 0; i < USHM_SIZE; i++) {
		uentp = &(sessionVar()->utmpshm->uinfo[i]);
		if ((uentp->active && uentp->pid == 1)) {
			if (strcasecmp(uentp->userid, userid)) {
				continue;
			}
			uentp->active = 0;
		}
	}
	
}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
