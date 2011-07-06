<?php

class Request{
	
	protected $server, $get, $post, $files, $cookie, $session;
	protected $isHTTPS = false;
	
	protected $scheme, $url, $host, $port, $path, $query, $fragment;
	protected $requestMethod, $requestTime, $userAgent, $remoteAddr;
	protected $accept, $acceptEncoding, $acceptCharset, $acceptLanguage;
	
	/**
	 * @param $_SERVER	
	 * @param $_GET		
	 * @param $_POST	
	 * @param $_FILES	
	 * @param $_COOKIE	
	 * @param $_SESSION	
	 */
	public function __construct($server, $get, $post, $files, $cookie, $session){
		$this->server = $server;
		$this->get = $get;
		$this->post = $post;
		$this->files = $files;
		$this->cookie = $cookie;
		$this->session = $session;
		
		// Parsing info from $_SERVER / $server:
		
		if($server['HTTPS']){
			$this->scheme = 'https://';
			$this->isHTTPS = true;
		}
		else{
			$this->scheme = 'http://';
		}
		$this->url = $this->scheme . $server['HTTP_HOST'] . $server['REQUEST_URI'];
		$this->host = $server['HTTP_HOST'];
		if(empty($server['SERVER_PORT'])) {
			if($this->isHTTPS){
				$this->port = 443;
			} else {
				$this->port = 80;
			}
		} else {
			$this->port = $server['SERVER_PORT'];
		}
		$this->path = parse_url($this->url, PHP_URL_PATH);
		$this->query = parse_url($this->url, PHP_URL_QUERY);
		$this->fragment = parse_url($this->url, PHP_URL_FRAGMENT);
		
		$this->requestMethod = $server['REQUEST_METHOD'];
		$this->requestTime = $server['REQUEST_TIME'];
		
		$this->userAgent = $server['HTTP_USER_AGENT'];
		$this->accept = $server['HTTP_ACCEPT'];
		$this->acceptEncoding = $server['HTTP_ACCEPT_ENCODING'];
		$this->acceptCharset = $server['HTTP_ACCEPT_CHARSET'];
		$this->acceptLanguage = $server['HTTP_ACCEPT_LANGUAGE'];
		$this->remoteAddr = $server['REMOTE_ADDR'];
	}
	
	function get($name){
		return $this->get[$name];
	}
	
	function post($name){
		return $this->post[$name];
	}
	
	function session($name){
		return $this->session[$name];
	}
	
	function files($name){
		return $this->files[$name];
	}
	
	function cookie($name){
		return $this->cookie[$name];
	}
	
	function getSplittedPath($separator = '/'){
		return explode($separator, $this->server['QUERY_STRING']);
	}
	
	function getInternalPath(){
		return $this->server['QUERY_STRING'];
	}
	
	public function __call($name, $parameters){
		if (isset($this->{$name})) {
			return $this->{$name};
		}
	}
	
}