<?php

class Configuration{
	
	private static $instance = null;
	
	private function __construct(){
		$this->readFromFile();
	}
	
	private function readFromFile(){
		$filename = __DIR__ . '/../application/configuration.json';
		if(!file_exists($filename)){
			die("Configuration file not found! Were looking at: $filename");
		}
		$contents = file_get_contents($filename);
		if($contents === FALSE){
			die('Configuration file empty!');
		}
		$this->configurations = json_decode($contents);
	}

	public function __get($variableName){
		if (isset($this->configurations->{$variableName})) {
			return $this->configurations->{$variableName};
		}
	}
	
	public static function get(){
		if(self::$instance == null){
			self::$instance = new Configuration();
		}
		return self::$instance;
	}

}