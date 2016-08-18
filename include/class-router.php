<?php

class Router {

    public $rules;
    public $funcprefix;
    
    function __construct($rules=null, $funcprefix='')
    {
        if($rules !== null)
            $this->set_rules($rules);
        $this->funcprefix = $funcprefix;
    }

    function set_rules($rules)
    {
        $this->rules = $rules;
        return $this;
    }

    function get_rules($rules)
    {
        return $this->rules;
    }

    function go($url)
    {
        $urlconf = $this->rules;
        $funcprefix = $this->funcprefix . '_';
        foreach($urlconf as $pattern => $callback)
        {
            if(preg_match($pattern, $url, $matchs) == 1)
            {
                array_shift($matchs);
                require_once($callback[0]);
                return call_user_func($funcprefix . $callback[1], $matchs);
            }
        }
        return $this->handle_404();
    }

    function handle_404() {
        header("Status: 404 Not Found");
        echo "404 错误，哎呀呀，没有这个地址啦~^o^~";
    }

    static function get_parameters($query_string)
    {
        $get_parameters = array();
        $pairs = explode('&', $query_string);
        foreach($pairs as $pair) {
            $part = explode('=', $pair);
            $get_parameters[$part[0]] = urldecode($part[1]);
        }
        return $get_parameters;
    }

    function go_request_uri($uri=null)
    {
        if(!$uri)
            $uri = $_SERVER['REQUEST_URI'];
        $arr = parse_url($uri);
        $this->go($arr['path']);
    }
    
}

?>
