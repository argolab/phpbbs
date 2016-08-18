<?php

return array(
             
             '{^/site/test$}' => array('handler/site.php', 'test'),
             '{^/board/get$}' => array('handler/board.php', 'get_board'),
             '{^/board/list$}' => array('handler/board.php', 'list_board'),
             '{^/adminer.php$}' => array('handler/adminer.php', 'main'),
             '{^/post/posts/list$}' => array('handler/post.php', 'get_post_list'),
             '{^/post/topics/list$}' => array('handler/post.php', 'get_topic_list'),
             '{^/post/topic$}' => array('handler/post.php', 'get_post_in_topic'),
             
);

?>
