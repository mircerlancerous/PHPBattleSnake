<?php
//redirect if access to a sub folder is requested
$root = str_replace("index.php","",$_SERVER['PHP_SELF']);
$path = str_replace($root,"",$_SERVER['REQUEST_URI']);
if(strpos($path,"/") !== FALSE){
	//header("Location: ".$root);
	//exit;
}
/*
if(class_exists("SQLite3")){
	echo "has sqlite";
}
else{
	echo "no sqlite";
}
phpinfo();
exit;
*/

function initSystem($sqlite=TRUE){
	set_include_path("./");
	date_default_timezone_set("America/Vancouver");
	include_once("systemAssets/classFiles/Config.class.php");
	if($sqlite){
		include_once("systemAssets/classFiles/Database/SQLite/DB.SQLite3.class.php");
		$db = new \sqlite\Database("games",!\Config::isProduction());
	}
	else{
		include_once("systemAssets/classFiles/Database/MySQL/DB.MySQL.class.php");
		$db = new \mysql\Database("games",!\Config::isProduction());
	}
	if(!$db->Connect()){
		error_log("error connecting to games database");
		exit;
	}
	return $db;
}

$pos = strpos($path,"?");
if($pos !== FALSE){
	//restore $_GET
	parse_str(substr($path,$pos+1),$_GET);
	//var_dump($_GET);
	//strip parameters from the path
	$path = substr($path,0,$pos);
}

$path = explode("/",$path);//var_dump($path);exit;
while(sizeof($path) < 4){
	$path[] = NULL;
}
list($serv,$ver,$op,$id) = $path;

switch($serv){
	case "api":
		$db = initSystem();
		include_once("systemAssets/classFiles/API.class.php");
		$api = new \API($db,$ver,$op,$id);
		exit;
	case "view":
		$_GET['view'] = $ver;
	default:
		if(!isset($_GET['view'])){
			//redirect to error page
			
			exit;
		}
		$db = initSystem();
		include_once("adminAssets/views/Main.view.php");
		$ctrl = new \views\Main($db,$root);
		break;
}
?>