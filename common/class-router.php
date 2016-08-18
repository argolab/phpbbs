<?php
// define('ABSPATH', dirname(__FILE__));
/* Simple URL Router */

class Router {
	var $url;

	function __construct() {
		/* omit requests */        
		$arr = parse_url($_SERVER['REQUEST_URI']);

        /* Ugly !! rewrite to api for json post */
        if(strpos($arr['path'], '/api', 0) === 0)
        {
            $rest_json = file_get_contents("php://input");
            if($rest_json &&
               !($_GET = $_POST = json_decode($rest_json, true)))
            {
                ajax_error('Wrong Json Arguments.', 104);
                return;
            }
            $arr['path'] = '/ajax' . substr($arr['path'], 4);
        }
        
        $this->url = $arr['path'];

        if (isset($arr['query'])) {
            $_GET = $this->get_parameters($arr['query']); 
        }

	}
    
    function get_parameters($query_string)
    {
        $get_parameters = array();
        $pairs = explode('&', $query_string);
        foreach($pairs as $pair) {
            $part = explode('=', $pair);
            $get_parameters[$part[0]] = urldecode($part[1]);
        }
        return $get_parameters;
    }

    function init() 
    {
    	$this->parse_urls();
	}

    function parse_urls() {
        $urlconf = $this->get_rules();
		foreach($urlconf as $pattern => $callback) {
            if (preg_match($pattern, $this->url, $matches) == 1) {
				return $this->dispatch($callback, $matches);
			}
        }
		return $this->handle_404();
	}
	function get_rules() {
		return require("urls.conf.php");
	}
	
	function dispatch($callback, $matches) {
		array_shift($matches);
		require_once($callback[0]);       
		call_user_func_array($callback[1], $matches);
	}

	function handle_404() {
        header("Status: 404 Not Found");
		echo "404 错误，哎呀呀，没有这个地址啦~^o^~";
	}
}
?>
