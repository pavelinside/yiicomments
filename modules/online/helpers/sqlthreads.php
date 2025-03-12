<?php
//include_once("Packets/Scripts/sqlthreads.php"); exit;

class sqlthreds {

	public function kill($params){
		if(isset($params['id']) && ($id = intval($params['id']))){
			// $lnk = \db::
			// $answ= self::$_lnk->query($query);
			
			\db::query("KILL $id");
		}
		return $this->get();
	}

	public function get(){
		$arr = \db::arr("SHOW FULL PROCESSLIST");
		foreach($arr as &$row)
			$row = <<<HEREDOC
<tr>
<td><a class="btn" href="#" onclick="kill($row[Id])">KILL</a></td>
<td>$row[Id]</td>
<td>$row[User]</td>
<td>$row[Time]</td>
<td>$row[State]</td>
<td><textarea readonly='readonly'>$row[Info]</textarea></td>
</tr>
HEREDOC;
		
		return array(
			'html' => implode('', $arr) 
		);
	}
}

if(isset($_POST['action']))
	switch($_POST['action']){
	case 'get':
	case 'kill':
		$act = $_POST['action'];
		$st = new sqlthreds();
		echo \sys\json($st->$act($_POST));
		exit();
	}

?>
<!DOCTYPE HTML>
<html>
<head>

<link rel='Stylesheet' type='text/css' href='/css/reset.css' />
<link rel='Stylesheet' type='text/css' href='/css/skin.css' />
<link rel='Stylesheet' type='text/css' href='/css/wnd.css' />
<script type='text/javascript' src='/jslib/jquery-1.4.2.min.js'></script>
<script type='text/javascript' src='/js/jsonquery.js'></script>
<meta http-equiv="Content-Type"	content="text/html; charset=windows-1251">

<title>SQL процессы</title>

<style type="text/css">
#head th,#data td {
	text-align: left;
	padding: 0;
}

#data textarea {
	width: 100%;
	height: 95%;
	margin: 0;
	padding: 0;
	border: 0;
	border-bottom: 1px solid black;
}

#data textarea:focus {
	width: 700px;
	height: 400px;
	position: absolute;
	left: 100px;
	top: 50px;
}

#data td {
	padding: 0 5px;
	height: 18px;
	overflow: hidden;
	/*
	text-overflow: ellipsis;
*/
}
</style>
</head>
<body>

	<button onclick="show()">Обновить</button>
	обновлять постоянно
	<input type="checkbox" onclick="setRefreshPermanent(this)">

	<table>

		<tbody id="head">
			<tr>
				<th style="width: 30px"></th>
				<th style="width: 70px">Id</th>
				<th style="width: 80px">User</th>
				<th style="width: 30px">Time</th>
				<th style="width: 100px">State</th>
				<th style="width: 300px">Info</th>
			</tr>
		</tbody>

		<tbody id="data"></tbody>
	</table>

	<script>
var path = '?sqlthreads';

var refrTimer = null;
function setRefreshPermanent(chkb){
	if(refrTimer)
		clearInterval(refrTimer);
	if(chkb.checked)
		refrTimer = setInterval(show, 1000);
}

function kill(id){
	postJSON(path, {action: 'kill', id: id}, _answ);
}

function show(){
	postJSON(path, {action: 'get'}, _answ);
}

function _answ(answ){
	if(answ.error)
		return warn(answ.error);
	$('#data').html(answ.html);
}

var wTimer;
function warn(mess, isgreen){
	clearTimeout(wTimer);
    $('#info').html(mess).css('color', isgreen ? 'green' : 'red');
    wTimer = setTimeout("warn('')", mess.length*100 + 4000);
}

show();
</script>

</body>
</html>