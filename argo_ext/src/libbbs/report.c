#include "libbbs.h"

const char *trace_file = "wwwlog/trace";

/* The following code is adopted from PukeBBS telnet version */
char datestring[256];

char *
getdatestring (now)
time_t now;
{
        struct tm *tm;
        char weeknum[7][3]={"天","一","二","三","四","五","六"};

        tm = localtime((time_t *)&now);
        sprintf(datestring,"%4d年%02d月%02d日%02d:%02d:%02d 星期%2s",
                tm->tm_year+1900,tm->tm_mon+1,tm->tm_mday,
                tm->tm_hour,tm->tm_min,tm->tm_sec,
                weeknum[tm->tm_wday]);
        return datestring;
}

int 
getnewfilename (board)
char *board; {
	int i, now, fd;
	char filename[16], buf[80];
	now = time(0);
	setboardfile(buf, board, ".DIR");
    //snprintf(buf, sizeof(buf), "boards/%s/%s", board, ".DIR");
	fd = filelock(buf, 1);
	for (i = 0; i<100; i++) {
		snprintf(filename, sizeof(filename), "M.%d.A", now + i);
		setboardfile(buf, board, filename);
		if (!file_exist(buf)) {
			/* Henry: 防止其他并行进程覆盖掉该文件 */
			utime(buf, NULL);
			break;
		}
	}
	fileunlock(fd);
	if (i > 99) return -1;
	return now + i;
}

int file_copy(FILE *fp1, FILE *fp2) {
	char buf[1024];
	if (fp1 == NULL || fp2 == NULL) return -1;
        while(1) {
                if(fgets(buf, 1000, fp1)<=0) break;
                fprintf(fp2, "%s", buf);
        }
	return 0;
}


void
do_securityreport(char *str, struct userec *userinfo, int fullinfo, char *addinfo)
{
        FILE *se;
        char fname[STRLEN];

        sprintf(fname, "tmp/security.%s.%05d", userinfo->userid, getpid());
        if ((se = fopen(fname, "w")) != NULL) {
                fprintf(se, "系统安全记录\n\033[1m原因：%s\033[m\n", str);
                if (addinfo)
                        fprintf(se, "%s\n", addinfo);
                if (fullinfo) {
                        fprintf(se, "\n以下是个人资料：");
                        /* Rewrite by cancel at 01/09/16 */
                        /* 修改了getuinfo()，加上了第二个参数 */
                        getuinfo(se, userinfo);
                } else {
                        getdatestring(userinfo->lastlogin);
                        fprintf(se, "\n以下是部分个人资料：\n");
                        fprintf(se, "最近光临日期 : %s\n", datestring);
                        fprintf(se, "最近光临机器 : %s\n", userinfo->lasthost);
                }
                fclose(se);
                post_security_inform(userinfo, str, fname);
                unlink(fname);
        }
}

void
securityreport2(struct userec *user, char *str, char *addinfo, int fullinfo)
{
        do_securityreport(str, user, fullinfo, addinfo);
}

/* Rewrite by cancel at 01/09/16 */
void
getuinfo(FILE *fn, struct userec *userinfo)
{
        int num;
        char buf[40];

        fprintf(fn, "\n他的代号     : %s\n", userinfo->userid);
        fprintf(fn, "他的昵称     : %s\n", userinfo->username);
        fprintf(fn, "真实姓名     : %s\n", userinfo->realname);
        fprintf(fn, "居住住址     : %s\n", userinfo->address);
        fprintf(fn, "电子邮件信箱 : %s\n", userinfo->email);
        fprintf(fn, "真实 E-mail  : %s\n", userinfo->reginfo);
        fprintf(fn, "帐号注册地址 : %s\n", userinfo->ident);
        getdatestring(userinfo->firstlogin);
        fprintf(fn, "帐号建立日期 : %s\n", datestring);
        getdatestring(userinfo->lastlogin);
        fprintf(fn, "最近光临日期 : %s\n", datestring);
        fprintf(fn, "最近光临机器 : %s\n", userinfo->lasthost);
        fprintf(fn, "上站次数     : %d 次\n", userinfo->numlogins);
        fprintf(fn, "文章数目     : %d\n", userinfo->numposts);
        fprintf(fn, "上站总时数   : %d 小时 %d 分钟\n",
                userinfo->stay / 3600, (userinfo->stay / 60) % 60);
        strcpy(buf, "bTCPRp#@XWBA#VS-DOM-F012345678");
        for (num = 0; num < 30; num++)
                if (!(userinfo->userlevel & (1 << num)))
                        buf[num] = '-';
        buf[num] = '\0';
        fprintf(fn, "使用者权限   : %s\n\n", buf);
}
/* Rewrite End. */

int post_security_inform(struct userec *user, char *title, char *filename) {
        struct fileheader fh;
        char fname[STRLEN];
        FILE *fp1, *fp2;
        int i;
        time_t now;
	    char *board = "syssecurity";

        fp1 = fopen(filename, "r");
        if (fp1 == NULL) return -1;

        i = getnewfilename(board);
        if (i == -1) return -1;
        fh.id = i;
        sprintf(fh.filename, "M.%d.A", i);
        setboardfile(fname, board, fh.filename);
        fp2 = fopen(fname, "w");
        if (fp2 == NULL) return -1;

        strlcpy(fh.owner, user->userid, IDLEN + 1);
        strlcpy(fh.title, title, TITLELEN);
        fh.flag = 0;
        fh.size = file_size(filename);
        now = time(NULL);
	    fh.filetime = now;
        setboardfile(fname, board, ".DIR");
        append_record(fname, &fh, sizeof(fh));

        fprintf(fp2, "发信人: %s (%s), 信区: %s\n",
                user->userid, user->username, board);
        fprintf(fp2, "标  题: %s\n", title);
        fprintf(fp2, "发信站: %s (%24.24s)\n", BBSNAME, ctime(&now));
	fprintf(fp2, "\n");
        file_copy(fp1, fp2);

        fclose(fp2);
        fclose(fp1);
        return 0;
}


