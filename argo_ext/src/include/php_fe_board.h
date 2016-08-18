#ifndef PHP_FE_BOARD_H
#define PHP_FE_BOARD_H

PHP_FUNCTION(ext_update_lastpost);
PHP_FUNCTION(ext_board_header);
PHP_FUNCTION(ext_get_denyheader);
PHP_FUNCTION(ext_brctotalpost);
PHP_FUNCTION(ext_gettopicfiles);
PHP_FUNCTION(ext_getpostlist);
PHP_FUNCTION(ext_getsections);
PHP_FUNCTION(ext_getboards);
PHP_FUNCTION(ext_getfavboards);
PHP_FUNCTION(ext_gettopiclist);
PHP_FUNCTION(ext_getfileheader);
PHP_FUNCTION(ext_delete_post);
PHP_FUNCTION(ext_get_allboards);

#define PHP_FE_BOARD_EXPORT_FUNCTIONS			\
	PHP_FE(ext_update_lastpost, NULL)			\
	PHP_FE(ext_board_header, NULL)				\
    PHP_FE(ext_get_denyheader, NULL)				\
	PHP_FE(ext_brctotalpost, NULL)				\
	PHP_FE(ext_gettopicfiles, NULL)				\
	PHP_FE(ext_getpostlist, NULL)				\
	PHP_FE(ext_getsections, NULL)				\
	PHP_FE(ext_getboards, NULL)					\
	PHP_FE(ext_getfavboards, NULL)				\
    PHP_FE(ext_gettopiclist, NULL)				\
    PHP_FE(ext_getfileheader, NULL)				\
    PHP_FE(ext_delete_post, NULL)				\
    PHP_FE(ext_get_allboards, NULL)				\

    


#endif //PHP_FE_BOARD_H

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
