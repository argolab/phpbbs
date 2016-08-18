<?php

define('MAX_NEWBRD', 25);

require_once('bin.php');

$boards = ext_get_allboards();
foreach($boards as &$b)
{
    $b = new Board($b);
}
$boards = array_filter($boards, "board_perm_filter");
$boards = array_values($boards); /* rebuild keys */
beautify_board($boards);

function sort_board_by_lastupdate($a, $b)
{
    return $b->lastpost - $a->lastpost;
}

usort($boards, "sort_board_by_lastupdate");

$fh = fopen(BBSHOME . '/etc/sysnewpostbrd', 'w');
    
$i = 0;
foreach($boards as &$b)
{
    fwrite($fh,  $b->boardname . "\n");
    if(++$i == MAX_NEWBRD)
    {
        break;
    }
}

function map_to_board($boards)
{
    $res = array();
    foreach($boards as $b)
    {
        $t = new Board($b);
        if($t->is_vail())
            $res[] = $b;            
    }
    return $res;
}

$boards = array('month' => 'sysmonthbrd', 'good' => 'sysgoodbrd',
                'fresh' => 'sysnewpostbrd', 'hot' => 'syshotbrd');
$ret = array();
foreach($boards as $k => $v)
{
    $b = map_to_board(fjdb_lget($v));
    //    beautify_board($b);
    $ret[$k] = $b;
}

fjdb_set(BBSHOME . '/etc/', 'phpgoodbrds', $ret);

?>
