<?php
namespace mysql;

class DBUpdate{
	protected $db = NULL;
	private $current = 0;
	private $definitionPath = "systemAssets/classFiles/Database/MySQL/";
	
	public function __construct(&$db,$current,$target){
		$this->db = $db;
		if(!$current){
			$current = 0;
			if(!$this->version0()){
				return 0;
			}
		}
		//check if there is a custom path for this database definition
		if(class_exists("\Config")){
			$check = \Config::dbDefinitionPath($db->getDBName(TRUE));
			if($check){
				$this->definitionPath = $check;
			}
		}
		//if this is a new database
		if($current === 0){
			//check for create new database script
			if(file_exists($this->definitionPath.$db->getDBName(TRUE)."DBNew.class.php")){
				include_once($this->definitionPath.$db->getDBName(TRUE)."DBNew.class.php");
				$uObj = "\\mysql\\".$db->getDBName(TRUE)."DBNew";
				$uObj = new $uObj($db);
				if($uObj->success){
					$this->current = $target;
					$this->updateCurrent();
					return $target;
				}
				else{
					return FALSE;
				}
			}
		}
		//find the update class for the supplied database
		$res = include_once($this->definitionPath.$db->getDBName(TRUE)."DBUpdate.class.php");
		if(!$res){
			//no update file found, so assume it's up to date
			return $target;
		}
		$uObj = "\\mysql\\".$db->getDBName(TRUE)."DBUpdate";
		$uObj = new $uObj($db);
		//loop through update
		$this->current = $current;
		for($i=$current+1; $i<=$target; $i++){
			$update = "version".$i;
			//begin a transaction so that if anything fails, it can be undone
			if(!$this->db->BeginTransaction()){
				return FALSE;
			}
				if(!$uObj->$update()){
					//some updates may cancel the transaction
					if($this->db->inTransaction()){
						//update failed so rollback the transaction
						$this->db->RollbackTransaction();
					}
					if($this->current > $current){
						//save the version to the database is case we did a partial update
						$this->updateCurrent();
					}
					return $this->current;
				}
			//some updates may cancel the transaction
			if($this->db->inTransaction()){
				//update was successful so commit the transaction
				$this->db->CommitTransaction();
			}
			//track the new version
			$this->current = $i;
		}
		if(!$this->updateCurrent()){
			//echo "bad--".$this->current;
		}
		return $this->current;
	}
	
	public function getCurrent(){
		return $this->current;
	}
	
	public function updateCurrent(){
		if(!$this->db->query_single("SELECT value FROM settings WHERE id=1")){
			if(!$this->db->query_insert("settings",array("id"=>1,"value"=>$this->current))){
				return FALSE;
			}
			return TRUE;
		}
		return $this->db->query_update("settings",array("value"=>$this->current),"id=1");
	}
	
	private function version0(){
		//add settings table
		if(!$this->db->query("
			CREATE TABLE IF NOT EXISTS settings
				(
					id BIGINT AUTO_INCREMENT,
					value TEXT,
					PRIMARY KEY (id)
				) ENGINE=INNODB;")){
			return FALSE;
		}
		return TRUE;
	}
	
##################################################################################
	
	protected function createTable($table,$columns){
		if($this->tableExists($table)){
			return TRUE;
		}
		if(isset($columns['id'])){
			unset($columns['id']);
		}
		if(isset($columns['status'])){
			unset($columns['status']);
		}
		$sql = "id INT UNSIGNED AUTO_INCREMENT, status TINYINT UNSIGNED";
		foreach($columns as $col => $type){
			$sql .= ", $col $type";
		}
		return $this->db->query("CREATE TABLE $table ($sql, PRIMARY KEY (id)) ENGINE=INNODB CHARACTER SET utf8;");
	}
	
	protected function tableExists($table){
		$check = $this->db->query_single("SHOW TABLES LIKE '$table';");
		if($check == $table){
			return TRUE;
		}
		return FALSE;
	}
	
	protected function columnExists($table,$column){
		$check = $this->db->query_single("SHOW COLUMNS FROM $table WHERE FIELD='$column';");
		if($check){
			return TRUE;
		}
		return FALSE;
	}
	
	protected function indexExists($table,$idx){
		$sql = "SELECT COUNT(1)
				FROM INFORMATION_SCHEMA.STATISTICS
				WHERE table_schema=DATABASE() AND table_name='$table' AND index_name='$idx';";
		if(!$this->db->query_single($sql)){
			return TRUE;
		}
		return FALSE;
	}
	
	protected function settingExists($id){
		if(!$this->db->query_single("SELECT id FROM settings WHERE id=".$id)){
			return FALSE;
		}
		return TRUE;
	}
	
	protected function dropColumn($table,$column){
		if(!$this->db->query("ALTER TABLE $table DROP `$column`;")){
			return FALSE;
		}
		return TRUE;
	}
	
	protected function renameTable($oldTable,$newTable){
		if(!$this->db->query("RENAME TABLE $oldTable TO $newTable;")){
			return FALSE;
		}
		return TRUE;
	}
	
	protected function addColumn($table,$column,$type){
		if($this->columnExists($table,$column)){
			return TRUE;
		}
		if(!$this->db->query("ALTER TABLE $table ADD COLUMN $column $type")){
			return FALSE;
		}
		return TRUE;
	}
	
	protected function renameColumn($table,$oldColumn,$newColumn,$newType){
		if(!$this->db->query("ALTER TABLE $table CHANGE `$oldColumn` `$newColumn` $newType;")){
			return FALSE;
		}
		return TRUE;
	}
}
?>