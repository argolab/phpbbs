<?php
function handler_namelist($tid, $nid)
{
    global $user;
    $user->set_stat(STAT_DIGESTRACE);

    header('Content-Type:text/html; charset=UTF-8');

    $types = ext_annpath('namelist');
    if(!isset($types[$tid]))
    {
        ajax_error('Wrong Type.');
    }
    $cur_type = $types[$tid];
    $otypes = array();
    foreach($types as $i=>$t)
    {
        if($t['title'][0] == ' ')
        {
            continue;
        }
        array_push($otypes, array('number' => $i,
                                  'text' => @iconv('gbk', 'utf-8',
                                                   $t['title'])));
    }

    $nodes = ext_annpath('namelist/' . $cur_type['filename']);
    $onodes = array();
    foreach($nodes as $i=>$n)
    {
        if($n['title'][0] == '-')
        {
            array_push($onodes, array('number' => $i,
                                      'fake' => true,
                                      'text' => @iconv('gbk', 'utf-8',
                                                       substr($n['title'], 1))));
        }
        else
        {
            if($nid==0)
            {
                $nid = $i;
            }
            array_push($onodes, array('number' => $i,
                                      'text' => @iconv('gbk', 'utf-8',
                                                       $n['title'])));
        }
    }
    if(!isset($nodes[$nid]))
    {
        ajax_error('Wrong Node.');
    }
    $cur_node = $nodes[$nid];
    if($cur_node['title'][0] == '-')
    {
        ajax_error('Wrong Node index.');
    }

    $file = @iconv('gbk', 'utf-8',
                   ext_annfile('namelist/' . $cur_type['filename'] . '/' .
                               $cur_node['filename'], 0));
    $lines = explode("\n", $file);
    $namelist = array();
    foreach($lines as &$r)
    {
        $pos = strpos($r, ' ');
        if($pos)
        {
            $r = array('userid' => substr($r, 0, $pos),
                       'intro' => substr($r, $pos));
        }
        else
        {
            $r = trim($r, '\0\t\x0B\r\n  ');
        }
    }
    
    global $tpl;
    $tpl->loadTemplate('standard/namelist.html');

    echo $tpl->render(array('types' => $otypes,
                            'nodes' => $onodes,
                            'tid' => $tid,
                            'nid' => $nid,
                            'title' => @iconv('gbk', 'utf-8',
                                              $cur_node['title']),
                            'list' => $lines));

}
?>
