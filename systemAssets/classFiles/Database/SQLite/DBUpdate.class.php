<?php
namespace sqlite;

class DBUpdate{
	protected $db = NULL;
	private $current = 0;
	private $definitionPath = "systemAssets/classFiles/Database/SQLite/";
	
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
		//find the update class for the supplied database
		include_once($this->definitionPath.$db->getDBName(TRUE)."DBUpdate.class.php");
		$uObj = "\\sqlite\\".$db->getDBName(TRUE)."DBUpdate";
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
					//update failed so rollback the transaction
					$this->db->RollbackTransaction();
					if($this->current > $current){
						//save the version to the database is case we did a partial update
						$this->updateCurrent();
					}
					return $this->current;
				}
			//update was successful so commit the transaction
			$this->db->CommitTransaction();
			//track the new version
			$this->current = $i;
		}
		if(!$this->updateCurrent()){
			//do nothing
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
		if(!$this->tableExists('settings')){
			if(!$this->db->query("
				CREATE TABLE settings
					(
						id INTEGER PRIMARY KEY,
						value TEXT
					);")){
				return FALSE;
			}
		}
		//cancel the transaction as journal mode cannot be changed in a transaction
		$res = $this->db->query("PRAGMA journal_mode=WAL;",FALSE);error_log("A");
		if($res[0]['journal_mode'] != 'wal'){error_log("B: ".json_encode($res));
			return FALSE;
		}error_log("C");
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
		$sql = "id INTEGER PRIMARY KEY, status INT";
		foreach($columns as $col => $type){
			$sql .= ", $col $type";
		}
		return $this->db->query("CREATE TABLE $table ($sql);");
	}
	
	protected function tableExists($table){
		if($this->db->query_single('SELECT name FROM sqlite_master WHERE type="table" AND name="'.$table.'";')){
			return TRUE;
		}
		return FALSE;
	}
	
	protected function columnExists($table,$column){
		$columns = $this->db->fetch_all_array('PRAGMA table_info("'.$table.'");');
		if(!$columns){
			return FALSE;
		}
		$found = FALSE;
		foreach($columns as $col){
			if($col['name'] == $column){
				$found = TRUE;
				break;
			}
		}
		return $found;
	}
	
	protected function dropColumn($table,$column){
		return $this->renameColumn($table,$column,NULL);
	}
	
	protected function renameTable($oldTable,$newTable){
		return DBUpdate::renameColumn($oldTable,NULL,NULL,NULL,$newTable);
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
	
	//rewrite with https://stackoverflow.com/questions/4253804/insert-new-column-into-table-in-sqlite
	protected function renameColumn($table,$oldColumn,$newColumn,$newType=NULL,$newTable=NULL){
		$success = TRUE;
		$tempTable = $table."_temp";
		if($newTable){
			$tempTable = $newTable;
		}
		//fetch a list of columns from the source
		$columns = $this->db->fetch_all_array('PRAGMA table_info("'.$table.'");');
		//create a new table with the new column changed
		$sql = "CREATE TABLE $tempTable (id INTEGER PRIMARY KEY,status INT";
			foreach($columns as $col){
				if($col['name'] == 'id' || $col['name'] == 'status'){
					continue;
				}
				if($col['name'] == $oldColumn){
					//only create the column if the new column isn't null. If it's null then we're dropping it
					if($newColumn !== NULL){
						$col['name'] = $newColumn;
						if($newType){
							$col['type'] = $newType;
						}
					}
					else{continue;}
				}
				$sql .= ',"'.$col['name'].'" '.$col['type'];
			}
		$sql .= ");";
		if(!$this->db->query($sql)){
			$success = FALSE;
		}
		else{
			//copy the old data to the new table
			$sql = "INSERT INTO $tempTable(id,status";
				foreach($columns as $col){
					if($col['name'] == 'id' || $col['name'] == 'status'){
						continue;
					}
					if($col['name'] == $oldColumn){
						//only create the column if the new column isn't null. If it's null then we're dropping it
						if($newColumn !== NULL){
							$sql .= ',"'.$newColumn.'"';
						}
					}
					else{
						$sql .= ',"'.$col['name'].'"';
					}
				}
				$sql .= ") SELECT id,status";
				foreach($columns as $col){
					if($col['name'] == 'id' || $col['name'] == 'status'){
						continue;
					}
					if($newColumn === NULL && $col['name'] == $oldColumn){
						continue;
					}
					$sql .= ',"'.$col['name'].'"';
				}
			$sql .= " FROM $table";
			if(!$this->db->query($sql)){
				$success = FALSE;
			}
			//drop the original table
			else if(!$this->db->query("DROP TABLE $table;")){
				$success = FALSE;
			}
			//if we're not renaming the table then rename the temp table to the original
			else if(!$newTable){
				if(!$this->db->query("ALTER TABLE $tempTable RENAME TO $table")){
					$success = FALSE;
				}
			}
		}
		return $success;
	}
}
?>