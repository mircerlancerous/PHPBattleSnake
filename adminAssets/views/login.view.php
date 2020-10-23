<?php
namespace views;

class login_View extends Main{
	public $db;
	
	private $authCode = "accesscode";
	
	public function __construct($db){
		$this->db = $db;
		
		if(isset($_POST["authcode"]) && $_POST["authcode"] == $this->authCode){
			$_SESSION['isauth'] = \Status::$active;
			header("Location: ?view=home");
			exit;
		}
	}
	
	public function getTitle(){
		echo "Login";
	}
	
	public function getHead(){
		
	}
	
	public function getView(){
		?>
		<h1>Battlesnake Admin - Login</h1>
		<p>&nbsp;</p>
		<div>
			<form action="" method="post">
				<p>Enter Login Code</p>
				<input type="password" name="authcode"/><br/>
				<input type="submit" value="login"/>
			</form>
		</div>
		<?php
	}
}
?>