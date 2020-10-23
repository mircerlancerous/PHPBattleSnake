<?php
namespace engines\unittests;

include_once("snakeAssets/engines/tools/AllowedMoves.tool.php");
class AllowedMoves_filterForWall extends \engines\tools\AllowedMoves{
	private $results = [];
	public $obj;
	
	public function __construct(){
		include_once("snakeAssets/models/Move.model.php");
		include_once("snakeAssets/models/Battle.model.php");
		$this->obj = new \models\Battle();
		$this->obj->board->height = 11;
		$this->obj->board->width = 11;
		
		//set snake to bottom right - 1 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Left
		];
		$this->obj->you->body = [$this->getCoordinateObj(10,10)];
		$this->runTest($allowedMoves,"bottom-right");
		
		//set snake to top right - 1 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Down,
			\models\MoveTypes::$Left
		];
		$this->obj->you->body = [$this->getCoordinateObj(10,0)];
		$this->runTest($allowedMoves,"top-right");
		
		//set snake to top right - 1 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Down,
			\models\MoveTypes::$Right
		];
		$this->obj->you->body = [$this->getCoordinateObj(0,0)];
		$this->runTest($allowedMoves,"top-left");
		
		//set snake to top right - 1 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Right
		];
		$this->obj->you->body = [$this->getCoordinateObj(0,10)];
		$this->runTest($allowedMoves,"bottom-left");
		
		//set snake to middle bottom - 4 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Right,
			\models\MoveTypes::$Left
		];
		$this->obj->you->body = [
			//{"x":5,"y":10},{"x":5,"y":9},{"x":4,"y":9},{"x":4,"y":8}
			$this->getCoordinateObj(5,10),
			$this->getCoordinateObj(5,9),
			$this->getCoordinateObj(4,9),
			$this->getCoordinateObj(4,8)
		];
		$this->runTest($allowedMoves,"bottom-middle");
		
	}
	
	public function getResults(){
		return $this->results;
	}
	
	private function getCoordinateObj($x,$y){
		$obj = new \stdClass();
		$obj->x = $x;
		$obj->y = $y;
		return $obj;
	}
	
	private function getStartOptions(){
		$head = $this->obj->you->body[0];
		$options = [
			$this->getMoveOption($head->x, $head->y-1, \models\MoveTypes::$Up),
			$this->getMoveOption($head->x, $head->y+1, \models\MoveTypes::$Down),
			$this->getMoveOption($head->x-1, $head->y, \models\MoveTypes::$Left),
			$this->getMoveOption($head->x+1, $head->y, \models\MoveTypes::$Right)
		];
		return $options;
	}
	
	
	
	private function runTest($allowedMoves,$label){
		$pass = TRUE;
		$testObj = new \engines\tools\AllowedMoves($this->obj);
		$res = $testObj->filterForWall($this->getStartOptions());
		if(sizeof($res) != sizeof($allowedMoves)){
			$pass = FALSE;
		}
		else{
			foreach($res as $i => $move){
				if(in_array($move->move,$allowedMoves)){
					unset($res[$i]);
				}
			}
		}
		if(sizeof($res) > 0){
			$pass = FALSE;
		}
		if(!$pass){
			$str = "";
			foreach($res as $m){
				if($str){
					$str .= ", ";
				}
				$str .= $m->move;
			}
			$this->results[$label] = $str." (FAIL)";
		}
		else{
			$this->results[$label] = "Pass";
		}
	}
}
?>