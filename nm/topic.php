<?php

header('Content-Type:text/html;charset=gbk');

global $version;
$version = 0;

function handle404(){
}

function topic($boardname, $filename){
    global $tpl;
    global $user;
    global $version;
    $board = new Board($boardname);
    $boardname = $board->filename;
    if($user->has_read_perm($board) == false){
        handle404();
    }
    // filename and author
    $files = ext_gettopicfiles($boardname, $filename);
    $tpl->loadTemplate('nm/topic.html');
    $postlist = array();
    $author = array("" => 0);
    foreach($files as $f){
        $post = new Post($boardname, $f->filename);
        if($post->userid == ""){
            $ah = ext_getfileheader($boardname, $f->filename);
            if($ah){
                $urec = ext_get_urec($ah->owner);
                $post->userid = $ah->owner;
                $post->board = $boardname;
                $post->username = $urec['username'];
                $post->post_time = $ah->filetime;
                $post->title = $ah->title;
            } else {
                continue;
            }
        }
        $rawcontent = $post->rawcontent;
        $post->wordscount = strlen($post->rawcontent);
        $firstline = strpos($post->rawcontent, "\n¡¾ ÔÚ");
        if(!isset($author[$post->userid])){
            $author[$post->userid] = 0;
        }
        $author[$post->userid] ++;
        if(($author[$post->userid] > 5)
           || ($post->wordscount < 140)
           || ($firstline && $firstline < 140)){
            $post->short = 1;
        }
        unset($post->content);
        unset($post->signature);
        array_push($postlist, $post);
    }
    $topic = array_shift($postlist);
    unset($postlist[0]->type);
    echo $tpl->render(array('board' => $board,
                            'topic' => $topic,
                            'boardname' => $boardname,
                            'filename' => $filename,
                            'static_css' => '/static/nm.css?_=' . $version,
                            'static_js' => '/static/nm.js?_=' . $version,
                            'replys' => $postlist));
}
