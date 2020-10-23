<?php
namespace views;

class snakes_View extends Main{
	public $db;
	
	public function __construct($db){
		$this->db = $db;
		
		if(isset($_POST['save'])){
			$this->saveSnake();
		}
	}
	
	public function getTitle(){
		echo "Snakes";
	}
	
	public function getHead(){
		
	}
	
	public function getView(){
		?>
		<h1>Battlesnake</h1>
		<p>&nbsp;</p>
		<a class="abtn" href="?view=snakes&snake=new">create new</a>
		<?php
		if(isset($_GET['snake'])){
			$this->showSnake($_GET['snake']);
			return;
		}
		?>
		<p>&nbsp;</p>
		<h2>List of Snakes</h2>
		<table>
			<tr>
				<td style="width:150px;">Created</td>
				<td style="width:350px;">GitHub</td>
				<td style="width:350px;">Name</td>
				<td style="width:150px;">Engine</td>
			</tr>
			<?php
			
			//fetch list of snakes
			$snakes = $this->db->fetch_all_array("SELECT id,created,name,github,engine FROM snakes WHERE status=".\Status::$active);
			foreach($snakes as $snake){
				?>
				<tr>
					<td>
						<a href="?view=snakes&snake=<?php echo $snake['id'];?>">
							<?php echo date("Y-m-d H:i",$snake['created']);?>
						</a>
					</td>
					<td><?php echo $snake['github'];?></td>
					<td><?php echo $snake['name'];?></td>
					<td><?php echo $snake['engine'];?></td>
				</tr>
				<?php
			}
			
			?>
		</table>
		<?php
	}
	
	private function showSnake($id){
		if($id == 'new' || isset($_GET['edit'])){
			$this->newEditSnake($id);
			return;
		}
		if(!is_numeric($id)){
			echo "invalid snake";
			return;
		}
		$snake = $this->db->query_first("SELECT * FROM snakes WHERE id=$id");
		if(!$id){
			echo "invalid snake";
			return;
		}
		?>
		<a class="abtn" href="?view=snakes&snake=<?php echo $snake['id'];?>&edit">edit snake</a>
		<p>&nbsp;</p>
		<table>
			<tr>
				<td style="width:100px;">Created</td>
				<td><?php echo date("Y-m-d H:i",$snake['created']);?></td>
			</tr>
			<tr>
				<td>GitHub</td>
				<td><?php echo $snake['github'];?></td>
			</tr>
			<tr>
				<td>Name</td>
				<td><?php echo $snake['name'];?></td>
			</tr>
			<tr>
				<td>Engine</td>
				<td><?php echo $snake['engine'];?></td>
			</tr>
			<tr>
				<td>Colour</td>
				<td><?php echo $snake['colour'];?></td>
			</tr>
			<tr>
				<td>Head</td>
				<td><?php echo $snake['head'];?></td>
			</tr>
			<tr>
				<td>Tail</td>
				<td><?php echo $snake['tail'];?></td>
			</tr>
		</table>
		<?php
	}
	
	private function newEditSnake($id='new'){
		if($id == 'new'){
			$snake = [
				"created" => time(),
				"github" => "",
				"name" => "",
				"engine" => \Settings::fetch($this->db,"DEFAULTENGINE"),
				"colour" => "",
				"head" => "",
				"tail" => ""
			];
		}
		else{
			if(!is_numeric($id)){
				echo "invalid snake";
				return;
			}
			$snake = $this->db->query_first("SELECT * FROM snakes WHERE id=$id");
			if(!$id){
				echo "invalid snake";
				return;
			}
		}
		if($id == 'new'){
			?>
			<a class="abtn" href="?view=snakes">cancel</a>
			<?php
		}
		else{
			?>
			<a class="abtn" href="?view=snakes&snake=<?php echo $snake['id'];?>">cancel</a>
			<?php
		}
		?>
		<p>&nbsp;</p>
		<form action="?view=snakes" method="post">
		<table>
			<tr>
				<td style="width:100px;">Created</td>
				<td><?php echo date("Y-m-d H:i",$snake['created']);?></td>
			</tr>
			<tr>
				<td>GitHub</td>
				<td>
					<input type="text" name="github" value="<?php echo $snake['github'];?>"/>
				</td>
			</tr>
			<tr>
				<td>Name</td>
				<td>
					<input type="text" name="name" value="<?php echo $snake['name'];?>"/>
				</td>
			</tr>
			<tr>
				<td>Engine</td>
				<td>
					<input type="text" name="engine" value="<?php echo $snake['engine'];?>"/>
				</td>
			</tr>
			<tr>
				<td>Colour</td>
				<td>
					<input type="text" name="colour" value="<?php echo $snake['colour'];?>"/>
				</td>
			</tr>
			<tr>
				<td>Head</td>
				<td>
					<input type="text" name="head" value="<?php echo $snake['head'];?>"/>
				</td>
			</tr>
			<tr>
				<td>Tail</td>
				<td>
					<input type="text" name="tail" value="<?php echo $snake['tail'];?>"/>
				</td>
			</tr>
		</table>
		<input type="hidden" name="id" value="<?php echo $id;?>"/>
		<input type="submit" name="save" value="save"/>
		</form>
		<?php
	}
	
	private function saveSnake(){
		$snake = [
			"created" => time(),
			"github" => $_POST['github'],
			"name" => $_POST['name'],
			"engine" => $_POST['engine'],
			"colour" => $_POST['colour'],
			"head" => $_POST['head'],
			"tail" => $_POST['tail']
		];
		
		if($_POST['id'] == 'new'){
			$snake["status"] = \Status::$active;
			$res = $this->db->query_insert("snakes",$snake);
			if($res){
				$_GET['snake'] = $res;		//snake id
			}
		}
		else{
			$res = $this->db->query_update("snakes",$snake,"id=".$_POST['id']);
		}
		
		if(!$res){
			$this->errors[] = "error saving snake";
		}
		else{
			$this->messages[] = "snake saved";
		}
	}
}
?>