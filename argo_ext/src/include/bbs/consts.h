/*******************************************
 *              BBS PARAMETERS             *
 *******************************************/

#define STRLEN          80    	/* length of string buffer */
#define IDLEN           12	/* length of user id. */
#define BUFLEN          1024	/* length of general buffer */
#define TITLELEN        56	/* length of article title */
#define FNAMELEN        16	/* length of filename */
#define NAMELEN         20	/* length of realname */

#ifdef MULTILINE_MESSAGE

#define MSGSIZE			256	/* һ��messsage��ռ��С */
#define MSGLINE			3	/* ��Ϣ������ռ���� */
#define MSGLEN			180	/* �û����������Ϣ���� */
#define MSGHEADP		7

#else  /* MULTILINE_MESSAGE */

#define MSGSIZE			129
#define MSGLINE			1
#define MSGLEN			55
#define MSGHEADP		12

#endif /* MULTILINE_MESSAGE */


#ifdef LONGNICKNAME
#define NICKNAMELEN		40	/* length of long nickname */
#else
#define NICKNAMELEN		20	/* length of normal nickname */
#endif

#define BTITLELEN               40	/* length of board title */
#define BMLEN                   40	/* length of bm list (BMLEN >= 3 * (IDLEN + 1)) */
#define BFNAMELEN               20	/* length of board name */

#define MD5_PASSLEN             16	/* length of encrypted password (MD5) */
#define DES_PASSLEN             14	/* length of encrypted password (DES) */
#define PASSLEN                 40	/* length of password */
#define RNDPASSLEN              10      /* ������֤�İ��볤�� (���˷�Χ 4~10) */

#define MULTI_LOGINS		2       /* ͬʱ����վ ID �� */
#define MAXGUEST            	256 	/* ��� guest �ʺ���վ���� */
#define MAXPERIP		10	/* ͬIPͬʱ����վ ID �� */

#define MAXFRIENDS 		200	/* �����Ѹ��� */
#define MAXREJECTS 		32	/* ����˸��� */

#define REG_EXPIRED         	180 	/* �������ȷ������ */

#define MAX_POSTRETRY        	2000
#define MAX_BOARD_POST  	8000	/* ��ͨ������������ */
/* #define MAX_BOARD_POSTW 	8000 */	/* ˮ������������ */ /* ȡ��ʹ��, by gcc */

#define MAX_BOARD_POST_II	12000	/* �ڶ������������� */

#define MORE_BUFSIZE       	4096
#define FILE_BUFSIZE        	200
#define FILE_MAXLINE         	25
#define MAX_WELCOME          	15 	/* ��ӭ������ */
#define MAX_GOODBYE          	15 	/* ��վ������ */
#define MAX_ISSUE            	15 	/* ����վ������ */
#define MAX_DIGEST         	1500 	/* �����ժ�� */

#define MAX_USERICON   51200 /* ͷ��ͼƬ��С���� added by Cypress */

#ifndef BIGGER_MOVIE
#define MAXMOVIE		6  	/* ��������� (�ޱ߿�) */
#else
#define MAXMOVIE		8  	/* ��������� (�б߿�) */
#endif

#define MAX_ACBOARD		15	/* ��������� */

#define ACBOARD_BUFSIZE     	250
#define ACBOARD_MAXLINE        (MAX_ACBOARD * MAXMOVIE)
#define ENDLINE_BUFSIZE     	250
#define ENDLINE_MAXLINE      	32

#define MAXSIGLINES             6       /* ǩ����������� */

#define NUMPERMS		30

#define	MSQKEY			4716	/* key of message queue */

#define BBSNET_CONNECT_TIMEOUT	10
#define BBSNET_NOINPUT_TIMEOUT	300

#define TALK_CONNECT_TIMEOUT	5
#define CHAT_CONNECT_TIMEOUT	10

/*******************************************
 *          BBS RELATED CONSTANTS          *
 *******************************************/

/* filenames */
#define PASSFILE        BBSHOME"/.PASSWDS"
#define BOARDS          BBSHOME"/.BOARDS"
#define VISITLOG        BBSHOME"/reclog/.visitlog"
#define BADLOGINFILE    "logins.bad"

#define DOT_DIR     	".DIR"
#define THREAD_DIR  	".THREAD"
#define DIGEST_DIR  	".DIGEST"
#define MARKED_DIR  	".MARKEDDIR"
#define AUTHOR_DIR  	".AUTHORDIR"
#define KEY_DIR     	".KEYDIR"
#define DELETED_DIR	".DELETED"
#define JUNK_DIR	".JUNK"
#define DENY_DIR	".DENYLIST"

/* fileheader->flag */
#define FILE_READ		0x000001
#define FILE_OWND		0x000002
#define FILE_VISIT		0x000004
#define FILE_MARKED		0x000008	/* article is marked */
#define FILE_DIGEST		0x000010	/* article is added to digest */
#define FILE_FORWARDED		0x000020	/* article restored from recycle bin */
#define MAIL_REPLY		0x000020	/* mail replyed */
#define FILE_NOREPLY		0x000040	/* reply to the article is not allowed */
#define FILE_DELETED		0X000080
#define FILE_SELECTED		0x000100	/* article selected */
#define FILE_ATTACHED		0x000200	/* article comes with attachments */
#define FILE_RECOMMENDED	0x000400	/* article has been recommended */
#define FILE_MAIL		0x000800	/* send mail when replied */
#define FILE_OUTPOST		0x010000
#define FILE_REPLYNOTIFY 0x020000 /*  Notify @userid if reply . add by Cypress */

/* fileheader->reserved[0] -- thread flag */
#define THREAD_BEGIN	0
#define THREAD_END	1
#define THREAD_OTHER	2

/* boardheader->flag */
#define VOTE_FLAG       0x000001
#define NOZAP_FLAG      0x000002
#define OUT_FLAG        0x000004
#define ANONY_FLAG      0x000008
#define NOREPLY_FLAG    0x000010
#define READONLY_FLAG   0x000020
#define JUNK_FLAG       0x000040
#define NOPLIMIT_FLAG   0x000080

#define BRD_READONLY    0x000100		  /* ֻ�� (���ܷ���, ֻ��ɾ�ĺ������ */
#define BRD_RESTRICT    0x000200		  /* ���ư� */
#define BRD_NOPOSTVOTE  0x000400		  /* ͶƱ��������� */
#define BRD_ATTACH	0x000800		  /* �����ϴ����� */
#define BRD_GROUP	0x001000		  /* �����б� */
#define BRD_HALFOPEN	0x002000		  /* changed by freestyler: �����û��ɷ��� */
#define BRD_INTERN  	0x004000		  /* Added by betterman :����У�ڷ��� */
#define BRD_MAXII_FLAG	0x008000		  /* Added by gcc: ���ӵڶ������������� */

/* announce.c */
#define ANN_FILE                0x01              /* ��ͨ�ļ� */
#define ANN_DIR                 0x02              /* ��ͨĿ¼ */
#define ANN_PERSONAL            0x04              /* �����ļ�Ŀ¼ */
#define ANN_GUESTBOOK           0x08              /* ���Ա� */
#define ANN_LINK                0x10              /* Local Link */
#define ANN_RLINK               0x20              /* Remote Link (unused) */
#define ANN_SELECTED            0x100             /* ��ѡ�� */
#define ANN_ATTACHED		0x200		  /* ���и��� */
#define ANN_RESTRICT            0x010000          /* �������ļ�/Ŀ¼ */
#define ANN_READONLY            0x020000          /* ֻ�� (�����޸�����/����) */

#define ANN_COPY		0		  /* ���� */
#define ANN_CUT			1		  /* ���� */
#define ANN_MOVE		2		  /* �ı���� */
#define ANN_EDIT		3		  /* �༭�ļ� */
#define ANN_CREATE		4		  /* ������Ŀ */
#define ANN_DELETE		5		  /* ɾ����Ŀ */
#define ANN_CTITLE		6		  /* ���ı��� */
#define ANN_ENOTES		7		  /* �༭����¼ */
#define ANN_DNOTES		8		  /* ɾ������¼ */
#define ANN_INDEX		9		  /* ���ɾ��������� */

/* read.c */
#define DONOTHING       0       /* Read menu command return states */
#define FULLUPDATE      1       /* Entire screen was destroyed in this oper */
#define PARTUPDATE      2       /* Only the top three lines were not destroyed */
#define DOQUIT          3       /* Exit read menu was executed */
#define NEWDIRECT       4       /* Directory has changed, re-read files */
#define READ_NEXT       5       /* Direct read next file */
#define READ_PREV       6       /* Direct read prev file */
#define GOTO_NEXT       7       /* Move cursor to next */
#define DIRCHANGED      8       /* Index file was changed */
#define MODECHANGED     9       /* ... */
#define NEWDIRECT2      10      /* Directory has changed, re-read files and jump to spec pnt*/
#define PREUPDATE       11      /* post preview */

/* user_info->pager */
#define ALL_PAGER       0x1
#define FRIEND_PAGER    0x2
#define ALLMSG_PAGER    0x4
#define FRIENDMSG_PAGER 0x8

/* userec->flags[0] */
#define PAGER_FLAG      0x01    /* true if pager was OFF last session */
#define CLOAK_FLAG      0x02    /* true if cloak was ON last session */
#define BRDSORT_FLAG    0x20    /* true if the boards sorted alphabetical */
#ifdef INBOARDCOUNT
#define BRDSORT_FLAG2   0x10    /* true if the boards sorted according the number of inboard users  */
#endif

/* apply_record */
#define QUIT            0x666	/* to terminate apply_record */

/* I/O control */
#define I_TIMEOUT   	-2	/* used for the getchar routine select call */
#define I_OTHERDATA 	-333	/* interface, (-3) will conflict with chinese */

/* board rc */
#define BRC_MAXSIZE     50000
#define BRC_MAXNUM      60
#define BRC_STRLEN      BFNAMELEN
#define BRC_ITEMSIZE    (BRC_STRLEN + 1 + BRC_MAXNUM * sizeof( int ))
#define GOOD_BRC_NUM    50

/* display */
#define BBS_PAGESIZE	(t_lines - 4)


/* Pudding: cosnts for AUTHHOST */
#ifdef AUTHHOST
#define HOST_AUTH_YEA		0xffffffff
#define HOST_AUTH_NA		0
#define UNAUTH_PERMMASK		(~(PERM_POST))
#endif

/* betterman: consts for new account system 06.07 */
#define MULTIAUTH 3
#define MAXMAIL 3

/*******************************************
 *                ANSI CODES               *
 *******************************************/

#define   ANSI_RESET    "\033[m"
#define   ANSI_REVERSE  "\033[7m\033[4m"

/*******************************************
 *	  KEYBOARD RELATED CONSTANTS       *
 *******************************************/

#define EXTEND_KEY
#define KEY_TAB         9
#define KEY_ESC         27
#define KEY_UP          0x0101
#define KEY_DOWN        0x0102
#define KEY_RIGHT       0x0103
#define KEY_LEFT        0x0104
#define KEY_HOME        0x0201
#define KEY_INS         0x0202
#define KEY_DEL         0x0203
#define KEY_END         0x0204
#define KEY_PGUP        0x0205
#define KEY_PGDN        0x0206

/*******************************************
 *        SCREEN Related CONSTANTS         *
 *******************************************/

#define LINELEN		256	/* maxinum length of a single line */

/* line buffer modes */
#define MODIFIED	1	/* if line has been modifed, output to screen */
#define STANDOUT	2	/* if this line has a standout region */

/*******************************************
 *        FUNCTION RELATED CONSTANTS       *
 *******************************************/

/* general constants */
#define YEA		1
#define NA		0

#define TRUE		1
#define FALSE		0

#define CRLF		"\r\n"

/* addtodeny & delfromdeny */
#define D_ANONYMOUS     0x01    /* ������� */
#define D_NOATTACH      0x02    /* �޸��� (ֱ�ӷ��) */
#define D_FULLSITE      0x04    /* ���ȫվ */
#define D_NODENYFILE    0x08    /* �����ɷ����¼�ļ� */
#define D_IGNORENOUSER	0x10	/* �����û������ڵĴ��� */

/* getdata */
#define DOECHO		1
#define NOECHO		0

/* getfilename/getdirname */
#define GFN_FILE        0x00
#define GFN_LINK        0x01
#define GFN_UPDATEID    0x02	/* update article id */
#define GFN_SAMETIME	0x04
#define GFN_NOCLOSE	0x08

/* locate_article and bm functions */

#define LOCATE_THREAD		0x01
#define LOCATE_AUTHOR		0x02
#define LOCATE_TITLE		0x04
#define LOCATE_TEXT		0x08
#define LOCATE_SELECTED		0x10
#define LOCATE_ANY		0x20

#define LOCATE_FIRST		0x100
#define LOCATE_LAST		0x200
#define LOCATE_PREV		0x400
#define LOCATE_NEXT		0x800
#define LOCATE_NEW		0x1000

/* process_records */
#define KEEPRECORD		0
#define REMOVERECORD		1

/* listedit (used by its callback function */
#define	LE_ADD			0
#define	LE_REMOVE		1

#define MAX_IDLIST		3

/* vedit */
#define EDIT_NONE		0x00
#define	EDIT_SAVEHEADER		0x01
#define EDIT_MODIFYHEADER	0x02
#define EDIT_ADDLOGINFO		0x04

/* more */
#define MORE_NONE		0x00
#define MORE_STUFF		0x01
#define MORE_MSGVIEW		0x02
#define MORE_ATTACHMENT		0x04

/*******************************************
 *           GLOSSARY CONSTANTS            *
 *******************************************/

#ifndef NOEXP

/* ��������ֵ�ȼ� */
#define GLY_CEXP0               "û�ȼ�"
#define GLY_CEXP1               "������·"
#define GLY_CEXP2               "һ��վ��"
#define GLY_CEXP3               "�м�վ��"
#define GLY_CEXP4               "�߼�վ��"
#define GLY_CEXP5               "��վ��"
#define GLY_CEXP6               "���ϼ�"
#define GLY_CEXP7               "��վԪ��"
#define GLY_CEXP8               "��������"

/* �����������ȼ� */
#define GLY_CPOST0              "ûд����"
#define GLY_CPOST1              "�Ĳ�һ��"
#define GLY_CPOST2              "�Ĳ�����"
#define GLY_CPOST3              "��̳����"
#define GLY_CPOST4              "��̳��ʿ"
#define GLY_CPOST5              "��̳����"

/* ��������ֵ�ȼ� */
#define GLY_CPERF0              "û�ȼ�"
#define GLY_CPERF1              "�Ͽ����"
#define GLY_CPERF2              "Ŭ����"
#define GLY_CPERF3              "������"
#define GLY_CPERF4              "�ܺ�"
#define GLY_CPERF5              "�ŵ���"
#define GLY_CPERF6              "̫������"
#define GLY_CPERF7              "��վ֧��"
#define GLY_CPERF8              "�񡫡�"

#endif
