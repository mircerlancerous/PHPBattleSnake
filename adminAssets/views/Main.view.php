<?php
namespace views;

class Main{
	protected $messages = array();
	protected $errors = array();
	
	public function __construct($db,$rootURL=NULL){
		
		$viewname = "home";
		if(isset($_GET['view'])){
			$viewname = $_GET['view'];
		}
		session_start();
		if(!isset($_SESSION['isauth'])){
			$viewname = "login";
		}
		else if($viewname == "login"){
			unset($_SESSION['isauth']);
		}
		$res = @include_once("adminAssets/views/$viewname.view.php");
		if(!$res){
			$viewname = "home";
			include_once("adminAssets/views/$viewname.view.php");
		}
		$view = "\\views\\".$viewname."_View";
		$vObj = new $view($db);
		
		ob_start();
			$this->showView($db,$rootURL,$vObj,$viewname);
		$this->minifyOutput();
	}
	
	private function showView($db,$rootURL,$vObj,$viewname){
		?><!DOCTYPE html>
		<html>
			<head>
				<base href="<?php echo $rootURL;?>"/>
				<meta charset="utf-8"/>
				<meta http-equiv="Content-Security-Policy" content="default-src * data:; style-src * 'unsafe-inline'; script-src * 'unsafe-inline' 'unsafe-eval'"/>
				<!--<link rel="shortcut icon" href="staticAssets/images/offthebricks_dark.png"/>-->
				<title><?php $vObj->getTitle();?> - Battlesnake</title>
				<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"/>
				<link rel="stylesheet" href="staticAssets/styles/structure.css"/>
				<!--<script src="staticAssets/scripts/structure.js"></script>--><?php
				$vObj->getHead();?>
			</head>
			<body>
				<?php
				$this->showMessages($vObj);
				?>
				<table class="structure">
					<tr>
						<td class="mainMenu">
							<?php
							$this->getMainMenu();
							?>
						</td>
						<td class="mainBody">
							<div class="miniMenu">
								<div class="menuBtn">
									<img src="staticAssets/images/menu.png"/>
								</div>
								<div style="position: relative;">
									<div class="mainMenu">
										<?php
										$this->getMainMenu();
										?>
									</div>
								</div>
							</div>
							<?php
							$vObj->getView();
							?>
						</td>
					</tr>
				</table>
				<div id="popuphldr" style="display:none;"></div>
				<div id="popupshade" style="display:none;"></div>
			</body>
		</html>
		<?php
	}
	
	private function getMainMenu(){
		?>
		<a href="?view=home" title="home"><img src="staticAssets/images/home_icon.png"/></a>
		<a href="?view=snakes" title="snakes"><img src="staticAssets/images/battlesnake.png"/></a>
		<a href="?view=tests" title="tests"><img src="staticAssets/images/tools.png"/></a>
		<a href="?view=unittests" title="unit tests"><img src="staticAssets/images/formx.png"/></a>
		<?php
	}
	
	//called by getMessages in the file class
	protected function showMessages($vObj){
		if($vObj->messages || $vObj->errors){
			?><div id="messageBox">
				<div class="close"></div>
				<?php
				foreach($vObj->messages as $msg){
					echo "<p class='message'>$msg</p>";
				}
				foreach($vObj->errors as $msg){
					echo "<p class='error'>
						<img src='../staticAssets/images/error.png'/>
						$msg
					</p>";
				}
			?></div><?php
		}
	}
	
	protected function connectToResultDB($org){
		//verify user has permission to access this organization
		$check = $this->db->query_single("SELECT id FROM organizationusers WHERE organization=$org AND user=".$this->auth->user['id']." AND status=".\Status::$active);
		if(!$check){
			return "unauthorized";
		}
		//init the results database
		$rdb = new \mysql\Database("xresults",!\Config::isProduction());
		if(!$rdb->Connect()){
			return "error connecting to results database";
		}
		return $rdb;
	}
	
	private function minifyOutput($closeConnection=FALSE){
		$buffer = ob_get_clean();
		
		$search = array(
			'/\>[^\S ]+/s',  // strip whitespaces after tags, except space
			'/[^\S ]+\</s',  // strip whitespaces before tags, except space
			'/(\s)+/s'       // shorten multiple whitespace sequences
		);
		
		$replace = array(
			'>',
			'<',
			'\\1'
		);
		
		$buffer = preg_replace($search, $replace, $buffer);
		
		ob_end_clean();		//need to send this before headers for some reason - http://php.net/manual/en/features.connection-handling.php
		ob_start();
		echo $buffer;
		
		if($closeConnection){
			ignore_user_abort(TRUE);
			header("Connection: close");
			header("Content-Length: ".ob_get_length());
			
		}
		ob_end_flush();
		flush();
		@ob_end_clean();		//may not be required
	}
}
?>