#include "php.h"

/* get data from associative array by key */
/* key_len = strlen(key) + 1 */
int zval_array_get_str(zval *arr, const char *key, unsigned int key_len,
						 char **out_data, unsigned int *out_len) {
	zval **data;
	HashTable *arr_hash;
	HashPosition pointer;
	int array_count;

	char *ikey;
	int ilen;
	long index;
	
	arr_hash = Z_ARRVAL_P(arr);
	array_count = zend_hash_num_elements(arr_hash);
    
	for (zend_hash_internal_pointer_reset_ex(arr_hash, &pointer);
	     zend_hash_get_current_data_ex(arr_hash, (void**) &data, &pointer) == SUCCESS;
	     zend_hash_move_forward_ex(arr_hash, &pointer)) {

		if (zend_hash_get_current_key_ex(arr_hash, &ikey, &ilen, &index, 0, &pointer)
		    != HASH_KEY_IS_STRING) continue;
		if (key_len != ilen) continue;
		if (strncmp(key, ikey, ilen) != 0) continue;
		
		*out_data = Z_STRVAL_PP(data);
		*out_len = Z_STRLEN_PP(data);
		return 0;
	}

	*out_data = NULL;
	*out_len = 0;
	return -1;
}

int zval_array_get_long(zval *arr, const char *key, unsigned int key_len, long *out) {
	zval **data;
	HashTable *arr_hash;
	HashPosition pointer;
	int array_count;

	char *ikey;
	int ilen;
	long index;
	
	arr_hash = Z_ARRVAL_P(arr);
	array_count = zend_hash_num_elements(arr_hash);
    
	for (zend_hash_internal_pointer_reset_ex(arr_hash, &pointer);
	     zend_hash_get_current_data_ex(arr_hash, (void**) &data, &pointer) == SUCCESS;
	     zend_hash_move_forward_ex(arr_hash, &pointer)) {

		if (zend_hash_get_current_key_ex(arr_hash, &ikey, &ilen, &index, 0, &pointer)
		    != HASH_KEY_IS_STRING) continue;
		if (key_len != ilen) continue;
		if (strncmp(key, ikey, ilen) != 0) continue;
		*out = Z_LVAL_PP(data);
		return 0;
	}

	*out = 0;
	return -1;
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
