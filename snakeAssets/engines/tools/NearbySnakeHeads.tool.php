<?php
namespace engines\tools;

//finds closest snake heads
class NearbySnakeHeads{
	public $obj;
	private $ignoreSmaller = TRUE;
	
	public function __construct($obj){
		$this->obj = $obj;
		include_once("snakeAssets/models/Move.model.php");
	}
	
	public function setIgnoreSmaller($ignore){
		$this->ignoreSmaller = $ignore;
	}
	
	//remove moves that have more snakes closeby than others - never removes all moves
	public function filterMoves($moves,$distance){
		$check = [];
		$min = NULL;
		//count the snake heads within 'distance' moves of our location
		foreach($moves as $i => $move){
			$check[$i] = $this->countAtMoveDistance($move,$distance);
			if($min === NULL || $check[$i] < $min){
				$min = $check[$i];
			}
			//error_log("m: ".$move.", ".$check[$i]);
		}
		//see if there are any moves that are riskier than others (more snakes)
		$count = 0;
		foreach($check as $chk){
			if($chk > $min){
				$count++;
			}
		}
		//remove moves which have more snakes than the lowest option
		if($count > 0){
			foreach($check as $i => $chk){
				if($chk > $min){
					unset($moves[$i]);
				}
			}
			$moves = array_values($moves);
		}
		return $moves;
	}
	
	protected function countAtMoveDistance($moveType,$distance){
		$head = $this->obj->you->body[0];
		$x = $y = 0;
		switch($moveType){
			case \models\MoveTypes::$Up:
				$x = $head->x; $y = $head->y - 1; break;
			case \models\MoveTypes::$Down:
				$x = $head->x; $y = $head->y + 1; break;
			case \models\MoveTypes::$Left:
				$x = $head->x - 1; $y = $head->y; break;
			case \models\MoveTypes::$Right:
				$x = $head->x + 1; $y = $head->y; break;
			default:return 0;
		}
		return $this->getCount($distance,$x,$y);
	}
	
	private function getCount($distance,$checkx,$checky){
		$size = sizeof($this->obj->you->body);
		$count = 0;
		foreach($this->obj->board->snakes as $snake){
			if($snake->id == $this->obj->you->id){
				continue;
			}
			if($this->ignoreSmaller && sizeof($snake->body) < $size){
				continue;
			}
			$snake = $snake->body[0];
			//error_log("d: $distance, cx: $checkx, cy: $checky, fx: ".$snake->x.", fy:".$snake->y);
			$x = $checkx - $snake->x;
			if($x < 0){
				$x *= -1;
			}
			$y = $checky - $snake->y;
			if($y < 0){
				$y *= -1;
			}
			//error_log("x: $x, y: $y");
			if($x + $y == $distance - 1){
				$count++;
			}
		}
		return $count;
	}
	
	private function makeCellObject($x,$y){
		$obj = new \stdClass();
		$obj->x = $x;
		$obj->y = $y;
		return $obj;
	}
}
?>