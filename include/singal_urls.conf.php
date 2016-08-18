<?php

return array(

             '{^/test$}' => array('handler/singal.php', 'test'),
             '{^/post/changedigest$}' => array("handler/singal.php",
                                           "changedigest"),
             '{^/post/cross$}' => array("handler/singal.php", "cross"), 
             '{^/post/newtopic$}' => array("handler/singal.php", "newtopic"),
             '{^/post/reply$}' => array("handler/singal.php", "reply"),
             '{^/post/updatepost$}' => array("handler/singal.php", "updatepost"),
             '{^/post/changetitle$}' => array("handler/singal.php", "changetitle"),
             '{^/post/changemark$}' => array("handler/singal.php", "changemark"),
             '{^/post/del$}' => array("handler/singal.php", "del"),
             '{^/post/rscore$}' => array("handler/singal.php", "update_all_score"),
             '{^/post/cancelpost$}' => array("handler/singal.php", "cancelpost"));


              