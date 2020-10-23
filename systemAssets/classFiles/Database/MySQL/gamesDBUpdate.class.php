<?php
namespace mysql;

class gamesDBUpdate extends DBUpdate{
	protected $db;
	
	public function __construct(&$db){
		$this->db = $db;
	}
	
	public function version1(){
		//create games table
		$columns = [
			"created" => "BIGINT UNSIGNED",
			"ended" => "BIGINT UNSIGNED",
			"game" => "VARCHAR(30)",
			"snakes" => "VARCHAR(30)"
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
			"created" => "BIGINT UNSIGNED",
			"name" => "VARCHAR(30)",
			"github" => "VARCHAR(30)",		//github user
			"engine" => "TINYINT UNSIGNED",
			"colour" => "VARCHAR(10)",
			"head" => "VARCHAR(20)",
			"tail" => "VARCHAR(20)"
		];
		if(!$this->createTable("snakes",$columns)){
			return FALSE;
		}
		
		//create requests table
		$columns = [
			"created" => "BIGINT UNSIGNED",
			"game" => "VARCHAR(30)",
			"snake" => "INT UNSIGNED",
			"json" => "TEXT",
			"command" => "VARCHAR(20)",
			"response" => "TEXT"
		];
		if(!$this->createTable("requests",$columns)){
			return FALSE;
		}
		
		return TRUE;
	}
}
?>