<?php
namespace models;

class Battle{
	public $game;			//game obj
	public $turn;			//int
	public $board;			//board obj
	public $you;			//snake obj
	
	public function __construct(){
		$this->game = new game();
		$this->turn = 0;
		$this->board = new board();
		$this->you = new snake();
	}
}

class game{
	public $id;				//id string
}

class board{
	public $height;			//int
	public $width;			//int
	public $food = [];		//array of coordinates
	public $snakes = [];	//array of snake
}

class snake{
	public $id;				//id string
	public $name;			//string
	public $health;			//int
	public $body = [];		//array of coordinates
}

class coordinates{
	public $x;				//int
	public $y;				//int
}
?>