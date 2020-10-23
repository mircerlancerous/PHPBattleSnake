<?php
namespace engines\unittests;

include_once("snakeAssets/engines/tools/RandomMove.tool.php");
class RandomMove_Get extends \engines\tools\RandomMove{
	private $results = [];
	public $obj;
	
	public function __construct(){
		include_once("snakeAssets/models/Move.model.php");
		$moves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Left,
			\models\MoveTypes::$Right,
			\models\MoveTypes::$Down
		];
		$this->runTest($moves,"four_moves");
		$moves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Left,
			\models\MoveTypes::$Right
		];
		$this->runTest($moves,"three_moves");
		$moves = [
			\models\MoveTypes::$Up,
			\models\MoveTypes::$Left
		];
		$this->runTest($moves,"two_moves");
		$moves = [
			\models\MoveTypes::$Up
		];
		$this->runTest($moves,"one_move");
		
	}
	
	public function getResults(){
		return $this->results;
	}
	
	private function runTest($moves,$label){
		$pass = TRUE;
		$check = [];
		$t = 100;
		$len = sizeof($moves);
		$inc = round(1000 / $len);
		for($i=0; $i<$len; $i++){
			$t += $inc;
			$tstr = "".$t;
			while(strlen($tstr) < 3){
				$t = "0".$tstr;
			}
			$tstr = "0.000".$tstr;
			$check[] = \engines\tools\RandomMove::Get($moves,$tstr);
			//echo $tstr." -- ";
		}
		//echo "<br/>";
		if(!$this->no_dupes($check)){
			$str = "";
			foreach($check as $chk){
				$str .= $chk.",";
			}
			$this->results[$label] = "FAIL ($str)";
		}
		else{
			$this->results[$label] = "Pass";
		}
	}
	
	private function no_dupes(array $input_array) {
		return count($input_array) === count(array_flip($input_array));
	}
}
?>