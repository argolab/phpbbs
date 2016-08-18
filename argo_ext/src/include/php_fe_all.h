#ifndef PHP_FE_ALL_H
#define PHP_FE_ALL_H

#include "php_fe_home.h"
#include "php_fe_mail.h"
#include "php_fe_post.h"
#include "php_fe_user.h"
#include "php_fe_board.h"
#include "php_fe_utmp.h"
#include "php_fe_ann.h"

#define PHP_FE_ALL_EXPORT_FUNCTIONS				\
	PHP_FE_HOME_EXPORT_FUNCTIONS				\
	PHP_FE_MAIL_EXPORT_FUNCTIONS				\
	PHP_FE_POST_EXPORT_FUNCTIONS				\
	PHP_FE_USER_EXPORT_FUNCTIONS				\
	PHP_FE_BOARD_EXPORT_FUNCTIONS				\
	PHP_FE_ANN_EXPORT_FUNCTIONS					\
	PHP_FE_UTMP_EXPORT_FUNCTIONS				\
	
	

#endif // PHP_FE_ALL_H

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */

