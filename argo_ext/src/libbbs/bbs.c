/*
 * divide into pieces later
 */

#include "libbbs.h"


int
searchuser(char *userid)
{
	int i;
	for (i = 0; i < sessionVar()->uidshm->number; i++)
		if (!strncasecmp(userid, sessionVar()->uidshm->userid[i], IDLEN + 1))
			return i + 1;
	return 0;
}

/* 返回userid在PASSFILE的id(下标1开始).并填充lookuser 
 * 返回0, 表示没找到userid记录  */
int
getuser(char *userid, struct userec *lookupuser)
{
	int uid;

	if ((uid = searchuser(userid)) == 0)
		return 0;

	if (lookupuser == NULL)
		return uid;
	
	return (get_record(PASSFILE, lookupuser, sizeof(struct userec), uid) == -1 || lookupuser->userid[0] == '\0') ? 0 : uid;
}




struct boardheader* getbcache(char *board) {
	int i;
	if(board == NULL) return NULL;
	for(i=0; i<MAXBOARD; i++)
		if (!strcasecmp(board, sessionVar()->bcache[i].filename))
			return &sessionVar()->bcache[i];
	return NULL;
}



/* from stuff.c */
int getfilename(char *basedir, char *filename, int flag, unsigned int *id)
{
	char fname[PATH_MAX + 1], ident = 'A';
	int fd = 0, count = 0;
	time_t now = time(NULL);

	while (1) {
		if (count++ > MAX_POSTRETRY)
			return -1;

		snprintf(fname, sizeof(fname), "%s/M.%d.%c", basedir, (int)now, ident);
		if (flag & GFN_LINK) {
			if(link(filename, fname) == 0) {
				unlink(filename);
				break;
			}
		} else {
			if ((fd = open(fname, O_CREAT | O_EXCL | O_WRONLY, 0644)) != -1) {
				if (!(flag & GFN_NOCLOSE)) {
					close(fd);
					fd = 0;
				}
				break;
			}

			if (errno == EEXIST) {   // monster: 仅当文件存在时才重试
				if (!(flag & GFN_SAMETIME) || ident == 'Z') {
					ident = 'A';
					++now;
				} else {
					++ident;
				}
				continue;
			}
		}
		return -1;
	}

	if ((flag & GFN_UPDATEID) && id != NULL)
		*id = now;

	strlcpy(filename, fname, sizeof(fname));
	return fd;
}

char * show_special(char *id2) {
        FILE *fp;
        char  id1[80], name[80];
        static char special[512];
        fp=fopen("etc/sysops", "r");
        memset(special, 0, sizeof(special));
        if(fp==0) return special;
        while(1) {
                id1[0]=0;
                name[0]=0;
                if(fscanf(fp, "%s %s", id1, name)<=0) break;
                if(!strcmp(id1, id2)) {
                    strcat(special, name);
                    strcat(special, " ");
                }
        }
        fclose(fp);
	return special;
}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */

