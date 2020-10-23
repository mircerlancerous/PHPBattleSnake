<?php
namespace engines\tools;

//finds closest food(s)
class NearbyFood{
	public $obj;
	
	public function __construct($obj){
		$this->obj = $obj;
		include_once("snakeAssets/models/Move.model.php");
	}
	
	public function countFoodAtMoveDistance($distance,$moveType){
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
			default:return 0;		//todo - should throw an exception instead
		}
		return $this->getFoodCount($distance,$x,$y);
	}
	
	private function getFoodCount($distance,$checkx,$checky){
		$foodcount = 0;
		foreach($this->obj->board->food as $food){
			//error_log("d: $distance, cx: $checkx, cy: $checky, fx: ".$food->x.", fy:".$food->y);
			$x = $checkx - $food->x;
			if($x < 0){
				$x *= -1;
			}
			$y = $checky - $food->y;
			if($y < 0){
				$y *= -1;
			}
			if($x + $y == $distance - 1){
				$foodcount++;
			}
		}
		return $foodcount;
	}
	
	private function makeCellObject($x,$y){
		$obj = new \stdClass();
		$obj->x = $x;
		$obj->y = $y;
		return $obj;
	}
}
?>