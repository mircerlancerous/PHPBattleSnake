<?php
namespace api;

class start extends \API{
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
		//check if this game already exists
		$check = $this->db->query_first("SELECT id,ended,snakes FROM games WHERE game='".$obj->game->id."'");
		//if game has already ended, don't allow more snakes to be added
		if($check && $check['ended']){
			$this->outputError("game has already ended");
			return;
		}
		else if(!$check){
			$check = [
				"id" => NULL,
				"ended" => NULL,
				"snakes" => ""
			];
		}
		//lookup the 'you' snake
		if(strpos($obj->you->name,"/") !== FALSE){
			list($github,$name) = explode(" / ",$obj->you->name);
		}
		else{
			$github = "mircerlancerous";
			$name = $obj->you->name;
		}
		$snake = $this->db->query_first("SELECT id,colour,head,tail FROM snakes WHERE github='".trim($github)."' AND name='".trim($name)."' AND status=".\Status::$active);
		if($snake){
			$snakes = explode(",",$check['snakes']);
			if(!in_array($snake['id'],$snakes)){
				$check['snakes'] .= $snake['id'].",";
			}
		}
		
		$robj = [
			"color" => "#4088ff",
    		"headType" => "evil",
    		"tailType" => "curled"
    	];
		if($snake['colour']){
			$robj['color'] = $snake['colour'];
		}
		if($snake['head']){
			$robj['headType'] = $snake['head'];
		}
		if($snake['tail']){
			$robj['tailType'] = $snake['tail'];
		}
		
		$this->outputSuccess($robj);
		
		//save new game data
		if(!$check['id']){
			$data = [
				"status" => \Status::$active,
				"created" => time(),
				"game" => $obj->game->id,
				"snakes" => $check['snakes']
			];
			$res = $this->db->query_insert("games",$data);
			if($res){
				$check['id'] = $res;
			}
		}
		//add snake to game
		else{
			$data = [
				"snakes" => $check['snakes']
			];
			$res = $this->db->query_update("games",$data,"id=".$check['id']);
		}
		//if error
		if(!$res){
			//do nothing for now...
		}
		
		//record request - performed after the request to ensure response is fast
		$req = [
			"status" => \Status::$active,
			"created" => time(),
			"game" => $check['id'],
			"snake" => $snake['id'],
			"command" => "start",
			"json" => json_encode($obj)
		];
		$this->db->query_insert("requests",$req);
	}
}
?>