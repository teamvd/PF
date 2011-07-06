<?php
	
	function __autoload($class){
		require(__DIR__ . "/framework/$class.php");
	}
	
	$request = new Request($_SERVER, $_GET, $_POST, $_FILES, $_COOKIE, $_SESSION);
	
	$router = new Router($request);
	
?>