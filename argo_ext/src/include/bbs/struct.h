/*
    Pirate Bulletin Board System
    Copyright (C) 1990, Edward Luke, lush@Athena.EE.MsState.EDU
    Eagles Bulletin Board System
    Copyright (C) 1992, Raymond Rocker, rocker@rock.b11.ingr.com
			Guy Vega, gtvega@seabass.st.usm.edu
			Dominic Tynes, dbtynes@seabass.st.usm.edu
    Firebird Bulletin Board System
    Copyright (C) 1996, Hsien-Tsung Chang, Smallpig.bbs@bbs.cs.ccu.edu.tw
			Peng Piaw Foong, ppfoong@csie.ncu.edu.tw

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 1, or (at your option)
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/
/*
$Id: struct.h,v 1.15 2008-08-28 17:09:15 freestyler Exp $
*/

/* Note the protocol field is not inside an #ifdef FILES...
   this is a waste but allows you to add/remove UL/DL support without
   rebuilding the PASSWDS file (and it's only a lil ole int anyway).
*/
struct userec {			/* Structure used to hold information in */
	char userid[IDLEN + 2];	/* PASSFILE */
	time_t firstlogin;
	char lasthost[16];	/* ��һ�ε�¼��ַ */
	unsigned int numlogins;
	unsigned int numposts;
	char flags[2];			/* �õ���3λ, consts.h userec->flag[0] */
	char passwd[MD5_PASSLEN];
	char username[NICKNAMELEN + 1]; /* nick */
	char ident[NAMELEN + 1];	/*�ʺ�ע���ַ */

	char termtype[16];	
	char reginfo[STRLEN - 16];	/* Ҳ������֤email */
	unsigned int userlevel;   	/* �û�Ȩ�� */
	unsigned char usertitle;  	/* �ƺ� */
	unsigned char reserved[7];
	time_t lastlogin;
	time_t lastlogout;	  	
	time_t stay;
	char realname[NAMELEN + 1];
	char address[STRLEN];
	char email[STRLEN - 12];
	unsigned int nummails;
	time_t lastjustify;
	char gender;
	unsigned char birthyear;
	unsigned char birthmonth;
	unsigned char birthday;
	int signature;
	unsigned int userdefine;	/* �����趨���� */
	time_t notedate;		/* no use currently */
	int noteline;			/* ��վʱ���Ķ�notepad noteline���� */
};

struct user_info {		/* Structure used in UTMP file */
	int active;		/* When allocated this field is true */
	int uid;		/* Used to find user name in passwd file */
	int pid;		/* kill() to notify user of talk request */
	int invisible;		/* Used by cloaking function in Xyz menu */
	int sockactive;		/* Used to coordinate talk requests */
	int sockaddr;		/* ... */
	int destuid;		/* talk uses this to identify who called */
	int mode;		/* UL/DL, Talk Mode, Chat Mode, ... */
	int pager;		/* pager toggle, YEA, or NA */
	int in_chat;		/* for in_chat commands   */
	int fnum;		/* number of friends */
	int ext_idle;		/* has extended idle time, YEA or NA */
	char chatid[10];	/* chat id, if in chat mode */
	char from[60];		/* machine name the user called in from */
	char hideip;
	time_t idle_time;	/* to keep idle time */
	time_t deactive_time;	/* last deactive time */
	char userid[IDLEN + 2];
	char realname[NAMELEN + 1];
	char username[NICKNAMELEN + 1];
	char nickcolor;
	unsigned short friends[MAXFRIENDS];	/* uid in ucache */
	unsigned short reject[MAXREJECTS];
	int utmpkey;		/* used by nju wwwbbs */
};

struct override {
	char id[13];
	char exp[40];
};

struct fileheader {
	char filename[FNAMELEN];		// filename format: {M|G}.time.{Alphabet}
	char owner[IDLEN + 2];
	char realowner[IDLEN + 2];		// to perserve real owner id even in anonymous board
	char title[TITLELEN];
	unsigned int flag;
	unsigned int size;
	unsigned int id;  			// identity of article (per thread)
	time_t filetime;
	char reserved[12];
};

struct boardheader {
	char filename[BFNAMELEN];
	char title[BTITLELEN];
	char BM[BMLEN];
	unsigned flag;		/* �������� */
	unsigned level;		/* read/postȨ�� */
	unsigned lastpost;	/* lastpost time */
	unsigned total; 	/* ������ */
	unsigned parent;	/* parent board ID */
	unsigned int total_today;
	unsigned char reserved[4];
};

struct denyheader {
	char filename[FNAMELEN];
	char executive[IDLEN + 2];
	char blacklist[IDLEN + 2];
	char title[TITLELEN];
	unsigned int flag;
	unsigned int size;
	time_t undeny_time;
	time_t filetime;
	char reserved[12];
};

struct feedheader{   /* friend feed header*/
    char userid[IDLEN+2];
    char board[FNAMELEN];
    char filename[FNAMELEN];
    time_t filetime;
    char reserverd[6];
};
struct annheader {      		/* announce header */
	char filename[FNAMELEN];
	char owner[IDLEN + 2];
	char editor[IDLEN + 2];
	char title[TITLELEN];
	unsigned int flag;
	int mtime;                      /* modification time */
	char reserved[20];
};

struct annpath {
	char board[BFNAMELEN];
	char title[TITLELEN];
	char path[PATH_MAX + 1];
};

struct anninfo {
	int flag;
	int manager;
	char title[TITLELEN];
	char basedir[PATH_MAX + 1];
	char direct[PATH_MAX + 1];
};

struct anncopypaste {
	int copymode;
	char basedir[PATH_MAX + 1];
	char location[TITLELEN];
	struct annheader fileinfo;
};

struct anntrace {							/* ������������¼ */
	int operation;							/* �������� */
	time_t otime;							/* ����ʱ�� */
	char executive[IDLEN + 2];					/* ������ */
	char location[TITLELEN];					/* ����Ŀ¼ */
	char info[2][TITLELEN];						/* ������Ϣ */
	char reserved[256 - TITLELEN * 3 - IDLEN - 2 - sizeof(int) - sizeof(time_t)];
};

struct one_key {		/* Used to pass commands to the readmenu */
	int key;
	int (*fptr) ();
};

#define USHM_SIZE       (MAXACTIVE + 10)

struct UTMPFILE {
	struct user_info uinfo[USHM_SIZE];
	time_t uptime;		 /* ����ʱ�� */
	unsigned short usersum;  /* Ŀǰ���ʺ��� */
	int max_login_num; 	 /* �����վ����, (VISITLOG) */
//	int visit_total;
};


struct BCACHE {
	struct boardheader bcache[MAXBOARD];
	int number;
	int updating;
	time_t uptime;
	time_t pollvote;
#ifdef INBOARDCOUNT
	unsigned short inboard[MAXBOARD];/* added by freestyler */
#endif
	int total_today;
};

struct UCACHE {
	char userid[MAXUSERS][IDLEN + 2];
	int number;		/* number of all users */
	int updating;		/* is shm updating ? */
	time_t uptime;		/* last update time */
};

struct postheader {
	char title[STRLEN];	
	char ds[40];		/* ���������� */
	int reply_mode;		/* re�� */
	char include_mode; 	/* ����ģʽ */
	int chk_anony;		
	int postboard;		/* ��������or���� */
};

struct keeploc {
	char *key;
	int top_line;
	int crs_line;
	struct keeploc *next;
};

/* ���������� */
struct bmfuncarg {
	int flag;				/* ������ʽ */
	union {
		char author[IDLEN + 2];		/* ���� */
		char title[TITLELEN];		/* ����ؼ��� */
		int id;				/* ���±�ʾ (thread id) */
	};
	void *extrarg[4];			/* ���� */
};

/* active board */
struct ACSHM {
	char data[ACBOARD_MAXLINE][ACBOARD_BUFSIZE];
	int movielines;
	int movieitems;
	time_t update;
};

/* endline msg */
struct ELSHM {
	char data[ENDLINE_MAXLINE][ENDLINE_BUFSIZE];
	int count;
};

struct FILESHM {
	char line[FILE_MAXLINE][FILE_BUFSIZE];
	int fileline;
	int max;
	time_t update;
};

struct STATSHM {
	char line[FILE_MAXLINE][FILE_BUFSIZE];
	time_t update;
};

#if 0
//Added by cancel at 02.03.02
struct new_reg_rec {
	int usernum;
	time_t regtime;
	char userid[IDLEN + 2];
	char rname[NAMELEN];
	char addr[STRLEN];
	char phone[STRLEN];
	char dept[STRLEN];
	char assoc[STRLEN];
	int Sip;
	int Sname;
	int Slog;
	char mark;
};
#endif

/* betterman: consts for new account system 06.07 */
/* Modifyed by betterman at 06.07.27 */
struct new_reg_rec {
        int usernum;
        time_t regtime;
        char userid[IDLEN + 2];
        char rname[NAMELEN];
        char addr[STRLEN];
        char phone[STRLEN];
        char dept[STRLEN];
        char assoc[STRLEN];
        int Sip;
        int Sname;
        int Slog;
        char mark;
        char account[STRLEN];
        unsigned char auth[MD5_PASSLEN];//���ϵ�����
        unsigned char birthyear;
        unsigned char birthmonth;
        unsigned char birthday;
        int graduate;  //��ҵ���
};

struct postlog {
	char board[BFNAMELEN];
	unsigned int id;
	time_t date;
	int number;
};

struct mypostlog {
	int hash_ip;
	unsigned int id[64];
};

struct screenline {
	unsigned char oldlen;   // previous line length
	unsigned char len;      // current length of line
	unsigned char mode;     // status of line, as far as update
	unsigned char smod;     // start of modified data
	unsigned char emod;     // end of modified data
	unsigned char sso;      // start stand out
	unsigned char eso;      // end stand out
	unsigned char data[LINELEN];
};


/* ����ͷ */
struct attacheader {
    char filename[FNAMELEN];
    char board[BFNAMELEN];	
    char filetype[10];
	char origname[30];	
	int articleid;
	char desc[48];
};

/* @����Ϣͷ */
struct msgheader{
    time_t when;
    char userid[IDLEN+2]; //who
    char board[BFNAMELEN];
    char filename[FNAMELEN];
    unsigned int flag;
    char type[2]; /*  @:message ,  r: reply */
    char reserve[8];
};
