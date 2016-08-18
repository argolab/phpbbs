#ifndef PHP_FE_HOME_H
#define PHP_FE_HOME_H

/* 管理 home 目录的文件，包括各种用户档案 */

PHP_FUNCTION(ext_is_in_restrict_board);
PHP_FUNCTION(ext_is_read);
PHP_FUNCTION(ext_get_readmark);
PHP_FUNCTION(ext_mark_read);
PHP_FUNCTION(ext_delfavboards);
PHP_FUNCTION(ext_addfavboards);
PHP_FUNCTION(ext_get_whole_file);
PHP_FUNCTION(ext_set_whole_file);
PHP_FUNCTION(ext_get_signatures);
PHP_FUNCTION(ext_get_www);
PHP_FUNCTION(ext_set_www);
PHP_FUNCTION(ext_add_override);
PHP_FUNCTION(ext_del_override);
PHP_FUNCTION(ext_get_override);
PHP_FUNCTION(ext_get_attacheader);
PHP_FUNCTION(ext_get_attachlist);
PHP_FUNCTION(ext_del_attach);
PHP_FUNCTION(ext_upload_attach);
PHP_FUNCTION(ext_add_msg);
PHP_FUNCTION(ext_get_msglist);
PHP_FUNCTION(ext_message_markread);


#define PHP_FE_HOME_EXPORT_FUNCTIONS			\
	PHP_FE(ext_is_in_restrict_board, NULL)		\
	PHP_FE(ext_is_read, NULL)					\
	PHP_FE(ext_mark_read, NULL)					\
	PHP_FE(ext_get_readmark, NULL)					\
    PHP_FE(ext_delfavboards, NULL)                      \
    PHP_FE(ext_addfavboards, NULL)                      \
    PHP_FE(ext_get_whole_file, NULL)					\
    PHP_FE(ext_set_whole_file, NULL)					\
    PHP_FE(ext_get_signatures, NULL)					\
    PHP_FE(ext_get_www, NULL)					\
    PHP_FE(ext_set_www, NULL)					\
    PHP_FE(ext_add_override, NULL)					\
    PHP_FE(ext_del_override, NULL)					\
    PHP_FE(ext_get_override, NULL)					\
    PHP_FE(ext_get_attacheader, NULL)					\
    PHP_FE(ext_get_attachlist, NULL)					\
    PHP_FE(ext_del_attach, NULL)					\
    PHP_FE(ext_upload_attach, NULL)					\
    PHP_FE(ext_add_msg, NULL)					\
    PHP_FE(ext_get_msglist, NULL)					\
    PHP_FE(ext_message_markread, NULL)					\    


#endif //PHP_FE_HOME_H

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
