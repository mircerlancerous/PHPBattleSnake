<?php
namespace engines\unittests;

include_once("snakeAssets/engines/tools/NearbyFood.tool.php");
class NearbyFood_getDistanceToFood extends \engines\tools\NearbyFood{
	private $results = [];
	public $obj;
	
	public function __construct(){
		include_once("snakeAssets/models/Move.model.php");
		include_once("snakeAssets/models/Battle.model.php");
		$this->obj = new \models\Battle();
		$this->obj->board->height = 11;
		$this->obj->board->width = 11;
		
		$this->testStandardConditions();
	}
	
	public function getResults(){
		return $this->results;
	}
	
	private function testStandardConditions(){
		//set snake to middle - 1 cell snake
		$this->obj->you->body = [$this->getCoordinateObj(5,5)];
		//**NOTE: all test use '\models\MoveTypes::$Right' as the move type
		//test for no food
		$this->obj->board->food = [];
		$this->runTest(1,0,"no_food");
		//test with one food outside check distance
		$this->obj->board->food = [
			$this->getCoordinateObj(1,5)
		];
		$this->runTest(4,0,"far_foodA");
		//test with three food outside check distance
		$this->obj->board->food = [
			$this->getCoordinateObj(7,3),
			$this->getCoordinateObj(8,6),
			$this->getCoordinateObj(6,8)
		];
		$this->runTest(2,0,"far_foodB");
		//test with one food one move away
		$this->obj->board->food = [
			$this->getCoordinateObj(6,5)
		];
		$this->runTest(1,1,"one_move_food");
		//test with one food, two moves away - up
		$this->obj->board->food = [
			$this->getCoordinateObj(6,4)
		];
		$this->runTest(2,1,"two_move_foodA");
		//test with one food, two moves away - right
		$this->obj->board->food = [
			$this->getCoordinateObj(7,5)
		];
		$this->runTest(2,1,"two_move_foodB");
		//test with one food, two moves away - down
		$this->obj->board->food = [
			$this->getCoordinateObj(6,6)
		];
		$this->runTest(2,1,"two_move_foodC");
		//test with two food, two moves away
		$this->obj->board->food = [
			$this->getCoordinateObj(6,4),
			$this->getCoordinateObj(6,6)
		];
		$this->runTest(2,2,"two_move_two_food");
		//test with two food, three moves away
		$this->obj->board->food = [
			$this->getCoordinateObj(7,4),
			$this->getCoordinateObj(6,7)
		];
		$this->runTest(3,2,"three_move_two_food");
		//test with three food, four moves away
		$this->obj->board->food = [
			$this->getCoordinateObj(7,3),
			$this->getCoordinateObj(8,6),
			$this->getCoordinateObj(6,8)
		];
		$this->runTest(4,3,"four_move_three_food");
	}
	
	private function getCoordinateObj($x,$y){
		$obj = new \stdClass();
		$obj->x = $x;
		$obj->y = $y;
		return $obj;
	}
	
	private function runTest($distance,$count,$label){
		$pass = TRUE;
		$testObj = new \engines\tools\NearbyFood($this->obj);
		$res = $testObj->countFoodAtMoveDistance($distance,\models\MoveTypes::$Right);
		if($res !== $count){
			$pass = FALSE;
		}
		if(!$pass){
			$this->results[$label] = "FAIL ($res : $distance : $count)";
		}
		else{
			$this->results[$label] = "Pass ($res)";
		}
	}
}
?>