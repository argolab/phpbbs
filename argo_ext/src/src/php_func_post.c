#include "ext_prototype.h"


/* notice: 无检查访问权限，文章上限，关键字过滤，
   签名档，发帖记录，转寄，未读标记，发文数等  */
PHP_FUNCTION(ext_simplepost)
{
	struct fileheader header,x;
	struct user_info *uinfo;
    struct attacheader oah,ah;
    struct stat st;
	char genbuf[2048];
	char posthead[512];
	char *postfoot; /* 鉴于签名档大小不定，用动态内存存下 */
	char filepath[PATH_MAX + 1],path[256];
    char dir[256];    
	int i, fd,  articleid, ncount, has_attach, srcfd, desfd, n;
	long aid;
    
	struct iovec iov[3];
	
	zval *parm;
	char *userid, *board, *title, *content, *fromaddr,*articlename, *attach_tmpfile, *attach_origname, *attach_type, *signature;
	unsigned int ulen, blen, tlen, clen, flen,alen,atmlen, atolen, atylen, slen, anonymous, reply_notify;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "a", &parm) == FAILURE)
		WRONG_PARAM_COUNT;

    chdir(BBSHOME);

    
	zval_array_get_str(parm, "userid", 7, &userid, &ulen);
	if (!userid) RETURN_NULL();
	zval_array_get_str(parm, "board", 6, &board, &blen);
	if (!board) RETURN_NULL();
	zval_array_get_str(parm, "title", 6, &title, &tlen);
	if (!title) RETURN_NULL();
	zval_array_get_str(parm, "content", 8, &content, &clen);
	if (!content) RETURN_NULL();
	zval_array_get_str(parm, "fromaddr", 9, &fromaddr, &flen);
	if (!fromaddr) RETURN_NULL();
	zval_array_get_str(parm, "articleid", 10, &articlename,&alen);
    
    zval_array_get_str(parm, "signature", 10, &signature,&slen);
    
    zval_array_get_str(parm, "attach_tmpfile", 15, &attach_tmpfile,&atmlen);
    zval_array_get_str(parm, "attach_origname",16 , &attach_origname,&atolen);
    zval_array_get_str(parm, "attach_type",12 , &attach_type,&atylen);
    
    zval_array_get_long(parm, "anonymous", 10, &anonymous);
    zval_array_get_long(parm, "reply_notify", 13, &reply_notify);
    
    if(atmlen >0) has_attach = 1;
    else has_attach = 0;    
    
        /* 查找要回复的帖子的id，articlename格式为 M.123456789.A*/
    if(alen>0){
        setboardfile(dir, board, ".DIR");
        if(search_record(dir,&x,sizeof(x),cmpfilename,articlename)==0){
            RETURN_NULL();
        }
        aid=x.id;        
    }else aid=0;       
    
	resolve_utmp();	
	for (i = 0; i < MAXACTIVE; i++) {
		uinfo = &(sessionVar()->utmpshm->uinfo[i]);
		if (uinfo->userid[ulen] == '\0' && !strncmp(uinfo->userid, userid, ulen)) {
			break;
		}
	}
        /* not login */
	if (i == MAXACTIVE) {
		RETURN_NULL();
	}

	memset(&header, 0, sizeof(header));

	snprintf(genbuf, sizeof(genbuf), "boards/%s", board);
	if ((fd = getfilename(genbuf, filepath,
						  GFN_FILE | GFN_UPDATEID | GFN_NOCLOSE, &articleid)) == -1)
		RETURN_NULL();


    if(has_attach) header.flag |= FILE_ATTACHED;
    if(reply_notify) header.flag |= FILE_REPLYNOTIFY; /* 回复提醒 */
    
	strcpy(header.filename, strrchr(filepath, '/') + 1);
	header.filetime = articleid;
    if(anonymous) {
        strlcpy(header.owner, "Diary", sizeof(header.owner));
    } else {
        strlcpy(header.owner, userid, sizeof(header.owner));
    }
	strlcpy(header.realowner, userid, sizeof(header.realowner));
	strlcpy(header.title, title, sizeof(header.title));

	
	if (aid != 0) { /* 回复文章时使用 */
		header.id = aid;        
	} else {
		header.id = articleid; /*新主题*/
            /* 新主题 "Re: " -> "re: " */
            /*if (strncmp(header.title, "Re: ", 4) == 0)
              header.title[0] = 'r'; */
	}

    if(anonymous) {
        snprintf(posthead, sizeof(posthead),
                 "发信人: Diary (我是匿名天使), 信区: %s\n标  题: %s\n发信站: %s (%24.24s)\n\n",
                  board, header.title, BBSNAME, Ctime(time(0)));
    } else {
        snprintf(posthead, sizeof(posthead),
                 "发信人: %s (%s), 信区: %s\n标  题: %s\n发信站: %s (%24.24s)\n\n",
                 uinfo->userid, uinfo->username, board, header.title, BBSNAME, Ctime(time(0)));
    }
    
    int postfoot_len = slen+512;
    postfoot = (char *)emalloc(postfoot_len);
    if(anonymous) {
        snprintf(postfoot, postfoot_len,
                 "\n--\n\033[m\n\033[1;%dm※ 来源:．%s http://%s:874/ [FROM: %.20s]\33[m\n",
                  31 + rand() % 7, BBSNAME, BBSHOST, "匿名天使的家");
    } else {
        	snprintf(postfoot, postfoot_len,
			 "\n--%s\033[m\n\033[1;%dm※ 来源:．%s http://%s:874/ [FROM: %.20s]\33[m\n",
			 signature, 31 + rand() % 7, BBSNAME, BBSHOST, fromaddr);
    }
	
	iov[0].iov_base = posthead;
	iov[0].iov_len = strlen(posthead);
	iov[1].iov_base = content;
	iov[1].iov_len = clen;
	iov[2].iov_base = postfoot;
	iov[2].iov_len = strlen(postfoot);

	ncount = writev(fd, iov, 3);

	close(fd);
    efree(postfoot);

	if (ncount != iov[0].iov_len + iov[1].iov_len + iov[2].iov_len) {
		unlink(filepath);
		RETURN_NULL();
	}
		
	setboardfile(genbuf, board, ".DIR");
	if (append_record(genbuf, &header, sizeof(header)) == -1) {
		unlink(filepath);
		RETURN_NULL();
	}
    
        /* 下面开始处理附件 */
    char attach_prefix[64];
    snprintf(attach_prefix, sizeof(attach_prefix), "attach/%s", board);

      if(has_attach) {

          //snprintf(dir, sizeof(dir), "home/%c/%s/attach", mytoupper(userid[0]), userid);
        if(access(attach_prefix, F_OK) < 0) { 
            if(mkdir(attach_prefix, 0744) < 0) RETURN_NULL();
        } else {
            if(stat(attach_prefix, &st) < 0) RETURN_NULL();
            if( !S_ISDIR(st.st_mode)) {
                unlink(attach_prefix);
                if(mkdir(attach_prefix, 0744) < 0) RETURN_NULL();
            }
        }
        //snprintf(dir, sizeof(dir), "home/%c/%s/attach/.DIR", mytoupper(userid[0]), userid);
        snprintf(dir, sizeof(dir), "%s/.DIR", attach_prefix);
        snprintf(ah.filename, sizeof(ah.filename), "A.%d.A", header.filetime);
        ah.articleid = header.filetime;
        strlcpy(ah.origname, attach_origname, sizeof(ah.origname));
        strlcpy(ah.board, board, sizeof(ah.board));
        strlcpy(ah.desc, attach_type, sizeof(ah.desc));
        if(strchr(attach_type, '/') != NULL) { //类型判断都放在php层处理，默认类型都是合法的
            strlcpy(ah.filetype, strchr(attach_type, '/')+1, sizeof(ah.filetype));
        } else strcpy(ah.filetype, "unknow");

        append_record(dir, &ah, sizeof(struct attacheader));

        /* 把attach_tmpfile 复制过来 */
        //snprintf(path, sizeof(path), "home/%c/%s/attach/%s", mytoupper(userid[0]), userid, ah.filename);
        snprintf(path, sizeof(path), "%s/%s", attach_prefix, ah.filename);
        if((srcfd = open(attach_tmpfile, O_RDONLY, 0644)) <0) RETURN_NULL();
        if((desfd = open(path, O_CREAT | O_WRONLY | O_TRUNC, 0644)) <0) RETURN_NULL();
        
        while((n=read(srcfd, genbuf, sizeof(genbuf))) > 0)
            safewrite(desfd, genbuf, n);
        
        close(srcfd);
        close(desfd);
        unlink(attach_tmpfile);
    }
   
      /* Add to .post. For top ten */
    add_post(board, header.id, userid); 

        /* so that we can mark as read */
	RETURN_STRING(header.filename, 1);
}

PHP_FUNCTION(ext_editpost)
{
	struct fileheader header,x,*hptr;
    struct attacheader ah,oah;
	char genbuf[2048],firstline[128];
	char posthead[512],postattach[512];
	char *postfoot,postedit[512];
    char path[256];
    char dir[80];    
	int i, fd,  ncount, total, n, srcfd, desfd;
    struct stat st;
    
	struct iovec iov[3];
	
	zval *parm;
	char *userid, *board, *title, *content, *fromaddr,*articleid;
    char *attach_tmpfile, *attach_type, *attach_origname, *signature;
	unsigned int ulen, blen, tlen, clen, flen,alen,idx, atmlen, atylen, atolen, slen, anonymous, reply_notify;
    int has_attach;

    if (ZEND_NUM_ARGS() == 1) {

        has_attach = 0; 
        	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "a", &parm) == FAILURE)
		WRONG_PARAM_COUNT;
        
    } else  WRONG_PARAM_COUNT;

    chdir(BBSHOME);
        
	zval_array_get_str(parm, "userid", 7, &userid, &ulen);
	if (!userid) RETURN_NULL();
	zval_array_get_str(parm, "board", 6, &board, &blen);
	if (!board) RETURN_NULL();
	zval_array_get_str(parm, "title", 6, &title, &tlen);
	if (!title) RETURN_NULL();
	zval_array_get_str(parm, "content", 8, &content, &clen);
	if (!content) RETURN_NULL();
	zval_array_get_str(parm, "fromaddr", 9, &fromaddr, &flen);
	if (!fromaddr) RETURN_NULL();
    
	zval_array_get_str(parm, "articleid", 10, &articleid,&alen);
    zval_array_get_str(parm, "signature", 10, &signature,&slen);
    
    zval_array_get_str(parm, "attach_tmpfile", 15, &attach_tmpfile,&atmlen);
    zval_array_get_str(parm, "attach_origname",15 , &attach_origname,&atolen);
    zval_array_get_str(parm, "attach_type",12 , &attach_type,&atylen);
    zval_array_get_long(parm, "anonymous", 10, &anonymous);
    
    zval_array_get_long(parm, "reply_notify", 13, &reply_notify);
    
    if(atmlen >0) has_attach = 1;
    else has_attach = 0;
    
        /* 查找要修改的帖子的id，articleid格式为 M.123456789.A*/
    if(alen>0){
        setboardfile(dir, board, ".DIR");
        if(search_record(dir,&x,sizeof(x),cmpfilename,articleid)==0){
            RETURN_NULL();
        }
    }else RETURN_NULL();
    
    setboardfile(dir, board, ".DIR");
    
    total=get_num_records(dir, sizeof(struct fileheader));
    hptr=(struct header *)emalloc(total*sizeof(struct fileheader));
    
    if((fd=open(dir, O_RDONLY, 0644))<0) RETURN_NULL();
    
    if(read(fd, hptr, total*sizeof(struct fileheader))<=0){
        close(fd);
        efree(hptr);
        RETURN_NULL();
    }
    
    close(fd);
    
    for(idx=total-1;idx>=0;idx--)
        if(strcmp(articleid,hptr[idx].filename)==0) {
            header=hptr[idx];
            break;
        }
    if(idx<0) RETURN_NULL();

    efree(hptr);

    /* 附件 ：  找到该文件的fileheader之后，就可以存储附件了 */    
    if(has_attach) {
        /* 附件放在 BBSHOME/attach/<boardname>/xxx    */
        char prefix[64];
        snprintf(prefix, sizeof(prefix), "attach/%s", board);

        if(access(prefix, F_OK) < 0) { 
            if(mkdir(prefix, 0744) < 0) RETURN_NULL();
        } else {
            if(stat(prefix, &st) < 0) RETURN_NULL();
            if( !S_ISDIR(st.st_mode)) {
                unlink(prefix);
                if(mkdir(prefix, 0744) < 0) RETURN_NULL();
            }
        }
        
        snprintf(dir, sizeof(dir), "%s/.DIR", prefix, userid);
        
        if ((fd=open(dir,O_RDWR | O_CREAT ,0644)) < 0) RETURN_NULL();
        snprintf(ah.filename, sizeof(ah.filename), "A.%d.A", header.filetime);
        ah.articleid = header.filetime;
        strlcpy(ah.origname, attach_origname, sizeof(ah.origname));
        strlcpy(ah.board, board, sizeof(ah.board));
        strlcpy(ah.desc, attach_type, sizeof(ah.desc));
        if(strchr(attach_type, '/') != NULL) {
            strlcpy(ah.filetype, strchr(attach_type, '/')+1, sizeof(ah.filetype));
        } else strcpy(ah.filetype, "unknow");

        if(header.flag & FILE_ATTACHED) { /* 已经有附件则覆盖原来的 */ 
            i = 0 ;
            while(read(fd, &oah, sizeof(struct attacheader))>0) {
                i++;
                if(oah.articleid == ah.articleid) {
                    substitute_record(dir, &ah, sizeof(struct attacheader), i);
                    break;
                }
            }
            if(oah.articleid != ah.articleid) /* 有标志，但是没记录，则自动加上记录 */
                append_record(dir, &ah, sizeof(struct attacheader));
        } else {
            header.flag |= FILE_ATTACHED;        
            append_record(dir, &ah, sizeof(struct attacheader));
        }
        close(fd);
            /* 把attach_tmpfile 复制过来 */
        snprintf(path, sizeof(path), "%s/%s", prefix, ah.filename);
        if((srcfd = open(attach_tmpfile, O_RDONLY, 0644)) <0) RETURN_NULL();
        if((desfd = open(path, O_CREAT | O_WRONLY | O_TRUNC, 0644)) <0) RETURN_NULL();
        
        while((n=read(srcfd, genbuf, sizeof(genbuf))) > 0)
            safewrite(desfd, genbuf, n);
        
        close(srcfd);
        close(desfd);
        unlink(attach_tmpfile);
    }  /* 如果原来有附件，修改时没上传，则保留附件。*/

    
    snprintf(genbuf, sizeof(genbuf), "boards/%s/%s", board, articleid);
        /* 先测试文件是否存在 */
    if((fd = open(genbuf, O_CREAT | O_EXCL | O_WRONLY, 0644) != -1)) {
        RETURN_NULL();
    }

     //发信人: %s (%s), 信区: %s ,由于修改的人和原作者会不相同，这里要ad hoc的处理之，郁闷~
    if((fd = open(genbuf, O_RDONLY, 0644))<0) {
        RETURN_NULL();
    }
    read(fd, firstline, sizeof(firstline));
    for(i=0; i<sizeof(firstline) && firstline[i] !='\n'; i++) ;
    firstline[i] ='\0';
    close(fd);
        
    if((fd = open(genbuf, O_CREAT | O_WRONLY | O_TRUNC , 0644))<0) {
        RETURN_NULL();
    }
    
        //strlcpy(header.owner, userid, sizeof(header.owner));
        //strlcpy(header.realowner, userid, sizeof(header.realowner));
    strlcpy(header.title, title, sizeof(header.title));
        
	snprintf(posthead, sizeof(posthead),
			 "%s\n标  题: %s\n发信站: %s (%24.24s)\n\n",
			firstline , header.title, BBSNAME, Ctime(time(0)));
    int postfoot_len = slen+512;
    postfoot = (char *)emalloc(postfoot_len);
    if(anonymous) {
        snprintf(postfoot, postfoot_len,
                 "\n--\n\033[m\n\033[1;%dm※ 来源:．%s http://%s/ [FROM: %.20s]\33[m\n",
                  31 + rand() % 7, BBSNAME, BBSHOST, "匿名天使的家");
        snprintf(postedit, sizeof(postedit), "\x1b[1;36m※ 修改:．%s 於 %s 修改本文．[FROM: %s] \n", 	"Diary", Ctime(time(0)), "匿名天使的家");    
    } else {
        snprintf(postfoot, postfoot_len,
                 "\n--%s\033[m\n\033[1;%dm※ 来源:．%s http://%s/ [FROM: %.20s]\33[m\n",
                 signature, 31 + rand() % 7, BBSNAME, BBSHOST, fromaddr);
        snprintf(postedit, sizeof(postedit), "\x1b[1;36m※ 修改:．%s 於 %s 修改本文．[FROM: %s] \n", 	userid, Ctime(time(0)), fromaddr);    
    }
    
    
    strcat(postfoot, postedit);
    
	iov[0].iov_base = posthead;
	iov[0].iov_len = strlen(posthead);
	iov[1].iov_base = content;
	iov[1].iov_len = clen;
	iov[2].iov_base = postfoot;
	iov[2].iov_len = strlen(postfoot);

	ncount = writev(fd, iov, 3);

	close(fd);
    efree(postfoot);

	if (ncount != iov[0].iov_len + iov[1].iov_len + iov[2].iov_len ) {
		unlink(genbuf);
		RETURN_NULL();
	}
		
	setboardfile(genbuf, board, ".DIR");
    if(reply_notify) {
        header.flag |= FILE_REPLYNOTIFY;
    } else {
        header.flag &= ~(FILE_REPLYNOTIFY);
    }
        
	if (substitute_record(genbuf, &header, sizeof(header),idx+1) == -1) {
		RETURN_NULL();
	}

    RETURN_STRING(articleid, 1);
}

PHP_FUNCTION(ext_read_post)
{
	char genbuf[256];
    char dir[80];
	struct fileheader x;
	char *article;
	off_t size;
	
	char *board, *filename;
	int board_len, fname_len;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &board, &board_len, &filename, &fname_len) == FAILURE) {
		WRONG_PARAM_COUNT;
	}

	chdir(BBSHOME);
    setboardfile(dir,board,".DIR");
        /* 查看该文件是否在.DIR中 */
	if(search_record(dir,&x,sizeof(x),cmpfilename,filename)==0){
        RETURN_NULL();
    }
	setboardfile(genbuf, board, filename);

	if(mmapfile(genbuf, O_RDONLY, &article, &size, NULL) == 0)
        RETURN_NULL();
	
	php_start_ob_buffer(NULL, 0, 0 TSRMLS_CC);
	html_print_buffer(article, size);
	php_ob_get_buffer(return_value TSRMLS_CC);
	php_end_ob_buffer(0, 0 TSRMLS_CC);
	
	munmapfile(article, size, -1);

}

PHP_FUNCTION(ext_read_digest)
{
	char path[256];
	char *data;
	struct fileheader fh;
	off_t size;
	
	char *board;
	int board_len;
	long start;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sl", &board, &board_len, &start) == FAILURE) {
		WRONG_PARAM_COUNT;
	}

	chdir(BBSHOME);

	setboardfile(path, board, ".DIGEST");
	size = file_size(path);
	if (start > size || start < 1) {
		RETURN_NULL();
	}
	
	get_record(path, &fh, sizeof(fh),  start);

	setboardfile(path, board, fh.filename);
	
	if(mmapfile(path, O_RDONLY, &data, &size, NULL) == 0)
        RETURN_NULL();
	
	php_start_ob_buffer(NULL, 0, 0 TSRMLS_CC);
	html_print_buffer(data, size);
	php_ob_get_buffer(return_value TSRMLS_CC);
	php_end_ob_buffer(0, 0 TSRMLS_CC);
	
	munmapfile(data, size, -1);

}


/* 回复文章时引用，不同于ext_quote_mail，这里只需要正文信息 */
/* 调用时注意判断权限 */
PHP_FUNCTION(ext_quote_post)
{
    struct fileheader header;
	FILE *fp;
	char buf[512];
	char path[256];
	char content[2048];
	int i, contlen = sizeof(content) - strlen(content);
	
	char *board, *filename;
	int blen, flen;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &board, &blen, &filename, &flen) == FAILURE)
		WRONG_PARAM_COUNT;
	chdir(BBSHOME);
    
	setboardfile(path, board, ".DIR");
	if (search_record_forward(path, &header, sizeof(struct fileheader), 1,
							  cmp_filename, filename) == 0) {
		RETURN_NULL();
	}

	setboardfile(path, board, header.filename);

	int lines = 0;
	char *quser, *ptr;
	content[0] = '\0';
	
	fp = fopen(path, "r");

	if (!fp) RETURN_NULL();

	if (fgets(buf, 500, fp) != 0) {
		if ((ptr = strrchr(buf, ')')) != NULL) {
			ptr[1] = '\0';
			if ((ptr = strchr(buf, ':')) != NULL) {
				quser = ptr + 1;
				while (*quser == ' ') quser++;
			}
		}
		snprintf(buf, sizeof(buf), "【 在 %-.55s 的大作中提到: 】\n", quser);
		safe_strcat(content, buf, 0, &contlen);
	}

	for(i = 0; i < 3; i++)
		if(fgets(buf, 500, fp) == 0) break;

	while(1) {
		if (fgets(buf, 500, fp) == NULL) break;
		if (!strncmp(buf, ": 【", 4)) continue;
		if (!strncmp(buf, ": : ", 4)) continue;
		if (!strncmp(buf, "--\n", 3)) break;
		if (buf[0]=='\n') continue;
		safe_strcat(content, ": ", 0, &contlen);
		safe_strcat(content, nohtml(buf), 0, &contlen);
		if (lines++ >= 2) {
			safe_strcat(content, ": (以下引言省略...)\n", 0, &contlen);
			break;
		}
	}
	fclose(fp);

    int cont_size=strlen(content);
	while (content[cont_size-1] == '\n') content[cont_size-1] = '\0',cont_size--;

	RETURN_STRING(content, 1);
}

PHP_FUNCTION(ext_post_content_classify)
{
    struct fileheader x, *fh;
    struct attacheader ah;
    char genbuf[256],dir[256];
    char *board,*filename;
    char userid[256],username[256],boardname[256],title[256],post_time[256],bbsname[256];
    char *signature,fromaddr[256];
    char *article,*s,*s2,*ptr,*content, *endpos;
    zval *arr,*z_signature,*z_content, *zah;
    int i, blen,flen,size,cont_size,fd, slen, low, high, mid, total, ftime;
    
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &board, &blen, &filename, &flen) == FAILURE) {
		WRONG_PARAM_COUNT;
	}

    chdir(BBSHOME);
    
    array_init(return_value);
    
        /* 查看该文件是否在.DIR中 */
    setboardfile(genbuf,board,".DIR");

    if(mmapfile(genbuf, O_RDONLY, &fh, &size, NULL) == 0) {
        RETURN_NULL();
    }
    total = size / sizeof(struct fileheader);
    ftime = atoi(filename+2);
    
    if(ftime == 0) RETURN_NULL();
        //二分查找之
    low = 0;
    high = total-1;
    while(low < high)
    {
        mid = (low + high) / 2;
        if(ftime == fh[mid].filetime) {
            low = high = mid;
            break;
        } else if (ftime < fh[mid].filetime) high = mid;
        else low = mid+1;
    }
    i = low;
    x = fh[i];
    if(low != high ||  strcmp(filename, x.filename) != 0)
    {
        if(search_record(genbuf,&x,sizeof(x),cmpfilename,filename)==0) {
            RETURN_NULL();
        } 
    }
    
    munmapfile(fh, size, -1);
        
    
    setboardfile(genbuf, board, filename);
	if(mmapfile(genbuf, O_RDONLY, &article, &size, NULL)==0) {
        RETURN_NULL();
	}

    endpos = article + size;
        /* 根据每个帖子的格式来把内容分类 */
    s=strchr(article,':');
    if(s==NULL)  RETURN_NULL();
    s+=2;
    for(i=0; s<endpos && *s != ' ' && *s!='\0'  &&   i<IDLEN; s++) userid[i++]=*s;
    userid[i]='\0';
    
    s=strchr(s, '(');
    if(s == NULL) RETURN_NULL();
    s+=1;
    s2=strstr(s, "), 信区: ");
    if(s2==NULL) RETURN_NULL();    
    for(i=0; s<endpos && s<s2 && *s!='\0' &&   i<NAMELEN; s++)
        username[i++]=*s;
    username[i]='\0';
    
    s=strchr(s,':');
    if(s==NULL)    RETURN_NULL();
    s+=2;
    for(i=0; s<endpos && isalpha(*s) &&
            *s!='\0' &&  i<BFNAMELEN; s++)    boardname[i++]=*s;
    boardname[i]='\0';
    while(*s!='\n' && *s!='\0') s++;

    s=strchr(s,':');
    if(s==NULL)    RETURN_NULL();
    s+=2;
    for(i=0; s<endpos && *s !='\n' && *s!='\0' && i<TITLELEN; s++) title[i++]=*s;
    title[i]='\0';

    s=strchr(s,':');
    if(s==NULL)   RETURN_NULL();
    s+=2;
    for(i=0; s<endpos && *s !='(' && *s!='\0' && i<STRLEN; s++) bbsname[i++]=*s;
    bbsname[i]='\0';

    if (*s=='(') s++;
    for(i=0; s<endpos &&  *s!=')' && *s!='\0' && i<STRLEN; s++) post_time[i++]=*s;
    post_time[i]='\0';

    while(s<endpos && *s!='\n' && *s!='\0') s++;  /* 忽略转信，站内信这个标志，以后再议 */

    if(*s == '\0') RETURN_NULL();
    s++;
   
        /* 从后往前扫，避免中间出现其他符号的问题 */
    s2=article+size-1;
    while(*s2 != ':' && s2>article) s2--;

    if(s2 < article) RETURN_NULL();
    if(*s2 != '\0'  && s2+2 < endpos) ptr=s2+2;
    else RETURN_NULL();
        
    for(i=0; ptr < endpos && *ptr != ']' && *ptr!='\0' && i<STRLEN; ptr++) fromaddr[i++]=*ptr;
    fromaddr[i]='\0';

        /* 签名档的开始\n--\n */
    if(s2>article) s2--;
    else RETURN_NULL();
    
    while(! ( *s2=='\n' && *(s2+1)=='-' && *(s2+2)=='-' ) && s2>article) s2--; 

    if (s2 <= article) RETURN_NULL();
    if (s < article || s >= article+size) RETURN_NULL();
    
        /* 内容 */
    cont_size=s2-s+1;    
    if(cont_size < 0 || cont_size > size) RETURN_NULL();    
    content=(char*)emalloc(cont_size+1);
    if(content == NULL) RETURN_NULL();
    memcpy(content,s,cont_size);
    content[cont_size]='\0';
   
        /* 处理签名档 ，及后面的内容：除了最后一行，都归入到签名档中 */
    if(s2 + 3 < article+size) s=s2+3;
    while(*s == '\n' && s<article+size) s++;
    s2 = article + size-1;
    
        /* int newline = 0;
    for(; s2 > s && newline < 2; s2--)
    if(*s2 == '\n') newline++; */
    
    slen = s2 -  s + 1;
    signature = (char*)emalloc(slen+10);
    if(signature == NULL) RETURN_NULL();
    strlcpy(signature, s, slen+1);
    signature[slen] = '\0';
   
        /* 若有附件，记录附件信息 */
    
    MAKE_STD_ZVAL(zah);
    object_init(zah);
    
    if(x.flag & FILE_ATTACHED) {
        //snprintf(dir, sizeof(dir), "home/%c/%s/attach/.DIR", mytoupper(x.owner[0]), x.owner);
        snprintf(dir, sizeof(dir), "attach/%s/.DIR", board);
        if( (fd=open(dir, O_RDONLY)) > 0)  {
            while(read(fd, &ah, sizeof(ah)) > 0) 
                if(ah.articleid == x.filetime) {
                    add_property_string(zah, "filename", ah.filename, 1);
                    add_property_string(zah, "origname", ah.origname, 1);
                    add_property_string(zah, "desc", ah.desc, 1);
                    add_property_string(zah, "filetype", ah.filetype, 1);
                    add_property_long(zah, "articleid", ah.articleid);
                    break;
                }
        }
    }

        /**************************/
    
    MAKE_STD_ZVAL(z_content);
    php_start_ob_buffer(NULL, 0, 0 TSRMLS_CC);
	html_print_buffer(content, cont_size);
	php_ob_get_buffer(z_content TSRMLS_CC);
    php_end_ob_buffer(0, 0 TSRMLS_CC);
    
    MAKE_STD_ZVAL(z_signature);    
    php_start_ob_buffer(NULL, 0, 0 TSRMLS_CC);
	html_print_buffer(signature, strlen(signature));
	php_ob_get_buffer(z_signature TSRMLS_CC);
	php_end_ob_buffer(0, 0 TSRMLS_CC);
    
     
    add_assoc_string(return_value, "userid", userid, 1);
    add_assoc_string(return_value, "username", username, 1);
    add_assoc_string(return_value, "title", title, 1);
    add_assoc_string(return_value, "board", boardname, 1);
    add_assoc_long(return_value, "post_time",x.filetime);
    add_assoc_string(return_value, "bbsname", bbsname, 1);
    add_assoc_string(return_value, "rawcontent", content, 1);
    add_assoc_zval(return_value, "content", z_content);    
    add_assoc_zval(return_value, "signature", z_signature);
    add_assoc_string(return_value, "rawsignature", signature, 1);
    add_assoc_zval(return_value, "ah", zah);
    
    efree(content);
    efree(signature);
    munmapfile(article, size, -1);

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
