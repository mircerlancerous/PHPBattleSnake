<?php
namespace api;

class end extends \API{
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
		//https://docs.battlesnake.com/snake-api#tag/endpoints/paths/~1start/post
		$body = $this->getRequestBody();
		//prepare model
		include_once("snakeAssets/models/Battle.model.php");
		$obj = new \models\Battle();
		include_once("systemAssets/classFiles/Utilities.class.php");
		\Utilities::InitModel($obj,$body,FALSE);
		
		$this->outputSuccess("");
		
		//look up the game id
		$game = $this->db->query_first("SELECT id,status FROM games WHERE game='".$obj->game->id."'");
		if($game && $game['status'] == \Status::$active){
			//save event data
			$data = [
				"status" => \Status::$inactive,
				"ended" => time()
			];
			$res = $this->db->query_update("games",$data,"id=".$game['id']);
		}		
		
		//lookup the 'you' snake
		if(strpos($obj->you->name,"/") !== FALSE){
			list($github,$name) = explode(" / ",$obj->you->name);
		}
		else{
			$github = "mircerlancerous";
			$name = $obj->you->name;
		}
		$snake = $this->db->query_first("SELECT id,status FROM snakes WHERE github='".trim($github)."' AND name='".trim($name)."' AND status=".\Status::$active);
		
		//record request - performed after the request to ensure response is fast
		$req = [
			"status" => \Status::$active,
			"created" => time(),
			"game" => $game['id'],
			"snake" => $snake['id'],
			"command" => "end",
			"json" => json_encode($obj)
		];
		$this->db->query_insert("requests",$req);
	}
}
?>