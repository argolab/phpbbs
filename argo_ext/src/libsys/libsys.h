/* most copy from libSystem */

#ifndef LIBSYS_H
#define LIBSYS_H

#include <stdio.h>
#include <stdlib.h>
#include <fcntl.h>
#include <sys/file.h>
#include <sys/stat.h>
#include <sys/mman.h>
#include <sys/types.h>
#include <sys/ipc.h>
#include <sys/shm.h>
#include <dirent.h>
#include <string.h>
#include <unistd.h>
#include <signal.h>
#include <setjmp.h>
#include <errno.h>
#include <ctype.h>
#include <time.h>		/* for time_t prototype */
#include "sysdep.h"



/* crypt.c */
typedef struct {
	unsigned long A, B, C, D;
	unsigned long Nl, Nh;
	unsigned long data[16];
	int num;
} MD5_CTX;
void MD5Init(MD5_CTX *);
void MD5Update(MD5_CTX *, const unsigned char *, unsigned int);
void MD5Final(MD5_CTX *, unsigned char[16]);
char *crypt_des(char *buf, char *salt);

/* fileio.c */
int file_append(char *fpath, char *msg);
int file_appendfd(char *fpath, char *msg, int *fd);
int file_appendline(char *fpath, char *msg);
int dashf(char *fname);
int dashd(char *fname);
int dash(char *fname);
int part_cp(char *src, char *dst, char *mode);
int f_cp(char *src, char *dst, int mode);
int valid_fname(char *str);
int touchfile(char *filename);
int f_rm(char *fpath);
int f_mv(char *src, char *dst);
int f_mkdir(char *path, int omode);
int f_exlock(int fd);
int f_unlock(int fd);
int filelock(char *filename, int block);
int fileunlock(int fd);
int seek_in_file(char *filename, char *seekstr);

/* string.c */
char *substr(char *string, int from, int to);
char *stringtoken(char *string, char tag, int *log);
void strtolower(char *dst, char *src);
void strtoupper(char *dst, char *src);
int killwordsp(char *str);
int is_alpha(int ch);
void my_ansi_filter(char *source);
char *ansi_filter(char *source);
char *Cdate(time_t * clock);
char *strstr2(char *s, char *s2);
char *strstr2n(char *s, char *s2, size_t size);
void fixstr(char *str, char *fixlist, char ch);
void trim(char *str);
size_t strlcpy(char *dst, const char *src, size_t siz);
size_t strlcat(char *dst, const char *src, size_t siz);
char *strcasestr(const char *phaystack, const char *pneedle);

int inset(char *set, char c);
char* strsect(char *str, char *sect, char *delim);


/* system.c */

struct stat* f_stat (const char *file);
int mmapfile(const char* filepath, int flag, char **ret_ptr, off_t *size, int *ret_fd);
void munmapfile(void *ptr, off_t size, int fd);

#define file_size(x) f_stat(x)->st_size
#define file_time(x) f_stat(x)->st_mtime
#define file_rtime(x) f_stat(x)->st_atime
#define file_exist(x) (file_time(x)!=0)
#define file_isdir(x) ((f_stat(x)->st_mode & S_IFDIR)!=0)
#define file_isfile(x) ((f_stat(x)->st_mode & S_IFREG)!=0)

#endif // LIBSYS_H


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */

