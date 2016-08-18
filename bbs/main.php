<?php
require_once('common/functions.php');

function frame() {
    global $user;
	global $tpl;

    $myface = get_myface($user->userid()); /* return {userid}.jpg */
    if($myface)  {
        $myface = '/attach/' . $user->userid() . '/' . $myface;
    } else {
        $myface = '/images/gcc.jpg';
    }
    $userlevel = get_user_level($user);

    $total_online = ext_get_total('online');
    $total_users = ext_get_total('total');

        
    $tpl->loadTemplate('standard/frame.html');    
	echo $tpl->render(array('myface' => $myface,
                            'userlevel' => $userlevel,
                            'total_online' => $total_online,
                            'total_users' => $total_users
                        ));
}

function get_recommend_content($board, $filename)
{
    chdir(BBSHOME);
	$path = 'boards/'  . $board . '/' . $filename;
	if ( !file_exists($path) ) return '';
    $arr = file($path);
    $ret = '';
    $len = 0;
    for($i=8; $i < count($arr) ; $i++)
    {
        if($len +  strlen($arr[$i])  > 140*2) break;
        $len += strlen($arr[$i]);        
        $ret .= $arr[$i];        
    }
    return $ret;
}
function main() {
	global $tpl;

        /* 处理十大的内容*/
	$top = etc_top_ten();    
    foreach($top as &$t)
    {
        if($t['num'] < 10) $t['color'] = "gray";
        else  if($t['num'] < 20) $t['color'] = "green";
        else  if($t['num'] < 50) $t['color'] = "blue";
        else  if($t['num'] < 80) $t['color'] = "yellow";
        else  $t['color'] = "red";
    }
        /* job-info*/
    $list_num = 10;
    $board = new Board("Job");
    $jobs = $board->get_topic_list(0, $list_num, 0);
    $jobs->list =array_reverse($jobs->list);
        /*foreach($jobs->list as &$job)
          $job->update = show_last_time($job->update);*/

        //处理各种推荐内容
    $campus = etc_get_recom("campus-suggestion", 10); //获取最后推荐的10个
    $community = etc_get_recom("community-activity", 10);
    $recom_topic = etc_get_recom("recom-topic", 10);
    
        /* 处理文章推荐 */
    $board = new Board("Recommend");
        $remlist = $board->get_post_list(0,5,1);
        
        foreach($remlist->list as &$post)  // G.12435435.A => M.12345456.A
        {
			$post->filename = str_replace("G", "M", $post->filename);
            $post->content = '  ' . get_recommend_content("Recommend", $post->filename) . '...';
        }
            /* 处理生日 */
        $birthday = etc_birthday_today(); //return array 
        
        $remlist->list = array_reverse($remlist->list);
        $tpl->loadTemplate('standard/index.html');
        echo $tpl->render(array(
                              'top' => $top,
                              'jobs' => $jobs->list,
                              'camp' => $campus,
                              'community' => $community,
                              'recom_topic' => $recom_topic,                              
                              'rem' => $remlist->list,
                              'birthday' => $birthday));
}

function do_wiki($wiki_name, $filename)
{
    global $user;
    global $tpl;

    
    $term_list = etc_wiki_list($wiki_name, $wiki_name . "-dir"); // fetch content list array<term_name, filename> in etc/{term}/{term}-dir

    $perm_edit = 0; 
        // 检查是否是讨论区主管～
    if($user->hasperm(PERM_OBOARDS)) {
        $perm_edit = 1;
    }
    
    if($_SERVER["REQUEST_METHOD"] == "POST") { //submit form
        
        if(!$user->islogin()) {
            echo "请先登录";
            return ;
        }
        if(!$perm_edit || strstr($filename, "..")) {
            echo "你无权修改";
            return ;
        }
        
        $res = etc_set_content($wiki_name, $filename, $_POST["content"]);
        echo $res ? "修改成功" : "修改失败";
        return ;
    }

    if($filename != "") { //edit the page 
        if(!$perm_edit || strstr($filename, "..")) {
            echo "没有该页面";
            return ;
        }
        $form_content = etc_get_content($wiki_name, $filename);
        $tpl->loadTemplate("standard/forms/wiki_form.html");
        echo $tpl->render(array(
                              "wiki_name" => $wiki_name,
                              "filename" => $filename,
                              "content" => $form_content));
        return ;
    }

        //otherwise show the wiki pages
    if(count($term_list))
        foreach($term_list as &$tl) {
            $tl['content'] = etc_get_content($wiki_name, $tl['term_name']); // etc/faq/{filename}
        }

    $tpl->loadTemplate("standard/wiki_page.html");
    echo $tpl->render(array(
                          "wiki_name" => $wiki_name,
                          "term_list" => $term_list,
                          "perm_edit" => $perm_edit));
}
function faq($filename = "")
{  
    do_wiki("faq", $filename);    
}

function hall($filename = "")
{
    do_wiki("hall", $filename);
}

?>
