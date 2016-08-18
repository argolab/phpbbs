PHP_ARG_ENABLE(argo_ext, whether to enable argo_ext support,
[  --enable-cli           Enable cli support])

if test "$PHP_ARGO_EXT" != "no"; then

     PHP_ADD_INCLUDE(src/include)
     PHP_ADD_INCLUDE(src/libsys)
     PHP_ADD_INCLUDE(src/libbbs)

     PHP_NEW_EXTENSION(argo_ext, argo_ext.c \
     src/src/zval.c \
     src/src/string.c \
     src/src/stuff.c \
     src/src/php_func_ann.c \
     src/src/php_func_post.c \
     src/src/php_func_home.c \
     src/src/php_func_mail.c \
     src/src/php_func_user.c \
     src/src/php_func_board.c \
     src/src/php_func_utmp.c \
     src/libsys/crypt.c \
     src/libsys/fileio.c \
     src/libsys/string.c \
     src/libsys/system.c \
     src/libbbs/bbs.c \
     src/libbbs/report.c \
     src/libbbs/pass.c \
     src/libbbs/cache.c \
     src/libbbs/record.c,
     $ext_shared)

fi
