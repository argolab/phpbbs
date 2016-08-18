<?php

function api_get_board()
{
    json_assert_param($_GET, 'boardname');
    $user = UserSession::get_cookie_user();
    $bmc = new BoardManager($user->get_userid());
    $board = $bmc->get_board($_GET['boardname']);
    json_assert($board, 'No such board.');
    json_success(array('boardname' => $board['boardname'],
                       'data' => $board));
}

function api_list_board()
{
    json_assert_param($_GET, 'selector');
    $user = UserSession::get_cookie_user();
    $bmc = new BoardManager($user->get_userid());
    $selector = $_GET['selector'];
    if($selector == '$all')
        $ret = $bmc->get_allboards();
    else if($selector == '$fav')
    {
        json_assert_login();
        $ret = $bmc->get_boards_from_fav();
    }else
    {
        $secc = new SectionManager($user->get_userid());
        $ret = $bmc->get_boards_from_sec($selector, $secc);
    }
    if(isset($_GET['index']))
    {
        $index = (int)$_GET['index'];
        if($_GET['limit'])
        {
            $limit = (int)$_GET['limit'];
            $ret = array_slice($ret, $index, $limit);
        }
        else
            $ret = array_slice($ret, $index);
    }
    else
    {
        $index = 0;
        $limit = count($ret);
    }
    make_range($index, count($ret), $limit, $prev, $next);
    json_success(array('index' => $index, 'count' => count($ret),
                       'items' => $ret, '_prev_page' => $prev,
                       '_next_page' => $next));
}