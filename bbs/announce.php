<?php
require_once("common/config.php");
/* bbsann.php */
/* �������б� */


/* fixme: ���澫�����Ķ�Ȩ�� */



function ann_dir($path) {

	global $tpl;
    global $user;
    
    static $static_secs = array('0', 'u', 'z', 'c', 'r', 'a',
                                's', 't', 'b', 'p');
    
    $user->set_stat(STAT_DIGESTRACE);

    if(strstr($path, "..")) {
        echo "�Ҳ���������";
        return ;
    }
    
    $annlist = array();
	if (strncmp($path, '@GROUP', 6)) {
        $annlist = ext_annpath($path);
    } else {        
        //������������
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
            else $ann['flag'] = 'e';
        }
    }
    /* ����url */
    chdir(BBSHOME);
	if (strncmp($path, '@GROUP', 6)) {
        if(count($annlist)) {
            foreach($annlist as &$ann) {            
                if ($ann['filename'] == '@NULL') {
                    $ann['url'] = '#';
                } else if ($ann['filename'] == '@BOARDS') {
                    $ann['url'] = '/ann/boards/' . $ann['owner'];
                } else if (!strncmp($ann['filename'], '@GROUP', 6)) {
                    /* ��ʽ: "@GROUP:[0-9, A-Z, *]����" */
                    $ann['url'] = '/ann/' . $ann['filename'];// . $ann['title'];
                } else {
                    $file_path = '0Announce/' . $path . '/' . $ann['filename'];		
                    if(file_exists($file_path)){
                        if($path == '') $slash='';
                        else {
                            if(strrpos($path, '/') == strlen($path)-1) $slash='';
                            else $slash='/';
                        }
                        if ($ann['flag'] == 'd' || $ann['flag'] == 'l') {
                            $ann['url'] = '/ann/' . $path . $slash . $ann['filename'];
                        } else {
                            $ann['url'] = '/anc/' . $path . $slash  . $ann['filename'];
                        }
                    }else $ann['url'] = '#';
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

    if(count($annlist)) {
        foreach($annlist as &$ann){
            if(!is_string($ann['mtime']))
                $ann['mtime'] = date('M d', $ann['mtime']);
        }
    } 
    
    $tpl->loadTemplate('standard/bbsann.html');
	echo $tpl->render(array('annlist' => $annlist));

}

function anc($path)
{
    global $tpl;
    if(strstr($path, "..")) {
        echo "�Ҳ���������";
        return ;
    }
    
    if(ext_file_exists("0Announce/" . $path)){
        $content = ext_annfile($path, 1); // html
        $tpl->loadTemplate('standard/bbsanc.html');
        echo $tpl->render(array( 'content' => $content));
    }else {
        echo "�Ҳ���������";
        return ;
    }
}
?>
