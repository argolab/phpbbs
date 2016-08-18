<?php
require_once('common/functions.php');
require_once('common/etc.php');
//��飬�ļ������Ƽ���
function recom($bname, $filename)
{
    global $user;
    global $tpl;

    if(!$user->islogin()) {
        echo "���ȵ�¼";
        return;
    }

    if(!$user->has_BM_perm($bname)) {
        echo "����Ȩ�Ƽ�����";
        return ;
    }

    if($_SERVER['REQUEST_METHOD'] == 'POST')  {
        if(!isset($_POST['place'])) {
            echo "ѡ����Ϊ���ţ�";
            return;
        }
        switch ($_POST['place']) {
            case '0':  //�Ƽ�����
                etc_add_recom('recom-topic', $bname, $filename);
                break;
            case '1': //У԰����
                etc_add_recom('campus-suggestion', $bname, $filename);
                break;
            case '2':  //���Ż
                etc_add_recom('community-activity', $bname, $filename);
                break;            
            default: echo "δ֪ѡ��"; return ;
        }                
        return;
    }

    $tpl->loadTemplate("standard/forms/recommend_form.html");
    echo $tpl->render(array(
                          'board' => $bname,
                          'filename' => $filename));    
}

?>