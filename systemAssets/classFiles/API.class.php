<?php
class API{
	public function __construct($db,$version,$op=NULL,$id=NULL){
		//validate api version
		$apiPath = "snakeAssets/api/";//realpath(get_include_path());
		if(!file_exists($apiPath.$version)){
			$this->outputError("invalid api for $version/$op");
			return;
		}
		/*
		//check for operation type
		if(!$op && isset($_GET['op'])){error_log("abc");
			$op = $_GET['op'];
		}
		*/
		//check if this API version contains a compatible operation
		if(!file_exists($apiPath."$version/$op.api.php")){
			$this->outputError("invalid op: $version/$op");
			return;
		}
		
		//check if this is the 'OPTIONS' method
		if($this->getHTTPMethod() == "OPTIONS"){
			$this->sendBaseHeaders(FALSE,TRUE);
			return;
		}
		
		//call the API operation
		$res = include_once($apiPath."$version/$op.api.php");
		if(!$res){
			$this->outputError("op not found: api/$version/$op.api.php");
			return;
		}
		$op = "\\api\\".$op;
		$obj = new $op($db);
	}
	
	protected function getHTTPMethod(){
		return strtoupper($_SERVER['REQUEST_METHOD']);
	}
	
	protected function getRequestBody(){
		$obj = file_get_contents('php://input');
		$obj = json_decode($obj);
		//if content cannot be decoded
		if($obj === NULL){
			$this->outputError("invalid content encoding");
			exit;
		}
		
		return $obj;
	}
	
	private function sendBaseHeaders($returnInstead=FALSE,$isOptions=FALSE){
		$headers = array(
	//		'Content-Type: text/plain; charset=utf-8',
			'Access-Control-Allow-Origin: *',
			'Access-Control-Allow-Headers: Content-Type'
		);
		if($isOptions){
			$headers[] = 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS';
	//		$headers[] = 'Access-Control-Allow-Headers: authorization';
		}
		if($returnInstead){
			return $headers;
		}
		foreach($headers as $header){
			header($header);
		}
	}
	
	protected function outputSuccess($obj,$needEncoding=TRUE){
		if($needEncoding && $obj){
			$obj = json_encode($obj);
		}
		ob_end_clean();		//need to send this before headers for some reason - http://php.net/manual/en/features.connection-handling.php
		
		$this->sendBaseHeaders();
	    header('Content-type: application/json; charset=utf-8');
		header('Content-Length: '.strlen($obj));
		
		ignore_user_abort(TRUE);
		ob_start();
			echo $obj;
		ob_end_flush();
		flush();
		@ob_end_clean();		//may not be required
		
		if(function_exists("fastcgi_finish_request")){
			@fastcgi_finish_request();
		}
	}
	
	protected function outputError($msg="",$code=400){
		http_response_code($code);
		if($msg){
	    	header("Content-type: text/plain; charset=utf-8");
	    	header("Content-Length: ".strlen($msg));
	    }
	    echo $msg;
	}
	
	protected function connectToAppDB($exitOnError=TRUE){
		//init the app database
		$adb = new \sqlite\Database(\Config::getAppDBName($this->app),!\Config::isProduction());
		if(!$adb->Connect()){
			if($exitOnError){
				$this->outputError("error connecting to app database");
				exit;
			}
			else{
				return FALSE;
			}
		}
		return $adb;
	}
}
?>