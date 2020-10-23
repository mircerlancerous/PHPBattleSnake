<?php
namespace api;

class move extends \API{
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
		
		//check if our snake is alive - the game may never send this
		if($obj->you->health <= 0){
			//we're dead - do nothing/send canned response
			$this->outputSuccess(["move"=>"right"]);
			return;
		}
		
		//lookup the 'you' snake
		if(strpos($obj->you->name,"/") !== FALSE){
			list($github,$name) = explode(" / ",$obj->you->name);
		}
		else{
			$github = "mircerlancerous";
			$name = $obj->you->name;
		}
		$snake = $this->db->query_first("SELECT id,engine FROM snakes WHERE github='".trim($github)."' AND name='".trim($name)."' AND status=".\Status::$active);
		if($snake){
			$engine = $snake['engine'];
		}
		else{
			$engine = \Settings::fetch($this->db,"DEFAULTENGINE");
			$snake = ["id" => NULL];
		}
		//error_log("engine:".$engine);
		//ask the engine to determine the next move
		$res = @include_once("snakeAssets/engines/Engine$engine.class.php");
		if(!$res){
			$this->outputError("invalid snake engine");
			return;
		}
		$name = "\\engines\\Engine$engine";
		$eObj = new $name($this->db,$obj);
		
		$move = $eObj->getMove();
		$this->outputSuccess(["move"=>$move]);
		
		//look up the game id
		$game = $this->db->query_first("SELECT id FROM games WHERE game='".$obj->game->id."'");
		if($game){
			//record request - performed after the request to ensure response is fast
			$req = [
				"status" => \Status::$active,
				"created" => time(),
				"game" => $game['id'],
				"snake" => $snake['id'],
				"command" => "move",
				"response" => $move,
				"json" => json_encode($obj)
			];
			//$this->db->query_insert("requests",$req);
		}
	}
}
?>