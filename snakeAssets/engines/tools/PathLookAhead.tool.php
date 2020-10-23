<?php
namespace engines\tools;

class PathLookAhead{
	public $obj;
	
	private $dangerList = [];
	private $safeList = [];
	private $countMax;
	private $count;
	
	public function __construct($obj){
		$this->obj = $obj;
		
		foreach($obj->board->snakes as $snake){
			$this->dangerList = array_merge($this->dangerList,$snake->body);
		}//error_log(json_encode($this->dangerList));
		//if we haven't just eaten, then remove the tail from the dangerList
		if($obj->you->health < 100){
			$tail = $obj->you->body[sizeof($obj->you->body) - 1];
			unset($this->dangerList[$this->inList($tail,"dangerList")]);
		}
	}
	
	//counts how many safe squares exist to move into if nothing moves and the passed move is taken
	//helps to prevent heading down a hole with no escape
	public function Count($moveType,$max=NULL){
		$this->countMax = $max;
		$this->count = 0;
		
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
		$this->safeList = [];
		$this->checkCoordinate($this->makeCellObject($x, $y));
		
		return $this->count;
	}
	
	private function makeCellObject($x,$y){
		$obj = new \stdClass();
		$obj->x = $x;
		$obj->y = $y;
		return $obj;
	}
	
	private function checkCoordinate($coord){
		//is this cell dangerous or already checked
		if($this->isOutOfBounds($coord) || $this->inList($coord,"dangerList") !== FALSE || $this->inList($coord,"safeList") !== FALSE){
			return;
		}
		$this->count++;
		$this->safeList[] = $coord;
		if($this->countMax && $this->count >= $this->countMax){
			return;
		}
		//check all four surrounding options
		$this->checkCoordinate($this->makeCellObject($coord->x + 1, $coord->y));
		$this->checkCoordinate($this->makeCellObject($coord->x - 1, $coord->y));
		$this->checkCoordinate($this->makeCellObject($coord->x, $coord->y + 1));
		$this->checkCoordinate($this->makeCellObject($coord->x, $coord->y - 1));
	}
	
	private function isOutOfBounds($value){
		if($value->x < 0 || $value->y < 0 || $value->x >= $this->obj->board->width || $value->y >= $this->obj->board->height){
			return TRUE;
		}
		return FALSE;
	}
	
	private function inList($value,$listname){
		foreach($this->$listname as $i => $coord){
			if($this->isEqual($value,$coord)){
				return $i;
			}
		}
		return FALSE;
	}
	
	private function isEqual($value,$coord){
		if($value->x == $coord->x && $value->y == $coord->y){
			return TRUE;
		}
		return FALSE;
	}
}
?>