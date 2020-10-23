<?php
namespace api;

class purgetests extends \API{
	public $db;
	
	protected $app = "Tactics";	//required for some extended methods
	
	public function __construct($db){
		$this->db = $db;
		
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
		$this->db->query("DELETE FROM games WHERE game LIKE '%abc123-%'");
		
		$this->outputSuccess("tests purged");
	}
}
?>