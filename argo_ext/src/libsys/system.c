/*
 * add for argo_ext
 */

#include "libsys.h"

struct stat* f_stat (const char *file) {
        static struct stat buf;
        
        memset(&buf, 0, sizeof(buf));
        if(stat(file, &buf)==-1) memset(&buf, 0, sizeof(buf));
        return &buf;
}



/* only accept O_RDWR and O_RDONLY */
int mmapfile(const char* filepath, int flag, char **ret_ptr, off_t *size, int *ret_fd)
{
	int fd;
	struct stat st;

	if (flag != O_RDWR && flag != O_RDONLY)
		return 0;
	if ((fd = open(filepath, flag)) < 0)
		return 0;
	if (fstat(fd, &st) < 0 || !S_ISREG(st.st_mode) || st.st_size <= 0) {
		close(fd);
		return 0;
	}

	if (flag == O_RDWR)
		*ret_ptr = mmap(NULL, st.st_size, PROT_READ | PROT_WRITE, MAP_SHARED, fd, 0);
	else 
		*ret_ptr = mmap(NULL, st.st_size, PROT_READ, MAP_SHARED, fd, 0);

	if (*ret_ptr == MAP_FAILED) {
		close(fd);
		return 0;
	}
	
	ret_fd == NULL ? close(fd) : (*ret_fd = fd);

	*size = st.st_size;
	return 1;
}

void munmapfile(void *ptr, off_t size, int fd)
{
	munmap(ptr, size);
	if (fd != -1)
		close(fd);
}



/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */

