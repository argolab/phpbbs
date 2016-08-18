#include "ext_prototype.h"

/* search_recordÊ±ÓÃµ½ */
int cmp_filename(void *header, void *filename) {
	struct fileheader *f = (struct fileheader *)header;
	return (strncmp(f->filename, filename, sizeof(f->filename)) == 0);
}

int 
normalboard(char* bname)
{
	struct boardheader *bp;
	
    if ((bp = getbcache(bname)) == NULL)
		return NA;
	if (bp->flag & (ANONY_FLAG | JUNK_FLAG | BRD_RESTRICT | BRD_NOPOSTVOTE))
		return NA;
	
	return (bp->level == 0) ? YEA : NA;
}

   
int 
if_exist_id(const char *userid, unsigned int id)
{
        static struct mypostlog my_posts;
        char buf1[256];
        int n;
        FILE *fp;
                 
        snprintf(buf1, sizeof(buf1), "home/%c/%s/%s", mytoupper(userid[0]), userid, "my_posts");
        if ((fp = fopen(buf1, "r+")) == NULL) {
                if ((fp = fopen(buf1, "w+")) == NULL)
                        return 0;
        }
        fread(&my_posts, sizeof (my_posts), 1, fp);
        for (n = 0; n < 64; n++)
		if (my_posts.id[n] == id) {		
                        fclose(fp);
                        return 1;
                };
        my_posts.hash_ip = (my_posts.hash_ip + 1) & 63;
        my_posts.id[my_posts.hash_ip] = id;
        fseek(fp, 0, SEEK_SET);
        fwrite(&my_posts, sizeof (my_posts), 1, fp);
        fclose(fp);
        return 0;
}


/* copy from post.c : write_posts */
int 
add_post(const char *board, unsigned int id, const char *userid)
{
    struct postlog log;

    if (normalboard(board) == NA) return YEA;

    strlcpy(log.board, board, BFNAMELEN);
    log.id = id;
    log.date = time(NULL);
    log.number = 1;

    if (!if_exist_id(userid, id))
        append_record(".post", &log, sizeof(log));
    append_record(".post2", &log, sizeof(log));

    return YEA;

}


