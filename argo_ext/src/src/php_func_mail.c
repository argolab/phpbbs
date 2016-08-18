#include "ext_prototype.h"

/* mail目录操作相关 */

PHP_FUNCTION(ext_count_mail)
{
	char dir[80];
	int total;
	
	char *userid;
	int ulen;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &userid, &ulen) == FAILURE)
		WRONG_PARAM_COUNT;
	
	chdir(BBSHOME);
	snprintf(dir, sizeof(dir), "mail/%c/%s/.DIR", mytoupper(userid[0]), userid);
	if (access(dir, F_OK) < 0) RETURN_LONG(0);

	total = get_num_records(dir, sizeof(struct fileheader));

	RETURN_LONG(total);

}
/* start = { x | 0 <= x < total } */
PHP_FUNCTION(ext_list_mail)
{
	char dir[80];
	int i, fd, total;
	struct fileheader x;
	zval *l[100];
	
	char *userid;
	int ulen;
	int start, count;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sll", &userid, &ulen, &start, &count) == FAILURE)
		WRONG_PARAM_COUNT;
	
	chdir(BBSHOME);
	snprintf(dir, sizeof(dir), "mail/%c/%s/.DIR", mytoupper(userid[0]), userid);


	total = get_num_records(dir, sizeof(struct fileheader));
	if (start <= 0 || start > total)
		RETURN_NULL();

	if ((fd = open(dir, O_RDONLY)) == -1)
		RETURN_NULL();

	count = count > 100 ? 100 : count;
	lseek(fd, (start-1) * sizeof(struct fileheader), SEEK_SET);
	for (i = 0; i < count; i++) {
		if (read(fd, &x, sizeof(x)) <= 0) break;
		MAKE_STD_ZVAL(l[i]);
		object_init(l[i]);
		add_property_long(l[i], "index", start + i);
		add_property_long(l[i], "flag", x.flag);
		add_property_long(l[i], "filetime", x.filetime);
		add_property_string(l[i], "owner", x.owner, 1);
		add_property_string(l[i], "title", x.title, 1);
	}
	close(fd);
	
	//if (total != i) total = i;
    total = i;

	array_init(return_value);
	for (i = 0; i < total; i++)
		add_index_zval(return_value, i, l[i]);
	
}

/* 阅读邮件内容 
 * type 0: raw
 * type 1: html
 */
PHP_FUNCTION(ext_read_mail)
{
	char dir[80];
	struct fileheader x;
	char *content;
	int total;
	off_t size;
	
	char *userid;
	int ulen, type;
	long index;
    
    // type 0: raw, type 1: with html
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sll", &userid, &ulen, &index, &type) == FAILURE)
		WRONG_PARAM_COUNT;

	chdir(BBSHOME);
	snprintf(dir, sizeof(dir), "mail/%c/%s/.DIR", mytoupper(userid[0]), userid);
	
	total = get_num_records(dir, sizeof(struct fileheader));
	if (index > total)
		RETURN_NULL();
	if (get_record(dir, &x, sizeof(struct fileheader), index+1) < 0)
		RETURN_NULL();

	snprintf(dir, sizeof(dir), "mail/%c/%s/%s", mytoupper(userid[0]), userid, x.filename);
	if(mmapfile(dir, O_RDONLY, &content, &size, NULL) == 0)
        RETURN_NULL();
    
    if (type) {
	    php_start_ob_buffer(NULL, 0, 0 TSRMLS_CC);
	    html_print_buffer(content, size);
	    php_ob_get_buffer(return_value TSRMLS_CC);
	    php_end_ob_buffer(0, 0 TSRMLS_CC);
    } else {
        ZVAL_STRINGL(return_value, content, size, 1);
    }
	
	munmapfile(content, size, -1);

	x.flag |= FILE_READ;
	snprintf(dir, sizeof(dir), "mail/%c/%s/.DIR", mytoupper(userid[0]), userid);
	substitute_record(dir, &x, sizeof(struct fileheader), index + 1);
	
}

/* 标记单封邮件已读 */
PHP_FUNCTION(ext_mark_read_mail)
{
	struct fileheader x;
	char dir[STRLEN];
	
	char *userid;
	int ulen;
	long index;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sl", &userid, &ulen, &index) == FAILURE)
		WRONG_PARAM_COUNT;

	chdir(BBSHOME);
	snprintf(dir, sizeof(dir), "mail/%c/%s/.DIR", mytoupper(userid[0]), userid);

	if (get_record(dir, &x, sizeof(struct fileheader), index + 1) < 0)
		RETURN_FALSE;

	if (x.flag & FILE_READ) {
		RETURN_TRUE;
	}
	
	x.flag |= FILE_READ;
	substitute_record(dir, &x, sizeof(struct fileheader), index + 1);

	RETURN_TRUE;
}

/* 回复邮件时的引用 */
/* return_value 是数组 */
/* "receiver" => */
/* "title" => */
/* "quote" => */
/* "articleid" => */

PHP_FUNCTION(ext_quote_mail)
{
	FILE *fp;
	char dir[STRLEN], buf[512], *file;
	struct userec urec;
	struct fileheader x;
	int i, total, lines = 0;

	char *userid;
	int ulen;
	long index;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sl", &userid, &ulen, &index) == FAILURE)
		WRONG_PARAM_COUNT;

	chdir(BBSHOME);
	snprintf(dir, sizeof(dir), "mail/%c/%s/.DIR", mytoupper(userid[0]), userid);
	
	total = get_num_records(dir, sizeof(struct fileheader));
	if (index < 0 || index >= total)
		RETURN_NULL();
	if (get_record(dir, &x, sizeof(struct fileheader), index + 1) < 0)
		RETURN_NULL();

	char content[2048];
	int contlen = sizeof(content) / sizeof(char);
	
	snprintf(dir, sizeof(dir), "mail/%c/%s/%s", mytoupper(userid[0]), userid, x.filename);
	fp = fopen(dir, "r");

	for (i = 0; i < 4; i++) {
		if (fgets(buf, 512, fp) == 0)
			break;
		while (buf[strlen(buf) - 1] == '\n')
			buf[strlen(buf) - 1] = 0;
		if (i == 0) {
			snprintf(content, sizeof(content), "\n\n【 在 %s 的来信中提到: 】\n", &buf[8]);
		}
	}
				
	while(1) {
		if (fgets(buf, 500, fp) == 0) break;
		if (!strncmp(buf, ": 【", 4)) continue;
		if (!strncmp(buf, ": : ", 4)) continue;
		if (!strncmp(buf, "--\n", 3)) break;
		if (buf[0] == '\n') continue;
		safe_strcat(content, ": ", 0, &contlen);
		safe_strcat(content, nohtml(buf), 0, &contlen);
		if( lines++ > 20) {
			safe_strcat(content, ": (以下引言省略 ......)", 0, &contlen);
			break;
		}
	}
	fclose(fp);

	array_init(return_value);
	add_assoc_string(return_value, "receiver", x.owner, 1);
	add_assoc_string(return_value, "filename", x.filename, 1);	
	add_assoc_string(return_value, "title", x.title, 1);
	add_assoc_string(return_value, "quote", content, 1);
	add_assoc_long(return_value, "articleid", x.id);
}


PHP_FUNCTION(ext_send_mail)
{
	struct fileheader header;
	struct user_info *uinfo;	/* from */
	struct userec urec;			/* to */
	char genbuf[128];
	char mailhead[512];
	char mailfoot[512];
	
	char filepath[PATH_MAX + 1];
	int i, fd, articleid, ncount;
	
	zval *parm;
	char *from, *to, *title, *content;
	int flen, tolen, ulen, titlen, clen;
	long aid;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "a", &parm) == FAILURE)
		WRONG_PARAM_COUNT;

	zval_array_get_str(parm, "from", 5, &from, &flen);
	if (!from) RETURN_FALSE;
	zval_array_get_str(parm, "to", 3, &to, &tolen);
	if (!to) RETURN_FALSE;
	zval_array_get_str(parm, "title", 6, &title, &titlen);
	if (!title) RETURN_FALSE;
	zval_array_get_str(parm, "content", 8, &content, &clen);
	if (!content) RETURN_FALSE;
	zval_array_get_long(parm, "articleid", 10, &aid);

	chdir(BBSHOME);
	resolve_utmp();
	
	for (i = 0; i < MAXACTIVE; i++) {
		uinfo = &(sessionVar()->utmpshm->uinfo[i]);
		if (uinfo->userid[flen] == '\0' && !strncmp(uinfo->userid, from, flen)) {
			break;
		}
	}
	/* not login */
	if (i == MAXACTIVE) {
		RETURN_FALSE;
	}

	
	if (getuser(to, &urec) == 0) RETURN_FALSE;

	snprintf(genbuf, sizeof(genbuf), "mail/%c/%s", mytoupper(urec.userid[0]), urec.userid);
	if ((fd = getfilename(genbuf, filepath,
						  GFN_FILE | GFN_UPDATEID | GFN_NOCLOSE, &articleid)) == -1)
		RETURN_FALSE;
	
	memset(&header, 0, sizeof(header));
	strcpy(header.filename, strrchr(filepath, '/') + 1);
	header.filetime = time(NULL);
	strlcpy(header.owner, from, sizeof(header.owner));
	strlcpy(header.title, title, sizeof(header.title));
	header.id = (aid == 0) ? articleid : aid;

	struct iovec iov[3];
	
	snprintf(mailhead, sizeof(mailhead),
			 "发信人: %s (%s)\n标  题: %s\n发信站: %s (%24.24s)\n来  源: 精简版\n\n",
			 uinfo->userid, uinfo->username, header.title, BBSNAME, Ctime(time(0)));
	snprintf(mailfoot, sizeof(mailfoot),
			 "\n--\n\033[m\n\033[1;%dm※ 来源:．%s http://%s/m/ [FROM: %s%.20s]\33[m\n",
			 31 + rand() % 7, BBSNAME, BBSHOST, BBSNAME, "精简版");
	
	iov[0].iov_base = mailhead;
	iov[0].iov_len = strlen(mailhead);
	iov[1].iov_base = content;
	iov[1].iov_len = clen;
	iov[2].iov_base = mailfoot;
	iov[2].iov_len = strlen(mailfoot);

	ncount = writev(fd, iov, 3);

	close(fd);

	if (ncount != iov[0].iov_len + iov[1].iov_len + iov[2].iov_len) {
		unlink(filepath);
		RETURN_FALSE;
	}
	
	snprintf(genbuf, sizeof(genbuf), "mail/%c/%s/.DIR", mytoupper(urec.userid[0]), urec.userid);
	if (append_record(genbuf, &header, sizeof(header)) == -1) {
		unlink(filepath);
		RETURN_FALSE;
	}
	
	RETURN_TRUE;
	
}

/* 检查是否有新邮件 */
PHP_FUNCTION(ext_check_mail)
{
	struct fileheader x;
	char dir[80];
	int total;
	
	char *userid;
	int ulen;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &userid, &ulen) == FAILURE)
		WRONG_PARAM_COUNT;
	
	chdir(BBSHOME);
	snprintf(dir, sizeof(dir), "mail/%c/%s/.DIR", mytoupper(userid[0]), userid);
	
	total = get_num_records(dir, sizeof(struct fileheader));

	if (!total || get_record(dir, &x, sizeof(struct fileheader), total) < 0) {
		RETURN_FALSE;
	}

	if (x.flag & FILE_READ) {
        RETURN_FALSE;
	}

	RETURN_TRUE;
}

/* 删除邮件 */
PHP_FUNCTION(ext_del_mail)
{
	struct fileheader *fhlist;
	zval *arr, **data;
    HashTable *harr;
	char *userid;
	int ulen,index_count, *index_arr, fd, total, i, cnt;
   	char dir[256], path[256]; 
    
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sa", &userid, &ulen, &arr) == FAILURE)
		WRONG_PARAM_COUNT;

    chdir(BBSHOME);
    harr = Z_ARRVAL_P(arr);
    index_count = zend_hash_num_elements(harr);
    
    if(index_count == 0) RETURN_TRUE;

    index_arr = (long *)emalloc((index_count+1) * sizeof(long));

    int n=0;
    for(zend_hash_internal_pointer_reset(harr);
        zend_hash_get_current_data(harr, &data) == SUCCESS;
        zend_hash_move_forward(harr)) {

        index_arr[n++] = atoi(Z_STRVAL_PP(data));
    }
    index_count = n;
    
    snprintf(dir, sizeof(dir), "mail/%c/%s/.DIR", mytoupper(userid[0]), userid);
    total = get_num_records(dir, sizeof(struct fileheader));
    if(total <= 0) {
        efree(index_arr);
        RETURN_FALSE;
    }

    if((fd = open(dir, O_RDWR, 0644)) < 0) {
        efree(index_arr);
        RETURN_FALSE;
    }

    fhlist = (struct fileheader *)mmap(NULL, total*sizeof(struct fileheader),PROT_READ | PROT_WRITE, MAP_SHARED, fd, 0);
    if(fhlist == MAP_FAILED) {
        close(fd);
        efree(index_arr);
        RETURN_FALSE;
    }
    
    for(i=0; i<index_count; i++)
    {
        if(index_arr[i] < 0 || index_arr[i] >= total) continue;
        snprintf(path, sizeof(path), "mail/%c/%s/%s",
                 mytoupper(userid[0]), userid, fhlist[index_arr[i]].filename);
        f_rm(path);
        fhlist[index_arr[i]].filename[0] = '\0';
    }    
    efree(index_arr);

    cnt = 0;
    for(i=0; i<total; i++)
    {
        if(fhlist[i].filename[0] != '\0') {
            fhlist[cnt++] = fhlist[i];
        }
    }
    munmap((void *)fhlist, total*sizeof(struct fileheader));
    ftruncate(fd, cnt*sizeof(struct fileheader));
    close(fd);
    RETURN_TRUE;
        /*
	chdir(BBSHOME);    
	snprintf(path, sizeof(path), "mail/%c/%s/.DIR", mytoupper(userid[0]), userid);
	if (get_record(path, &fh, sizeof(struct fileheader), index + 1) < 0) {
		RETURN_FALSE;
	}

	if (delete_record(path, sizeof(struct fileheader), index + 1) < 0) {
		RETURN_FALSE;
	}

	snprintf(path, sizeof(path), "mail/%c/%s/%s", mytoupper(userid[0]), userid, fh.filename);
	f_rm(path);

	RETURN_TRUE;
        */
}

/* 标记为已回复 */
/* unknow what the filename come from, don't use it */
PHP_FUNCTION(ext_mark_replied)
{
	char dir[80];
	struct fileheader fh;
	int id;
	
	char *userid, *filename;
	int ulen, flen;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &userid, &ulen, &filename, &flen) == FAILURE)
		WRONG_PARAM_COUNT;

	chdir(BBSHOME);
	snprintf(dir, sizeof(dir), "mail/%c/%s/.DIR", mytoupper(userid[0]), userid);
	id = search_record_forward(dir, &fh, sizeof(struct fileheader), 1, cmp_filename, filename);
	if (id == 0) RETURN_FALSE;

	fh.flag |= MAIL_REPLY;

	if (substitute_record(dir, &fh, sizeof(struct fileheader), id) == -1)
		RETURN_FALSE;
	
	RETURN_TRUE;

}

PHP_FUNCTION(ext_mark_replied_mail)
{
	struct fileheader x;
	char dir[STRLEN];
	
	char *userid;
	int ulen;
	long index;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sl", &userid, &ulen, &index) == FAILURE)
		WRONG_PARAM_COUNT;

	chdir(BBSHOME);
	snprintf(dir, sizeof(dir), "mail/%c/%s/.DIR", mytoupper(userid[0]), userid);

	if (get_record(dir, &x, sizeof(struct fileheader), index + 1) < 0)
		RETURN_FALSE;

	if (x.flag & MAIL_REPLY) {
		RETURN_TRUE;
	}
	
	x.flag |=  MAIL_REPLY;
	substitute_record(dir, &x, sizeof(struct fileheader), index + 1);

	RETURN_TRUE;
}


PHP_FUNCTION(ext_used_mail_size)
{
    char *userid;
    int ulen, used_size, fd, total;
    char dir[80],path[80];
    struct stat st;
    struct fileheader fh;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &userid, &ulen) == FAILURE)
        WRONG_PARAM_COUNT;

    chdir(BBSHOME);
    
    snprintf(dir, sizeof(dir), "mail/%c/%s/.DIR", mytoupper(userid[0]), userid);

    total = 0;
    if((fd = open(dir, O_RDONLY, 0644)) < 0) RETURN_LONG(total);
    
    while(read(fd, &fh, sizeof(fh)) > 0)
    {
        snprintf(path, sizeof(path), "mail/%c/%s/%s", mytoupper(userid[0]), userid, fh.filename);
        if(stat(path, &st) <0) continue;
        total += st.st_size;
    }
    
    close(fd);
    RETURN_LONG(total);
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
