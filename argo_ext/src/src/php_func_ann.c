#include "ext_prototype.h"


/* read directly from .DIR */
/* not allow "@GROUP:",  handle it separately */
PHP_FUNCTION(ext_annpath)
{

	char fname[512];
	struct annheader ah;
	zval *z;
	FILE *fp;
	int total, i;
	
	char *path;
	int plen;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &path, &plen) == FAILURE)
		WRONG_PARAM_COUNT;

    if(strstr(path, ".."))  RETURN_NULL();
    
	chdir(BBSHOME);
	snprintf(fname, sizeof(fname) - 6, "0Announce/%s", path);

	if (!file_isdir(fname)) RETURN_NULL();

	strcat(fname, "/.DIR");
	total = file_size(fname) / sizeof(ah);
	if (total == 0) RETURN_NULL();
	
	fp = fopen(fname, "r");
	array_init(return_value);
	for (i = 0; i < total; i++) {
		fread(&ah, sizeof(ah), 1, fp);
		MAKE_STD_ZVAL(z);
		array_init(z);
		add_assoc_long(z, "flag", ah.flag);
		add_assoc_long(z, "mtime", ah.mtime);
		add_assoc_string(z, "filename", ah.filename, 1);
		add_assoc_string(z, "title", ah.title, 1);
		add_assoc_string(z, "owner", ah.owner, 1);
		add_assoc_string(z, "editor", ah.editor, 1);
		add_index_zval(return_value, i + 1, z);
	}
    fclose(fp);	
}


/* argument: seccode */
/* not path, NULL represents all */
PHP_FUNCTION(ext_annpath_group)
{

	char fname[512];
	struct boardheader *bhp;
	zval *z;
	int i, j;
	time_t t;
	
	char *seccode;
	int seclen;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &seccode, &seclen) == FAILURE)
		WRONG_PARAM_COUNT;

	chdir(BBSHOME);

	t = time(0);
	array_init(return_value);
	for (i = 0, j = 1; i < MAXBOARD; i++) {
		bhp = &(sessionVar()->bcache[i]);

		if (bhp->filename[0] <= 32 || bhp->filename[0] > 'z')
			continue;
		if (seclen != 0 && !strchr(seccode, bhp->title[0]))
			continue;
		
		MAKE_STD_ZVAL(z);
		array_init(z);
		add_assoc_long(z, "flag", 2);
		add_assoc_string(z, "mtime", ctime(&t), 1);
		add_assoc_string(z, "filename", bhp->filename, 1);
		add_assoc_string(z, "title", bhp->title + 8, 1);
		add_index_zval(return_value, j++, z);
	}
	
}

/*
 * type 0: raw
 * type 1: html
 */
PHP_FUNCTION(ext_annfile)
{
	char buf[512];
	char *content, *output;
	off_t size;
	
	char *path;
	int plen, type;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sl", &path, &plen, &type) == FAILURE)
		WRONG_PARAM_COUNT;

	chdir(BBSHOME);

    if(strstr(path, "..")) RETURN_NULL();
    
	snprintf(buf, sizeof(buf), "0Announce/%s", path);
    
    if (file_isdir(path)) RETURN_NULL();

	if (mmapfile(buf, O_RDONLY, &content, &size, NULL) == 0) {
		RETURN_NULL();
	}
    
    if (type == 1) {
        php_start_ob_buffer(NULL, 0, 0 TSRMLS_CC);
        html_print_buffer(content, size);
        php_ob_get_buffer(return_value TSRMLS_CC);
        php_end_ob_buffer(0, 0 TSRMLS_CC);
    } else  if (type == 0) {
        ZVAL_STRINGL(return_value, content, size, 1);
    }
    
    munmapfile(content, size, -1);    
}

PHP_FUNCTION(ext_file_exists)
{
    char *path;
	int plen;
    int fd;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &path, &plen) == FAILURE)
	    WRONG_PARAM_COUNT;

	chdir(BBSHOME);

    if(strstr(path, "..")) RETURN_FALSE;
    
    if((fd = open(path, O_CREAT | O_EXCL, 0644))<0) {
        RETURN_TRUE;
    }  else {
        RETURN_FALSE;
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
