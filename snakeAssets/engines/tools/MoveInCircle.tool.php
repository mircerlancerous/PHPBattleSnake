<?php
namespace engines\tools;

//determine move to travel in a circle path without dying
class MoveInCircle{
	public $obj;
	
	public function __construct($obj){
		$this->obj = $obj;
		include_once("snakeAssets/models/Move.model.php");
	}
	
	public function getMoves(){
		$head = $this->obj->you->body[0];
		
		$coords = $this->getMoveOptions();
		$moves = [];
		
		foreach($coords as $c){
			if($c->x == $head->x){
				if($c->y == $head->y - 1){
					$moves[] = \models\MoveTypes::$Up;
				}
				//y+1
				else{
					$moves[] = \models\MoveTypes::$Down;
				}
			}
			else{
				if($c->x == $head->x - 1){
					$moves[] = \models\MoveTypes::$Left;
				}
				//x+1
				else{
					$moves[] = \models\MoveTypes::$Right;
				}
			}
		}
		
		return $moves;
	}
	
	private function getMoveOptions(){
		//get snake body
		$body = $this->obj->you->body;
		$head = $body[0];
		$len = sizeof($body);
		if($len == 1){
			$moves = [
				$this->coordinateObject($head->x,$head->y - 1),
				$this->coordinateObject($head->x,$head->y + 1),
				$this->coordinateObject($head->x - 1,$head->y),
				$this->coordinateObject($head->x + 1,$head->y)
			];
			return $moves;
		}
		//find displacement of tail to head
		$tail = $body[$len-1];
		$x = $head->x - $tail->x;
		$y = $head->y - $tail->y;
		
		if($len == 2){
			//if the tail is straight above or below the head
			if($x == 0){
				$moves = [
					$this->coordinateObject($head->x - 1,$head->y),
					$this->coordinateObject($head->x + 1,$head->y)
				];
				return $this->filterMovesForSelf($moves);
			}
			//if the tail is on the same row as the head
			if($y == 0){
				$moves = [
					$this->coordinateObject($head->x,$head->y - 1),
					$this->coordinateObject($head->x,$head->y + 1)
				];
				return $this->filterMovesForSelf($moves);
			}
		}
		
		//prepare final move options
		$moves = [];
		//normalize sign to determine priority
		$nx = $x;
		$x = -1;
		if($nx < 0){
			$nx *= -1;
			$x = 1;
		}
		$ny = $y;
		$y = -1;
		if($ny < 0){
			$ny *= -1;
			$y = 1;
		}
		//if equal priority
		if($nx == $ny){
			$moves = $this->filterMovesForSelf([
				$this->coordinateObject($head->x + $x,$head->y),
				$this->coordinateObject($head->x,$head->y + $y)
			]);
		}
		//if x is more important
		else if($nx > $ny){
			$moves = $this->filterMovesForSelf([
				$this->coordinateObject($head->x + $x,$head->y)
			]);
			if(!$moves){
				$moves = $this->filterMovesForSelf([
					$this->coordinateObject($head->x, $head->y-1),
					$this->coordinateObject($head->x, $head->y+1),
				]);
			}
		}
		//y must be the most important
		else{
			$moves = $this->filterMovesForSelf([
				$this->coordinateObject($head->x,$head->y + $y)
			]);
			if(!$moves){
				$moves = $this->filterMovesForSelf([
					$this->coordinateObject($head->x-1, $head->y),
					$this->coordinateObject($head->x+1, $head->y)
				]);
			}
		}
		
		return $moves;
	}
	
	private function filterMovesForSelf($moves){
		$body = $this->obj->you->body;
		//if we're not at full health, then we don't have to worry about hitting the tail, so remove it from the list
		if($this->obj->you->health < 100){
			unset($body[sizeof($body)-1]);
		}
		foreach($moves as $i => $m){
			foreach($body as $b){
				if($m->x == $b->x && $m->y == $b->y){
					unset($moves[$i]);
					break;
				}
			}
		}
		return array_values($moves);
	}
	
	private function coordinateObject($x,$y){
		$obj = new \stdClass();
		$obj->x = $x;
		$obj->y = $y;
		return $obj;
	}
}
?>