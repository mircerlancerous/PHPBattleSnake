<?php
namespace sqlite;

class gamesDBUpdate extends \sqlite\DBUpdate{
	protected $db;
	
	public function __construct(&$db){
		$this->db = $db;
	}
	
	public function version1(){
		//create games table
		$columns = [
			"created" => "INT",
			"ended" => "INT",
			"game" => "TEXT",
			"snakes" => "TEXT"
		];
		if(!$this->createTable("games",$columns)){
			return FALSE;
		}
		
		//create default game engine (DEFAULTENGINE) setting
		$data = ["id"=>2,"value"=>1];
		if(!$this->db->query_insert("settings",$data)){
			return FALSE;
		}
		
		//create snakes table
		$columns = [
			"created" => "INT",
			"name" => "TEXT",
			"github" => "TEXT",		//github user
			"engine" => "INT",
			"colour" => "TEXT",
			"head" => "TEXT",
			"tail" => "TEXT"
		];
		if(!$this->createTable("snakes",$columns)){
			return FALSE;
		}
		
		//create requests table
		$columns = [
			"created" => "INT",
			"game" => "INT",
			"snake" => "INT",
			"json" => "TEXT"
		];
		if(!$this->createTable("requests",$columns)){
			return FALSE;
		}
		
		return TRUE;
	}
	
	public function version2(){
		if(!$this->addColumn("requests","command","TEXT")){
			return FALSE;
		}
		return TRUE;
	}
	
	public function version3(){
		if(!$this->addColumn("requests","response","TEXT")){
			return FALSE;
		}
		return TRUE;
	}
}
?>