<?php

class Post{

    // Construct means query.
    // $this->userid is '' if not exists.
    static function is_picture($type) {
        $pics = array("jpeg", "gif", "png",  "bmp");
        foreach($pics as $pic)
        {
            if(strcasecmp($type, $pic) == 0) return true;
        }
        return false;
    }
    
    public function __construct($boardname,$filename)
    {
        $fh = ext_getfileheader($boardname, $filename);
        if(!$fh)
        {
            $this->userid = '';
            return ;
        }
        $arr=ext_post_content_classify($boardname,$filename);
        $this->userid = $arr['userid'];
        $this->username = $arr['username'];
        $this->title = $arr['title'];
        $this->board = $boardname ;
              // $arr['board']; A BUG in boardname with underline like 'BBS_Help
        $this->post_time = $arr['post_time'];
        $this->content = ($arr['content']);
        $this->rawcontent = $arr['rawcontent'];
        $this->signature = ($arr['signature']); /* signature | rawsignature */ 
        $this->rawsignature = ($arr['rawsignature']); /* signature | rawsignature */ 
        $this->bbsname = $arr['bbsname'];
        $this->ah = $arr['ah']; /* attacheader object */ 

        if(isset($this->ah->filename)) {
            //$this->ah->link = "/attach/" . $this->userid . "/" . $this->ah->filename ;
            $this->ah->link = "/attach/" . $boardname . "/" . $this->ah->filename ;
            $this->ah->is_picture = $this->is_picture($this->ah->filetype);
        }
        $this->filename = $filename;
        $this->index = $fh->index;
    }
}
?>
