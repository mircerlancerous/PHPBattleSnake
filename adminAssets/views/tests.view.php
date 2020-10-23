<?php
namespace views;

class tests_View extends Main{
	public $db;
	
	public function __construct($db){
		$this->db = $db;
	}
	
	public function getTitle(){
		echo "Tests";
	}
	
	public function getHead(){
		?>
		<script src="staticAssets/scripts/testdata.js"></script>
		<script src="staticAssets/scripts/tests.js"></script>
		<link rel="stylesheet" href="staticAssets/styles/snakeboard.css"/>
		<?php
	}
	
	public function getView(){
		?>
		<h1>Test Battlesnake API</h1>
		<p>&nbsp;</p>
		<div>
			<p>Data Payload</p>
			<textarea id="payload" style="width: 80%;height: 200px;"></textarea>
		</div>
		<div>
			<input type="button" value="test start" onclick="teststart();"/>
			<input type="button" value="test move" onclick="testmove();"/>
			<input type="button" value="test end" onclick="testend();"/>
			<input type="button" value="test ping" onclick="testping();"/>
			<input type="button" value="render board" onclick="renderboard();"/>
			<input type="button" value="purge tests" onclick="purgetests();"/>
		</div>
		<div>
			<p>Result</p>
			<div id="result"></div>
		</div>
		<div>
			<p>&nbsp;</p>
			<table id="board" class="snakeboard"></table>
		</div>
		<?php
	}
}
?>