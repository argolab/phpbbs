#include "ext_prototype.h"

/* 更新bcache中的信息 */
PHP_FUNCTION(ext_update_lastpost) {
	char path[128];
	struct fileheader fh;	
	struct boardheader *bptr;
	int size;
	
	char *board;
	int *blen;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &board, &blen) == FAILURE)
		WRONG_PARAM_COUNT;

	if ((bptr = getbcache(board)) == NULL)
		RETURN_FALSE;

	setboardfile(path, bptr->filename, ".DIR");

	size = file_size(path);
	bptr->total = size / sizeof(fh);

	get_record(path, &fh, sizeof(fh),  bptr->total);
	bptr->lastpost = fh.filetime;

	RETURN_TRUE;
	
}

/* 获取讨论区文章数 */
/* board: 讨论区名称 */
/* type: 类型, 0为普通模式，1为g文模式 */
PHP_FUNCTION(ext_brctotalpost)
{
	struct boardheader *x1;
	char dir[80];
	int fd, total;

	char *board;
	int blen;					/* length of board name */
	long type;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sl", &board, &blen, &type) == FAILURE)
		WRONG_PARAM_COUNT;

	if (type == 1) {
		chdir(BBSHOME);
		setboardfile(dir, board, ".DIGEST");
		if ((fd = open(dir, O_RDONLY)) == -1)
			RETURN_NULL();
		total = get_num_records(dir, sizeof(struct fileheader));
		close(fd);
	} else {
		x1 = getbcache(board);
		total = x1->total;
	}
	
	RETURN_LONG(total);	
}


int cmpfilename(void *filename, void *fh) {
	return (!strcmp(((struct fileheader *)fh)->filename, (char *)filename));
}

int cmparticleid(void *id, void *fh) {
	return (((struct fileheader *)fh)->id == *(int *)id);
}

/* 获取同主题的所有文件名,作者，返回一个obj的数组 */
PHP_FUNCTION(ext_gettopicfiles)
{
	zval *z,*obj;
	struct fileheader x,*hptr;
	struct boardheader *x1;
	char dir[80];
    int *arr,narr;
	int id, num, total, i, fd;
	
	char *board;
	int blen;					/* length of board name */
	char *file;					/* first entry to show */
	int flen;				/* less then MAXLIST */

	chdir(BBSHOME);
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &board, &blen, &file, &flen) == FAILURE)
		WRONG_PARAM_COUNT;

	setboardfile(dir, board, ".DIR");
    
    total = get_num_records(dir, sizeof(x));
    hptr=(struct fileheader *)emalloc(total*sizeof(struct fileheader));

    if((fd = open(dir, O_RDONLY, 0644))<0){
        efree(hptr);
        RETURN_NULL();
    }

    if( read(fd, hptr, total*sizeof(struct fileheader)) <0){
        efree(hptr);
        close(fd);
    }
    
    close(fd);
    
	array_init(return_value);
    
    id = -1;
    for(i=total-1; i>=0; i--)
        if(strncmp(hptr[i].filename, file, flen)==0) {
            id=hptr[i].id;
            break;
        }
    if(id == -1){
        efree(hptr);
        RETURN_NULL();
    }

    arr=(int *)emalloc(total*sizeof(int));
    narr=0;
    for(i=total-1; i>=0; i--){
        if(hptr[i].id == id){
            arr[narr++]=i;
        }
        if(hptr[i].filetime <= id) break; /* 比第一篇时间还早，所以没有必要继续找下去 */
    }
    for(i=narr-1; i>=0; i--) {
        MAKE_STD_ZVAL(obj);
        object_init(obj);
        add_property_string(obj, "filename", hptr[arr[i]].filename, 1);
        add_property_string(obj, "userid", hptr[arr[i]].owner, 1);
        add_index_zval(return_value, i, obj);
            // add_index_string(return_value, i, hptr[arr[i]].filename, 1);
    }
    efree(hptr);
    efree(arr);
}

/* 获取讨论区文章header信息 */
/* board: 讨论区名称 */
/* start: 从第start篇开始 */
/* t_lines: 获取的数量 */
/* type: 类型, 0为普通模式，1为g文模式 */
/* 返回 total 以及 列表数组 */
PHP_FUNCTION(ext_getpostlist)
{
#define MAXLIST 100
	zval *l[MAXLIST];
	zval *arr;
	struct fileheader x;
	struct boardheader *x1;
	char dir[80];
	int fd, total, len, i;
	
	char *board;
	int blen;					/* length of board name */
	long start;					/* first entry to show */
	long t_lines;				/* less then MAXLIST */
	long type;

	chdir(BBSHOME);
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "slll", &board, &blen, &start, &t_lines, &type) == FAILURE)
		WRONG_PARAM_COUNT;
	if (t_lines > MAXLIST) t_lines = MAXLIST;
	x1 = getbcache(board);

	if (x1 == 0) RETURN_NULL();

	if (type == 1) {
		setboardfile(dir, board, ".DIGEST");
	} else {
		setboardfile(dir, board, ".DIR");
	}
	
	if ((fd = open(dir, O_RDONLY)) == -1)
		RETURN_NULL();

	total = get_num_records(dir, sizeof(struct fileheader));
	if (total == 0) {
		close(fd);
		RETURN_NULL();
	}

    if(start == 0)
        start = total - t_lines + 1;
    /* MUST from start and AT MOST t_lines */
    if(start + t_lines -1 > total)
        t_lines = total - start + 1;

	if (start <= 0) start = 1;
		
	lseek(fd, (start - 1) * sizeof(struct fileheader), SEEK_SET);
	for (i = 0; i < t_lines; i++) {
		if (read(fd, &x, sizeof(x)) <= 0) break;
		
		MAKE_STD_ZVAL(l[i]);
		object_init(l[i]);
		add_property_long(l[i], "index", start + i);
		add_property_long(l[i], "flag", x.flag);
		add_property_long(l[i], "id", x.id);
		add_property_long(l[i], "update", atoi(x.filename + 2));
		add_property_string(l[i], "owner", x.owner, 1);
		add_property_string(l[i], "title", x.title, 1);
		add_property_string(l[i], "filename", x.filename, 1);			
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

/* 获取讨论区主题文章header信息 */
/* board: 讨论区名称 */
/* start: 从第start篇开始 */
/* t_lines: 获取的数量 */
/* 返回 total 以及 主题列表数组 */

PHP_FUNCTION(ext_gettopiclist)
{
#define MAXLIST 100
    struct post_node{
        int index,lastpost;
        int count;
    }node;
	zval *perlist;
	zval *objarr;
    HashTable hst;
	struct fileheader x,*headarr;
	struct boardheader *x1;
	char dir[80];
    char buf[50];
	int fd, total, len, i;
	
	char *board;
	int blen;					
    long start;				
    long t_lines;			
	long type,topic,idx;
    int *arr;

	chdir(BBSHOME);
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sll", &board, &blen, &start, &t_lines) == FAILURE)
		WRONG_PARAM_COUNT;
	if (t_lines > MAXLIST) t_lines = MAXLIST;

    setboardfile(dir, board, ".DIR");	
	
	if ((fd = open(dir, O_RDONLY)) == -1)
		RETURN_NULL();

	total = get_num_records(dir, sizeof(struct fileheader));
    
	if (total == 0) {
		close(fd);
		RETURN_NULL();
	}
    
    headarr=(struct fileheader*)emalloc(sizeof(struct fileheader)*(total+1));

    for(i=0;i<3;i++)
        if(read(fd,headarr, total*sizeof(struct fileheader))>0)  break;
    close(fd);
    
    if(i==3) RETURN_NULL();

    zend_hash_init(&hst, total, NULL,NULL ,0);

    arr=(int*)emalloc(sizeof(int)*total);
    memset(arr,255,total*sizeof(int));
    
    topic=0;
    struct post_node *nodeptr;
    for(i=total-1;i>=0;i--)
    {
        sprintf(buf,"%d",headarr[i].id);
        if(zend_hash_exists(&hst,buf,strlen(buf))==0)
        {
            node.index=topic;
            node.count = 0;
            node.lastpost=headarr[i].filetime;
            arr[topic] = i;
            zend_hash_add(&hst, buf, strlen(buf), &node, sizeof(node),NULL);            
            topic++; 
        } else {
            zend_hash_find(&hst, buf, strlen(buf), (void **)&nodeptr);
            node = *nodeptr;
            node.count ++;
            arr[nodeptr->index] = i;
            zend_hash_update(&hst, buf, strlen(buf), &node, sizeof(node),NULL);
        }
    }
    
    int realtopic=0;
    for(i=0;i<topic;i++){
        if(arr[i]!=-1) arr[realtopic++]=arr[i];
    }
    topic=realtopic;
    
    if (start == 0 || start > topic)
		start = topic- t_lines + 1;
	if (start <= 0) start = 1;
    

    MAKE_STD_ZVAL(objarr);
    array_init(objarr);
    for(i=topic-start;i>=0 && i<topic && i>topic-start-t_lines; i--)
    {
            idx=arr[i];                
            sprintf(buf,"%d",headarr[idx].id);
            zend_hash_find(&hst, buf, strlen(buf), (void **)&nodeptr);
            node=*nodeptr;                
            MAKE_STD_ZVAL(perlist);
            x=headarr[idx];
            object_init(perlist);
            add_property_long(perlist, "index", topic-i);
            add_property_long(perlist, "flag", x.flag);
            add_property_long(perlist, "id", x.id);
            add_property_long(perlist, "update", node.lastpost);
            add_property_string(perlist, "owner", x.owner, 1);
            add_property_string(perlist, "title", x.title, 1);
            add_property_string(perlist, "filename", x.filename, 1);
            add_property_long(perlist, "total_reply", node.count);
            add_next_index_zval(objarr, perlist);
    }
    
    object_init(return_value);
    add_property_long(return_value,"total",topic);
    add_property_zval(return_value,"list",objarr);
        
    zend_hash_destroy(&hst);
    
    efree(arr);
    efree(headarr);
} 

PHP_FUNCTION(ext_getfileheader)
{
    struct fileheader *x;

    char *board,*filename;
    int blen,flen,fd,size,i,total, ftime;
    char dir[80];
    
    chdir(BBSHOME);
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &board, &blen, &filename, &flen) == FAILURE)
		WRONG_PARAM_COUNT;

    setboardfile(dir,board,".DIR");
      
    
    if(mmapfile(dir, O_RDONLY, &x, &size, NULL) == 0) RETURN_NULL();

    ftime = atoi(filename+2);
    if(ftime == 0) RETURN_NULL();        
    
    total=size/sizeof(struct fileheader);
    int low = 0, high = total-1, mid;
        /* 二分查找之  */
    while(low < high) {
        mid =(low+high) / 2;
        if(x[mid].filetime == ftime) {
            low = high = mid;
            break;
        } else if(ftime < x[mid].filetime) high = mid;
        else low = mid+1;
    }
    if(low == high && strcmp(x[low].filename, filename) == 0)   i = low;
    else {
            /* 防止出现一些偶尔无序情况导致无法读入文章，这里会暴力扫一次，
             由于是稀有操作，对性能影响不大 */
        for(i=0; i<total; i++)
            if(strcmp(x[i].filename ,filename) == 0) break;
            //若还是没有则就真没有了。
        if(i == total) {
            munmapfile(x, size, -1);
            return ;
        }
    }
    object_init(return_value);
    add_property_long(return_value,"index", i+1);
    add_property_string(return_value,"filename",x[i].filename,1);
    add_property_string(return_value,"owner",x[i].owner,1);
    add_property_string(return_value,"realowner",x[i].realowner,1);
    add_property_string(return_value,"title",x[i].title,1);
    add_property_long(return_value,"flag",x[i].flag);
    add_property_long(return_value,"size",x[i].size);
    add_property_long(return_value,"id",x[i].id);
    add_property_long(return_value,"filetime",x[i].filetime);
    add_property_string(return_value,"reserved",x[i].reserved,1);

    munmapfile(x, size, -1);    
}

/* 修改自原代码中的int getGroupset(void); */
/* 初始化并返回所有分类及其属性，从而可以调用ext_getboards */
PHP_FUNCTION(ext_getsections)
{
	int secnum = 0;
	char seccode[36][5];
	char secname[36][2][20];

	char genbuf[256];
	zval *l[36];
	FILE *fp;
	int i, j, k;
	char *ptr, *ptr2;

	chdir(BBSHOME);
	fp = fopen("etc/menu.ini", "r");
	if (fp == NULL) RETURN_NULL();

	for (i = 0; i < 36; i++) {
		seccode[i][0] = '\0';
		secname[i][0][0] = '\0';
		secname[i][1][0] = '\0';
	}

	j = 0; k = 0;
	while (1) {
		fgets(genbuf, sizeof(genbuf), fp);
		if (feof(fp)) break;
		if (strstr(genbuf, "@EGroups")) {
			if (genbuf[0] != '@') continue;
			ptr = strtok(genbuf, "\"");
			ptr = strtok(NULL, "\"");
			if (*ptr >= '0' && *ptr <= '9') i = *ptr - '0'; 
			else if (*ptr >= 'A' && *ptr <= 'Z') i = *ptr - 'A' + 10;
			else if (*ptr >= 'a' && *ptr <= 'z') i = *ptr - 'a' + 10;
			else continue;
			j++;
			ptr = strtok(NULL, ")");
			ptr = strtok(NULL, "[");
			ptr2 = ptr + strlen(ptr) - 1;
			while (strchr("- ", *ptr2)) {
				ptr2--;
			}
			*(ptr2 + 1) = '\0';
			while (*ptr == ' ' && ptr < ptr2) ptr ++;
			strlcpy(secname[i][0], ptr, sizeof(secname[i][0]));
			ptr = strtok(NULL, "\"");
			ptr2 = ptr + strlen(ptr) - 1;
			while (*ptr2 != ']') ptr2--;
			*(ptr2 + 1) = '\0';
			snprintf(secname[i][1], sizeof(secname[i][1]), "[%s", ptr);
		}
		if (strstr(genbuf, "EGROUP")) {
			if (genbuf[0] != 'E') continue;
			ptr = strtok(genbuf, "\"");
			if (ptr[6] >= '0' && ptr[6] <= '9') i = ptr[6] - '0';
			else if (ptr[6] >= 'A' && ptr[6] <= 'Z') i = ptr[6] - 'A' + 10;
			else if (ptr[6] >= 'a' && ptr[6] <= 'z') i = ptr[6] - 'a' + 10;
			else continue;
			k++;
			ptr = strtok(NULL, "\"");
			strlcpy(seccode[i], ptr, sizeof(seccode[i]));
		}
	}
	fclose(fp);
	
	secnum = j;
	if (k < secnum) secnum = k;

	
	for (i = 0; i < secnum; i++) {
		MAKE_STD_ZVAL(l[i]);
		object_init(l[i]);
		add_property_string(l[i], "seccode", seccode[i], 1);
		add_property_string(l[i], "secname", secname[i][0], 1);
	}

	array_init(return_value);
	for (i = 0; i < secnum; i++)
		add_index_zval(return_value, i, l[i]);

}

/* 获取对应section的所有board header，非0~9时则所有讨论区 */
/* 参数：seccode(char*) */

PHP_FUNCTION(ext_getboards)
{
	zval *l[MAXBOARD];
	struct boardheader *x;
	char *seccode;
	long seclen;
	int i, total, groupflag;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &seccode, &seclen) == FAILURE)
		WRONG_PARAM_COUNT;

	for (i = 0, total = 0; i < MAXBOARD; i++) {
		x = &(sessionVar()->bcache[i]);
        
		if (x->filename[0] <= 32 || x->filename[0] > 'z') continue;
        
		if (!strchr(seccode, x->title[0])) continue;
		
		MAKE_STD_ZVAL(l[total]);
		object_init(l[total]);
		add_property_string(l[total], "filename", x->filename, 1);
		add_property_string(l[total], "title", x->title, 1);
		add_property_string(l[total], "BM", x->BM, 1);
		add_property_long(l[total], "flag", x->flag);
		add_property_long(l[total], "level", x->level);
		add_property_long(l[total], "lastpost", x->lastpost);
		add_property_long(l[total], "total", x->total);
		add_property_long(l[total], "total_today", x->total_today);
		add_property_long(l[total], "parent", x->parent);
        
		total++;
	}

	/* 函数返回值为zval数组(array_init)，
	** 每个zval是一个当结构体用的对象(object_init) */
   
	array_init(return_value);
	for (i = 0; i < total; i++)
		add_index_zval(return_value, i, l[i]);

}

PHP_FUNCTION(ext_getfavboards)
{

	zval *l[MAXBOARD];
	struct boardheader *x;
	char *user;
	long ulen;

	char path[256];
	char mybrd[GOOD_BRC_NUM][80];
	int i, j, total, mybrdnum = 0;
	FILE *fp;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &user, &ulen) == FAILURE)
		WRONG_PARAM_COUNT;

	chdir(BBSHOME);
	sprintf(path, "home/%c/%s/.goodbrd", mytoupper(user[0]), user);

	if ((fp = fopen(path, "r")) != NULL) {
		while(fgets(mybrd[mybrdnum], 80, fp)) {
			mybrd[mybrdnum][strlen(mybrd[mybrdnum])-1]='\0';
			mybrdnum++;
			if (mybrdnum > GOOD_BRC_NUM) break;
		}
		fclose(fp);
	}

	if (mybrdnum == 0) {
		if (getbcache(DEFAULTFAVBOARD)) {
			strcpy(mybrd[0], DEFAULTFAVBOARD);
			mybrdnum = 1;
		} else {
			RETURN_NULL();
		}
	}

	total = 0;
	
	for (i = 0; i < MAXBOARD; i++) {
		x = &(sessionVar()->bcache[i]);
		if (x->filename[0] <= 32 || x->filename[0] > 'z') continue;

		for (j = 0; j < mybrdnum; j++) {
			if (!strcmp(x->filename, mybrd[j]))
				break;
		}
		if (j == mybrdnum) continue;
		
		MAKE_STD_ZVAL(l[total]);
		object_init(l[total]);
		add_property_string(l[total], "filename", x->filename, 1);
		add_property_string(l[total], "title", x->title, 1);
		add_property_string(l[total], "BM", x->BM, 1);
		add_property_long(l[total], "flag", x->flag);
		add_property_long(l[total], "level", x->level);
		add_property_long(l[total], "lastpost", x->lastpost);
		add_property_long(l[total], "total", x->total);
		add_property_long(l[total], "total_today", x->total_today);		
		add_property_long(l[total], "parent", x->parent);
		total++;
	}

	array_init(return_value);
	for (i = 0; i < total; i++)
		add_index_zval(return_value, i, l[i]);

}


PHP_FUNCTION(ext_board_header) {

	struct  boardheader *bptr;
	
	char *bname;
	int blen;

    chdir(BBSHOME);
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &bname, &blen) == FAILURE)
		WRONG_PARAM_COUNT;

	if ((bptr = getbcache(bname)) == NULL)
		RETURN_NULL();

	object_init(return_value);
	add_property_string(return_value, "filename", bptr->filename, 1);
	add_property_string(return_value, "title", bptr->title, 1);
	add_property_string(return_value, "BM", bptr->BM, 1);
	add_property_long(return_value, "flag", bptr->flag);
	add_property_long(return_value, "level", bptr->level);
	add_property_long(return_value, "lastpost", bptr->lastpost);
	add_property_long(return_value, "total", bptr->total);
	add_property_long(return_value, "total_today", bptr->total_today);	
	add_property_long(return_value, "parent", bptr->parent);
	
}

PHP_FUNCTION(ext_get_denyheader) {

	struct  denyheader dheader;
	zval *dh;
    
	char *bname;
	int blen,fd;
    char dir[80];

    chdir(BBSHOME);
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &bname, &blen) == FAILURE)
		WRONG_PARAM_COUNT;

    array_init(return_value);
    snprintf(dir, sizeof(dir), "boards/%s/.DENYLIST", bname);
    if((fd = open(dir, O_RDONLY, 0644)) <0) RETURN_NULL();
    while(read(fd, &dheader, sizeof(dheader)) >0) {
        MAKE_STD_ZVAL(dh);
        object_init(dh);
        add_property_string(dh, "filename", dheader.filename, 1);
        add_property_string(dh, "executive", dheader.executive, 1);
        add_property_string(dh, "blacklist", dheader.blacklist, 1);
        add_property_string(dh, "title", dheader.title, 1);
        add_property_long(dh, "flag", dheader.flag);
        add_property_long(dh, "size", dheader.size);
        add_property_long(dh, "undeny_time", dheader.undeny_time);
        add_property_long(dh, "filetime", dheader.filetime);
        add_next_index_zval(return_value, dh);
    }
    close(fd);
}

PHP_FUNCTION(ext_delete_post)
{
    struct fileheader x,*hptr;
    char *board,*filename,*who;
    int blen,flen,wlen,fd,size,i,total;
    char dir[80],dest[80]; /* 删除人如果不是本人则放.DELETE,否则放.JUNK */    
    struct stat st;
    
    chdir(BBSHOME);
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sss", &board, &blen, &filename, &flen,&who,&wlen) == FAILURE)
		WRONG_PARAM_COUNT;

    setboardfile(dir,board,".DIR");

    if((fd=open(dir,O_RDONLY,644))==-1)
        RETURN_FALSE;
    
    total=get_num_records(dir,sizeof(struct fileheader));

    hptr=(struct fileheader *)emalloc(total*sizeof(struct fileheader));
    if(hptr==NULL) RETURN_FALSE;
    
    if(read(fd,hptr,total*sizeof(struct fileheader))<0) {
        efree(hptr);
        close(fd);
        RETURN_FALSE;
    }
    
    close(fd);

    int del_ok=0;
    for(i=total-1; i>=0; i--)
        if(strcmp(hptr[i].filename, filename) == 0){
            
            if(strcmp(hptr[i].owner, who)==0){ /* deleted by the owner*/
                setboardfile(dest, board, ".JUNK");
            }else{  /* delete by BM*/
                setboardfile(dest, board, ".DELETED");
            }
                /* index start from 1*/
            if(delete_record(dir, sizeof(struct fileheader), i+1)<0){
                del_ok=0;
                break;
            }
            
            append_record(dest, &hptr[i],sizeof(struct fileheader));
            del_ok=1;
            break;
        }

    efree(hptr);
    if(del_ok) RETURN_TRUE;
    RETURN_FALSE;
}


PHP_FUNCTION(ext_get_allboards)
{
    struct boardheader *x;
    int i;
    
    array_init(return_value);    
	for (i = 0; i < MAXBOARD; i++) {
		x = &(sessionVar()->bcache[i]);
		if (x->filename[0] <= 32 || x->filename[0] > 'z') continue;
        add_next_index_string(return_value, x->filename, 1);
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
