<?php
namespace engines\tools;

//get allowed moves - filter out moves that might not be allowed due to the walls and snakes
//only looks one move forward
//considers last part of another snake's body, and if a snake is about to eat
class AllowedMoves{
	public $obj;
	private $moves = [];			//moves that are for sure safe, at least for the one move
	private $maybeMoves = [];		//moves that might be safe if another snake moves or dies
	
	public function __construct($obj){
		$this->obj = $obj;
		
		include_once("snakeAssets/models/Move.model.php");
		$obj = $this->obj;
		
		$arr = $obj->you->body;
		//get head
		$head = $arr[0];
		//create list of possible moves
		$options = [
			$this->getMoveOption($head->x, $head->y-1, \models\MoveTypes::$Up),
			$this->getMoveOption($head->x, $head->y+1, \models\MoveTypes::$Down),
			$this->getMoveOption($head->x-1, $head->y, \models\MoveTypes::$Left),
			$this->getMoveOption($head->x+1, $head->y, \models\MoveTypes::$Right)
		];
		
		$options = $this->filterForWall($options);
		$options = $this->filterForBody($obj->you,$options);
		$options = $this->filterForSnakes($options);
		
		foreach($options as $o){
			$this->moves[] = $o->move;
		}
	}
	
	public function Get(){
		return $this->moves;
	}
	
	public function GetMaybes(){
		return $this->maybeMoves;
	}
	
	protected function getMoveOption($x,$y,$move){
		$obj = new \stdClass();
		$obj->x = $x;
		$obj->y = $y;
		$obj->move = $move;
		return $obj;
	}
	
	protected function filterForWall($options){
		$obj = $this->obj;
		foreach($options as $i => $o){
			if($o->x < 0 || $o->y < 0 || $o->x >= $obj->board->width || $o->y >= $obj->board->height){
				unset($options[$i]);
			}
		}
		
		return array_values($options);
	}
	
	protected function filterForBody($snake,$options){
		$obj = $this->obj;
		$len = sizeof($snake->body);
		//length minus one as the tail will move one space forward
		for($i=0; $i<$len; $i++){
			$check = $snake->body[$i];
			foreach($options as $v => $o){
				if($check->x == $o->x && $check->y == $o->y){
					//if this is the end of the snake
					if($i == $len-1 && $snake->health < 100 && $len >= 3){
						//didn't just eat, so this move is safe
						continue;
					}//error_log("opt:".json_encode($options[$v]).", sh:".$snake->health);
					//remove this move
					unset($options[$v]);
					//continue in case the body is long and curvy
				}
			}
		}
		
		return array_values($options);
	}
	
	private function checkForFood($x,$y){
		foreach($this->obj->board->food as $f){
			if($x == $f->x && $y == $f->y){
				return TRUE;
			}
		}
		return FALSE;
	}
	
	protected function filterForSnakes($options){
		$you = $this->obj->you->id;
		$obj = $this->obj;
		foreach($obj->board->snakes as $snake){
			$options = $this->filterForBody($snake,$options);
			if(sizeof($options) <= 1){
				break;
			}
		}
		return array_values($options);
	}
}
?>