<?php
class Status{
	public static $active = 1;
	public static $inactive = 0;
	public static $deleted = 9;
}

class Config{
	public static function dbVersion($dbname,$dbtype="sqlite"){
		if($dbtype == "sqlite"){
			switch($dbname){
				case 'games':return 3;
				default:break;
			}
		}
		else if($dbtype == "mysql"){
			switch($dbname){
				case "games":return 1;
				default:break;
			}
		}
		return 1;
	}
	
	public static function dbDefinitionPath($dbname,$dbtype="sqlite"){
		/*
		switch($dbname){
			case 'games':return "classFiles/";
			default:break;
		}
		*/
		return NULL;
	}
	
	public static function getAppDBName($app){
		switch($app){
			case "Battlesnake":return "games";
			default:break;
		}
		return NULL;
	}
	
	public static function isProduction(){
		//server url
		$servername = $_SERVER['SERVER_NAME'];
		if(isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']){
			$servername = $_SERVER['HTTP_HOST'];
		}
		//server domain
		$domain = explode(".",$servername);
		if(sizeof($domain) == 1){		//mostly just to support dev on localhost
			$domain = $domain[0];
			$sub = "";
		}
		else{
			list($sub,$domain) = $domain;
		}
		
		if($sub == 'www'){
			return TRUE;
		}
		return FALSE;
	}
	
	public static function getServerName(){
		$servername = $_SERVER['SERVER_NAME'];
		if(isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']){
			$servername = $_SERVER['HTTP_HOST'];
		}
		return $servername;
	}
	
	public static function getRelativePath($servername=NULL){
		if($servername === NULL){
			$servername = self::getServerName();
		}
		$url = realpath(get_include_path()."/");
		$url = str_replace("\\","/",$url);				//do this here as DOCUMENT_ROOT will not have backslashes, even on Windows
		//if the path contains a drive letter, then drop it
		if(substr($url,1,1) == ":"){
			$url = substr($url,2);
			$url = str_replace(substr($_SERVER["DOCUMENT_ROOT"],2),"",$url);
		}
		else if(strpos($url,$_SERVER["DOCUMENT_ROOT"]) !== FALSE){
			$url = str_replace($_SERVER["DOCUMENT_ROOT"],"",$url);
		}
		//handle linux symlink
		else{
			$url = get_include_path();
			$count = 1;
			switch($url){
				case ".":break;
				case "..":
					$count = 2;break;
				default:
					$count = substr_count($url,"/");
					//if the last character is a slash, one less
					if(substr($url,strlen($url)-1,1) == "/"){
						$count--;
					}
					break;
			}
			$url = $_SERVER['PHP_SELF'];
			for($i=0; $i<$count; $i++){
				$url = substr($url,0,strrpos($url,"/"));
			}
		}
		if($url && strpos($url,"/") !== 0 && strrpos($servername,"/") !== strlen($servername) - 1){
			$url = "/".$url;
		}
		return $url."/";
	}
	
	public static function getRootURL(){
		$servername = self::getServerName();
		$url = self::getRelativePath($servername);
		$url = "https://".$servername.$url;
		return $url;
	}
	
	public static function fetchHttpHeaders($target=NULL){
		$target = strtoupper($target);
		$final = $headers = array();
		foreach($_SERVER as $name => $value){
			if(substr($name, 0, 5) == 'HTTP_'){
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		foreach($headers as $name => $value){
			$name = strtoupper($name);
			if($target && $target == $name){
				return $value;
			}
			$final[$name] = $value;
		}
		if($target){
			return FALSE;
		}
		return $final;
	}
}

class Settings{
	public static function fetch($db,$name){
		$id = self::getIdFromName($db,$name);
		return $db->query_single("SELECT value FROM settings WHERE id=$id");
	}
	
	public static function update($db,$name,$value){
		$id = self::getIdFromName($db,$name);
		$data = ["value"=>$value];
		return $db->query_update("settings",$data,"id=$id");
	}
	
	private static function getIdFromName($db,$name){
		if($name == "VERSION"){
			return 1;
		}
		switch($db->getDBName(TRUE)){
			default:
			case "games":
				return self::getGamesDBID($name);
				break;
		}
	}
	
	private static function getGamesDBID($name){
		switch($name){
			case "DEFAULTENGINE":return 2;
			default:break;
		}
		return NULL;
	}
}
?>