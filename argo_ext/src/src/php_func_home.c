#include "php.h"
#include "ext_prototype.h"

/* .lastread */
/* 给zapbuf用 */



/* board.ctl */
/* 检查是否可以进名单版 */
PHP_FUNCTION(ext_is_in_restrict_board)
{
	char path[STRLEN];

	char *userid, *board;
	int ulen, blen;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &userid, &ulen, &board, &blen) == FAILURE)
		WRONG_PARAM_COUNT;

	chdir(BBSHOME);
	snprintf(path, sizeof(path), "home/%c/%s/board.ctl", mytoupper(userid[0]), userid);
	if (seek_in_file(path, board)) {
		RETURN_TRUE;
	}

	RETURN_FALSE;
}


char * brc_getrecord(char *ptr, char *name, int *pnum, int *list)
{
	int num;
	char *tmp;

	strlcpy(name, ptr, BRC_STRLEN);
	ptr += BRC_STRLEN;
	num = (*ptr++) & 0xff;
	tmp = ptr + num * sizeof (int);
	if (num > BRC_MAXNUM) {
		num = BRC_MAXNUM;
	}
	*pnum = num;
	memcpy(list, ptr, num * sizeof (int));
	return tmp;
}

int brc_locate (int num, int *brc_list, int brc_cur, int brc_num)
{
	if (brc_num == 0) {
		brc_cur = 0;
		return 0;
	}
	if (brc_cur >= brc_num)
		brc_cur = brc_num - 1;
	if (num <= brc_list[brc_cur]) {
		while (brc_cur < brc_num) {
			if (num == brc_list[brc_cur])
				return 1;
			if (num > brc_list[brc_cur])
				return 0;
			brc_cur++;
		}
		return 0;
	}
	while (brc_cur > 0) {
		if (num < brc_list[brc_cur - 1])
			return 0;
		brc_cur--;
		if (num == brc_list[brc_cur])
			return 1;
	}
	return 0;
}

/* .boardrc */
/* 参数: 用户名，版块，filename[str]或filetime[int]升序无重复数组 */
/* 返回值: n -1 ~ 0 的索引数组 */
PHP_FUNCTION(ext_is_read)
{
	char brcfile[80];
	char brc_buf[BRC_MAXSIZE], *ptr;
	int brc_cur, brc_size;
	char brc_name[BRC_STRLEN];
	int brc_list[BRC_MAXNUM], brc_num;
	int  fd, filetime, is_read, count, low, high, mid;
	
	zval *arr, **data;
    HashTable *arr_hash;
    HashPosition pointer;
	
	char path[STRLEN];
	char *userid, *board;
	int ulen, blen;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ssa", &userid, &ulen, &board, &blen, &arr) == FAILURE)
		WRONG_PARAM_COUNT;

	chdir(BBSHOME);

        /* brc_initial */
	snprintf(brcfile, sizeof(brcfile), "home/%c/%s/.boardrc", mytoupper(userid[0]), userid);
	if ((fd = open(brcfile, O_RDONLY)) != -1) {
		brc_size = read(fd, brc_buf, sizeof(brc_buf));
		close(fd);
	} else {
		brc_size = 0;
	}
	brc_cur = 0;
	ptr = brc_buf;
	while (ptr < &brc_buf[brc_size] && (*ptr >= ' ' && *ptr <= 'z')) {
		ptr = brc_getrecord(ptr, brc_name, &brc_num, brc_list);
		if (strcmp(brc_name, board) == 0) {
			break;
		}
	}
	if (strcmp(brc_name, board) != 0) {
		brc_num = 1;
		brc_list[0] = 1;
	}
	
	array_init(return_value);
    arr_hash = Z_ARRVAL_P(arr);
	count = zend_hash_num_elements(arr_hash);
        /* 逆序遍历 */
	for (zend_hash_internal_pointer_end_ex(arr_hash, &pointer);
		 zend_hash_get_current_data_ex(arr_hash, (void**) &data, &pointer) == SUCCESS;
		 zend_hash_move_backwards_ex(arr_hash, &pointer)) {
		
        filetime = (Z_TYPE_PP(data) == IS_STRING) ? atoi(Z_STRVAL_PP(data) + 2) : Z_LVAL_PP(data);
		is_read = 0;

            /* 二分查找 brc_list 从大到小 */
        if(filetime < brc_list[brc_num-1]) {
            is_read = 1;
        } else {        
            low = 0; high = brc_num-1;
            while(low < high) {
                mid = (low + high) /2;
                if(brc_list[mid] == filetime) {
                    low = high = mid;                
                } else if(filetime>brc_list[mid]) high = mid-1;
                else low = mid+1;
            }
            if(low == high && filetime == brc_list[low])   is_read = 1;
        }
            /* while (brc_cur < brc_num) {
			if (filetime == brc_list[brc_cur]) {
				is_read = 1;
				break;
			} else if (filetime > brc_list[brc_cur]) {
				break;
			}
			brc_cur++;
            } 
		if (brc_cur >= brc_num && filetime < brc_list[brc_num - 1]) {
			is_read = 1;
            } */
		add_next_index_bool(return_value, is_read);
    }
	
}

/*
 * 某个版面已读标志的记录
 * 返回的是一个数组,如果第三个参数0，返回filetime列表，1 返回index列表
 * @param userid, boardname, type
 * @return array(a1, a2, ...)
 */
PHP_FUNCTION(ext_get_readmark)
{
	char brcfile[80];
	char brc_buf[BRC_MAXSIZE], *ptr;
	char brc_name[BRC_STRLEN];
	int brc_list[BRC_MAXNUM], brc_num, brc_size, brc_cur;
	
	char path[STRLEN];
	char *userid, *board;
	int ulen, blen, i, type, total, fd;
    struct fileheader fh;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ssl", &userid, &ulen, &board, &blen, &type) == FAILURE)
		WRONG_PARAM_COUNT;

	chdir(BBSHOME);

        /* brc_initial */
	snprintf(brcfile, sizeof(brcfile), "home/%c/%s/.boardrc", mytoupper(userid[0]), userid);
	if ((fd = open(brcfile, O_RDONLY)) != -1) {
		brc_size = read(fd, brc_buf, sizeof(brc_buf));
		close(fd);
	} else {
		brc_size = 0;
	}
	brc_cur = 0;
	ptr = brc_buf;
	while (ptr < &brc_buf[brc_size] && (*ptr >= ' ' && *ptr <= 'z')) {
		ptr = brc_getrecord(ptr, brc_name, &brc_num, brc_list);
		if (strcmp(brc_name, board) == 0) {
			break;
		}
	}
	if (strcmp(brc_name, board) != 0) {
		brc_num = 0;
		brc_list[0] = -1;
	}
	
	array_init(return_value);

    if (type == 0) { //返回filetime列表(时间戳)
        for (i = 0; i < brc_num; i++) {
            add_next_index_long(return_value, brc_list[i]);
        }
    } else if (type == 1) { //返回index列表
        setboardfile(path, board, ".DIR");
        total = file_size(path) / sizeof(fh);
        int idx = 0;
        while (total > 0 && idx < brc_num && brc_list[idx] > 1) {
            get_record(path, &fh, sizeof(fh), total);    
            if (fh.filetime == brc_list[idx]) 
                add_next_index_long(return_value, total);
            if (fh.filetime <= brc_list[idx])  {
                idx++;
            } else {
                total--;
            }
        }
    }
	
}

/* 清除单篇文章的未读标记 */
/* 相当于telnet的brc_initial, brc_addlist, brc_update */
PHP_FUNCTION(ext_mark_read)
{
	char brcfile[80];
	char brc_buf[BRC_MAXSIZE], *ptr;
	int brc_cur, brc_size;
	char brc_name[BRC_STRLEN];
	int brc_list[BRC_MAXNUM], brc_num;
	int tmp_size;
	char tmp_buf[BRC_STRLEN + BRC_MAXNUM * sizeof(int) + sizeof(*ptr)], tmp_name[BRC_STRLEN];
	int tmp_list[BRC_MAXNUM], tmp_num;
	int fd, filetime;

	char *userid, *board, *filename;
	int ulen, blen, flen;
	

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sss", &userid, &ulen, &board, &blen, &filename, &flen) == FAILURE)
		WRONG_PARAM_COUNT;

	chdir(BBSHOME);

        /* brc_initial, same as ext_is_read */
	snprintf(brcfile, sizeof(brcfile), "home/%c/%s/.boardrc", mytoupper(userid[0]), userid);
	if ((fd = open(brcfile, O_RDONLY)) != -1) {
		brc_size = read(fd, brc_buf, sizeof(brc_buf));
		close(fd);
	} else {
		brc_size = 0;
	}
	brc_cur = 0;
	ptr = brc_buf;
	while (ptr < &brc_buf[brc_size] && (*ptr >= ' ' && *ptr <= 'z')) {
		ptr = brc_getrecord(ptr, brc_name, &brc_num, brc_list);
		if (strcmp(brc_name, board) == 0) {
			break;
		}
	}
	if (strcmp(brc_name, board) != 0) {
		brc_num = 1;
		brc_list[0] = 1;
	}

        /* find the position */
	filetime = atoi(filename + 2);
	while (brc_cur < brc_num) {
		if (filetime == brc_list[brc_cur]) {
			RETURN_TRUE;
		} else if (filetime > brc_list[brc_cur]) {
			break;
		}
		brc_cur++;
	}

	if (brc_cur == brc_num) {
		RETURN_TRUE;
	}

        /* do insertion */
	if (brc_num < BRC_MAXNUM)
		brc_num++;
	memmove(&brc_list[brc_cur + 1], &brc_list[brc_cur],
            sizeof (brc_list[0]) * (brc_num - brc_cur - 1));
	brc_list[brc_cur] = filetime;

        /* write back to file */
        /* reusing brc_buf */
	ptr = brc_buf;
	while (ptr < &brc_buf[brc_size] && (*ptr >= ' ' && *ptr <= 'z')) {
		ptr = brc_getrecord(ptr, tmp_name, &tmp_num, tmp_list);
		if (strcmp(tmp_name, board) == 0) { /* remove current board record */
			tmp_size = tmp_num * sizeof(int) + BRC_STRLEN + sizeof(*ptr);
			memmove(ptr - tmp_size, ptr, brc_size - (ptr - brc_buf));
			brc_size -= tmp_size;
		}
	}
	ptr = tmp_buf;
	strlcpy(ptr, board, BRC_STRLEN);
	ptr += BRC_STRLEN;
	*ptr++ = brc_num;
	memcpy(ptr, brc_list, brc_num * sizeof(int));
	ptr += brc_num * sizeof(int);
	fd = open(brcfile, O_WRONLY | O_CREAT, 0644);
	if (fd == -1) RETURN_FALSE;
	ftruncate(fd, 0);
	write(fd, tmp_buf, ptr - tmp_buf);
	write(fd, brc_buf, brc_size);
	close(fd);
	RETURN_TRUE;
	
}

/*  .goodbrd */
PHP_FUNCTION(ext_delfavboards)
{
    char *userid, *delboard;
    char gbrdfile[80], *bname;
    int ulen,id,total,i, blen;
    FILE *fp;
    char boards[MAXBOARD][BFNAMELEN];
    
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &userid, &ulen,&delboard, &blen) == FAILURE)
		WRONG_PARAM_COUNT;

    chdir(BBSHOME);
    
    snprintf(gbrdfile, sizeof(gbrdfile), "home/%c/%s/.goodbrd", mytoupper(userid[0]), userid);

    if ((fp=fopen(gbrdfile, "r")) ==NULL ) RETURN_FALSE;
    total = 0;
    while(fscanf(fp, "%s", boards[total]) != EOF) {
        if(strcmp(boards[total], delboard) == 0) total --;
        total++;
    }    
    fclose(fp);

    if ((fp=fopen(gbrdfile, "w")) ==NULL ) RETURN_FALSE;
    for(i=0; i<total; i++)
    {
        fprintf(fp, "%s\n", boards[i]);
    }
    fclose(fp);
    
    RETURN_TRUE;
}
PHP_FUNCTION(ext_addfavboards)
{
    char *userid, *delboard;
    char gbrdfile[80], *bname;
    int ulen,id,total,i, blen;
    FILE *fp;
    char boards[MAXBOARD][BFNAMELEN];
    
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &userid, &ulen,&delboard, &blen) == FAILURE)
		WRONG_PARAM_COUNT;

    chdir(BBSHOME);
    
    snprintf(gbrdfile, sizeof(gbrdfile), "home/%c/%s/.goodbrd", mytoupper(userid[0]), userid);

	total = 0;

    if ((fp=fopen(gbrdfile, "r")) != NULL ) { 
		while(fscanf(fp, "%s", boards[total]) != EOF) {
			if(strcmp(boards[total], delboard) == 0) {
				fclose(fp);
				RETURN_TRUE;
			}
			total++;
		}    
		fclose(fp);
	}

    strcpy(boards[total++], delboard);
    
    if ((fp=fopen(gbrdfile, "w")) ==NULL ) RETURN_FALSE;
    for(i=0; i<total; i++)
    {
        fprintf(fp, "%s\n", boards[i]);
    }
    fclose(fp);
    
    RETURN_TRUE;
}

/* get plan or signature . will get/set the whole file */
/* type =0 , return raw text , type =1 return html */
PHP_FUNCTION(ext_get_whole_file)
{
    char path[80];
    int ulen,clen,fd,flen, mode,type;
    char *cont, *userid,*fname;    
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ssl", &userid, &ulen,&fname,&flen,&type) == FAILURE)
		WRONG_PARAM_COUNT;
    
    chdir(BBSHOME);

    snprintf(path, sizeof(path), "home/%c/%s/%s", mytoupper(userid[0]), userid, fname);
    
    if((fd=open(path, O_CREAT | O_EXCL, 0644)) != -1 ) RETURN_NULL();    /* file not exist */
    close(fd);

    clen=0;
    mmapfile(path, O_RDONLY, &cont, &clen, NULL);
    if(clen == 0) RETURN_NULL();

    if(type == 0) {
        ZVAL_STRINGL(return_value, cont, clen, 1);
    } else {
        php_start_ob_buffer(NULL, 0, 0 TSRMLS_CC);
        html_print_buffer(cont, clen);
        php_ob_get_buffer(return_value TSRMLS_CC);
        php_end_ob_buffer(0, 0 TSRMLS_CC);
    }
    munmapfile(cont, clen, -1);
}

PHP_FUNCTION(ext_set_whole_file)
{
    char path[80];
    int ulen,clen,fd,flen;
    char *cont, *userid, *fname;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sss", &userid, &ulen,&fname,&flen,&cont,&clen) == FAILURE)
		WRONG_PARAM_COUNT;
    
    chdir(BBSHOME);

    snprintf(path, sizeof(path), "home/%c/%s/%s", mytoupper(userid[0]), userid,fname);
    
    if((fd=open(path, O_CREAT | O_WRONLY | O_TRUNC, 0644)) < 0 )  RETURN_FALSE;

    if(write(fd, cont, clen) < 0) RETURN_FALSE;

    close(fd);
    
    RETURN_TRUE;
}

PHP_FUNCTION(ext_get_www)
{
    char *userid;
    int ulen,i;
    char buf1[256],buf2[256];
    char path[256];
    FILE *fp;
    
     if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &userid, &ulen) == FAILURE)
         WRONG_PARAM_COUNT;
     
     array_init(return_value);
     snprintf(path, sizeof(path), "home/%c/%s/.mywww", mytoupper(userid[0]), userid);
     
     if((fp=fopen(path, "r")) ==NULL) RETURN_NULL();
     while(fscanf(fp, "%s %s", buf1,buf2) != EOF)
     {
         add_assoc_string(return_value, buf1, buf2, 1);
     }
     fclose(fp);
}
/* 把参数写入.mywww文件，所改参数有则更新，无则不变 */
PHP_FUNCTION(ext_set_www)
{
    char *userid,*string_key,*sptr;
    int ulen,i,len_key,num_index;
    char buf1[256],buf2[256],path[256];    
    FILE *fp;
    zval *sarr,*arr,**data;
    HashTable *harr;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sa", &userid, &ulen,&arr) == FAILURE)
         WRONG_PARAM_COUNT;

    chdir(BBSHOME);

     snprintf(path, sizeof(path), "home/%c/%s/.mywww", mytoupper(userid[0]), userid);

     MAKE_STD_ZVAL(sarr);
     array_init(sarr);

     
     if((fp=fopen(path, "r")) !=NULL)
     {
         while(fscanf(fp, "%s %s", buf1,buf2) != EOF)
         {
             add_assoc_string(sarr, buf1, buf2, 1);
         }
         fclose(fp);
     }

     harr = Z_ARRVAL_P(arr);

     zend_hash_internal_pointer_reset(harr);

     while(zend_hash_get_current_data(harr, &data) == SUCCESS) {

         if(zend_hash_get_current_key_ex(harr,&string_key, &len_key,&num_index,0, 0)
            !=HASH_KEY_IS_STRING) {
             RETURN_FALSE;             
         }
         add_assoc_string(sarr, string_key, Z_STRVAL_PP(data), 1);
         
         zend_hash_move_forward(harr);
     }

     /* 把设置写回原文件 */

     if ((fp=fopen(path,"w")) == NULL) RETURN_NULL();

     harr = Z_ARRVAL_P(sarr);
     zend_hash_internal_pointer_reset(harr);
     
     while(zend_hash_get_current_data(harr, &data) == SUCCESS) { /* */

          if(zend_hash_get_current_key_ex(harr,&string_key, &len_key, &num_index, 0, 0)
            !=HASH_KEY_IS_STRING) {
             RETURN_FALSE;             
         }
          fprintf(fp,"%s %s\n", string_key, Z_STRVAL_PP(data));
         
         zend_hash_move_forward(harr);
     }
     fclose(fp);
     RETURN_TRUE;
}
/* 获取签名档，返回以行为单位的数组 */
PHP_FUNCTION(ext_get_signatures)
{
    char *userid;
    int ulen, fd;
    char dir[80];
    char line[2048];
    FILE *fp;
     if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &userid, &ulen) == FAILURE)
         WRONG_PARAM_COUNT;

     snprintf(dir, sizeof(dir), "home/%c/%s/signatures", mytoupper(userid[0]), userid);

     array_init(return_value);
     if((fp = fopen(dir, "r")) == NULL) return;

     while(fgets( line, sizeof(line), fp) != NULL) {
         line[strlen(line)-1] = 0;
         add_next_index_string(return_value, line, 1);
     }     
}
PHP_FUNCTION(ext_add_override)
{
    struct override x;
    char *myid,*friendid,*exp,*ofile; /* ofile : override file (friend or rejects) */
    int cmd;
    int mlen,flen,elen,olen,i,fd,total;
    char path[80];

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ssss", &myid, &mlen,&friendid,&flen,&exp,&elen,&ofile,&olen) == FAILURE)
            WRONG_PARAM_COUNT;

    chdir(BBSHOME);
    if(flen<=0) RETURN_LONG(2); /* 不存在该用户 */
    
    resolve_ucache();
    snprintf(path, sizeof(path), "home/%c/%s/%s", mytoupper(myid[0]), myid, ofile);

    if((fd=open(path, O_CREAT | O_RDWR, 0644))<0) RETURN_LONG(4);  /* 其他错误 */

    if(flen>IDLEN) flen=IDLEN;
    
    for(i=0; i<sessionVar()->uidshm->number; i++)
        if(strcasecmp(friendid, sessionVar()->uidshm->userid[i]) ==0 ) {
            strcpy(friendid, sessionVar()->uidshm->userid[i]);
            break;
        }
    if (i >= sessionVar()->uidshm->number) {
        close(fd);
        RETURN_LONG(2);  /* 不存在该用户 */
    }

    total = get_num_records(path, sizeof(x));
    if(total>=MAXFRIENDS) {
        close(fd);
        RETURN_LONG(3);  /* 超过上限 */
    }

    for(i=0; i<total; i++)
    {
        if(read(fd, &x, sizeof(x))<0)  {
            close(fd);
            RETURN_LONG(4);
        }
        if(strcmp(x.id, friendid ) == 0) {
            close(fd);
            RETURN_LONG(1); /* 用户已在名单中 */
        }
    }

    lseek(fd, 0, SEEK_END);

    if(elen > 40) elen = 40;
    memset(&x, 0, sizeof(x));
    strncpy(x.id, friendid, flen);
    strncpy(x.exp, exp, elen);
    
    if(write(fd, &x, sizeof(x)) <0) {
        RETURN_LONG(4);
        close(fd);
    }
    
    close(fd);
    RETURN_LONG(0);    
}
PHP_FUNCTION(ext_del_override)
{
    struct override x;
    char *myid,*friendid,*ofile; /* ofile : override file (friend or rejects) */
    int cmd;
    int mlen,flen,elen,olen,i,fd,total;
    char path[80];

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sss", &myid, &mlen,&friendid,&flen,&ofile,&olen) == FAILURE)
            WRONG_PARAM_COUNT;

    chdir(BBSHOME);
    if(flen<=0) RETURN_LONG(1); /* 不存在该用户 */
    
    resolve_ucache();
    snprintf(path, sizeof(path), "home/%c/%s/%s", mytoupper(myid[0]), myid, ofile);

    if((fd=open(path, O_CREAT | O_RDWR, 0644))<0) RETURN_LONG(4);  /* 其他错误 */

    if(flen>IDLEN) flen=IDLEN;    

    total = get_num_records(path, sizeof(x));
    if(total == 0) {
        close(fd);
        RETURN_LONG(2);  /* 列表为空 */
    }

    for(i=0; i<total; i++)
    {
        if(read(fd, &x, sizeof(x))<0)  {
            close(fd);
            RETURN_LONG(4);
        }
        if(strcmp(x.id, friendid) == 0) {            
            close(fd);
            delete_record(path, sizeof(x), i+1);
            RETURN_LONG(0);
        }
    }
    close(fd);
    RETURN_LONG(1);
}

/* 获取用户的friends 和 rejects */
PHP_FUNCTION(ext_get_override)
{
    char *myid,*ofile;
    int mlen,olen,fd,i,index,total;
    char path[80];
    zval *over;
    struct override x;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &myid, &mlen,&ofile,&olen) == FAILURE)
        WRONG_PARAM_COUNT;

    if(strcmp(ofile, "friends") !=0 && strcmp(ofile, "rejects") !=0 ) RETURN_NULL();

    snprintf(path, sizeof(path), "home/%c/%s/%s", mytoupper(myid[0]), myid, ofile);

    array_init(return_value);

    if((fd=open(path, O_RDONLY))<0) RETURN_NULL();

    total=get_num_records(path, sizeof(struct override));

    index=0;
    for(i=0; i<total; i++)
    {
        if(read(fd, &x, sizeof(struct override))<0) continue;
        MAKE_STD_ZVAL(over);
        object_init(over);
        add_property_string(over, "id", x.id, 1);
        add_property_string(over, "exp", x.exp, 1);

        add_index_zval(return_value, index, over);
        index++;
    }
    
    close(fd);
}

//board, filename
PHP_FUNCTION(ext_get_attacheader)
{
    struct attacheader ah;
    struct user_info *uinfo;
    char  *board, *filename;
    int blen,flen, i;
    int fd;
    char dir[256],attach_linkpath[256];
    
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &board, &blen, &filename, &flen) == FAILURE)
		WRONG_PARAM_COUNT;
    
    chdir(BBSHOME);

        /* check if userid exist */
    //resolve_cache();    
    //for(i=0; i<sessionVar()->uidshm->number; i++)
    //    if(!strncmp(sessionVar()->uidshm->userid[i], userid, ulen)) {
    //        break;
    //    }
    //if( i >= sessionVar()->uidshm->number) RETURN_NULL();
    
    //snprintf(dir, sizeof(dir), "home/%c/%s/attach/.DIR",
    //        mytoupper(userid[0]), userid);

    snprintf(dir, sizeof(dir), "attach/%s/.DIR", board);

    if((fd=open(dir, O_RDONLY, 0644)) <0) RETURN_NULL();

    int fileid=atoi(filename+2);

    object_init(return_value);

    int attach_found = 0;
    while(read(fd, &ah, sizeof(ah)) >0)
    {
        if(strncmp(ah.filename, filename, flen) == 0) {
            add_property_string(return_value, "filename", ah.filename, 1);
            add_property_string(return_value, "board", ah.board, 1);
            add_property_string(return_value, "filetype", ah.filetype, 1);
            add_property_string(return_value, "origname", ah.origname, 1);
            add_property_string(return_value, "desc", ah.desc, 1);
            add_property_long(return_value, "articleid", ah.articleid);
            attach_found = 1;
            break;
        }
    }
    close(fd);
    if(attach_found == 0) RETURN_NULL();
}

/* start = 0 为从最尾开始，num=-1 表示取全部*/
PHP_FUNCTION(ext_get_attachlist)
{
    struct attacheader ah;
    char  *userid, *filename;
    int ulen,flen, i, start, num, total, filesize;
    int fd;
    char dir[256],path[256],attach_linkpath[256];
    zval *zah, *zarr;
    struct stat st;
    
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sll", &userid, &ulen, &start, &num) == FAILURE)
		WRONG_PARAM_COUNT;
    
    chdir(BBSHOME);
    
    snprintf(dir, sizeof(dir), "home/%c/%s/attach/.DIR",
             mytoupper(userid[0]), userid);
    
    
    total = get_num_records(dir, sizeof(struct attacheader));    
    if(total <=0) RETURN_NULL();
    
    if(num <0) num = total;
    
    if(start == 0 || start > total ) start = total - num+1;
    if(start <=0) start = 1;

    MAKE_STD_ZVAL(zarr);
    array_init(zarr);
    
    if((fd=open(dir, O_RDONLY, 0644)) <0) RETURN_NULL();
    lseek(fd, (start-1)*sizeof(struct attacheader), SEEK_SET);
        
    for(i=0; i<num; i++)
    {
        if(read(fd, &ah, sizeof(ah)) <=0)  break;
        MAKE_STD_ZVAL(zah);
        object_init(zah);
        
        add_property_long(zah, "index", start);
        add_property_string(zah, "filename", ah.filename, 1);
        add_property_string(zah, "board", ah.board, 1);
        add_property_string(zah, "filetype", ah.filetype, 1);
        add_property_string(zah, "origname", ah.origname, 1);
        add_property_long(zah, "articleid", ah.articleid);

        snprintf(attach_linkpath, sizeof(attach_linkpath),
                 "/attach/%s/%s",  userid, ah.filename);
        add_property_string(zah, "attach_linkpath", attach_linkpath, 1);
        
            /* 看是否真有这个文件 */
        snprintf(path, sizeof(path), "home/%c/%s/attach/%s",
                 mytoupper(userid[0]), userid, ah.filename);
        if(stat(path,&st) < 0)  filesize = -1;
        else filesize = st.st_size;
        add_property_long(zah, "filesize", filesize);
        
        add_next_index_zval(zarr, zah);
        start++;    
    }
    close(fd);
    object_init(return_value);
    add_property_long(return_value, "total" , total);
    add_property_zval(return_value, "list", zarr);
    
}
/* 下标由1开始  , 数组内存的是字符数字 */
PHP_FUNCTION(ext_del_attach)
{
    struct attacheader *ahlist;
    char *userid;
    int ulen,index_count,i,cnt,fd,total;
    zval *arr,**data;
    long *index_arr;
    HashTable *harr;
    char dir[256],path[256];    
    
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sa", &userid, &ulen,&arr) == FAILURE)
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

        if(Z_STRVAL_PP(data) != NULL)
        index_arr[n++] = atoi(Z_STRVAL_PP(data));
    }
    index_count = n;
    
    snprintf(dir, sizeof(dir), "home/%c/%s/attach/.DIR", mytoupper(userid[0]), userid);
    total = get_num_records(dir, sizeof(struct attacheader));
    if(total <= 0) {
        efree(index_arr);
        RETURN_FALSE;
    }

    if((fd = open(dir, O_RDWR, 0644)) < 0) {
        efree(index_arr);
        RETURN_FALSE;
    }

    ahlist = (struct attacheader *)mmap(NULL, total*sizeof(struct attacheader),
                                        PROT_READ | PROT_WRITE, MAP_SHARED, fd, 0);
    if(ahlist == MAP_FAILED) {
        close(fd);
        efree(index_arr);
        RETURN_FALSE;
    }
    
    for(i=0; i<index_count; i++)
    {
        index_arr[i] --;
        if(index_arr[i] < 0 || index_arr[i] >= total) continue;
        snprintf(path, sizeof(path), "home/%c/%s/attach/%s",
                 mytoupper(userid[0]), userid, ahlist[index_arr[i]].filename);
        f_rm(path);
        ahlist[index_arr[i]].filename[0] = '\0';
    }    
    efree(index_arr);

    cnt = 0;
    for(i=0; i<total; i++)
    {
        if(ahlist[i].filename[0] != '\0') {
            ahlist[cnt++] = ahlist[i];
        }
    }
    munmap((void *)ahlist, total*sizeof(struct attacheader));
    ftruncate(fd, cnt*sizeof(struct attacheader));
    close(fd);
    RETURN_TRUE;
}

PHP_FUNCTION(ext_upload_attach)
{
    struct attacheader ah;
    char *userid, *attach_tmpfile, *attach_origname, *attach_type;
    char *appoint_name;
    int ulen, atmlen, atolen, atflen, aplen, fd, srcfd, desfd, n;
    char dir[256], path[256], genbuf[2048];
    struct stat st;
    
    if(ZEND_NUM_ARGS() == 4) {
            /* 默认情况 A.12345678.A */
        if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ssss", &userid, &ulen,&attach_tmpfile, &atmlen, &attach_origname, &atolen, &attach_type, &atflen) == FAILURE)
            WRONG_PARAM_COUNT;
        appoint_name = NULL;
        aplen = 0; 
    } else {
            /* 指定文件 appoint_name */
        if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sssss", &userid, &ulen,&attach_tmpfile, &atmlen, &attach_origname, &atolen, &attach_type, &atflen, &appoint_name, &aplen) == FAILURE)
            WRONG_PARAM_COUNT;
    }

    chdir(BBSHOME);

    snprintf(dir, sizeof(dir), "home/%c/%s/attach", mytoupper(userid[0]), userid);
    if(access(dir, F_OK) < 0) {
        if(mkdir(dir, 0744) < 0) RETURN_NULL();
    } else {
        if(stat(dir, &st) < 0) RETURN_NULL();
        if( !S_ISDIR(st.st_mode)) {
            unlink(dir);
            if(mkdir(dir, 0744) <0 ) RETURN_NULL();
        }
    }

    snprintf(dir, sizeof(dir), "home/%c/%s/attach/.DIR", mytoupper(userid[0]), userid);

    memset(&ah, 0, sizeof(ah));
    ah.articleid = 0; /* 私人附件,articleid为0 */
    
        /* 对指定文件名(如头像文件)特殊处理 */
    if(appoint_name) {
        snprintf(ah.filename, sizeof(ah.filename), "%s", appoint_name);
        ah.articleid = time(0); /* 头像文件为公开附件 */
        strlcpy(ah.origname, appoint_name, aplen+1);
    } else {
        snprintf(ah.filename, sizeof(ah.filename), "A.%d.A", time(0));
        strlcpy(ah.origname, attach_origname, sizeof(ah.origname));
    }
    
    if(strchr(attach_type, '/') !=NULL) {
        strlcpy(ah.filetype, strchr(attach_type, '/')+1, sizeof(ah.filetype));
    } else strcpy(ah.filetype, "unknow");

    if(append_record(dir, &ah, sizeof(ah)) < 0) RETURN_NULL();
    
    snprintf(path, sizeof(path), "home/%c/%s/attach/%s",
             mytoupper(userid[0]), userid, ah.filename);

    if((srcfd = open(attach_tmpfile, O_RDONLY, 0644)) <0) RETURN_NULL();
    if((desfd = open(path, O_CREAT | O_WRONLY | O_TRUNC, 0644)) <0) RETURN_NULL();

    while((n=read(srcfd, genbuf, sizeof(genbuf))) > 0)
            safewrite(desfd, genbuf, n);
        
    close(srcfd);
    close(desfd);
    unlink(attach_tmpfile);
    RETURN_STRING(ah.filename, 1);
}

/* s_user 在 board 版 filename 上 @到 d_user
   暂时规定@最多只保存最近的100+个，当达到150+个时，
   自动删除，只剩下最新的100个。
 */
PHP_FUNCTION(ext_add_msg)
{
    char *s_user, *d_user, *board, *filename, *type;    
    int slen, dlen, blen, flen, total, ncut, tlen;
    struct msgheader mh;
    char dir[80];
    
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sssss", &s_user, &slen, &d_user, &dlen, &board, &blen, &filename, &flen, &type, &tlen) == FAILURE)
        WRONG_PARAM_COUNT;

    chdir(BBSHOME);
    snprintf(dir, sizeof(dir), "home/%c/%s/.MSG", mytoupper(d_user[0]), d_user);

        //如果太多了，自动清理
    total = get_num_records(dir, sizeof(struct msgheader));
    if(total > 150) {
        ncut = total - 100;
        while(ncut--) {
            delete_record(dir, sizeof(struct msgheader), 1);
        }
    } 
    
    memset(&mh, 0, sizeof(mh));
    
    mh.when = time(0);
    strlcpy(mh.userid, s_user, sizeof(mh.userid));
    strlcpy(mh.board, board, sizeof(mh.board));
    strlcpy(mh.filename, filename, sizeof(mh.filename));
    strlcpy(mh.type, type, sizeof(mh.type));/* a: @ ; r: reply ; f: friend*/
    mh.flag = 0; // unread
    
    if(append_record(dir, &mh, sizeof(mh)) < 0) RETURN_NULL();
    RETURN_TRUE;
}


PHP_FUNCTION(ext_get_msglist)
{
    #define MAXLIST 100
	zval *l[MAXLIST];
	zval *arr;
	struct msgheader x;
	char dir[80];
	int fd, total, len, i;
	
	char *userid;
	int ulen;
	long start;					/* first entry to show */
	long t_lines;				/* less then MAXLIST */

	chdir(BBSHOME);
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sll", &userid, &ulen, &start, &t_lines) == FAILURE)
		WRONG_PARAM_COUNT;
    
	if (t_lines > MAXLIST) t_lines = MAXLIST;

    snprintf(dir, sizeof(dir), "home/%c/%s/.MSG", mytoupper(userid[0]), userid);
	
	if ((fd = open(dir, O_RDONLY)) == -1)
		RETURN_NULL();

	total = get_num_records(dir, sizeof(struct msgheader));
	if (total == 0) {
		close(fd);
		RETURN_NULL();
	}

    if(t_lines > MAXLIST) t_lines = MAXLIST;
	if (start == 0 || start > total)
		start = total - t_lines + 1;

	if (start <= 0) start = 1;
		
	lseek(fd, (start - 1) * sizeof(struct msgheader), SEEK_SET);
	for (i = 0; i < t_lines; i++) {
		if (read(fd, &x, sizeof(x)) <= 0) break;
		
		MAKE_STD_ZVAL(l[i]);
		object_init(l[i]);
		add_property_long(l[i], "index", start + i);
		add_property_long(l[i], "flag", x.flag);
        add_property_long(l[i], "when", x.when);
        add_property_string(l[i], "userid", x.userid, 1);
		add_property_string(l[i], "board", x.board, 1);
		add_property_string(l[i], "filename", x.filename, 1);
        add_property_string(l[i], "type", x.type, 1);
	}

	close(fd);
	
	len = i;

	MAKE_STD_ZVAL(arr);
	array_init(arr);
	for (i = 0; i < len; i++)
		add_index_zval(arr, i, l[i]);
	
	object_init(return_value);
	add_property_long(return_value, "total", total);
	add_property_zval(return_value, "list", arr);
}

PHP_FUNCTION(ext_message_markread)
{
    char *userid;
    int ulen, index, total;
    struct msgheader mh;
    char dir[80];
    
    chdir(BBSHOME);
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sl", &userid, &ulen, &index) == FAILURE)
		WRONG_PARAM_COUNT;

    snprintf(dir, sizeof(dir), "home/%c/%s/.MSG", mytoupper(userid[0]), userid);
    
    total = get_num_records(dir, sizeof(struct msgheader));

    if(index<=0 || index >total) RETURN_NULL();

    if(get_record(dir, &mh, sizeof(mh), index) < 0) RETURN_NULL();
    mh.flag |= FILE_READ;

    if(substitute_record(dir, &mh, sizeof(mh), index) < 0) RETURN_NULL();
    RETURN_TRUE;
}
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 p */
