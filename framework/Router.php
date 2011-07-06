<?php

class Router{
	
	public function __construct(Request $request){
		// Använder UTF-8-varianten, se http://stackoverflow.com/questions/2744119/setlocale-strftime-issue
		// Krävde installation av locales på Debian, se http://stackoverflow.com/questions/2765247/setlocale-having-no-effect-in-php
		setlocale(LC_ALL, Configuration::get()->environment->locale .".".Configuration::get()->environment->charset);
		// TODO: setlocale() returnerar en sträng eller false om det misslyckades. HANTERA DET.
		// http://se2.php.net/setlocale
		
		date_default_timezone_set(Configuration::get()->environment->timezone);
		
		$session = Session::start($request->remoteAddr());
	}
	
}