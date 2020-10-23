<?php
namespace api;

class ping extends \API{
	public $db;
	public $auth;
	
	protected $ndb;
	protected $app = "Tactics";	//required for some extended methods
	
	public function __construct($db){
		switch($this->getHTTPMethod()){
			case "POST":
				$this->doPost();
				break;
			default:
				$this->outputError("unsupported method: ".$this->getHTTPMethod());
				break;
		}
	}
	
	private function doPost(){
		//https://docs.battlesnake.com/snake-api#tag/endpoints/paths/~1ping/post
		$this->outputSuccess("");
	}
}
?>