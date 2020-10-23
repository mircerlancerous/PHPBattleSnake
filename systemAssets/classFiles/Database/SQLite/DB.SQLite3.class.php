<?php
namespace sqlite;

class Database{
	
	private $database = "";		//database name
	private $path = "snakeAssets/dbdata/";			//database file path
	public $ext = "db";		//database file extension
	public $show_errors = FALSE;	//set whether the class will output error messages when applicable or not
	
	private $error = "";
	private $autoSanitize = TRUE;
	
	private $linkObj = NULL;
	private $busyTimeout = 30000;		//SQLite busy timeout of 30 seconds
	private $connected = FALSE;
	private $readonly = FALSE;
	private $allowIdentityInsert = FALSE;
	private $dbVersion;
	private $oopsDisabled = FALSE;
	
	private $preparedValues = NULL;
	private $intransaction = FALSE;
	private $insofttransaction = FALSE;
	private $softTransactionLog = NULL;
	
	# Desc: constructor
	public function __construct($database=NULL, $show_errors=FALSE, $path=NULL){
		$this->database = $database;
		$this->show_errors = $show_errors;
		if($path !== NULL){
			$last = substr($path,strlen($path)-1,1);
			if($last !== "/" && $last !== "\\"){
				$path .= "/";
			}
			$this->path = $path;
		}
	}
	
	#Desc: get database path
	public function getPath(){
		return $this->path;
	}
	
	#Desc: set database path
	#Param: the new path
	public function setPath($path){
		$this->path = $path;
	}
	
	# Desc: return TRUE if database has been opened in read only mode, or FALSE if not
	public function isReadOnly(){
		return $this->readonly;
	}
	
	# Desc: configures the class for whether to open a database in read only mode or not
	# Param: (optional) TRUE for read only, FALSE for read/write/create
	public function setReadOnly($readonly=TRUE) {
		$this->readonly = $readonly;
	}
	
	# Desc: checks whether the database has been opened in write mode; if not, it switches the database connection over to write mode
	private function checkWriteMode(){
		if($this->isReadOnly()){
			//need to switch from read-only to write mode
			$this->Close();
			$this->readonly = FALSE;
			$this->Connect(FALSE,TRUE);
		}
	}
	
	# Desc: configures the class for whether to allow insert to set the value for the 'id' column
	# Param: (optional) TRUE for allow identity inserts, FALSE for don't allow
	public function setAllowIdentityInsert($allow=TRUE){
		$this->allowIdentityInsert = $allow;
	}
	
	# Desc: fetches the name of the currently connected to database
	# Returns: string of db name
	public function getDBName($nosuffix=FALSE){
		$name = $this->database;
		if($nosuffix){
			$pos = strpos($name,"_");
			if($pos !== FALSE){
				$name = substr($name,0,$pos);
			}
		}
		return $name;
	}
	
	# Desc: gets the database connection status
	# Returns: TRUE for connected, FALSE for not
	public function isConnected(){
		return $this->connected;
	}
	
	# Desc: sets the connection flag
	# Param: TRUE for connected, FALSE for not
	private function setConnected($set){
		$this->connected = $set;
	}
	
	# Desc: closes existing connection, opens connection to new database
	# Param: (optional) name of new database to connect to, whether to fail if database file doesn't exist, or to create it
	# Returns: TRUE on success, FALSE on failure
	public function Connect($database=NULL,$createIfMissing=TRUE){
		if(!$this->Close()){
			return FALSE;
		}
		if($database === NULL){
			$database = $this->database;
		}
		else{
			$this->database = $database;
		}
		if(!$database){
			$this->oops("No database defined: ".$this->database);
			return FALSE;
		}
		$db = $this->path.$database.".".$this->ext;
		
		if(!$createIfMissing && !file_exists($db)){
			$this->oops("Invalid database - ".$db);
			return FALSE;
		}
		try{
			if($this->isReadOnly()){
				//readonly not currently supported in php
				$this->linkObj = new \SQLite3($db);//,SQLITE3_OPEN_READONLY);
			}
			else{
				$this->linkObj = new \SQLite3($db);
			}
		}
		catch(Exception $e){
			$this->linkObj = FALSE;
			$this->error .= "<p>".$e->getMessage()."</p>";
		}
		if(!$this->linkObj){
			$this->oops("Could not connect to database: <b>".$this->database."</b>.");
			return FALSE;
		}
		$this->linkObj->busyTimeout($this->busyTimeout);
		$this->setConnected(TRUE);
		if(!$this->databaseUpdate($db)){
			$this->Close();
			return FALSE;
		}
		return TRUE;
	}
	
	# Desc: check whether the database requires an update, and load the updater if it does
	# Returns: TRUE on no update needed or update successful, FALSE on update failure
	private function databaseUpdate($db){
		//check database version
		$DB_VERSION = \Config::dbVersion($this->getDBName(TRUE));
		if($DB_VERSION !== FALSE){
			$version = 0;
			if($this->query_single("SELECT name FROM sqlite_master WHERE type='table' AND name='settings';")){
				$version = $this->query_single("SELECT value FROM settings WHERE id=1");
				if($version){
					$this->dbVersion = $version;
				}
			}
			if($version < $DB_VERSION){
				include_once("classFiles/Database/SQLite/DBUpdate.class.php");
				$dbUpdate = new \sqlite\DBUpdate($this,$version,$DB_VERSION);
				if($dbUpdate->getCurrent() < $DB_VERSION){
					$this->oops("Database update failed");
					return FALSE;
				}
			}
		}
		return TRUE;
	}
	
	# Desc: closes existing connection, opens connection to new database
	# Param: name of new database to connect to
	# Returns: TRUE on success, FALSE on failure
	public function SwitchDB($new_db,$createIfMissing=FALSE){
		//if the new db is the same as the current one
		if($new_db == $this->database && $this->isConnected()){
			return true;
		}
		//connect to the new db
		return $this->Connect($new_db,$createIfMissing);
	}
	
	# Desc: close the connection
	# Returns: TRUE on success, FALSE on failure
	public function Close(){
		if(!$this->isConnected()){
			return TRUE;
		}
		if(!@$this->linkObj->close()){
			$this->oops("Connection close failed.");
			return FALSE;
		}
		$this->setConnected(FALSE);
		$this->linkObj = NULL;
		return TRUE;
	}
	
	# Desc: escapes characters to be sqlite3 ready
	# Param: string
	# Returns: string
	public function Escape($string){
		return SQLite3::escapeString($string);
	}
	
	# Desc: whether the database is in a transaction or not
	# Returns: boolean, TRUE for yes, FALSE for no
	public function inTransaction(){
		if($this->intransaction || $this->insofttransaction){
			return TRUE;
		}
	}
	
	# Desc: begins a database transaction
	# Returns: boolean, TRUE for succes, FALSE for failure
	public function BeginTransaction($useSoft=FALSE){
		if($useSoft){
			return $this->BeginSoftTransaction();
		}
		if($this->inTransaction()){
			return TRUE;
		}
		$this->checkWriteMode();
		$this->intransaction = TRUE;
		$res = $this->query("BEGIN IMMEDIATE;");
		
		if(!$res){
			$errno = @$this->linkObj->lastErrorCode();
			//if getting database locked error
			if($errno == 5){
				sleep(1);
				return $this->BeginTransaction();
			}
			return FALSE;
		}
		
		return TRUE;
	}
	
	# Desc: commits a database transaction
	# Returns: boolean, TRUE for succes, FALSE for failure
	public function CommitTransaction(){
		if(!$this->inTransaction()){
			return TRUE;
		}
		if($this->insofttransaction){
			return $this->CommitSoftTransaction();
		}
		$this->intransaction = FALSE;
		return $this->query("COMMIT;");
	}
	
	# Desc: rolls back a database transaction
	# Returns: boolean, TRUE for succes, FALSE for failure
	public function RollbackTransaction(){
		if(!$this->inTransaction()){
			return TRUE;
		}
		if($this->insofttransaction){
			return $this->RollbackSoftTransaction();
		}
		$this->intransaction = FALSE;
		return $this->query("ROLLBACK;");
	}
	
	# Desc: begins a 'soft' transaction where changes are simply recorded and deleting if rolled back
	# Returns: boolean, TRUE for succes, FALSE for failure
	private function BeginSoftTransaction(){
		if($this->insofttransaction){
			return;
		}
		$this->checkWriteMode();
		$this->softTransactionLog = array();
		$this->insofttransaction = TRUE;
	}
	
	# Desc: commits a soft transaction so it cannot be undone
	# Returns: boolean, TRUE for succes, FALSE for failure
	private function CommitSoftTransaction(){
		$this->softTransactionLog = NULL;
		$this->insofttransaction = FALSE;
	}
	
	# Desc: rolls back all changes made from when the soft transaction was started
	# Returns: boolean, TRUE for succes, FALSE for failure
	private function RollbackSoftTransaction(){
		if($this->softTransactionLog === NULL || sizeof($this->softTransactionLog) == 0){
			$this->softTransactionLog = NULL;
			$this->insofttransaction = FALSE;
			return FALSE;
		}
		foreach($this->softTransactionLog as $arr){
			//if the change was an update
			if(isset($arr['update'])){
				//restore values to before the update
				$this->query_update($arr['table'],$arr['update'],"id=".$arr['value']);
			}
			//the change was an insert
			else{
				//delete inserted row
				$this->query("DELETE FROM ".$arr['table']." WHERE id=".$arr['value']);
			}
		}
		$this->softTransactionLog = NULL;
		$this->insofttransaction = FALSE;
		return TRUE;
	}
	
	public function setPreparedValues($valueArr){
		if($valueArr && is_array($valueArr)){
			$this->preparedValues = $valueArr;
			return TRUE;
		}
		return FALSE;
	}
	
	# Desc: executes SQL query to an open connection
	# Params: SQLite query to execute
	# Returns: TRUE on success, FALSE on failure
	public function query($sql,$resultless=TRUE){
		if(!$this->isConnected()){
			return FALSE;
		}
		//if this is to be a prepared statement
	//	if($this->preparedValues){
			try{
				$stmt = $this->linkObj->prepare($sql);
			}
			catch(\Exception $e){
				$this->oops("<b>Prepare Fetch Query fail:</b> ".$e->getMessage()."<br/> $sql");
				return FALSE;
			}
		//	$stmt = @$this->linkObj->prepare($sql);
			if($this->preparedValues){
				foreach($this->preparedValues as $key => $value){
					$stmt->bindValue(":$key", $value);
				}
				$this->preparedValues = NULL;
			}
			try{
				$resObj = $stmt->execute();
			}
			catch(\Exception $e){
				$this->oops("<b>Fetch Query fail:</b> ".$e->getMessage()."<br/> $sql");
				return FALSE;
			}
			if(!$resObj){
				$this->oops("<b>Query fail:</b>", $sql);
				return FALSE;
			}
	/*	}
		//if this is a classic sql statement
		else{
			$resObj = @$this->linkObj->exec($sql);
			if(!$resObj){
				$this->oops("<b>Query fail:</b>", $sql);
				return FALSE;
			}
		}
		*/
		if(!$resultless && $resObj){
			$resArr = array();
			while($row = @$resObj->fetchArray(SQLITE3_ASSOC)){
				$resArr[] = $row;
			}
			$resObj->finalize();
			return $resArr;
		}
		
		return TRUE;
	}
	
	# Desc: returns all the results (not one row)
	# Param: the select query to run, boolean as to whether or not to only return array with associative keys
	# Returns: array of assoc array of ALL fetched result rows
	public function fetch_all_array($sql,$removeNumericKeys=TRUE){
		if(!$this->isConnected()){
			return FALSE;
		}
		$stmt = $this->linkObj->prepare($sql);
		//if this statement has prepared values
		if($this->preparedValues){
			foreach($this->preparedValues as $key => $value){
				$stmt->bindValue(":$key", $value);
			}
			$this->preparedValues = NULL;
		}
		$resObj = $stmt->execute();
		if(!$resObj){
			$this->oops("<b>Fetch Query fail:</b> $sql");
			return FALSE;
		}
		$resArr = array();
		while($row = @$resObj->fetchArray()){
			if($removeNumericKeys){
				$i = 0;
				while(array_key_exists($i,$row)){
					unset($row[$i]);
					$i++;
				}
			}
			$resArr[] = $row;
		}
		return $resArr;
	}
	
	# Desc: returns the first item for all the results (not one row)
	# Param: the select query to run
	# Returns: single value array of the first value from all fetched result rows
	public function fetch_single_array($sql){
		$resArr = $this->fetch_all_array($sql,FALSE);
		if($resArr === FALSE){
			return FALSE;
		}
		foreach($resArr as $i => $row){
			$resArr[$i] = $row[0];
		}
		return $resArr;
	}
	
	# Desc: does a query, fetches the first row only
	# Param: the select query to run
	# Returns: assoc array of fetched results
	public function query_first($sql){
		return $this->query_single($sql,TRUE);
	}
	
	# Desc: does a query, fetches the first item in first row only
	# Param: the select query to run
	# Returns: the first item in the result array
	public function query_single($sql,$fullRow=FALSE){
		if(!$this->isConnected()){
			return FALSE;
		}
		$resValue = @$this->linkObj->querySingle($sql,$fullRow);
		if($resValue === FALSE){
			$this->oops("<b>Query Single fail:</b> $sql");
			return FALSE;
		}
		return $resValue;
	}
	
	private function recordSoftTransactionUpdate($table, $dataArr, $where){
		$select = "id";
		foreach($dataArr as $key => $value){
			if($key == "id"){
				continue;
			}
			$select .= ",".$key;
		}
		$results = $this->fetch_all_array("SELECT $select FROM $table WHERE $where");
		foreach($results as $data){
			$id = $data['id'];
			unset($data['id']);
			$this->softTransactionLog[] = array("table" => $table, "value" => $id, "update" => $data);
		}
	}
	
	# Desc: does an update query with an array
	# Param: table, assoc array with data (doesn't need escaped), where condition
	# Returns: TRUE for success, FALSE for failure
	public function query_update($table, $dataArr, $where=""){
		if($this->isReadOnly() || !$this->isConnected()){
			return FALSE;
		}
		if($this->insofttransaction){
			$this->recordSoftTransactionUpdate($table, $dataArr, $where);
		}
		if($this->autoSanitize){
			$dataArr = $this->SanitizeInput($dataArr);
		}
		$sql = "";
		foreach($dataArr as $key => $val) {
			if(strlen($key) == 0){
				continue;
			}
			if(strlen($sql) > 0){
				$sql .= ", ";
			}
			$sql .= '"'.$key.'"=:'.$key;
		}
		if($where){
			$sql .= " WHERE $where";
		}
		$stmt = @$this->linkObj->prepare("UPDATE $table SET ".$sql);
		if(!$stmt){
			$this->oops("<b>Update Statement Prepare fail:</b> UPDATE $table SET $sql");
			return FALSE;
		}
		foreach($dataArr as $key => $val) {
			$stmt->bindValue(":$key", $val);
		}
		if(!$stmt->execute()){
			$this->oops("<b>Update Query fail:</b> $sql");
			return FALSE;
		}
		return TRUE;
	}
	
	# Desc: does an insert query with an array
	# Param: table, assoc array with data (doesn't need escaped)
	# Returns: id of inserted record, FALSE if error
	public function query_insert($table, $dataArr){
		if($this->isReadOnly() || !$this->isConnected()){
			return FALSE;
		}
		if($this->autoSanitize){
			$dataArr = $this->SanitizeInput($dataArr);
		}
		$part1 = $part2 = "";
		foreach($dataArr as $key => $val) {
			if(strlen($key) == 0){
				continue;
			}
			if(strlen($part1) > 0){
				$part1 .= ",";
				$part2 .= ",";
			}
			$part1 .= '"'.$key.'"';
			$part2 .= ":$key";
		}
		$sql = "INSERT INTO \"$table\" ($part1) VALUES ($part2)";
		$stmt = @$this->linkObj->prepare($sql);
		if(!$stmt){
			$this->oops("<b>Insert Statement Prepare fail:</b> $sql");
			return FALSE;
		}
		foreach($dataArr as $key => $val) {
			$stmt->bindValue(":$key", $val);
		}
			
		if(!$stmt->execute()){
			$this->oops("<b>Insert Query fail:</b> $sql");
			return FALSE;
		}
		$id = $this->linkObj->lastInsertRowID();
		if($this->insofttransaction){
			$this->softTransactionLog[] = array("table" => $table, "value" => $id);
		}
		return $id;
	}
	
	# Desc: disables automatic input sanitization for all inserts and updates
	public function disableAutoSanitize($disabled=TRUE){
		$this->autoSanitize = !$disabled;
	}
	
	# Desc: strips all HTML from all input elements
	public function SanitizeInput($arr){
		if(is_array($arr)){
			foreach($arr as $key => $val){
				$arr[$key] = $this->SanitizeInput($val);
			}
		}
		else if(is_object($arr)){
			$list = get_object_vars($arr);
			foreach($list as $key => $val){
				$arr->$key = $this->SanitizeInput($val);
			}
		}
		else if(is_string($arr)){
			//$arr = strip_tags($arr);
			$arr = htmlentities($arr);
		}
		return $arr;
	}
	
	# Desc: throw an error message
	# Param: [optional] any custom error to display
	private function oops($msg="",$sql=""){
		if($this->oopsDisabled){
			return;
		}
		$error = "";
		$errno = 0;
		if($this->isConnected()){
			$error = @$this->linkObj->lastErrorMsg();
			$errno = @$this->linkObj->lastErrorCode();
			if(!$this->dbVersion){
				$this->oopsDisabled = TRUE;
				$this->dbVersion = $this->query_single("SELECT value FROM settings WHERE id=1");	//db version must always be in settings at id=1
				$this->oopsDisabled = FALSE;
			}
		}
		ob_start();
		?>
		<table align="center" border="1" cellspacing="0" style="background:white;color:black;width:80%;">
			<tr><th colspan=2>Database Error</th></tr>
			<tr><td valign="top">Database:</td><td><?php echo $this->database." (v".$this->dbVersion.")";?></td></tr>
			<tr><td valign="top">Message:</td><td><?php echo $msg;?></td></tr>
			<?php
			if(strlen($error)>0){
				?><tr><td valign="top" nowrap>Error:</td><td><?php echo $error;?></td></tr><?php
			}
			if($errno){
				?><tr><td>Error #:</td><td><?php echo $errno;?></td></tr><?php
			}
			if(strlen($sql)>0){
				?><tr><td>SQL:</td><td><?php echo $sql;?></td></tr><?php
			}
			if(strlen(@$_SERVER['REQUEST_URI'])>0){
				?><tr><td>Script:</td><td><a href="<?php echo @$_SERVER['REQUEST_URI']; ?>"><?php echo @$_SERVER['REQUEST_URI']; ?></a></td></tr><?php
			}
			if(strlen(@$_SERVER['HTTP_REFERER'])>0){
				?><tr><td>Referer:</td><td><a href="<?php echo @$_SERVER['HTTP_REFERER'];?>"><?php echo @$_SERVER['HTTP_REFERER'];?></a></td></tr><?php
			}?>
			<tr><td valign="top">Trace:</td><td><pre><?php debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,5);?></pre></td></tr>
		</table>
		<br/>
		<?php
		$error = ob_get_clean();
		if($this->show_errors){
			echo $error;
		}
		error_log($error);
		
		$this->error .= $error;
	}
	
	#Desc: Gets all database errors since class initialization
	#Returns: a string of all errors
	public function getErrors(){
		return $this->error;
	}
}
?>