<?php
namespace engines;

/*
This engine prioritizes food eating, and will make a move towards food if it is safe to do so.
	If no food is found, a random move is selected.
	If no moves are safe, then 'right' will be returned.
*/
class Engine2{
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
			//filter for food - if some moves have food of a similar distance away, use those and remove the rest
			$moves = $this->filterForFood($obj,$moves);
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
	
	private function filterForFood($obj,$moves){
		include_once("snakeAssets/engines/tools/NearbyFood.tool.php");
		$tool = new tools\NearbyFood($obj);
		for($i=1; $i<=5; $i++){
			$check = [];
			$foundFood = FALSE;
			foreach($moves as $v => $move){
				//error_log("search for food: $move, $i");
				$dist = $tool->countFoodAtMoveDistance($i,$move);
				if($dist > 0){
					$foundFood = TRUE;
				}
				$check[$v] = $dist;
			}
			if($foundFood){
				//error_log("food at:".$i);
				//filter out all moves that did not find food
				foreach($check as $v => $chk){
					if(!$chk){
						unset($moves[$v]);
					}
				}
				$moves = array_values($moves);
				break;
			}
		}
		return $moves;
	}
	
	private function filterForSnakeHeads($obj,$moves){
		include_once("snakeAssets/engines/tools/NearbySnakeHeads.tool.php");
		$tool = new tools\NearbySnakeHeads($obj);
		//remove any moves which have snakes only two moves away - never removes all passed move options
		return $tool->filterMoves($moves,2);
	}
	
	private function selectRandomMove($moves){
		include_once("snakeAssets/engines/tools/RandomMove.tool.php");
		$this->move = tools\RandomMove::Get($moves);
	}
}
?>