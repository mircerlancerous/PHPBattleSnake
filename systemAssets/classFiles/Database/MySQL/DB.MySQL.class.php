<?php
namespace mysql;

class Database{
	
	private $database = ""; //database name
	private $show_errors = FALSE;	//set whether the class will output error messages when applicable or not

	private $server = ""; //database server
	private $user = ""; //database login name
	private $pass = ""; //database login password
	private $dbprefix = "";	//for shared hosting environments where all databases begin with a user or other prefix
	
	private $error = "";
	private $autoSanitize = TRUE;
	private $closeOnUpdateError = FALSE;
	private $mysqli = NULL;
	private $connected = FALSE;
	private $readonly = FALSE; //control whether write function will execute or just return false.  Writes performed by query will still be permitted
	//number of rows affected by SQL query
	private $allowIdentityInsert = TRUE;
	private $intransaction = FALSE;
	private $insofttransaction = FALSE;
	private $preparedValues = NULL;
	
	private $dbVersion;
	private $oopsDisabled = FALSE;
	private $resultObj;
	private $softTransactionLog = NULL;

	public function __construct($database=NULL, $show_errors=FALSE){
		//set connection params
		if(\Config::isProduction()){
			$this->server = "localhost";
			$this->user = "mircer_games";
			$this->pass = "otb_prod_pass";
			$this->dbprefix = "mircer_";
		}
		else{
			$this->server = "localhost";
			$this->user = "otb_mysql";
			$this->pass = "otb_dev_pass";
		}
		//save config
		$this->show_errors = $show_errors;
		$this->database = $database;
	}

	public function __destruct(){
		$this->Close();
	}

	# desc: return TRUE if database has been opened in read only mode, or FALSE if not
	public function isReadOnly(){
		return $this->readonly;
	}#-#isReadOnly()

	# desc: sets or clears the readonly flag
	public function setReadOnly($readonly = TRUE){
		$this->readonly = $readonly;
	}#-#setReadOnly()

	# desc: configures the class for whether to allow insert to set the value for the 'id' column
	# param: (optional) TRUE for allow identity inserts, FALSE for don't allow
	public function setAllowIdentityInsert($allow=TRUE){
		$this->allowIdentityInsert = $allow;
	}#-#setAllowIdentityInsert()

	# desc: fetches the name of the currently connected to database and returns a string
	public function getDBName($nosuffix=FALSE){
		$name = $this->database;
		if($nosuffix){
			$pos = strpos($name,"_");
			if($pos !== FALSE){
				$name = substr($name,0,$pos);
			}
		}
		return $name;
	}#-#getDBName()

	# desc: return TRUE if database has been opened in read only mode, or FALSE if not
	public function isConnected(){
		return $this->connected;
	}#-#isConnected()

	# Desc: sets the connection flag
	# Param: TRUE for connected, FALSE for not
	private function setConnected($set){
		$this->connected = $set;
	}#-#setConnected()

	# desc: opens connection to database
	# returns: TRUE on success, FALSE on failure
	public function Connect($createIfMissing=TRUE,$skipUpdate=FALSE){
		$this->mysqli = new \mysqli($this->server,$this->user,$this->pass);
		
		if($this->mysqli->connect_error){		//server connection failed
			$this->oops("Could not connect to server: <b>".$this->server."</b>.");
			return FALSE;
		}
		
		$res = $this->switchDB($this->database,$createIfMissing,$skipUpdate);
		if(!$res){
			$this->oops("Could not connect to database: <b>".$this->database."</b>.");
			return FALSE;
		}
		
		return TRUE;
	}#-#Connect()

	# desc: check whether the database requires an update, and load the updater if it does
	# returns: TRUE on no update needed or update successful, FALSE on update failure
	private function databaseUpdate($db){
		//check required database version
		$DB_VERSION = \Config::dbVersion($this->getDBName(TRUE),"mysql");
		if($DB_VERSION !== FALSE){
			$version = 0;
			$check = $this->query_single("SHOW TABLES LIKE 'settings';");
			if($check !== FALSE){
				$version = $this->query_single("SELECT value FROM settings WHERE id=1");	//db version must always be in settings at id=1
				if($version){
					$this->dbVersion = $version;
				}
			}
			if($version < $DB_VERSION){
				include_once("classFiles/Database/MySQL/DBUpdate.class.php");
				$dbUpdate = new \mysql\DBUpdate($this,$version,$DB_VERSION);
				if($dbUpdate->getCurrent() < $DB_VERSION){
					$this->oops("Database update failed");
					return FALSE;
				}
			}
		}
		return TRUE;
	}#-#databaseUpdate()

	# desc: select database using vars above - must be already connected to use
	# Param: $new_db is the name of the database
	public function switchDB($new_db,$createIfMissing=FALSE,$skipUpdate=FALSE) {
		if($new_db == $this->database && $this->isConnected()){
			return TRUE;
		}
		if($this->isConnected() && !$this->Close(TRUE)){
			return FALSE;
		}
		
		$this->database = $new_db;
		$res = $this->mysqli->select_db($this->dbprefix.$new_db);
		if(!$res){
			if($createIfMissing && !$this->query("SHOW DATABASES LIKE '".$new_db."'",FALSE)){
				$res = $this->query("CREATE DATABASE $new_db");
				if($res){
					return $this->switchDB($new_db,FALSE,$skipUpdate);
				}
			}
			$this->oops("Switch DB failed: ".$new_db);
			$this->Close();
			return FALSE;
		}
		
		$this->setConnected(TRUE);
		if(!$skipUpdate){
			if(!$this->databaseUpdate($this->database) && $this->closeOnUpdateError){
				$this->Close();
				return FALSE;
			}
		}
		
		return TRUE;
	}#-#switchDB()

	# desc: close the connection
	public function Close($maintainConnection=FALSE){
		if(!$this->mysqli){
			return TRUE;
		}
		if($this->intransaction){
			$this->RollbackTransaction();
		}
		else if($this->insofttransaction){
			$this->RollbackSoftTransaction();
		}
		if($maintainConnection){
			$this->setConnected(FALSE);
			return TRUE;
		}
		if(!$this->mysqli->close()){
			$this->oops("Connection close failed.");
			return FALSE;
		}
		$this->setConnected(FALSE);
		$this->mysqli = NULL;
		return TRUE;
	}#-#Close()

	# Desc: escapes characters to be mysql ready
	# Param: string
	# Returns: string
	private function Escape($string){
		if(get_magic_quotes_runtime()) $string = stripslashes($string);
		return $this->mysqli->real_escape_string($string);
	}#-#Escape()
	
	# Desc: adds quotes which are mysql friendly
	# Param: string
	# Returns: string
	public function EncaseInQuotes($str){
		return "`$str`";
	}

	# Desc: whether the database is in a transaction or not
	# Returns: boolean, TRUE for yes, FALSE for no
	public function inTransaction(){
		if($this->intransaction || $this->insofttransaction){
			return TRUE;
		}
		return FALSE;
	}#-#inTransaction()

	# Desc: begins a database transaction
	# Returns: boolean, TRUE for succes, FALSE for failure
	public function BeginTransaction($useSoft=FALSE){
		if($useSoft){
			return $this->BeginSoftTransaction();
		}
		if($this->inTransaction()){
			return TRUE;
		}
		$res = $this->mysqli->query("START TRANSACTION;");
		
		if(!$res){
			return FALSE;
		}
		$this->intransaction = TRUE;
		
		return TRUE;
	}#-#BeginTransaction()

	# Desc: commits a database transaction
	# Returns: boolean, TRUE for succes, FALSE for failure
	public function CommitTransaction(){
		if($this->insofttransaction){
			return $this->CommitSoftTransaction();
		}
		$this->intransaction = FALSE;
		return $this->mysqli->query("COMMIT;");
	}#-#CommitTransaction()

	# Desc: rolls back a database transaction
	# Returns: boolean, TRUE for succes, FALSE for failure
	public function RollbackTransaction(){
		if($this->insofttransaction){
			return $this->RollbackSoftTransaction();
		}
		$this->intransaction = FALSE;
		return $this->mysqli->query("ROLLBACK;");
	}#-#RollbackTransaction()

	# Desc: whether the database is in a soft transaction or not
	# Returns: None
	private function inSoftTransaction(){
		return $this->insofttransaction;
	}#-#inSoftTransaction()

	# Desc: begins a soft transaction
	# Returns: None
	private function BeginSoftTransaction(){
		if($this->insofttransaction){
			return TRUE;
		}
		$this->softTransactionLog = array();
		$this->insofttransaction = TRUE;
		return TRUE;
	}#-#BeginSoftTransaction()

	# Desc: commits a soft transaction
	# Returns: None
	private function CommitSoftTransaction(){
		$this->softTransactionLog = NULL;
		$this->insofttransaction = FALSE;
		return TRUE;
	}#-#CommitSoftTransaction()

	# Desc: rolls back a soft transaction
	# Returns: None
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
	}#-#RollbackSoftTransaction()

	# Desc: sets an array of values to be used later
	# Returns: boolean, TRUE for succes, FALSE for failure
	public function setPreparedValues($valueArr){
		if($valueArr && is_array($valueArr)){
			$this->preparedValues = $valueArr;
			return TRUE;
		}
		return FALSE;
	}#-#setPreparedValues()

	# desc: returns all the results (not one row)
	# param: (MySQL query) the query to run on server
	# returns: assoc array of ALL fetched results
	public function fetch_all_array($sql,$removeNumericKeys=TRUE) {
		if(!$this->isConnected()){
			return FALSE;
		}
		$mode = MYSQLI_BOTH;
		if($removeNumericKeys){
			$mode = MYSQLI_ASSOC;
		}
		return $this->query($sql,FALSE,$mode);
	}#-#fetch_all_array()

	# desc: returns the first item for all the results (not one row)
	# param: (MySQL query) the query to run on server
	# returns: assoc single value array of ALL fetched results
	public function fetch_single_array($sql){
		$resArr = $this->fetch_all_array($sql,FALSE);
		if($resArr === FALSE){
			return FALSE;
		}
		foreach($resArr as $i => $row){
			$resArr[$i] = $row[0];
		}
		return $resArr;
	}#-#fetch_single_array()

	# Desc: executes SQL query to an open connection
	# Param: (MySQL query) to execute
	# returns: TRUE on success, FALSE on failure
	public function query($sql, $resultless=TRUE, $arrayMode=MYSQLI_ASSOC) {
		if(!$this->mysqli){
			return FALSE;
		}
		
		$prepValues = NULL;
		//if this statement has prepared values
		if($this->preparedValues){
			$prepValues = $this->bindStatementValues($sql,$this->preparedValues);
			$this->preparedValues = NULL;
		}
		
		$stmt = $this->mysqli->prepare($sql);
		if(!$stmt){
			$this->oops("<b>Prepare Query fail:</b> $sql");
			return FALSE;
		}
		//if this statement has prepared values
		if($prepValues){
			$ref = new \ReflectionClass('mysqli_stmt');
			$method = $ref->getMethod("bind_param");
			$method->invokeArgs($stmt,$prepValues);
		}
		$res = $stmt->execute();
		if(!$res){
			$this->oops("<b>Query fail:</b> $sql");
			return FALSE;
		}
		
		$resVal = TRUE;
		
		//if a result is requested
		if(!$resultless){
			$resObj = $stmt->get_result();
			if(!$resObj){
				$this->oops("<b>Query get results fail:</b> $sql");
				return FALSE;
			}
			
			$resVal = array();
			while($row = $resObj->fetch_array($arrayMode)){
				$resVal[] = $row;
			}
			$resObj->free();
		}
		
		$stmt->close();
		
		return $resVal;
	}#-#query()

	# desc: does a query, fetches the first row only, frees resultset
	# param: (MySQL query) the query to run on server
	# returns: array of fetched results
	public function query_first($sql){
		return $this->query_single($sql,TRUE);
	}#-#query_first()

	# desc: does a query, fetches the first row only
	# param: (MySQL query) the query to run on server
	# returns: the first item in the result array - query should be designed to only return one item
	public function query_single($sql,$fullRow=FALSE){
		if(!$this->isConnected()){
			return FALSE;
		}
		//if the last character isn't a semicolon
		if(substr($sql,strlen($sql)-1,1) !== ";"){
			//if limit hasn't already been added to the query
			$pos = strpos($sql,"LIMIT 1");
			if($pos === FALSE){
				$sql .= " LIMIT 1";
			}
		}
		
		if($fullRow){
			$data = $this->query($sql, FALSE);
		}
		else{
			$data = $this->query($sql, FALSE, MYSQLI_NUM);
		}
		if(!$data){
			return FALSE;
		}
		
		$resValue = $data[0];
		if(!$fullRow){
			$resValue = $resValue[0];
		}

		return $resValue;
	}#-#query_single()

	# desc: does an update query with an array
	# param: table (no prefix), assoc array with data (doesn't need escaped), where condition
	# returns: TRUE on success, FALSE on failure
	public function query_update($table, $dataArr, $where=""){
		if(!$this->isConnected() || $this->readonly){
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
			if($val === NULL){
				$sql .= "`$key`=NULL";
			}
			else{
				$sql .= "`$key`='".$this->Escape($val)."'";
			}
		}
		if($where){
			$sql .= " WHERE $where";
		}
		$sql = "UPDATE `$table` SET ".$sql;
		
		$res = $this->query($sql);
		if(!$res){
			return FALSE;
		}
		
		return TRUE;
	}#-#query_update()

	# desc: does an insert query with an array
	# param: table (no prefix), assoc array with data (doesn't need escaped)
	# returns: id of inserted record (or TRUE if no auto increment), FALSE if error
	public function query_insert($table, $dataArr) {
		if(!$this->isConnected() || $this->readonly){
			return FALSE;
		}
		// Note: If allow is true and an id is set, autoincrement value will be reset to the highest column value.
		if(!$this->allowIdentityInsert) { // Remove all keys
			$primaryKeys = $this->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY';", FALSE);
			foreach($primaryKeys as $key) {
				$columnName = $key["Column_name"];
				unset($dataArr[$columnName]);
			}
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
			$part1 .= "`$key`";
			if(!$val && ($val === NULL || strlen($val) == 0)){
				$part2 .= "NULL";
			}
			else{
				$part2 .= "'".$this->Escape($val)."'";
			}
		}
		$sql = "INSERT INTO `$table` ($part1) VALUES ($part2)";
		
		$res = $this->query($sql);
		if(!$res){
			return FALSE;
		}
		
		$id = $this->mysqli->insert_id;
		if($this->insofttransaction){
			$this->softTransactionLog[] = array("table" => $table, "value" => $id);
		}
		return $id;
	}#-#query_insert()
	
	# desc: records an update query with an array during a soft transaction
	# param: table (no prefix), assoc array with data (doesn't need escaped), where condition
	# returns: None
	private function recordSoftTransactionUpdate($table, $dataArr, $where){
		$select = "id";
		foreach($dataArr as $key => $value){
			if($key == "id"){
				continue;
			}
			$select .= ",`$key`";
		}
		$results = $this->fetch_all_array("SELECT $select FROM `$table` WHERE $where");
		foreach($results as $data){
			$id = $data['id'];
			unset($data['id']);
			$this->softTransactionLog[] = array("table" => $table, "value" => $id, "update" => $data);
		}
	}
	
	private function bindStatementValues(&$sql,$prepValues){
		$refArr = array("");
		foreach($prepValues as $key => $value){
			//if this is a named key style of value prep (ie sqlite)
			if(!is_numeric($key)){
				//trust that the caller put the values into the array in the same order as the sql
				$sql = str_replace(":".$key,"?",$sql);
			}
			//setup type
			if(is_double($value)){
				$refArr[0] .= "d";
			}
			else{
				$refArr[0] .= "s";		//default to string - must do this to support big int, and unsigned int, etc
			}
			//safely add to list
			$val = $this->Escape($value);
			$refArr[] = &$val;
			unset($val);
		}
		return $refArr; 
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

	# desc: throw an error message
	# param: [optional] any custom error to display
	private function oops($msg="",$sql="") {
		if($this->oopsDisabled){
			return;
		}
		$error = "";
		$errno = 0;
		if($this->mysqli) {
			$error = $this->mysqli->error;
			$errno = $this->mysqli->errno;
			if(!$this->dbVersion){
				$this->oopsDisabled = TRUE;
				$this->dbVersion = $this->query_single("SELECT value FROM settings WHERE id=1");	//db version must always be in settings at id=1
				$this->oopsDisabled = FALSE;
			}
		}
		ob_start();
		?>
		<table align="center" border="1" cellspacing="0" style="background:white;color:black;width:80%;">
			<tr><th colspan="2">Database Error</th></tr>
			<tr><td align="right" valign="top">Database:</td><td><?php echo $this->database." (v".$this->dbVersion.")";?></td></tr>
			<tr><td align="right" valign="top">Message:</td><td><?php echo $msg;?></td></tr>
			<?php
			if(strlen($error)>0){
				?><tr><td align="right" valign="top" nowrap>MySQLi Error:</td><td><?php echo $error;?></td></tr><?php
			}
			if($errno){
				?><tr><td align="right">MySQLi Error #:</td><td><?php echo $errno;?></td></tr><?php
			}
			if(strlen($sql)>0){
				?><tr><td align="right">SQL:</td><td><?php echo $sql;?></td></tr><?php
			}
			if(strlen(@$_SERVER['REQUEST_URI'])>0){
				?><tr><td align="right">Script:</td><td><a href="<?php echo @$_SERVER['REQUEST_URI']; ?>"><?php echo @$_SERVER['REQUEST_URI']; ?></a></td></tr><?php
			}
			if(strlen(@$_SERVER['HTTP_REFERER'])>0){
				?><tr><td align="right">Referer:</td><td><a href="<?php echo @$_SERVER['HTTP_REFERER'];?>"><?php echo @$_SERVER['HTTP_REFERER'];?></a></td></tr><?php
			}?>
		</table>
		<br/>
		<?php
		$error = ob_get_clean();
		if($this->show_errors){
			echo $error;
		}
		error_log($error);
		
		$this->error .= $error;
	}#-#oops()

	# desc: get error messages
	# returns: string of error messages or false if none
	public function getErrors(){
		return $this->error;
	}#-#getErrors()

}
?>