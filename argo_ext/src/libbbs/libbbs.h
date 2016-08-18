#ifndef LIBBBS_H
#define LIBBBS_H

#include "libsys.h"
#include "ext_config.h"
#include "bbs/consts.h"
#include "bbs/struct.h"
#include "bbs/shmkey.h"
#include "bbs/macros.h"
#include "bbs/permissions.h"
#include "bbs/modes.h"

/* from bbsd.c in src and login.c in websrc */
#define BAD_HOST                BBSHOME"/etc/bad_host"


/* management of global value */
/* ext_session should be the only global variable */

struct session_t {

	struct BCACHE *brdshm;
	struct UCACHE *uidshm;
	struct UTMPFILE *utmpshm;
	struct boardheader *bcache;	/* brdshm->bcache */
	int numboards;				/* number of boards in brdshm */
	int usernumber;				/* number of users in uidshm  */

} ext_session;

#ifndef THREADSAFE
#define sessionVar() (&ext_session)
#endif	/* THREADSAFE */


/* pass.c */
int checkpasswd2(const char *passwd, struct userec *user);


/* record.c */
int get_record(char *filename, void *rptr, int size, int id);
int delete_record(char *filename, int size, int id);
int apply_record(char *filename, int (*fptr)(void *, int), int size);
int search_record_forward(char *filename, void *rptr, int size, int start, int (*fptr)(void *, void *), void *farg);
int get_num_records(char *filename, int size);
int append_record(char *filename, void *record, int size);
int substitute_record(char *filename, void *rptr, int size, int id);

/* cache.c */
void resolve_boards();
void resolve_ucache();
void resolve_utmp();


/* bbs.c */
int getuser(char *userid, struct userec *lookupuser);
struct boardheader* getbcache(char *board);


#endif // LIBBBS_H

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */

