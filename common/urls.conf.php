<?php

return array (
              
    /****************   new data interface for ajax (all using json)   ****************/
              
    '{^/?$}' => array('home.php', 'handle_home'),

    //    '{^/ajax/v2/post/topic$}' => array('handler/post.php', 'api_get_post_in_topic'),
    '{^/ajax/v2/post/addpost$}' => array('handler/post.php', 'ajax_addpost'),
    '{^/ajax/v2/post/delpost$}' => array('handler/post.php', 'ajax_delpost'),
    '{^/ajax/v2/post/topicinfo$}' => array('handler/post.php', 'api_topicinfo'),
    
    '{^/ajax/v2/post/mine$}' => array('handler/post.php', 'api_get_my_part_topic'),
    '{^/ajax/v2/post/vote/topic$}' => array('handler/post.php', 'api_vote_topic'),
    '{^/ajax/v2/top/topic$}' => array('handler/post.php', 'api_get_top_score_topic'),

    '{^/ajax/v2/post/byboard$}' => array('handler/post.php', 'api_board_topic'),

    '{^/ajax/section/?$}'   => array('ajax/ajax-section.php',	'ajax_get_sections'),
    '{^/ajax/ann/content/?$}' => array('ajax/ajax-ann.php', 'ajax_ann_content'),

    '{^/ajax/geninvcode/?$}'     => array('ajax/ajax-user.php',
                                          'ajax_gen_invcode'),

    '{^/ajax/auth/invcode/?$}'     => array('ajax/ajax-user.php',
                                            'ajax_auth_invcode'), // POST
    
    '{^/ajax/register3/?$}'     => array('ajax/ajax-user.php',	'ajax_register3'), // POST
    '{^/ajax/register/?$}'     => array('ajax/ajax-user.php',	'ajax_register'), // POST
//    '{^/ajax/register2/?$}'     => array('ajax/ajax-user.php',	'ajax_register2'), // POST
    '{^/ajax/auth/info/?$}'     => array('ajax/ajax-user.php',	'ajax_auth_info'), // POST
    '{^/ajax/auth/netid/?$}'     => array('ajax/ajax-user.php',	'ajax_auth_netid'), 
    '{^/ajax/login/?$}'     => array('ajax/ajax-user.php',	'ajax_login'), // POST
	'{^/ajax/logout/?$}'    => array('ajax/ajax-user.php',	'ajax_logout'), //POST
	'{^/ajax/friend/?$}'    => array('ajax/ajax-user.php',	'ajax_friend'), 
	'{^/ajax/addfriend/?$}'    => array('ajax/ajax-user.php',	'ajax_add_friend'),  //POST
	'{^/ajax/delfriend/?$}'    => array('ajax/ajax-user.php',	'ajax_del_friend'), //POST
    '{^/ajax/user/fav/?$}'  => array('ajax/ajax-user.php',	'ajax_getfav'),
    '{^/ajax/user/myinv/?$}'  => array('ajax/ajax-user.php',	'ajax_self_inv'),
    '{^/ajax/user/addfav/?$}' => array('ajax/ajax-user.php',	'ajax_addfav'), // POST
    '{^/ajax/user/delfav/?$}' => array('ajax/ajax-user.php',	'ajax_delfav'), // POST
    '{^/ajax/user/setfav/?$}' => array('ajax/ajax-user.php',	'ajax_setfav'), // POST
	'{^/ajax/user/query/?$}'  => array('ajax/ajax-user.php',	'ajax_user_query'),
	'{^/ajax/user/update/?$}' => array('ajax/ajax-user.php',	'ajax_user_update'),// POST
	'{^/ajax/user/info/?$}' => array('ajax/ajax-user.php',	'ajax_user_info'),
	'{^/ajax/user/setting/get?$}' => array('ajax/ajax-user.php',	'ajax_user_get_setting'),
	'{^/ajax/user/setting/update?$}' => array('ajax/ajax-user.php',	'ajax_user_update_setting'),

    '{^/ajax/board/good/?$}' => array('ajax/ajax-board.php',	'ajax_goodboards'),
    '{^/ajax/board/all/?$}' => array('ajax/ajax-board.php',	'ajax_allboards'),
    '{^/ajax/board/random/?$}' => array('ajax/ajax-board.php',	'ajax_random_boardname'),
    '{^/ajax/board/next/?$}' => array('ajax/ajax-board.php',	'ajax_next_boardname'),
    '{^/ajax/board/alls/?$}' => array('ajax/ajax-board.php',	'ajax_allboards_sec'),
    '{^/ajax/board/get/?$}' => array('ajax/ajax-board.php',	'ajax_getboard'),
    '{^/ajax/board/notes/?$}' => array('ajax/ajax-board.php',	'ajax_boardnotes'),
    '{^/ajax/board/getbysec/?$}'  => array('ajax/ajax-board.php',	'ajax_getbysec'),
	'{^/ajax/board/clear/?$}' => array('ajax/ajax-board.php',	'ajax_clearunread'), // POST
    '{^/ajax/board/readmark/?$}' => array('ajax/ajax-board.php',	'ajax_get_readmark'),
    '{^/ajax/board/setwww$}' => array('ajax/ajax-board.php', 'ajax_set_board_www_etc'),
    '{^/ajax/update_bavatar$}' => array('ajax/ajax-board.php', 'ajax_update_bavatar'),
    
    '{^/ajax/post/list/?$}' => array('ajax/ajax-post.php',	'ajax_getpost_list'),
    '{^/ajax/post/get/?$}' => array('ajax/ajax-post.php',	'ajax_getpost'),
    '{^/ajax/post/gettopic/?$}' => array('ajax/ajax-post.php',	'ajax_get_topic_posts'),
    
    '{^/ajax/post/nearname/?$}' => array('ajax/ajax-post.php',	'ajax_nearpost_name'),
    '{^/ajax/post/topiclist/?$}' => array('ajax/ajax-post.php',	'ajax_topiclist_name'),
	'{^/ajax/post/add/?$}'		=> array('handler/post.php',	'ajax_addpost'), //POST
	'{^/ajax/post/del/?$}' => array('handler/post.php',	'ajax_delpost'), // POST
    
    '{^/ajax/mail/mailbox/?$}'	=> array('ajax/ajax-mail.php',	'ajax_mailbox'),
    '{^/ajax/mail/list/?$}'	=> array('ajax/ajax-mail.php',	'ajax_getmail_list'),
    '{^/ajax/mail/get/?$}'	=> array('ajax/ajax-mail.php',	'ajax_getmail'),
    '{^/ajax/mail/send/?$}'	=> array('ajax/ajax-mail.php',	'ajax_sendmail'), //POST
    '{^/ajax/mail/sendnoname/?$}'	=> array('ajax/ajax-mail.php',	'ajax_noname_sendmail'), //POST
    '{^/ajax/mail/del/?$}'	=> array('ajax/ajax-mail.php',	'ajax_delmail'), //POST
    '{^/ajax/mail/check/?$}'	=> array('ajax/ajax-mail.php',	'ajax_hasnewmail'), //POST
    
    '{^/ajax/ann/?$}' => array('ajax/ajax-ann.php', 'ajax_ann_dir'),
    '{^/ajax/anc/?$}' => array('ajax/ajax-ann.php', 'ajax_anc'),

    '{^/ajax/comm/topten/?$}' => array('ajax/ajax-community.php', 'ajax_topten'),
    '{^/ajax/comm/tips/?$}' => array('ajax/ajax-community.php', 'ajax_tips'),
    '{^/ajax/comm/wish/?$}' => array('ajax/ajax-community.php', 'ajax_birthdaywish'),
    '{^/ajax/misc/errorcode/?$}' => array('ajax/ajax-misc.php', 'ajax_get_errorcode'),
    '{^/ajax/misc/profession/?$}' => array('ajax/ajax-misc.php', 'ajax_get_profession'),

    '{^/ajax/www/get$}' => array('ajax/ajax-community.php', 'www_get'),
    '{^/ajax/www/set$}' => array('ajax/ajax-community.php', 'www_set'),
    '{^/ajax/comp/www_home$}' => array('ajax/ajax-community.php', 'www_home'),
    '{^/ajax/comp/www_home2$}' => array('ajax/ajax-community.php', 'www_home2'),

    '{^/ajax/etc/alltarget$}' => array('ajax/ajax-community.php', 'ajax_get_etc_target'),
    '{^/ajax/etc/update$}' => array('ajax/ajax-community.php', 'ajax_update_etc'),
    '{^/ajax/etc/get$}' => array('ajax/ajax-community.php', 'ajax_get_etc'),

    '{^/ajax/test$}' => array('ajax/ajax-community.php', 'ajax_test'),
    '{^/ajax/refresh$}' => array('ajax/ajax-community.php', 'ajax_refresh'),
    '{^/ajax/page/update$}' => array('ajax/ajax-community.php', 'ajax_update_page'),

    '{^/ajax/admin/update_title$}' => array('ajax/ajax-user.php',
                                            'ajax_update_user_title'),

    '{^/ajax/weibo/check_auth$}' => array('weibo/ajax.php', 'check_auth'),
    '{^/ajax/weibo/use_avatar$}' => array('weibo/ajax.php', 'use_weibo_avatar'),
    '{^/ajax/weibo/update1$}' => array('weibo/ajax.php', 'update1'),

    '{^/ajax/message/list$}' => array('ajax/message.php', 'read_message'),
    '{^/ajax/message/mark$}' => array('ajax/message.php', 'mark_message_read'),

    '{^/ajax/!release$}' => array('ajax/!release.php', 'release'),
    '{^/ajax/!release_phpbbs$}' => array('ajax/!release.php', 'release_phpbbs'),
    '{^/ajax/hook/((!)?(?:@(\w{2,20}))?(?:\:(\w{2,20}))?\_\w+)$}' => array('ajax/hook.php', 'do_hook'),
    
    /******************************** php ***********************/
    '{^/namelist/(\d+)/(\d+)$}' => array('namelist.php','handler_namelist'),

    '{^/weibo/auth$}' => array('weibo/handler.php', 'auth'),
    '{^/weibo/token}' => array('weibo/handler.php', 'token'),

    '{^/account/register$}' => array('account.php', 'register'),
    '{^/account/register_netid$}' => array('account.php', 'register_netid_start'),
    '{^/account/auth$}' => array('account.php', 'auth'),
    '{^/account/auth/netid$}' => array('account.php', 'auth_netid'),
    '{^/account/auth/netid2$}' => array('account.php', 'auth_netid2'),
    '{^/account/netid$}' => array('account.php', 'register_netid'),
    '{^/account/auth/info$}' => array('account.php', 'auth_info'),
    '{^/account/sss$}' => array('account.php', 'sss'),
    '{^/account/sssok$}' => array('account.php', 'sssok'),
    
    /********************************  ext  ***************************************/
    '{^/ext/(\w{0, 20})}' => array('call_ext', 'call_ext'),

	'{^/avatar/(\w{0,20})/?$}'			        => array('common/avatar.php',	'user_avatar'),

	/****************   common ajax for both mobile and normal   ****************/    
    '{^/a/checkall/?$}'			        => array('bbs/message.php',	'checkall'),
    '{^/a/reg/(\w{2,12})/?$}'			        => array('bbs/profile.php',	'a_reg'),
    '{^/a/confirm/(\d{4})/?$}'			        => array('bbs/profile.php',	'a_confirm'),

	'{^/a/message/markread/(\d{1,5})/?$}'		=> array('bbs/message.php',	'a_message_markread'),
	'{^/a/allboards/?$}'			        => array('common/ajax-get.php',	'a_allboards'),
	'{^/a/addfav/(\w{2,16})/?$}'			=> array('common/ajax-get.php',	'a_addfav'),
    '{^/a/delfav/(\w{2,16})/?$}'			=> array('common/ajax-get.php',	'a_delfav'),
	
	'{^/a/(\w{2,16})/(M\.\d{9,10}\.A)/?$}'		=> array('common/ajax-get.php',	'a_read_post'),
    '{^/a/n/(\w{2,16})/(M\.\d{9,10}\.A)/?$}'		=> array('common/ajax-get.php',	'a_next_post'), //next post
    '{^/a/p/(\w{2,16})/(M\.\d{9,10}\.A)/?$}'		=> array('common/ajax-get.php',	'a_prev_post'), //prev post
    
	'{^/a/t/(\w{2,16})/(M\.\d{9,10}\.A)/?$}'	=> array('common/ajax-get.php',	'a_topic_list'),
	'{^/a/(\w{2,16})/post/?$}'			=> array('common/ajax-post.php','a_post_reply'),
	'{^/j/section/?$}'				=> array('common/json.php',	'json_get_sections'),
	'{^/j/board/(\w)/?$}'				=> array('common/json.php',	'json_get_boards'),
	'{^/j/mail/(\d{1,5})/?$}'			=> array('common/json.php',	'json_get_mail'),
	'{^/j/(\w{2,16})/quote/(M\.\d{9,10}\.A)/?$}'	=> array('common/json.php',	'json_get_quote_post'),
	/**************** END ajax ****************/	

	
	/****************   mobile   ****************/

    '{^/m/~(\w{2,16})/(\w.\d{9,10}\.\w)/?$}' => array('nm/topic.php', 'topic'),
    
    '{^/a/checkmail/?$}'			        => array('mobile/m_mail.php',	'm_checkmail'),    
    '{^/m/a/(\w{2,16})/(M\.\d{9,10}\.A)/?$}'		 => array('mobile/m_read.php',	'm_ajax_get'),
	'{^/m/?$}'				=> array('mobile/m_index.php',	'm_index'),
	'{^/m/login/?$}'			=> array('mobile/m_login.php',	'm_login'),
	'{^/m/logout/?$}'			=> array('mobile/m_login.php',	'm_logout'),
	'{^/m/fav/?$}'				=> array('mobile/m_board.php',	'm_list_fav_boards'),
	'{^/m/brds/?$}'				=> array('mobile/m_board.php',	'm_list_boards'),
	'{^/m/data/?$}'				=> array('mobile/m_misc.php',	'm_data'),
	'{^/m/about/?$}'			=> array('mobile/m_misc.php',	'm_about'),
	'{^/m/mail/(\d{0,5})/?$}'		=> array('mobile/m_mail.php',	'm_list_mail'),
	'{^/m/mail/send/(\d{0,5})/?$}'		=> array('mobile/m_mail.php',	'm_send_mail'),
	'{^/m/(\w{2,16})/?$}'			=> array('mobile/m_board.php',	'm_list_posts'),
	'{^/m/(\w{2,16})/post/?$}'		=> array('mobile/m_board.php',	'm_new_post'),
	'{^/m/(\w{2,16})/(\d{1,4})/?$}'		=> array('mobile/m_board.php',	'm_list_posts'),
	'{^/m/(\w{2,16})/(M\.\d{9,10}\.A)/?$}'	=> array('mobile/m_read.php',	'm_read_normal'),
	
	/**************** END mobile ****************/


	/**************** normal ****************/
	
	/* normal */

	'{^/main/?$}'				=> array('bbs/main.php',	'main'),
	'{^/login/?$}'				=> array('bbs/login.php',	'login'),
	'{^/logout/?$}'				=> array('bbs/login.php',	'logout'),
	'{^/ann/(.*)/?$}'			=> array('bbs/announce.php',	'ann_dir'),
	'{^/anc/(.*)/?$}'			=> array('bbs/announce.php',	'anc'),
	'{^/sec/(\d)?/?$}'			=> array('bbs/section.php',	'section'),
	'{^/fav/?$}'			        => array('bbs/board.php',	'list_fav_boards'),
    '{^/reg/$}'			        => array('bbs/profile.php',	'register'),
    '{^/auth/(\d{0,1})/?$}'			        => array('bbs/profile.php',	'auth'),
    '{^/faq/(.*)$}'			        => array('bbs/main.php',	'faq'),
    '{^/hall/(.*)?$}'			        => array('bbs/main.php',	'hall'),
	'{^/message/(\d{1,5})?/?$}'			        => array('bbs/message.php',	'read_message'),
    '{^/recom/(\w{2,16})/(M\.\d{9,10}\.A)/?$}'			        => array('bbs/recommend.php',	'recom'),
    
	'{^/profile/query/(\w{0,12})/?$}'			        => array('bbs/profile.php',	'profile_query'),
	'{^/profile/setting/(\w{0,12})/?$}'                => array('bbs/profile.php',	'profile_setting'),
	'{^/profile/addfriend/(\w{0,12})/?$}'			        => array('bbs/profile.php',	'add_friend'),
	'{^/profile/addreject/(\w{0,12})/?$}'			        => array('bbs/profile.php',	'add_reject'),
	'{^/profile/delfriend/(\w{0,12})/?$}'			        => array('bbs/profile.php',	'del_friend'),
	'{^/profile/delreject/(\w{0,12})/?$}'			        => array('bbs/profile.php',	'del_reject'),
	'{^/profile/online/friends/?$}'			        => array('bbs/profile.php',	'online_friends'),

    
	'{^/mail/(\d{0,5})/?$}'			=> array('bbs/mail.php',	'mailbox'),
	'{^/mail/del/?$}'			=> array('bbs/mail.php',	'delete_mail'),
	'{^/mail/merge/?$}'			=> array('bbs/mail.php',	'merge_mail'),	
	'{^/mail/markread/?$}'			=> array('bbs/mail.php',	'mark_as_read_mail'),
	'{^/mail/send/(\d{0,5})/?$}'			=> array('bbs/mail.php',	'send_mail'),
	'{^/mail/read/(\d{1,5})/?$}'			=> array('bbs/mail.php',	'read_mail'),
    
	'{^/post/(new)/(\w{2,16})/?$}'		=> array('bbs/post.php',	'post_form'), 
	'{^/post/(reply)/(\w{2,16})/(M\.\d{9,11}\.A)/?$}'=> array('bbs/post.php',	'post_form'),
	'{^/post/(edit)/(\w{2,16})/(M\.\d{9,11}\.A)/?$}'=> array('bbs/post.php',	'post_form'),
	'{^/post/(copy)/(\w{2,16})/(M\.\d{9,11}\.A)/?$}'=> array('bbs/post.php',	'post_form'),
	'{^/post/del/(\w{2,16})/(M\.\d{9,11}\.A)/?$}' => array('bbs/board.php',	'delete_post'),
	'{^/post/clear/(\w{2,16})/?$}' => array('bbs/board.php',	'clear_unread'),
    
	'{^/attach/del/?$}'		=> array('bbs/attach.php',	'attach_delete'),
	'{^/attach/upload/?$}'		=> array('bbs/attach.php',	'attach_upload'),
    '{^/attach/list/(\d{1,4})?/?$}'		=> array('bbs/attach.php',	'attach_list'),
	'{^/attach/(\w{2,16})/(\d{9,11})}'		=> array('bbs/attach.php',	'fattach'),
	'{^/attach/(\w{2,16})/(\w{2,12})/?$}'		=> array('bbs/attach.php',	'attach'),
	'{^/attach/(\w{2,16})/(A\.\d{9,11}\.A)}'		=> array('bbs/attach.php',	'attach'),
    /*
	'{^/(\w{2,16})/(\d{1,5})?/?$}'		=> array('bbs/board.php',	'list_post_normal'),
	'{^/(\w{2,16})/topic/(\d{1,5})?/?$}'	=> array('bbs/board.php',	'list_post_topic'),
	'{^/(\w{2,16})/digest/(\d{1,5})?/?$}'	=> array('bbs/board.php',	'list_post_digest'),
	'{^/(\w{2,16})/(M\.\d{9,10}\.A)/?$}'	=> array('bbs/read.php',	'read_post'),
	'{^/(\w{2,16})/t/(M\.\d{9,10}\.A)/?$}'	=> array('bbs/read.php',	'read_topic'),
	'{^/(\w{2,12})/g/(\d{1,6})/?$}'		=> array('bbs/read.php',	'read_digest'),
    */
    
	/**************** END normal ****************/

	);
?>
