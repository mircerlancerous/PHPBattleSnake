<?php
namespace engines\tools;

class RandomMove{
	public static function Get($moves,$mtime=NULL){
		$t = $mtime;
		if(!$t){
			//get the current time
			$t = microtime();
		}
		$t = substr($t,5,3);
		//there can be as many as four possible moves (four on first turn)
		$size = sizeof($moves);
		$increment = 1000 / $size;
		//set a direction based on the time
		for($i=$size-1; $i>0; $i--){
			if($t < 1000 - ($i * $increment)){
				return $moves[$i];
			}
		}
		return $moves[0];
	}
}
?>