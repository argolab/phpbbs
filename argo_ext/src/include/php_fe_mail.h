#ifndef PHP_FE_MAIL_H
#define PHP_FE_MAIL_H

/* mailœ‡πÿ */


PHP_FUNCTION(ext_count_mail);
PHP_FUNCTION(ext_list_mail);
PHP_FUNCTION(ext_read_mail);
PHP_FUNCTION(ext_mark_read_mail);
PHP_FUNCTION(ext_send_mail);
PHP_FUNCTION(ext_quote_mail);
PHP_FUNCTION(ext_check_mail);
PHP_FUNCTION(ext_del_mail);
PHP_FUNCTION(ext_mark_replied);
PHP_FUNCTION(ext_mark_replied_mail);
PHP_FUNCTION(ext_used_mail_size);

#define PHP_FE_MAIL_EXPORT_FUNCTIONS			\
	PHP_FE(ext_count_mail, NULL)				\
	PHP_FE(ext_list_mail, NULL)					\
	PHP_FE(ext_read_mail, NULL)					\
	PHP_FE(ext_mark_read_mail, NULL)			\
	PHP_FE(ext_send_mail, NULL)					\
	PHP_FE(ext_quote_mail, NULL)				\
	PHP_FE(ext_check_mail, NULL)				\
	PHP_FE(ext_del_mail, NULL)					\
	PHP_FE(ext_mark_replied, NULL)				\
	PHP_FE(ext_mark_replied_mail, NULL)				\
    PHP_FE(ext_used_mail_size, NULL)				\



#endif //PHP_FE_MAIL_H

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
