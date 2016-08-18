<?php
require_once("common/functions.php");

function attach($boardname, $filename)
{
    global $user;

        //$_SERVER['HTTP_REFERER']; // 防盗链,fix me later
    
    if(strstr($boardname, "..") || strstr($filename, "..")) {
        echo "非法路径";
        return ;
    }
    $ah=ext_get_attacheader($boardname, $filename);    
    if($ah) {
        
        $path = get_attach_path($boardname, $filename);

        if(Post::is_picture($ah->filetype)) {
            header("Content-type: image/". $ah->filetype);
            $st = stat($path);
            header("Last-Modified: "  . gmdate(DATE_RFC822, $st[10]));            
            if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $last_modify = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
                if($st[10] == $last_modify) {
                    header( "HTTP/1.1 304 Not Modified" );
                    return ;
                }
            }
        } else {
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment;filename=" . $ah->origname );
        }
        echo readfile($path);
    } else {
        echo "文件不存在!";
        return ;
    }
}

function fattach($boardname, $filename)
{
    return attach($boardname, 'A.' . $filename  . '.A');
}

function attach_delete()
{
    
	global $user;
	
	if (!$user->islogin()) {
		echo "请先登陆";
		return;
	}
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		echo "请求错误";
		return;
	}
	if (!isset($_POST['indexes']) || !is_array($_POST['indexes'])) {
		echo "参数错误";
		return;
	}
   
    $res = ext_del_attach($user->userid(), $_POST['indexes']);
    
    echo $res? "删除附件成功" : "删除附件失败"; 
}

function attach_upload()
{
    global $user;
    global $tpl;

    if(!$user->islogin()) {
        echo "请先登陆";
		return;
    }
       
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        if(isset($_FILES["attach"])) {
            if ($_FILES["attach"]["error"] >0) {
                switch ($_FILES["attach"]["error"]) {
                    case 1: echo "单个上传文件大小超过限制(<5M)" ; return ;
                    case 2: echo "单个上传文件大小超过限制(<5M)";  return ;
                    case 3: echo "文件上传不完整";  return ;
                    case 4: echo "请选择文件上传"; return ;
                    case 5: echo "上传文件大小不能为0";  return ;
                    default: echo "未知错误原因"; return ;
                }
            }
            
            $ahlist = ext_get_attachlist($user->userid(), 1, -1);
            $total_size = get_total_size();
            $used_size = $_FILES["attach"]["size"];
            foreach($ahlist->list as &$ah)
            {
                $used_size += $ah->filesize;                
            }
            if($used_size > $total_size) {
                echo "亲！您的附件空间已经装不下啦，赶紧清理吧~" ;
                return ;
            }
            /* 上传并返回 A.1234355.A 的文件名 */           
            $res = ext_upload_attach($user->userid(),
                                   $_FILES["attach"]["tmp_name"],
                                   $_FILES["attach"]["name"],
                                   $_FILES["attach"]["type"]);
            
            trace_report(" upload " .  $res . " size " . $_FILES["attach"]["size"]);
            echo $res ? "上传成功" : "上传失败";
        } else {
            echo "上传错误";
            return ;
        }
        return ;   
    }
    
    
    $tpl->loadTemplate('standard/forms/upload.html');
    echo $tpl->render();
}

function attach_list($start = 0)
{
    global $user;
    global $tpl;
    
    $list_num = 20;
        //$www = ext_get_www($user->userid());
    $ret = ext_get_attachlist($user->userid(), $start , $list_num);

    $start=$start ? $start : $ret->total-$list_num+1;
    if($start<=0)  $start=1;
   
    $prev = $start - $list_num;
    if($prev<=0) $prev=1;    
    $next = $start + $list_num;
    if($next > $ret->total)  $next=$start;
        
    $total_size = get_total_size();
    $used_size = 0;
    if(isset($ret) && count($ret->list))
        foreach($ret->list as &$ah)
        {
            $used_size += $ah->filesize;
            $ah->filesize = get_size($ah->filesize);
            if($ah->filename == $user->userid()) $ah->origname .= "(头像)";
            $ah->link = "/attach/" .  $user->userid() . "/" . $ah->filename;
        }
    
    $tpl->loadTemplate('standard/list_attacheader.html');
    echo $tpl->render(array(
                          'ahlist' => $ret->list,
                          'total' => $ret->total,
                          'prev' => $prev,
                          'next' => $next,
                          'used_size' => get_size($used_size),
                          'total_size' => get_size($total_size)
                            ));
        
}
?>
