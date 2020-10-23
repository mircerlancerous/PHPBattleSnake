<?php
namespace engines\unittests;

include_once("snakeAssets/engines/tools/AllowedMoves.tool.php");
class AllowedMoves_filterForBody extends \engines\tools\AllowedMoves{
	private $results = [];
	public $obj;
	
	public function __construct(){
		include_once("snakeAssets/models/Move.model.php");
		include_once("snakeAssets/models/Battle.model.php");
		$this->obj = new \models\Battle();
		$this->obj->board->height = 11;
		$this->obj->board->width = 11;
		//set health to less than full to start
		$this->obj->you->health = 55;
		$this->runSeries();
		//set health to full - snake just ate food
		$this->obj->you->health = 100;
		$this->runSeries("_fh");
	}
	
	private function runSeries($suff = ""){
		//set snake to middle - 1 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Left,
			\models\MoveTypes::$Down,
			\models\MoveTypes::$Right
		];
		$this->obj->you->body = [$this->getCoordinateObj(5,5),$this->getCoordinateObj(5,5),$this->getCoordinateObj(5,5)];
		$this->runTest($allowedMoves,"1_cell".$suff);
		
		//set snake to middle - 2 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Down,
			\models\MoveTypes::$Left,
			\models\MoveTypes::$Right
		];
		$this->obj->you->body = [
			$this->getCoordinateObj(5,6),
			$this->getCoordinateObj(5,5)
		];
		$this->runTest($allowedMoves,"2_cells".$suff);
		
		//set snake to middle - 3 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Down,
			\models\MoveTypes::$Right
		];
		$this->obj->you->body = [
			$this->getCoordinateObj(6,6),
			$this->getCoordinateObj(5,6),
			$this->getCoordinateObj(5,5)
		];
		$this->runTest($allowedMoves,"3_cells".$suff);
		
		//set snake to middle - 4 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Right
		];
		if($this->obj->you->health < 100){
			$allowedMoves[] = \models\MoveTypes::$Left;
		}
		$this->obj->you->body = [
			$this->getCoordinateObj(6,5),
			$this->getCoordinateObj(6,6),
			$this->getCoordinateObj(5,6),
			$this->getCoordinateObj(5,5)
		];
		$this->runTest($allowedMoves,"4_cells".$suff);	//short snake body
		
		//set snake to middle - 5 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Left,
			\models\MoveTypes::$Right
		];
		$this->obj->you->body = [
			$this->getCoordinateObj(6,4),
			$this->getCoordinateObj(6,5),
			$this->getCoordinateObj(6,6),
			$this->getCoordinateObj(5,6),
			$this->getCoordinateObj(5,5)
		];
		$this->runTest($allowedMoves,"5_cells".$suff);
		
		//set snake to middle - 6 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Left
		];
		if($this->obj->you->health < 100){
			$allowedMoves[] = \models\MoveTypes::$Down;
		}
		$this->obj->you->body = [
			$this->getCoordinateObj(5,4),
			$this->getCoordinateObj(6,4),
			$this->getCoordinateObj(6,5),
			$this->getCoordinateObj(6,6),
			$this->getCoordinateObj(5,6),
			$this->getCoordinateObj(5,5)
		];
		$this->runTest($allowedMoves,"6_cells".$suff);
		
		//set snake to middle - 7 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Left,
			\models\MoveTypes::$Down
		];
		$this->obj->you->body = [
			$this->getCoordinateObj(4,4),
			$this->getCoordinateObj(5,4),
			$this->getCoordinateObj(6,4),
			$this->getCoordinateObj(6,5),
			$this->getCoordinateObj(6,6),
			$this->getCoordinateObj(5,6),
			$this->getCoordinateObj(5,5)
		];
		$this->runTest($allowedMoves,"7_cells".$suff);
		
		//set snake to middle - 8 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Left,
			\models\MoveTypes::$Down
		];
		if($this->obj->you->health < 100){
			$allowedMoves[] = \models\MoveTypes::$Right;
		}
		$this->obj->you->body = [
			$this->getCoordinateObj(4,5),
			$this->getCoordinateObj(4,4),
			$this->getCoordinateObj(5,4),
			$this->getCoordinateObj(6,4),
			$this->getCoordinateObj(6,5),
			$this->getCoordinateObj(6,6),
			$this->getCoordinateObj(5,6),
			$this->getCoordinateObj(5,5)
		];
		$this->runTest($allowedMoves,"8_cells".$suff);
		
		//set snake to middle - 9 cell snake
		$allowedMoves = [
			\models\MoveTypes::$Left,
			\models\MoveTypes::$Down
		];
		$this->obj->you->body = [
			$this->getCoordinateObj(4,6),
			$this->getCoordinateObj(4,5),
			$this->getCoordinateObj(4,4),
			$this->getCoordinateObj(5,4),
			$this->getCoordinateObj(6,4),
			$this->getCoordinateObj(6,5),
			$this->getCoordinateObj(6,6),
			$this->getCoordinateObj(5,6),
			$this->getCoordinateObj(5,5)
		];
		$this->runTest($allowedMoves,"9_cells".$suff);
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
		$str = "";
		$testObj = new \engines\tools\AllowedMoves($this->obj);
		$res = $testObj->filterForBody($this->obj->you,$this->getStartOptions());
		if(sizeof($res) != sizeof($allowedMoves)){
			$pass = FALSE;
			$str = "* ".sizeof($res)." - ".sizeof($allowedMoves);
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