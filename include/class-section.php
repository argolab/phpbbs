<?php

class SectionManager extends Manager
{
    static $all = array(
                        array('seccode' => '0',
                              'secnum' => 0,
                              'secname' => 'BBS 系统'),
                        array('seccode' => 'u',
                              'secnum' => 1,
                              'secname' => '校园社团'),
                        array('seccode' => 'z',
                              'secnum' => 2,
                              'secname' => '院系交流'),
                        array('seccode' => 'c',
                              'secnum' => 3,
                              'secname' => '电脑科技'),
                        array('seccode' => 'r',
                              'secnum' => 4,
                              'secname' => '休闲娱乐'),
                        array('seccode' => 'a',
                              'secnum' => 5,
                              'secname' => '文化艺术'),
                        array('seccode' => 's',
                              'secnum' => 6,
                              'secname' => '学术科学'),
                        array('seccode' => 't',
                              'secnum' => 7,
                              'secname' => '谈天说地'),
                        array('seccode' => 'b',
                              'secnum' => 8,
                              'secname' => '社会信息'),
                        array('seccode' => 'p',
                              'secnum' => 9,
                              'secname' => '体育健身'));
    static $all_code = array(
                          '0' => 0,
                          'u' => 1,
                          'z' => 2,
                          'c' => 3,
                          'r' => 4,
                          'a' => 5,
                          's' => 6,
                          't' => 7,
                          'b' => 8,
                          'p' => 9);

    function get_all_sections()
    {
        return $all;
    }
    
    function get_section_by_code($code)
    {
        if(array_key_exists($code, self::$all_code))
        {
            return self::$all[self::$all_code[$code]];
        }
        else
        {
            return false;
        }
    }

    function get_section_by_num($num)
    {
        return $all[$num];
    }
    
}
