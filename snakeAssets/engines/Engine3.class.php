<?php
namespace engines;

/*
This engine attempts to follow its tail as closely as possible.
	The engine will increase food priority as health decreases.
	Food only one move away will always be eaten.
	If no moves are safe, then 'right' will be returned.
*/
class Engine3{
	private $move;
	private $minsize = 8;
	
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
			//error_log("C ".json_encode($moves));
			if(sizeof($moves) > 1){
				$moves = $this->filterForSpace($obj,$moves);//error_log("D ".json_encode($moves));
			}
			//filter out moves that are higher risk of snake head on collisions
			$moves = $this->filterForSnakeHeads($obj,$moves);
			//filter for food - if some moves have food of a similar distance away, use those and remove the rest
			$moves = $this->filterForFood($obj,$moves);//error_log("A ".json_encode($moves)." - ".sizeof($obj->you->body));
			//only circle once the snake is a minimum size
			if(sizeof($obj->you->body) >= $this->minsize){
				//select a move to set us following our tail
				$moves = $this->followTail($obj,$moves);//error_log("B ".json_encode($moves));
			}
			
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
		
		$d = 8;
		//aggressively look for food if less than minimum size
		if(sizeof($obj->you->body) >= $this->minsize){
			$d = 0;
			if($obj->you->health > 60){
				//do nothing $d == 1
			}
			else if($obj->you->health > 50){
		//		$d = 2;		//start looking for food up to two moves away
			}
			else if($obj->you->health > 40){
		//		$d = 3;
			}
			else if($obj->you->health > 30){
				$d = 4;
			}
			else{
				$d = 5;
			}
		}
		
		for($i=1; $i<=$d; $i++){
			$check = [];
			$foundFood = FALSE;
			foreach($moves as $v => $move){
				//error_log("search for food: $move, $i");
				$dist = $tool->countFoodAtMoveDistance($i,$move);
				//error_log("search result: $dist");
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
		//remove any moves which have snakes only one move away - calculation requires a two to be entered for one for now
		return $tool->filterMoves($moves,2);
	}
	
	private function filterForSpace($obj,$moves){
		include_once("snakeAssets/engines/tools/PathLookAhead.tool.php");
		$tool = new tools\PathLookAhead($obj);
		
		$count = [];
		$max = NULL;
		$len = sizeof($obj->you->body);
		foreach($moves as $i => $m){
			$num = $tool->Count($m,$len);
			if($max === NULL || $num > $max){
				$max = $num;
			}
			//error_log("m: $m-".$num);
			$count[$i] = $num;
		}
		if($max > $len){
			$max = $len;
		}
		foreach($moves as $i => $m){
			if($count[$i] < $max){
				unset($moves[$i]);
				//error_log("removed: $m, ".$count[$i]);
			}
		}
		
		return array_values($moves);
	}
	
	private function followTail($obj,$moves){
		include_once("snakeAssets/engines/tools/MoveInCircle.tool.php");
		$tool = new tools\MoveInCircle($obj);
		$ftmoves = $tool->getMoves();
		$final = [];
		foreach($ftmoves as $ft){
			foreach($moves as $m){
				if($ft == $m){
					$final[] = $m;
					break;
				}
			}
		}
		if($final){
			return $final;
		}
		return $moves;
	}
	
	private function selectRandomMove($moves){
		include_once("snakeAssets/engines/tools/RandomMove.tool.php");
		$this->move = tools\RandomMove::Get($moves);
	}
}
?>