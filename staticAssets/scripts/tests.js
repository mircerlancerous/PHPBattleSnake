var started = false;
window.addEventListener("load",setpayload,false);

function setpayload(){
	document.getElementById("payload").value = JSON.stringify(payload, null, 2);
}
function getpayload(){
	let data = document.getElementById("payload").value;
	return JSON.parse(data);
}
function teststart(){
	if(started){
		writeResult("can't start: game in progress");
		return;
	}
	started = true;
	let data = getpayload();
	data.game.id = "abc123-"+parseInt(Date.now()/1000);
	payload = data;
	setpayload();
	sendCmd("start",data);
}
function testmove(){
	let data = getpayload();
	sendCmd("move",data);
}
function testend(){
	if(!started){
		writeResult("can't end: no game started");
		return;
	}
	started = false;
	let data = getpayload();
	sendCmd("end",data);
}
function testping(){
	sendCmd("ping",null);
}
function purgetests(){
	sendCmd("purgetests",null);
}

function sendCmd(cmd,obj){
	writeResult("send cmd: "+cmd);
	let loc = window.location.href;
	let pos = loc.lastIndexOf("/");
	loc = loc.substring(0,pos+1) + "api/v1/";
	var result = "";
	window.fetch(
		loc+cmd,
		{
			method: "POST",
			body: JSON.stringify(obj)
		}
	).then(
		function(response){
			result = "<p>"+response.status+"</p>";
			if(response.ok){
				result += "<p>OK Result!</p>";
			}
			else{
				result += "<p>Error Result!</p>";
			}
			return response.text();
		}
	).then(
		function(restext){
			result += "<p>"+restext+"</p>";
			writeResult(result);
		}
	);
}
function writeResult(str){
	document.getElementById("result").innerHTML = str;
}

function renderboard(){
	let obj = getpayload();
	let table = document.getElementById("board");
	table.innerHTML = "";
	let i, v, m, check, row, cell;
	for(i=0; i<obj.board.height; i++){
		row = document.createElement("tr");
		for(v=0; v<obj.board.width; v++){
			cell = document.createElement("td");
			check = doesCellHave(obj.board.food,v,i);
			if(check !== false){
				cell.innerHTML = "F"+check;
			}
			else{
				for(m=0; m<obj.board.snakes.length; m++){
					check = doesCellHave(obj.board.snakes[m].body,v,i);
					if(check !== false){
						if(check === 0){
							cell.innerHTML = "S"+m+"h";
						}
						else{
							cell.innerHTML = "S"+m;
						}
						break;
					}
				}
			}
			row.appendChild(cell);
		}
		table.appendChild(row);
	}
}

function doesCellHave(arr,x,y){
	for(let i=0; i<arr.length; i++){
		if(arr[i].x == x && arr[i].y == y){
			return i;
		}
	}
	return false;
}