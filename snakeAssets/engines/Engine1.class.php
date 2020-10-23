<?php
namespace engines;

/*
This engine moves in a 'random' direction determined by the time (ms) of the request,
	as long as the move is safe at the time.
	If no moves are safe, then 'right' will be returned.
This engine does not look for food.
*/
class Engine1{
	private $move;
	
	public function __construct($db,$obj){
		include_once("snakeAssets/models/Move.model.php");
		
		//if already dead
		if($obj->you->health <= 0){
			return \models\MoveTypes::$Right;
		}
		
		//get allowed moves - filter out moves that might not be allowed due to the walls and snakes
		//considers snakes that are smaller and will die in head-on collision
		$moves = $this->getAllowedMoves($obj);
		//if no safe moves
		if(!$moves){
			$this->move = \models\MoveTypes::$Right;
		}
		//if just one safe move
		else if(sizeof($moves) == 1){
			$this->move = $moves[0];
		}
		else{
			//filter out moves that are higher risk of snake head on collisions
			$moves = $this->filterForSnakeHeads($obj,$moves);
			//select a random move from those remaining (handles just one move fine)
			$this->selectRandomMove($moves);
		}
	}
	
	public function getMove(){
		return $this->move;
	}
	
	private function getAllowedMoves($obj){
		include_once("snakeAssets/engines/tools/AllowedMoves.tool.php");
		$tool = new tools\AllowedMoves($obj);
		return $tool->Get();
	}
	
	private function filterForSnakeHeads($obj,$moves){
		include_once("snakeAssets/engines/tools/NearbySnakeHeads.tool.php");
		$tool = new tools\NearbySnakeHeads($obj);
		//remove any moves which have snakes only two moves away
		return $tool->filterMoves($moves,2);
	}
	
	private function selectRandomMove($moves){
		include_once("snakeAssets/engines/tools/RandomMove.tool.php");
		$this->move = tools\RandomMove::Get($moves);
	}
}
?>