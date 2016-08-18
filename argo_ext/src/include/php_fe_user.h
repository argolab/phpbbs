#ifndef PHP_FE_USER_H
#define PHP_FE_USER_H

/*  */

PHP_FUNCTION(ext_checkpassword);
PHP_FUNCTION(ext_get_urec);
PHP_FUNCTION(ext_update_urec);
PHP_FUNCTION(ext_get_uinfo);
PHP_FUNCTION(ext_igenpass);
PHP_FUNCTION(ext_post_stat);
PHP_FUNCTION(ext_is_user_exist);
PHP_FUNCTION(ext_get_total);
PHP_FUNCTION(ext_in_validate_ip_range);
PHP_FUNCTION(ext_count_register);
PHP_FUNCTION(ext_security_report);

#define PHP_FE_USER_EXPORT_FUNCTIONS			\
	PHP_FE(ext_checkpassword, NULL)				\
	PHP_FE(ext_get_urec, NULL)					\
	PHP_FE(ext_update_urec, NULL)				\
    PHP_FE(ext_get_uinfo, NULL)				\
    PHP_FE(ext_igenpass, NULL)				\
    PHP_FE(ext_post_stat, NULL)				\
    PHP_FE(ext_is_user_exist, NULL)         \
    PHP_FE(ext_get_total, NULL)         \
    PHP_FE(ext_in_validate_ip_range, NULL)         \
	PHP_FE(ext_count_register, NULL)         \
	PHP_FE(ext_security_report, NULL)         \

#endif //PHP_FE_USER_H

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
