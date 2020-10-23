<?php
namespace views;

class unittests_View extends Main{
	public $db;
	
	public function __construct($db){
		$this->db = $db;
	}
	
	public function getTitle(){
		echo "Unit Tests";
	}
	
	public function getHead(){
		
	}
	
	public function getView(){
		?>
		<h1>Battlesnake Engine Unit Tests</h1>
		<p>&nbsp;</p>
		<div>
			<a class="abtn" href="?view=unittests&section=RandomMove">Random Move</a>
			<a class="abtn" href="?view=unittests&section=AllowedMoves">Allowed Moves</a>
			<a class="abtn" href="?view=unittests&section=NearbyFood">Nearby Food</a>
			<a class="abtn" href="?view=unittests&section=MoveInCircle">Move In Circle</a>
		</div>
		<div>
			<?php
			if(isset($_GET['section'])){
				switch($_GET['section']){
					case "RandomMove":$this->sectionRandomMove();break;
					case "AllowedMoves":$this->sectionAllowedMoves();break;
					case "NearbyFood":$this->sectionNearbyFood();break;
					case "MoveInCircle":$this->sectionMoveInCircle();break;
					default:break;
				}
			}
			?>
		</div>
		<?php
	}
	
	private function sectionRandomMove(){
		?>
		<p>Tools - Random Move</p>
		<?php
		echo "<p>--Get</p>";
		include_once("snakeAssets/engines/unittests/RandomMove_Get.test.php");
		$test = new \engines\unittests\RandomMove_Get();
		foreach($test->getResults() as $key => $res){
			echo "<p>$key - $res</p>";
		}
	}
	
	private function sectionAllowedMoves(){
		?>
		<p>Tools - Allowed Moves</p>
		<?php
		echo "<p>--filterForWall</p>";
		include_once("snakeAssets/engines/unittests/AllowedMoves_filterForWall.test.php");
		$test = new \engines\unittests\AllowedMoves_filterForWall();
		foreach($test->getResults() as $key => $res){
			echo "<p>$key - $res</p>";
		}
		echo "<p>--filterForBody</p>";
		include_once("snakeAssets/engines/unittests/AllowedMoves_filterForBody.test.php");
		$test = new \engines\unittests\AllowedMoves_filterForBody();
		foreach($test->getResults() as $key => $res){
			echo "<p>$key - $res</p>";
		}
	}
	
	private function sectionNearbyFood(){
		?>
		<p>Tools - Nearby Food</p>
		<?php
		echo "<p>--getDistanceToFood--</p>";
		include_once("snakeAssets/engines/unittests/NearbyFood_getDistanceToFood.test.php");
		$test = new \engines\unittests\NearbyFood_getDistanceToFood();
		foreach($test->getResults() as $key => $res){
			echo "<p>$key - $res</p>";
		}
	}
	
	private function sectionMoveInCircle(){
		?>
		<p>Tools - Move In Circle</p>
		<?php
		echo "<p>--getMoves--</p>";
		include_once("snakeAssets/engines/unittests/MoveInCircle_getMoves.test.php");
		$test = new \engines\unittests\MoveInCircle_getMoves();
		foreach($test->getResults() as $key => $res){
			echo "<p>$key - $res</p>";
		}
	}
}
?>