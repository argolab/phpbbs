<?php

require_once("common/functions.php");

/*
 * 精华区：
 *  精华区基本按照了0Announce的目录来组织
 *  每个目录下有.DIR，存放annhead，包含当前目录下各个子目录的属性
 *  如果type是ann，那么这个是一个目录,
 *  如果type是anc，那么是一个文件
 *  - boards/$boardname/(.*) 是某个版面下的精华区
 *  - @GROUP:$seccode 
 *   是某个讨论区下所有版面的精华区
 *   （其实就是组织boards/$boardname 那些属于这个$seccode的版面来，其实很少用到）
 *
 * 根据type，如果是ann，那么是目录，继续通过ann_dir函数来处理
 * 如果是anc，那么通过anc函数来处理。
 * ann_dir是处理精华区的目录函数
 * anc处理精华区文章（返回文章)
 */

function get_ann_path($path)
{
    global $user;
    static $static_secs = array('0', 'u', 'z', 'c', 'r', 'a', 's',
                                't', 'b', 'p');
    $user->set_stat(STAT_DIGESTRACE);
    if(strstr($path, "..")) {
        ajax_error("Announce not found");
    }
    $annlist = array();
    if (strncmp($path, '@GROUP', 6)) {
        $annlist = ext_annpath($path);
    } else { 
        $secs = ext_getsections();
        $seccode = $static_secs[intval(substr($path, 7, 1))];
        foreach($secs as &$sec) {
            if ($sec->seccode == $seccode) {
                $_annlist = ext_annpath_group($sec->seccode);
                $annlist = array();
                foreach($_annlist as &$ann) {
                    if($user->has_read_perm($ann['filename']))
                    {
                        unset($ann['mtime']);
                        $ann['owner'] = $ann['filename'];
                        $ann['filename'] = '@BOARDS';
                        $annlist []= $ann;
                    }
                }
                break;
            }
        }
    }
    /* flag */
    if(count($annlist)) {
        $res = array();
        foreach($annlist as &$ann) {
            if ($ann['flag'] & ANN_FILE) $ann['flag'] = 'f';
            else if ($ann['flag'] & ANN_DIR) $ann['flag'] = 'd';
            else if ($ann['flag'] & ANN_LINK) $ann['flag'] = 'l';
            else if ($ann['flag'] & ANN_READONLY) $ann['flag'] = 'r';
            else if ($ann['flag'] & ANN_GUESTBOOK) $ann['flag'] = 'n';
            else if ($ann['flag'] & ANN_PERSONAL) $ann['flag'] = 'a';
            else $ann['flag'] = 'err';
            $res[] = $ann;
        }
        return $res;
    }
    else
    {
        return is_null($annlist)?null:array();
    }
}

function get_person_title($userid)
{
    $path = 'personal/' . strtoupper($userid[0]) . '/';
    $dir = get_ann_path($path);
    foreach($dir as &$dd)
    {
        if($dd['filename'] == $userid)
        {
            return $dd;
        }
    }
    return null;
}

function get_path_meta($req)
{
    $pattern = '/^([~:]?)([a-zA-z]+)(((\/[MD]\.\d{1,10}\.[A-Z])|(\/[a-zA-Z0-9]{1,20}))*|\/)$/';
    preg_match($pattern, $req, $matches);
    if(empty($matches) || !$matches[3])
    {
        return false;
    }
    if($matches[1] == '~')
    {
        $personal = get_person_title($matches[2]);
        return array('type' => 'personal',
                     'title' => $personal['title'],
                     'root' => $matches[1] . $matches[2],
                     'author' => $matches[2],
                     'path' => $matches[3]);
    }
    else if($matches[1] == ':')
    {
        return array('type' => 'board',
                     'root' => $matches[1] . $matches[2],
                     'boardname' => $matches[2],
                     'title' => $matches[2] ,
                     'path' => $matches[3]);
    }
    else if($matches[2] == 'site')
    {
        return array('type' => 'site',
                     'root' => 'site',
                     'title' => 'bbs',
                     'path' => $matches[3]);
    }
    return false;
}

function get_path_prefix($reqobj)
{
    if($reqobj['type'] == 'personal')
        return 'personal/' . strtoupper($reqobj['author'][0]) . '/' . $reqobj['author'] . '/';
    else if($reqobj['type'] == 'board')
        return 'boards/' . $reqobj['boardname'] . '/';
    else 
        return '/';
}

function ajax_ann_content()
{
    global $user;

    ajax_assert_param($_GET, array('reqpath'));
    $user->set_stat(STAT_DIGESTRACE);
    $reqpath = $_GET['reqpath'];

    if(!strncmp($reqpath, '@GROUP', 6))
    {
        $dir = get_ann_path($reqpath);
        $metainfo = array('type' => 'group',
                          'title' => $reqpath,
                          'root' => $reqpath);
        return ajax_success(array('metainfo' => $metainfo,
                                  'bc' => array($reqpath),
                                  'bt' => array($reqpath),
                                  'post' => '',
                                  'dir' => $dir));
    }    
    $reqobj = get_path_meta($reqpath);
    if($reqobj)
    {
        $prefix = get_path_prefix($reqobj);
        $path = $reqobj['path'];
        $fpath = $prefix . $path;
        if(is_dir(BBSHOME . '/0Announce/' . $fpath))
        {
            $post = null;
        }
        else
        {
            $post = ext_annfile($fpath, 0);
            $path = dirname($path);
            $fpath = $prefix . $path;
        }
        $resdir = get_ann_path($fpath);
        if(!$resdir)
        {
            return ajax_error('No such directory.');
        }
        $bc = array(); // Breadcrumbs
        $bt = array();
        $root = $reqobj['root'];
        $post_index = null;
        if($post)
        {
            $base = basename($reqobj['path']);
            foreach($resdir as &$dd)
            {
                if($dd['filename'] == $base)
                {
                    $post_index = $dd;
                    break;
                }
            }
            $bt[] = $post_index['title'];
            $bc[] = '';
        }
        $bc[] =$root . $path;
        while($path != '/')
        {
            $basename = basename($path);
            $path = dirname($path);
            $bc[] = $root . $path;
            $dir = get_ann_path($prefix . $path);
            foreach($dir as &$dd)
            {
                if($dd['filename'] == $basename)
                {
                    $bt[] = $dd['title'];
                    break;
                }
            }
        }
        $bt[] = $root . '/';
        return ajax_success(array("metainfo" => $reqobj,
                                  "bc" => array_reverse($bc),
                                  "bt" => array_reverse($bt),
                                  "post" => $post,
                                  "post_index" => $post_index,
                                  "dir" => $resdir));
    }
    return ajax_error('Wrong path.');
}

function get_annlist_seccode($path)
{
    static $static_secs = array('0', 'u', 'z', 'c', 'r', 'a', 's', 't', 'b', 'p');
    $seccode = $static_secs[intval(substr($path, 7, 1))];
    foreach($secs as &$sec) {
        if ($sec->seccode == $seccode) {
            $_annlist = ext_annpath_group($sec->seccode);
            $annlist = array();
            foreach($_annlist as &$ann) {
                if($user->has_read_perm($ann['filename']))
                    $annlist []= $ann;
            }
            break;
        }
    }
}

function ajax_ann_dir() 
{
    global $user;
    
    ajax_assert_param($_GET, array("path"));
    $path = $_GET["path"];

    static $static_secs = array('0', 'u', 'z', 'c', 'r', 'a', 's', 't', 'b', 'p');

    $user->set_stat(STAT_DIGESTRACE);

    if(strstr($path, "..")) {
        ajax_error("Announce not found");
    }

    $annlist = array();
    if (strncmp($path, '@GROUP', 6)) {
        $annlist = ext_annpath($path);
    } else { 
        $secs = ext_getsections();
        $seccode = $static_secs[intval(substr($path, 7, 1))];
        foreach($secs as &$sec) {
            if ($sec->seccode == $seccode) {
                $_annlist = ext_annpath_group($sec->seccode);
                $annlist = array();
                foreach($_annlist as &$ann) {
                    if($user->has_read_perm($ann['filename']))
                        $annlist []= $ann;
                }
                break;
            }
        }
    }

    /* flag */
    if(count($annlist)) {
        foreach($annlist as &$ann) {
            if ($ann['flag'] & ANN_FILE) $ann['flag'] = 'f';
            else if ($ann['flag'] & ANN_DIR) $ann['flag'] = 'd';
            else if ($ann['flag'] & ANN_LINK) $ann['flag'] = 'l';
            else if ($ann['flag'] & ANN_READONLY) $ann['flag'] = 'r';
            else if ($ann['flag'] & ANN_GUESTBOOK) $ann['flag'] = 'n';
            else if ($ann['flag'] & ANN_PERSONAL) $ann['flag'] = 'a';
            else $ann['flag'] = 'err';
        }
    }
    /* 增加url */

    if (strncmp($path, '@GROUP', 6)) {
        if(count($annlist)) {
            foreach($annlist as &$ann) {            
                if ($ann['filename'] == '@NULL') {
                    $ann['url'] = '#';
                } else if ($ann['filename'] == '@BOARDS') {
                    $ann['url'] = 'boards/' . $ann['owner'];
                } else if (!strncmp($ann['filename'], '@GROUP', 6)) {
                    /* 格式: "@GROUP:[0-9, A-Z, *]标题" */
                    $ann['url'] =  $ann['filename'] . $ann['title'];
                } else {
                    $file_path = '0Announce/' . $path . '/' . $ann['filename'];		
                    if(ext_file_exists($file_path)){
                        if($path == '') $slash='';
                        else {
                            if(strrpos($path, '/') == strlen($path)-1) $slash='';
                            else $slash='/';
                        }
                        if ($ann['flag'] == 'd' || $ann['flag'] == 'l') {
                            $ann['url'] = $path . $slash . $ann['filename'];
                            $ann['type'] = 'ann';
                        } else {
                            $ann['url'] = $path . $slash  . $ann['filename'];
                            $ann['type'] = 'anc';
                        }
                    } else $ann['url'] = '#';
                }
            }
        }

    } else {

        if(count($annlist)) {
            foreach($annlist as &$ann) {
                $ann['url'] = '/ann/boards/' . $ann['filename'];
            }
        }
    }
    
    $res = array();
    if (count($annlist)) {
        foreach($annlist as &$ann) {
            if (!is_string($ann['mtime']))
                $ann['mtime'] = date('M d', $ann['mtime']);
            $res []= $ann;
        }
    } 

    ajax_success($res); 
}

function ajax_anc()
{
    
    ajax_assert_param($_GET, array("path"));
    
    $path = $_GET["path"];
    if(strstr($path, "..")) {
        ajax_error("Announce not found");
    }
    
    chdir(BBSHOME);
    if (file_exists("0Announce/" . $path)) {
        $content = ext_annfile($path, 0); //raw content
        ajax_success($content);
    } else {
        ajax_error("Announce not found");
        return ;
    }
}

?>
