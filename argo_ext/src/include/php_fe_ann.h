#ifndef PHP_FE_ANN_H
#define PHP_FE_ANN_H

PHP_FUNCTION(ext_annpath);
PHP_FUNCTION(ext_annpath_group);
PHP_FUNCTION(ext_annfile);
PHP_FUNCTION(ext_file_exists);

#define PHP_FE_ANN_EXPORT_FUNCTIONS		\
	PHP_FE(ext_annpath, NULL)		\
	PHP_FE(ext_annpath_group, NULL)		\
	PHP_FE(ext_annfile, NULL)		\
    PHP_FE(ext_file_exists, NULL)		\



#endif //PHP_FE_ANN_H

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
