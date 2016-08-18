#include "libbbs.h"

static void log_usies(char *a, char *b) {}

void
attach_err(int shmkey, char *name, int err)
{
	fprintf(stderr, "Error! %s error #%d! key = %x.\n", name, err, shmkey);
	exit(1);
}

int
search_shmkey(char *keyname)
{
	int i = 0;

	while (shmkeys[i].key != NULL) {
		if (strcmp(shmkeys[i].key, keyname) == 0)
			return shmkeys[i].value;
		i++;
	}

	return 0;
}

/* 改自telnet code， 解决http先启动造成的shmat错误问题 */
void *
attach_shm(char *shmstr, int shmsize)
{
	void *shmptr;
	int shmkey, shmid;

	shmkey = search_shmkey(shmstr);
	shmid = shmget(shmkey, shmsize, IPC_CREAT | IPC_EXCL | 0644);
	if (shmid < 0) {
		shmid = shmget(shmkey, shmsize, 0);
		if (shmid < 0)
			attach_err(shmkey, "shmget", errno);
		shmptr = (void *) shmat(shmid, NULL, 0);
		if (shmptr == (void *) -1)
			attach_err(shmkey, "shmat", errno);
	} else {
		shmptr = (void *) shmat(shmid, NULL, 0);
		if (shmptr == (void *) -1)
			attach_err(shmkey, "shmat", errno);
	}
	return shmptr;
}



void resolve_utmp()
{
	if (sessionVar()->utmpshm == NULL) {
		sessionVar()->utmpshm = attach_shm("UTMP_SHMKEY", sizeof (*sessionVar()->utmpshm));
	}
}


int get_lastpost(char *board, unsigned int *lastpost, unsigned int *total)
{
	struct fileheader fh;
	struct stat st;
	char filename[PATH_MAX + 1];
	int fd, atotal;

	snprintf(filename, sizeof(filename), "boards/%s/.DIR", board);

	if ((fd = open(filename, O_RDONLY)) == -1)
		return -1;
	if (fstat(fd, &st) == -1 || st.st_size == 0) {
		close(fd);
		return -1;
	}

	atotal = st.st_size / sizeof (fh);
	*total = atotal;
	if (lseek(fd, (off_t) (atotal - 1) * sizeof (fh), SEEK_SET) != -1) /* seek到最后一篇文章header */
		if (read(fd, &fh, sizeof(fh)) == sizeof(fh))
			*lastpost = fh.filetime;
	close(fd);
	return 0;
}


int fillbcache(void *fptr, int unused)
{
	struct boardheader *bptr;

	if (sessionVar()->numboards >= MAXBOARD)
		return 0;

	bptr = &sessionVar()->bcache[sessionVar()->numboards++];
	memcpy(bptr, fptr, sizeof(struct boardheader));
	get_lastpost(bptr->filename, &bptr->lastpost, &bptr->total);
	return 0;
}


void resolve_boards()
{
	int fd;
	struct stat st;

	if (sessionVar()->brdshm == NULL) {
		sessionVar()->brdshm = attach_shm("BCACHE_SHMKEY", sizeof(*sessionVar()->brdshm));
	}
	sessionVar()->numboards = sessionVar()->brdshm->number;
	sessionVar()->bcache = sessionVar()->brdshm->bcache;

	if (sessionVar()->brdshm->updating != YEA && sessionVar()->brdshm->uptime + 300 < time(NULL)) {

		if (stat(BOARDS, &st) == -1)
			return;

		if (sessionVar()->brdshm->uptime > st.st_mtime)
			return;


		if ((fd = filelock("bcache.lock", NA)) > 0) {
			sessionVar()->brdshm->updating = YEA;
			log_usies("CACHE", "reload bcache");
			sessionVar()->numboards = 0;
			apply_record(BOARDS, fillbcache, sizeof (struct boardheader));
			sessionVar()->brdshm->number = sessionVar()->numboards;
			sessionVar()->brdshm->uptime = time(NULL);
			close(fd);
			sessionVar()->brdshm->updating = NA;
		} 
	}
}



int fillucache(void *p, int uid)
{
	struct userec *uentp = p;

	if (sessionVar()->usernumber < MAXUSERS) {
		strlcpy(sessionVar()->uidshm->userid[sessionVar()->usernumber], uentp->userid, sizeof(sessionVar()->uidshm->userid[sessionVar()->usernumber]));
		sessionVar()->usernumber++;
	}
	return 0;
}

void resolve_ucache()
{
	int fd;

	if (sessionVar()->uidshm == NULL) {
		sessionVar()->uidshm = attach_shm("UCACHE_SHMKEY", sizeof (*sessionVar()->uidshm));
	}

/*
 *	if (stat(FLUSH, &st) < 0) {
 *		ftime = time(NULL) - 86400;
 *	} else {
 *		ftime = st.st_mtime;
 *	}
 */

	if (sessionVar()->uidshm->updating != YEA && sessionVar()->uidshm->uptime + 86400 < time(NULL)) {
		if ((fd = filelock("ucache.lock", NA)) > 0) {
			sessionVar()->uidshm->updating = YEA;
			log_usies("CACHE", "reload ucache");
			sessionVar()->usernumber = 0;
			apply_record(PASSFILE, fillucache, sizeof (struct userec));
			sessionVar()->uidshm->number = sessionVar()->usernumber;
			sessionVar()->uidshm->uptime = time(NULL);
			close(fd);
			sessionVar()->uidshm->updating = NA;
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

