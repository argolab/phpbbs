#ifndef PHP_FE_POST_H
#define PHP_FE_POST_H

PHP_FUNCTION(ext_simplepost);
PHP_FUNCTION(ext_editpost);
PHP_FUNCTION(ext_read_post);
PHP_FUNCTION(ext_read_digest);
PHP_FUNCTION(ext_quote_post);
PHP_FUNCTION(ext_post_content_classify);


#define PHP_FE_POST_EXPORT_FUNCTIONS			\
	PHP_FE(ext_simplepost, NULL)				\
    PHP_FE(ext_editpost, NULL)                  \
	PHP_FE(ext_read_post, NULL)					\
	PHP_FE(ext_read_digest, NULL)				\
	PHP_FE(ext_quote_post, NULL)				\
    PHP_FE(ext_post_content_classify,NULL)      \
    


#endif //PHP_FE_POST_H

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
