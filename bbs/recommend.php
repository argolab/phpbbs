<?php
require_once('common/functions.php');
require_once('common/etc.php');
//版块，文件名，推荐人
function recom($bname, $filename)
{
    global $user;
    global $tpl;

    if(!$user->islogin()) {
        echo "请先登录";
        return;
    }

    if(!$user->has_BM_perm($bname)) {
        echo "你无权推荐文章";
        return ;
    }

    if($_SERVER['REQUEST_METHOD'] == 'POST')  {
        if(!isset($_POST['place'])) {
            echo "选择不能为空呐！";
            return;
        }
        switch ($_POST['place']) {
            case '0':  //推荐话题
                etc_add_recom('recom-topic', $bname, $filename);
                break;
            case '1': //校园建设
                etc_add_recom('campus-suggestion', $bname, $filename);
                break;
            case '2':  //社团活动
                etc_add_recom('community-activity', $bname, $filename);
                break;            
            default: echo "未知选择"; return ;
        }                
        return;
    }

    $tpl->loadTemplate("standard/forms/recommend_form.html");
    echo $tpl->render(array(
                          'board' => $bname,
                          'filename' => $filename));    
}

?>