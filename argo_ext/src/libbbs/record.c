#include"libbbs.h"

sigjmp_buf jmpbuf;

void
sigfault(int signo)
{
        /* recover from memory violation */
        siglongjmp(jmpbuf, signo);
}

//id 从1开始
int
get_record(char *filename, void *rptr, int size, int id)
{
	int fd;

	if ((fd = open(filename, O_RDONLY, 0)) == -1) {
		return -1;
	}
	if (lseek(fd, (off_t) (size * (id - 1)), SEEK_SET) == -1) {
		close(fd);
		return -1;
	}
	if (read(fd, rptr, size) != size) {
		close(fd);
		return -1;
	}
	close(fd);
	return 0;
}

int
delete_record(char *filename, int size, int id)
{
	int fd, result = 0;
	char *buf;
	struct stat st;

	if (id <= 0 || size <= 0)
		return -1;

	if ((fd = open(filename, O_RDWR, 0)) == -1)
		return -1;

	if (fstat(fd, &st) < 0) {
		close(fd);
		return -1;
	}

	f_exlock(fd);
	buf = mmap(NULL, st.st_size, PROT_READ | PROT_WRITE, MAP_SHARED | MAP_FILE, fd, 0);
	if (buf == MAP_FAILED || st.st_size <= 0 || id * size > st.st_size) {
		close(fd);
		return -1;
	}

	if (id * size < st.st_size) {
		TRY
			memmove(buf + size * (id - 1), buf + size * id, st.st_size - size * id);
		CATCH
			result = -1;
		END
	}

	munmap(buf, st.st_size);
	ftruncate(fd, size * (st.st_size / size - 1));
	close(fd);
	return result;
}

/* monster: fptr should not modify pointer specified by first parameter */
int
apply_record(char *filename, int (*fptr)(void *, int), int size)
{
	void *buf, *buf1;
	int fd, i = 0, id = 0;
	struct stat st;

	if ((fd = open(filename, O_RDONLY, 0)) == -1)
		return -1;

	if (fstat(fd, &st) < 0 || st.st_size < 0) {
		close(fd);
		return -1;
	}

	if (st.st_size == 0) {
		close(fd);
		return 0;
	}

	buf = mmap(NULL, st.st_size, PROT_READ, MAP_SHARED | MAP_FILE, fd, 0);
	close(fd);
	if (buf == MAP_FAILED) {
		return -1;
        }

	buf1 = buf;
	TRY
		while (i < st.st_size) {
			if ((*fptr) (buf1, ++id) == QUIT) {
				BREAK;
				munmap(buf, st.st_size);
				return QUIT;
			}
			i += size;
			buf1 += size;
		}
	END 

	munmap(buf, st.st_size);
	return 0;
}

int
search_record_forward(char *filename, void *rptr, int size, int start, int (*fptr)(void *, void *), void *farg)
{
	int fd, id = 1;
	void *buf, *buf1, *buf2;
	struct stat st;

	if (start <= 0)
		return 0; 

	if ((fd = open(filename, O_RDONLY, 0)) == -1)
		return 0;

	if (fstat(fd, &st) < 0) {
		close(fd);
		return 0;
	}

	buf = mmap(NULL, st.st_size, PROT_READ, MAP_SHARED | MAP_FILE, fd, 0);
	close(fd);
	if (buf == MAP_FAILED || st.st_size <= 0) {
		return 0;
	}

	buf1 = buf + (start - 1) * size;
	buf2 = buf + st.st_size;

	TRY
		while (buf1 < buf2) {
			if ((*fptr) (farg, buf1)) {
				memcpy(rptr, buf1, size);
				BREAK;
				munmap(buf, st.st_size);
				return id;
			}
			buf1 += size;
			id++;
		}
	END

	munmap(buf, st.st_size);
	return 0;
}

int
get_num_records(char *filename, int size)
{
	struct stat st;

	if (stat(filename, &st) == -1)
		return 0;
	return (st.st_size / size);
}


int
safewrite(int fd, void *buf, int size)
{
	int cc, sz = size, origsz = size;
	char *bp = buf;

	do {
		cc = write(fd, bp, sz);
		if ((cc < 0) && (errno != EINTR)) {
			return -1;
		}
		if (cc > 0) {
			bp += cc;
			sz -= cc;
		}
	}
	while (sz > 0);
	return origsz;
}

int
append_record(char *filename, void *record, int size)
{
	int fd;

	if ((fd = open(filename, O_WRONLY | O_CREAT, 0644)) == -1) {
		return -1;
	}
	f_exlock(fd);
	lseek(fd, 0, SEEK_END);
	safewrite(fd, record, size);
	f_unlock(fd);
	close(fd);
	return 0;
}



int
substitute_record(char *filename, void *rptr, int size, int id)
{
	int fd, err = 0;

	if (id < 1)
		return -1;

	if ((fd = open(filename, O_WRONLY | O_CREAT, 0644)) == -1)
		return -1;

	f_exlock(fd);
	if (lseek(fd, (off_t) (size * (id - 1)), SEEK_SET) == -1) {
		// report("substitue_record: seek error in %s, id %d", filename, id);
		err = -1;
	} else if (safewrite(fd, rptr, size) != size) {
		// report("substitue_record: cannot substitue record in %s, id %d", filename, id);
		err = -1;
	}
	close(fd);

	return err;
}
