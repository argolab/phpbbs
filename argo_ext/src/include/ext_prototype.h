#ifndef EXT_PROTOTYPE_H
#define EXT_PROTOTYPE_H

#include "php.h"
#include "libbbs.h"

/* src/zval.c */
int zval_array_get_str(zval *arr, const char *key, unsigned int key_len,
			 char **out_data, unsigned int *out_len);
int zval_array_get_long(zval *arr, const char *key, unsigned int key_len, long *out);

/* src/string.c */
int html_print_buffer(char *from, int size); 
char *nohtml(char *s);
char *safe_strcat(char *str1, const char *str2, int catlen, int *len);

/* src/stuff.c */
int cmp_filename(void *header, void *filename);

/*src/php_func_board.c */
int cmpfilename(void *filename, void *fh);
    
#endif // EXT_PROTOTYPE_H


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */

