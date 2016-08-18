#include "ext_prototype.h"

/* 通过验证则返回true，否则为用户名不存在或密码不正确 */
PHP_FUNCTION(ext_checkpassword)
{
	char *username;
	char *password;
	long ismd5 = 0;
	int u_len, p_len;
	int uid;
	struct userec user;

	if (ZEND_NUM_ARGS() == 2) {
		if (zend_parse_parameters(2 TSRMLS_CC, "ss", &username, &u_len, &password, &p_len) == FAILURE)
			return;
	} else if (ZEND_NUM_ARGS() == 3) {
		if (zend_parse_parameters(3 TSRMLS_CC, "ssl", &username, &u_len, &password, &p_len, &ismd5) == FAILURE)
			return;
	} else {
		WRONG_PARAM_COUNT;
	}

	chdir(BBSHOME);

	 resolve_ucache(); 
	if((uid = (getuser(username, &user))) == 0)
		RETURN_FALSE;

	if (ismd5) {
		if (memcmp(password, user.passwd, MD5_PASSLEN))
			RETURN_FALSE;
		RETURN_TRUE;
	} else if (checkpasswd2(password, &user)) {
		RETURN_TRUE;
	}

	RETURN_FALSE;
}


PHP_FUNCTION(ext_igenpass)
{
    char *userid,*passwd;
    int ulen,plen;
    unsigned char md5passwd[MD5_PASSLEN+1];
    
    if (zend_parse_parameters(2 TSRMLS_CC, "ss", &userid, &ulen, &passwd, &plen) == FAILURE)
        RETURN_FALSE;

    igenpass(passwd, userid, md5passwd);

    md5passwd[MD5_PASSLEN] = '\0';
    
    RETURN_STRINGL(md5passwd, MD5_PASSLEN, 1); 
}

/* 从.PASSWDS中获取用户信息，来自struct userec */
PHP_FUNCTION(ext_get_urec)
{

	struct userec user;

	char *userid, *special;
	int u_len;
    FILE *fp;

	if (ZEND_NUM_ARGS() != 1) {
		RETURN_NULL();
	}
	
	if (zend_parse_parameters(1 TSRMLS_CC, "s", &userid, &u_len) == FAILURE)
		return;

	chdir(BBSHOME);
	
	if (getuser(userid, &user) == 0)
		RETURN_NULL();

	array_init(return_value);
	add_assoc_string(return_value, "userid", user.userid, 1);
	add_assoc_long(return_value, "firstlogin", user.firstlogin);
	add_assoc_string(return_value, "lasthost", user.lasthost, 1);
	add_assoc_long(return_value, "numlogins", user.numlogins);
	add_assoc_long(return_value, "numposts", user.numposts);
	add_assoc_long(return_value, "flags", atoi(user.flags));
	add_assoc_string(return_value, "passwd", user.passwd, 1);
	add_assoc_string(return_value, "username", user.username, 1);
	/* not need: ident */
	/* not need: temptype */
	add_assoc_string(return_value, "reginfo", user.reginfo, 1);
	add_assoc_long(return_value, "userlevel", user.userlevel);
	add_assoc_long(return_value, "usertitle", user.usertitle); /* unsigned char */
	/* not need: reserved */
	add_assoc_long(return_value, "lastlogin", user.lastlogin);
	add_assoc_long(return_value, "lastlogout", user.lastlogout);
	add_assoc_long(return_value, "stay", user.stay);
	add_assoc_string(return_value, "realname", user.realname, 1);
	add_assoc_string(return_value, "address", user.address, 1);
	add_assoc_string(return_value, "email", user.email, 1);
	add_assoc_long(return_value, "nummails", user.nummails);
	add_assoc_long(return_value, "lastjustify", user.lastjustify);
	add_assoc_long(return_value, "gender", user.gender); /* char */
	add_assoc_long(return_value, "birthyear", user.birthyear); /* unsigned char */
	add_assoc_long(return_value, "birthmonth", user.birthmonth); /* unsigned char */
	add_assoc_long(return_value, "birthday", user.birthday); /* unsigned char */
	add_assoc_long(return_value, "signature", user.signature);
	add_assoc_long(return_value, "userdefine", user.userdefine);
	add_assoc_long(return_value, "notedate", user.notedate);
	add_assoc_long(return_value, "noteline", user.noteline);
   
	/* 顺便获取特殊称号 */
    special = show_special(user.userid);
    if(special[0])
        add_assoc_string(return_value, "special_title", special, 1);
}

/* 获取utmp中的user_info */
PHP_FUNCTION(ext_get_uinfo)
{

    struct user_info *uinfo;

	char *userid;
	int ulen;
    int i;

	if (ZEND_NUM_ARGS() != 1) {
		RETURN_NULL();
	}
	
	if (zend_parse_parameters(1 TSRMLS_CC, "s", &userid, &ulen) == FAILURE)
		return;

	chdir(BBSHOME);

    resolve_utmp();
    
    for(i=0; i < MAXACTIVE; i++){
        uinfo = &(sessionVar()->utmpshm->uinfo[i]);
        if(uinfo->active && !uinfo->invisible && uinfo->userid[ulen] == '\0' && !strncasecmp(uinfo->userid, userid, ulen)) {
            break;
        }
    }

    if(i >= MAXACTIVE) RETURN_NULL();
        
	array_init(return_value);
	add_assoc_long(return_value, "active", uinfo->active);
    add_assoc_long(return_value, "uid", uinfo->uid);
    add_assoc_long(return_value, "pid", uinfo->pid);
    add_assoc_long(return_value, "invisible", uinfo->invisible);
        /* sockactive */
        /* sockaddr */
        /* destuid */
    add_assoc_long(return_value, "mode", uinfo->mode);
        /* pager */
        /* in_chat */
    add_assoc_long(return_value, "fnum", uinfo->fnum);
    add_assoc_long(return_value, "ext_idle", uinfo->ext_idle);
        /* chatid */
        /* from */
    add_assoc_long(return_value, "hideip", uinfo->hideip);
    add_assoc_long(return_value, "idle_time", uinfo->idle_time);
    add_assoc_long(return_value, "deactive_time", uinfo->deactive_time);
    add_assoc_string(return_value, "userid", uinfo->userid, 1);
    add_assoc_string(return_value, "realname", uinfo->realname, 1);
    add_assoc_string(return_value, "username", uinfo->username, 1);
        /* nickcolor */
        /* friends */
        /* reject */
    return;
	/*  */	
}


/* 更新.PASSWDS，参数是用户id和php数组 key->value */
/* 需要从php代码中直接update的变量不多，如numlogins,numposts都无需在这里处理 */
/* 主要用于更新用户资料 */
/* 如果有第三个参数，那么是insert */
PHP_FUNCTION(ext_update_urec)
{

	zval *arr, **data;
	HashTable *arr_hash;
	int arr_count;
	char *string_key, *sinsert;
	char path[256];
	unsigned long num_key;
	int uid,slen;
	
	struct userec user;
	char *userid;
	int u_len;
	int is_insert;

	if(ZEND_NUM_ARGS() == 2) {
		is_insert = 0;
		if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sa", &userid, &u_len, &arr) == FAILURE)  WRONG_PARAM_COUNT;
	} else  {
		is_insert = 1;
		if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sas", &userid, &u_len, &arr, &sinsert, &slen) == FAILURE)  WRONG_PARAM_COUNT;
	}
	
	chdir(BBSHOME);
	if(!is_insert) {
		if ((uid = getuser(userid, &user)) == 0)
			RETURN_FALSE;
    } else {
		memset(&user, 0, sizeof(user));
	}
	arr_hash = Z_ARRVAL_P(arr);
	arr_count = zend_hash_num_elements(arr_hash);

	if (arr_count < 1) RETURN_TRUE;

	zend_hash_internal_pointer_reset(arr_hash);

	while (zend_hash_get_current_data(arr_hash, (void **) &data) == SUCCESS) {

		if (zend_hash_get_current_key(arr_hash, &string_key, &num_key, 0)
			!= HASH_KEY_IS_STRING) {
			RETURN_FALSE;
		}
		if (strcmp(string_key, "userid") == 0) {

			if (Z_TYPE_PP(data) != IS_STRING) RETURN_FALSE;
			strncpy(user.userid, Z_STRVAL_PP(data), IDLEN + 1);
			user.userid[IDLEN] = '\0';

		}  else if (strcmp(string_key, "lastlogin") == 0) {

			if (Z_TYPE_PP(data) != IS_LONG) RETURN_FALSE;
			user.lastlogin = (time_t)Z_LVAL_PP(data);

		} else if (strcmp(string_key, "firstlogin") == 0) {

			if (Z_TYPE_PP(data) != IS_LONG) RETURN_FALSE;
			user.firstlogin = (time_t)Z_LVAL_PP(data);

        } else if (strcmp(string_key, "lastlogout") == 0) {

			if (Z_TYPE_PP(data) != IS_LONG) RETURN_FALSE;
			user.lastlogout = (time_t)Z_LVAL_PP(data);

		} else if (strcmp(string_key, "stay") == 0) {

			if (Z_TYPE_PP(data) != IS_LONG) RETURN_FALSE;
			user.stay = (time_t)Z_LVAL_PP(data);

		} else if (strcmp(string_key, "numlogins") == 0) {

			if (Z_TYPE_PP(data) != IS_LONG) RETURN_FALSE;
			user.numlogins = Z_LVAL_PP(data);

		} else if (strcmp(string_key, "numposts") == 0) {

			if (Z_TYPE_PP(data) != IS_LONG) RETURN_FALSE;
			user.numposts = Z_LVAL_PP(data);

		} else if (strcmp(string_key, "lasthost") == 0) {

			if (Z_TYPE_PP(data) != IS_STRING) RETURN_FALSE;

			/* PHPWRITE(Z_STRVAL_PP(data), Z_STRLEN_PP(data)); */

			strncpy(user.lasthost, Z_STRVAL_PP(data), 16);
			user.lasthost[15] = '\0';
		} else if (strcmp(string_key, "passwd") == 0) {

			if (Z_TYPE_PP(data) != IS_STRING) RETURN_FALSE;
			memcpy(user.passwd, Z_STRVAL_PP(data), MD5_PASSLEN);

		} else if (strcmp(string_key, "username") == 0) {

			if (Z_TYPE_PP(data) != IS_STRING) RETURN_FALSE;

			strncpy(user.username, Z_STRVAL_PP(data), NICKNAMELEN + 1);
			user.username[NICKNAMELEN] = '\0';

		} else if (strcmp(string_key, "realname") == 0) {

			if (Z_TYPE_PP(data) != IS_STRING) RETURN_FALSE;

			strncpy(user.realname, Z_STRVAL_PP(data), NAMELEN + 1);
			user.realname[NAMELEN] = '\0';
			
		} else if (strcmp(string_key, "address") == 0) {

			if (Z_TYPE_PP(data) != IS_STRING) RETURN_FALSE;

			strncpy(user.address, Z_STRVAL_PP(data), STRLEN);
			user.address[STRLEN - 1] = '\0';
			
		} else if (strcmp(string_key, "email") == 0) {

			if (Z_TYPE_PP(data) != IS_STRING) RETURN_FALSE;

			strncpy(user.email, Z_STRVAL_PP(data), STRLEN - 12);
			user.email[STRLEN - 13] = '\0';

		} else if (strcmp(string_key, "gender") == 0) {
			
			if (Z_TYPE_PP(data) != IS_LONG) RETURN_FALSE;
			user.gender = (char)Z_LVAL_PP(data);
			
		} else if (strcmp(string_key, "birthyear") == 0) {
			
			if (Z_TYPE_PP(data) != IS_LONG) RETURN_FALSE;
			user.birthyear = (unsigned char)Z_LVAL_PP(data);
			
		} else if (strcmp(string_key, "birthmonth") == 0) {
			if (Z_TYPE_PP(data) != IS_LONG) RETURN_FALSE;
			user.birthmonth = (unsigned char)Z_LVAL_PP(data);

		} else if (strcmp(string_key, "birthday") == 0) {
			if (Z_TYPE_PP(data) != IS_LONG) RETURN_FALSE;
			user.birthday = (unsigned char)Z_LVAL_PP(data);
            
		} else if (strcmp(string_key, "userlevel") == 0) {
			if (Z_TYPE_PP(data) != IS_LONG) RETURN_FALSE;
			user.userlevel = (unsigned int)Z_LVAL_PP(data);

		} else if (strcmp(string_key, "reginfo") == 0) {
			if (Z_TYPE_PP(data) != IS_STRING) RETURN_FALSE;

			memcpy(user.reginfo, Z_STRVAL_PP(data), STRLEN - 16);
			user.reginfo[STRLEN - 17] = '\0';
		}
		zend_hash_move_forward(arr_hash);
	}
	
	if(!is_insert) {
		substitute_record(PASSFILE, &user, sizeof (user), uid);
	} else {
		//append_record(PASSFILE, &user, sizeof(user));
		//把user填写到PASSFILE中的空缺处，PASSFILE预先就补齐了MAXUSER的空间
		int fd,i,uid;
		if ((fd = open(PASSFILE, O_WRONLY | O_CREAT, 0644)) == -1) {
			RETURN_FALSE;
		}
		f_exlock(fd);
		uid = -1;
		for(i=0; i<MAXUSERS; i++)		
			if(sessionVar()->uidshm->userid[i][0] == '\0') {
				uid = i+1;
			}
		if(uid == -1) RETURN_FALSE;
		substitute_record(PASSFILE, &user, sizeof(user), uid);	
		memcpy(&sessionVar()->uidshm->userid[uid-1], &user.userid, IDLEN+2);
		f_unlock(fd);
		close(fd);

		//强制更新shmid
	    //resolve_ucache();	
		//sessionVar()->uidshm->uptime -= 86401;
		//resolve_ucache();
		//建立该用户的home
		snprintf(path, sizeof(path),"home/%c/%s", mytoupper(user.userid[0]), user.userid);
		mkdir(path, 0755);
	}
	RETURN_TRUE;
}

PHP_FUNCTION(ext_post_stat)
{
    char *userid;
    int ulen, dt, maxfeed, i, j, k, l, fd, total, fnum, cnt, maxfiletime, who;
    char dir[80];
    struct feedheader  x;
    struct fileheader fh;
    zval *feed,*arr, **data;
    HashTable *harr;
    struct feedheader *fdh[MAXFRIENDS];
    char fname[IDLEN+2];
    int index[MAXFRIENDS];
    
    char brc_buf[BRC_MAXSIZE], *ptr;
    char brc_name[BRC_STRLEN];
    int brc_list[BRC_MAXNUM], brc_num, brc_size;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "all", &arr, &dt, &maxfeed) == FAILURE) {            
        WRONG_PARAM_COUNT;
	}
	chdir(BBSHOME);

        /* 获取好友个数，顺便初始化列表 */
    harr = Z_ARRVAL_P(arr);
    fnum = zend_hash_num_elements(harr);
    if(fnum > MAXFRIENDS) fnum = MAXFRIENDS;    
    for(i=0; i<fnum; i++)
    {
        fdh[i] = (struct feedheader *) emalloc((maxfeed+5)*sizeof(struct feedheader));
        if(fdh[i] == NULL) RETURN_NULL();
        memset(fdh[i], 0, (maxfeed+5)*sizeof(struct feedheader));
    }

    dt = time(NULL) - dt*86400;
    
        /* 由于用户自己发贴都会被标记为已读，于是从.boardrc 中读取记录，
           因为好友数一般在几十个的数量级上，不用所有版都查一次
        */
    cnt = 0;
    zend_hash_internal_pointer_reset(harr);
    while(zend_hash_get_current_data(harr, &data) == SUCCESS) {
        strcpy(fname, Z_STRVAL_PP(data));

        snprintf(dir, sizeof(dir), "home/%c/%s/.boardrc", mytoupper(fname[0]), fname);
        if((fd = open(dir, O_RDONLY)) != -1) {
            brc_size = read(fd, brc_buf, sizeof(brc_buf));
            close(fd);
        } else {
            brc_size = 0;
        }
            //if(brc_size == 0) continue;

        ptr = brc_buf;
        while(ptr < brc_buf + brc_size && (*ptr >=' ' && *ptr <='z')) {
            ptr = brc_getrecord(ptr, brc_name, &brc_num, brc_list);
            if(brc_num <=0 ||  brc_list[0] <  dt) continue;
            
            snprintf(dir, sizeof(dir), "boards/%s/.DIR", brc_name);
            if((fd = open(dir, O_RDONLY)) <0) continue;
            int pos = get_num_records(dir, sizeof(struct fileheader)) ;
            
            for(i=0; i<brc_num; i++)
                if(brc_list[i] >= dt) {
                    do{
                        pos --;
                        lseek(fd, pos*sizeof(struct fileheader), SEEK_SET);
                        if(read(fd, &fh, sizeof(fh)) <0)  break;
                    } while(pos>0 && fh.filetime> brc_list[i]);
                    
                    if(fh.filetime < brc_list[i]) continue; 
                    if(strcmp(fh.owner, fname) != 0) continue;
                    
                    for(l=maxfeed-1; l>=0; l--)
                        if(brc_list[i] > fdh[cnt][l].filetime)  fdh[cnt][l+1] = fdh[cnt][l];
                        else break;
                    l++;
                    strlcpy(fdh[cnt][l].userid, fname, sizeof(fdh[cnt][l].userid));
                    strlcpy(fdh[cnt][l].board, brc_name, sizeof(fdh[cnt][l].board));
                    strlcpy(fdh[cnt][l].filename, fh.filename, sizeof(fdh[cnt][l].filename));
                        //snprintf(fdh[cnt][l].filename, sizeof(fdh[cnt][l].filename), "M.%d.A", brc_list[i]);
                    fdh[cnt][l].filetime = brc_list[i];
                } else break;
            close(fd);
        }
        zend_hash_move_forward(harr);
        cnt++;
    }
    
        /* 将各个好友的feed并归排序 */
    memset(index, 0, sizeof(index));    
    array_init(return_value);
  
    while(1) {
        maxfiletime = 0;
        for(i=0; i<fnum; i++)
            if(index[i] < maxfeed && fdh[i][index[i]].filetime > maxfiletime) {
                maxfiletime = fdh[i][index[i]].filetime;
                who = i;
            }
        if(maxfiletime == 0) break;
        
        MAKE_STD_ZVAL(feed);
        object_init(feed);
        add_property_string(feed, "userid", fdh[who][index[who]].userid, 1);
        add_property_string(feed, "board", fdh[who][index[who]].board, 1);
        add_property_string(feed, "filename", fdh[who][index[who]].filename, 1);
        add_property_long(feed, "filetime", fdh[who][index[who]].filetime);
        
        add_next_index_zval(return_value, feed);

        index[who] ++;
    }
    
        //for(i=0; i<fnum; i++) efree(fdh[i]);
}
PHP_FUNCTION(ext_is_user_exist)
{
    char *userid;
    int ulen,i;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &userid, &ulen) == FAILURE) {            
        WRONG_PARAM_COUNT;
	}
    
	chdir(BBSHOME);

    resolve_ucache();

    for(i=0; i<sessionVar()->uidshm->number; i++)
    {
        if(strcasecmp(userid, sessionVar()->uidshm->userid[i]) == 0)
            RETURN_STRING(sessionVar()->uidshm->userid[i], 1);
    }
  RETURN_NULL();
}
//check if in validate ip range  etc/auth_host
 /* cp from telnet src
  * modified by freestyler
  * lately modified by Cypress
  */
PHP_FUNCTION(ext_in_validate_ip_range)
{    
    int flen, nlen;
    int 	nofile	= 1; /* the return value when auth_host file not found */
	FILE *list;
	char buf[40], *ptr, *fname, *name;
    
    chdir(BBSHOME);
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &fname, &flen, &name, &nlen) == FAILURE) {            
        WRONG_PARAM_COUNT;
	}

	if ((list = fopen(fname, "r")) != NULL) {
		while (fgets(buf, 40, list)) {
			ptr = strtok(buf, " \n\t\r");
			if (ptr != NULL && *ptr != '#') {
				if (!strcmp(ptr, name)) {
					fclose(list);
					RETURN_TRUE;
				}
				if (ptr[0] == '-' &&
				    !strcmp(name, &ptr[1])) {
					fclose(list);
                    RETURN_FALSE;
                        //return 0;
				}
				if (ptr[strlen(ptr) - 1] == '.' &&
				    !strncmp(ptr, name, strlen(ptr) - 1)) {
					fclose(list);
                    RETURN_TRUE;
                        //return 1;
				}
				if (ptr[0] == '.' &&
				    strlen(ptr) < strlen(name)
				    && !strcmp(ptr,
					       name + strlen(name) -
					       strlen(ptr))) {
					fclose(list);
                    RETURN_TRUE;
                        //return 1;
				}
			}
		}
		fclose(list);
        RETURN_FALSE;
            //return 0;
	}
    if(nofile) RETURN_TRUE;
    RETURN_FALSE;
        //return nofile;
}
// online : online users ; total: total users
PHP_FUNCTION(ext_get_total)
{
    char *type;
    int tlen;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &type, &tlen) == FAILURE) {            
        WRONG_PARAM_COUNT;
	}
    chdir(BBSHOME);

    resolve_ucache();
    resolve_utmp();
    
    if(strcmp(type, "online") == 0)  {
        RETURN_LONG(sessionVar()->utmpshm->usersum);
    } else if (strcmp(type, "total") == 0) {
        RETURN_LONG(sessionVar()->uidshm->number);
    } else RETURN_LONG(0);
}

//检测是否注册次数过多
//只有一个参数时是检测 netid
//两个参数时，一个是校友验证信息的那一行，另外是真实名字，igenpass后和urec.reginfo比对
//
PHP_FUNCTION(ext_count_register) 
{
	struct userec user;

	char *netid, *special;
	char *buf, *rname;
	int u_len, blen, rlen, i; FILE *fp; char genbuf[MD5_PASSLEN+10];	

	int isNetID = 0;

	if(ZEND_NUM_ARGS() == 1) {
		isNetID = 1;
		if (zend_parse_parameters(1 TSRMLS_CC, "s", &netid, &u_len) == FAILURE)
			WRONG_PARAM_COUNT;
	} else {
		if (zend_parse_parameters(2 TSRMLS_CC, "ss", &buf, &blen, &rname, &rlen) == FAILURE)
			WRONG_PARAM_COUNT;
	}

	chdir(BBSHOME);
	
	int count = 0;
	if(!isNetID) igenpass(buf, rname, genbuf);	
	
	for(i=1; i<=sessionVar()->uidshm->number; i++)	
	{
		get_record(PASSFILE, &user, sizeof(user), i);
		if(isNetID) {
			if(memcmp(user.reginfo, netid, u_len) == 0) count++;
		} else {
			if(memcmp(user.reginfo, genbuf, MD5_PASSLEN) == 0)	count++;
		}
	}

	RETURN_LONG(count);
}

//发到 security 版的东西
PHP_FUNCTION(ext_security_report)
{
	char *userid, *info, *addinfo;
	int ulen, ilen, alen;
	struct userec user;

	chdir(BBSHOME);
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sss", &userid, &ulen, &info, &ilen, &addinfo, &alen) == FAILURE)
		WRONG_PARAM_COUNT;

	if (getuser(userid, &user) == 0)
		RETURN_FALSE;
	
	securityreport2(&user,info, addinfo, 1);
	
	RETURN_TRUE;
}
PHP_FUNCTION(ext_save_config)
{
	
	struct userec user;
	char *userid;
	int u_len;
	
	/* if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ssb", &userid, &u_len, &cfg_filename, &c_len, &content) == FAILURE) { */
	/* 	return; */
	/* } */
	/* chdir(BBSHOME); */
	/* if ((uid = getuser(userid, &user)) == 0) */
	/* 	RETURN_FALSE; */

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
