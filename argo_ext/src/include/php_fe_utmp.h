#ifndef PHP_FE_UTMP_H
#define PHP_FE_UTMP_H

/* 文章索引读取和查询 */


PHP_FUNCTION(ext_attach_utmp);
PHP_FUNCTION(ext_insert_utmp);
PHP_FUNCTION(ext_remove_utmp);
PHP_FUNCTION(ext_update_utmp);
PHP_FUNCTION(ext_kick_multi);

#define PHP_FE_UTMP_EXPORT_FUNCTIONS			\
	PHP_FE(ext_attach_utmp, NULL)				\
	PHP_FE(ext_insert_utmp, NULL)				\
	PHP_FE(ext_remove_utmp, NULL)				\
	PHP_FE(ext_update_utmp, NULL)				\
	PHP_FE(ext_kick_multi, NULL)				\
	


#endif //PHP_FE_UTMP_H

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
