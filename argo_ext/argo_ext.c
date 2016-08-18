/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2008 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author:                                                              |
  +----------------------------------------------------------------------+
*/

/* $Id: header,v 1.16.2.1.2.1.2.1 2008/02/07 19:39:50 iliaa Exp $ */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_argo_ext.h"
#include "ext_consts.h"
#include "ext_prototype.h"

ZEND_DECLARE_MODULE_GLOBALS(argo_ext)

/* True global resources - no need for thread safety here */
/* int le_argo_ext; */

const zend_function_entry argo_ext_functions[] = {
	PHP_FE_ALL_EXPORT_FUNCTIONS	/* from php_fe_all.h */
	{NULL, NULL, NULL}
};


zend_module_entry argo_ext_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	EXTENSION_NAME,
	argo_ext_functions,
	PHP_MINIT(argo_ext),
	PHP_MSHUTDOWN(argo_ext),
	PHP_RINIT(argo_ext),
	PHP_RSHUTDOWN(argo_ext),
	PHP_MINFO(argo_ext),
#if ZEND_MODULE_API_NO >= 20010901
	ARGO_EXT_VERSION,
#endif
	STANDARD_MODULE_PROPERTIES
};


#ifdef COMPILE_DL_ARGO_EXT
ZEND_GET_MODULE(argo_ext)
#endif


PHP_INI_BEGIN()
STD_PHP_INI_ENTRY("argo_ext.global_value",      "42", PHP_INI_ALL, OnUpdateLong, global_value, zend_argo_ext_globals, argo_ext_globals)
PHP_INI_ENTRY("argo_ext.global_string", "foobar", PHP_INI_ALL, NULL)
PHP_INI_END()

static void php_argo_ext_init_globals(zend_argo_ext_globals *argo_ext_globals) {}

PHP_MINIT_FUNCTION(argo_ext)
{
	ZEND_INIT_MODULE_GLOBALS(argo_ext, php_argo_ext_init_globals, NULL);
	REGISTER_ALL_PHP_CONSTANT;	/* from consts.h */
	REGISTER_INI_ENTRIES();

	chdir(BBSHOME);
	sessionVar()->utmpshm = NULL;
	sessionVar()->uidshm = NULL;
	sessionVar()->brdshm = NULL;
	resolve_boards();
	resolve_utmp();
	resolve_ucache();
	
	return SUCCESS;
}


PHP_MSHUTDOWN_FUNCTION(argo_ext)
{
	UNREGISTER_INI_ENTRIES();
	return SUCCESS;
}


PHP_RINIT_FUNCTION(argo_ext)
{
	return SUCCESS;
}

PHP_RSHUTDOWN_FUNCTION(argo_ext)
{
	return SUCCESS;
}

PHP_MINFO_FUNCTION(argo_ext)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "argo_ext support", "enabled");
	php_info_print_table_end();

	/* Remove comments if you have entries in php.ini
	   DISPLAY_INI_ENTRIES();
	*/
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */

