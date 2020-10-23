<?php
namespace engines\unittests;

include_once("snakeAssets/engines/tools/MoveInCircle.tool.php");
class MoveInCircle_getMoves extends \engines\tools\MoveInCircle{
	private $results = [];
	public $obj;
	
	public function __construct(){
		include_once("snakeAssets/models/Move.model.php");
		include_once("snakeAssets/models/Battle.model.php");
		$this->obj = new \models\Battle();
		$this->obj->board->height = 11;
		$this->obj->board->width = 11;
		
		$this->obj->you->health = 95;
		//test simple conditions
		$this->testOne();
		$this->testTwo();
		$this->testThree();
		//test complex scenarios
		$this->testTinyClosedBox();
		$this->testSmallClosedBox();
		$this->testTinyOpenBox();
		$this->testSmallOpenBox();
		$this->testSmallOpenBox2();
	}
	
	public function getResults(){
		return $this->results;
	}
	
	private function testOne(){
		//set snake to middle - 1 cell snake
		$this->obj->you->body = [$this->getCoordinateObj(5,5)];
		$exmoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Down,
			\models\MoveTypes::$Left,
			\models\MoveTypes::$Right
		];
		$this->runTest($exmoves,"one-cell snake");
	}
	
	private function testTwo(){
		//2 cell snake going up
		$this->obj->you->body = [
			$this->getCoordinateObj(5,4),
			$this->getCoordinateObj(5,5)
		];
		$exmoves = [
			\models\MoveTypes::$Left,
			\models\MoveTypes::$Right
		];
		$this->runTest($exmoves,"two-cell up");
		
		//2 cell snake going down
		$this->obj->you->body = [
			$this->getCoordinateObj(5,6),
			$this->getCoordinateObj(5,5)
		];
		$exmoves = [
			\models\MoveTypes::$Left,
			\models\MoveTypes::$Right
		];
		$this->runTest($exmoves,"two-cell down");
		
		//4 cell snake going up
		$this->obj->you->body = [
			$this->getCoordinateObj(5,2),
			$this->getCoordinateObj(5,3),
			$this->getCoordinateObj(5,4),
			$this->getCoordinateObj(5,5)
		];
		$exmoves = [
			\models\MoveTypes::$Left,
			\models\MoveTypes::$Right
		];
		$this->runTest($exmoves,"four-cell up");
		
		//4 cell snake going down
		$this->obj->you->body = [
			$this->getCoordinateObj(5,8),
			$this->getCoordinateObj(5,7),
			$this->getCoordinateObj(5,6),
			$this->getCoordinateObj(5,5)
		];
		$exmoves = [
			\models\MoveTypes::$Left,
			\models\MoveTypes::$Right
		];
		$this->runTest($exmoves,"four-cell down");
	}
	
	private function testThree(){
		//2 cell snake going left
		$this->obj->you->body = [
			$this->getCoordinateObj(4,5),
			$this->getCoordinateObj(5,5)
		];
		$exmoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Down
		];
		$this->runTest($exmoves,"two-cell left");
		
		//2 cell snake going right
		$this->obj->you->body = [
			$this->getCoordinateObj(6,5),
			$this->getCoordinateObj(5,5)
		];
		$exmoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Down
		];
		$this->runTest($exmoves,"two-cell right");
		
		//4 cell snake going left
		$this->obj->you->body = [
			$this->getCoordinateObj(2,5),
			$this->getCoordinateObj(3,5),
			$this->getCoordinateObj(4,5),
			$this->getCoordinateObj(5,5)
		];
		$exmoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Down
		];
		$this->runTest($exmoves,"four-cell left");
		
		//4 cell snake going right
		$this->obj->you->body = [
			$this->getCoordinateObj(8,5),
			$this->getCoordinateObj(7,5),
			$this->getCoordinateObj(6,5),
			$this->getCoordinateObj(5,5)
		];
		$exmoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Down
		];
		$this->runTest($exmoves,"four-cell right");
	}
	
	private function testTinyClosedBox(){
		//4 cell box snake
		$this->obj->you->body = [
			$this->getCoordinateObj(5,6),
			$this->getCoordinateObj(6,6),
			$this->getCoordinateObj(6,5),
			$this->getCoordinateObj(5,5)
		];
		$exmoves = [
			\models\MoveTypes::$Up
		];
		$this->runTest($exmoves,"tiny closed box");
	}
	
	private function testSmallClosedBox(){
		//8 cell box snake
		//[{"x":5,"y":5},{"x":6,"y":5},{"x":6,"y":6},{"x":6,"y":7},{"x":5,"y":7},{"x":4,"y":7},{"x":4,"y":6},{"x":4,"y":5}]
		$this->obj->you->body = [
			$this->getCoordinateObj(4,5),
			$this->getCoordinateObj(4,6),
			$this->getCoordinateObj(4,7),
			$this->getCoordinateObj(5,7),
			$this->getCoordinateObj(6,7),
			$this->getCoordinateObj(6,6),
			$this->getCoordinateObj(6,5),
			$this->getCoordinateObj(5,5)
		];
		$exmoves = [
			\models\MoveTypes::$Right,
		];
		$this->runTest($exmoves,"small closed box");
	}
	
	private function testTinyOpenBox(){
		//4 cell box snake
		$this->obj->you->body = [
			$this->getCoordinateObj(5,6),
			$this->getCoordinateObj(6,6),
			$this->getCoordinateObj(6,5)
		];
		$exmoves = [
			\models\MoveTypes::$Up
		];
		$this->runTest($exmoves,"tiny open box");
	}
	
	private function testSmallOpenBox(){
		//8 cell box snake
		//[{"x":6,"y":6},{"x":6,"y":7},{"x":5,"y":7},{"x":4,"y":7},{"x":4,"y":6},{"x":4,"y":5}]
		$this->obj->you->body = [
			$this->getCoordinateObj(4,5),
			$this->getCoordinateObj(4,6),
			$this->getCoordinateObj(4,7),
			$this->getCoordinateObj(5,7),
			$this->getCoordinateObj(6,7),
			$this->getCoordinateObj(6,6)
		];
		$exmoves = [
			\models\MoveTypes::$Right,
		];
		$this->runTest($exmoves,"small open box");
	}
	
	private function testSmallOpenBox2(){
		//8 cell box snake
		//[{"x":6,"y":6},{"x":6,"y":7},{"x":5,"y":7},{"x":4,"y":7},{"x":4,"y":6},{"x":4,"y":5}]
		$this->obj->you->body = [
			$this->getCoordinateObj(6,6),
			$this->getCoordinateObj(6,7),
			$this->getCoordinateObj(5,7),
			$this->getCoordinateObj(4,7),
			$this->getCoordinateObj(4,6),
			$this->getCoordinateObj(4,5)
		];
		$exmoves = [
			\models\MoveTypes::$Left
		];
		$this->runTest($exmoves,"small open box reversed");
	}
	
	private function getCoordinateObj($x,$y){
		$obj = new \stdClass();
		$obj->x = $x;
		$obj->y = $y;
		return $obj;
	}
	
	private function runTest($expectedMoves,$label){
		$testObj = new \engines\tools\MoveInCircle($this->obj);
		$res = $testObj->getMoves();
		$matches = [];
		
		foreach($res as $i => $m){
			foreach($expectedMoves as $v => $em){
				//if($m->x == $em->x && $m->y == $em->y){
				if($m == $em){
					$matches[] = $em;
					unset($res[$i]);
					unset($expectedMoves[$v]);
					break;
				}
			}
		}
		
		if($res || $expectedMoves){
			$this->results[$label] = "FAIL (m: ".json_encode($res).", em: ".json_encode($expectedMoves).", mx: ".json_encode($matches).")";
		}
		else{
			$this->results[$label] = "Pass";
		}
	}
}
?>