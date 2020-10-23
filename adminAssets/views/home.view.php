<?php
namespace views;

class home_View extends Main{
	public $db;
	
	public function __construct($db){
		$this->db = $db;
	}
	
	public function getTitle(){
		echo "Home";
	}
	
	public function getHead(){
		
	}
	
	public function getView(){
		?>
		<h1>Battlesnake</h1>
		<p>&nbsp;</p>
		<?php
		if(isset($_GET['game'])){
			$this->showGame($_GET['game']);
			return;
		}
		?>
		<div>
			<a href="?view=tests" class="abtn">Integrated Tests</a>
			&nbsp;
			<a href="?view=unittests" class="abtn">Unit Tests</a>
			&nbsp;
			<a href="?view=login" class="abtn">Logout</a>
		</div>
		<p>&nbsp;</p>
		<h2>List of Battles</h2>
		<table>
			<tr>
				<td style="width:150px;">Created</td>
				<td style="width:350px;">Game ID</td>
				<td style="width:150px;">Snakes</td>
			</tr>
			<?php
			
			//fetch last 5 games that have finished
			$games = $this->db->fetch_all_array("SELECT id,created,game,snakes FROM games WHERE status!=".\Status::$active." ORDER BY id DESC LIMIT 15");
			foreach($games as $game){
				$count = substr_count($game['snakes'],",");
				?>
				<tr>
					<td>
						<a href="?view=home&game=<?php echo $game['id'];?>">
							<?php echo date("Y-m-d H:i",$game['created']);?>
						</a>
					</td>
					<td><?php echo $game['game'];?></td>
					<td><?php echo $count;?></td>
				</tr>
				<?php
			}
			
			?>
		</table>
		<?php
	}
	
	private function showGame($id){
		if(!is_numeric($id)){
			echo "invalid game";
			return;
		}
		$game = $this->db->query_first("SELECT * FROM games WHERE id=$id");
		if(!$id){
			echo "invalid game";
			return;
		}
		?>
		<table>
			<tr>
				<td style="width:100px;">Created</td>
				<td><?php echo date("Y-m-d H:i",$game['created']);?></td>
			</tr>
			<tr>
				<td>Ended</td>
				<td><?php echo date("Y-m-d H:i",$game['ended']);?></td>
			</tr>
			<tr>
				<td>Game ID</td>
				<td><?php echo $game['game'];?></td>
			</tr>
			<tr>
				<td>Snakes</td>
				<td><?php echo $game['snakes'];?></td>
			</tr>
		</table>
		<h2>Requests</h2>
		<table>
			<tr>
				<td>Snake</td>
				<td>Command</td>
				<td>Response</td>
				<td>Time</td>
				<td>JSON</td>
			</tr>
			<?php
			$reqs = $this->db->fetch_all_array("SELECT * FROM requests WHERE game=$id");
			foreach($reqs as $req){
				echo "<tr>
					<td>".$req['snake']."</td>
					<td>".$req['command']."</td>
					<td>".$req['response']."</td>
					<td>".$req['created']."</td>
					<td>".$req['json']."</td>
				</tr>";
			}
			?>
		</table>
		<?php
	}
}
?>