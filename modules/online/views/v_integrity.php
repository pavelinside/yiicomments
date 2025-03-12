001save.php<?php
// ����������� ��
$aParams = $_POST;
if(isset($aParams['action'])){
	if($aParams['action']=='check'){
		$checker = new \m_integrity();
		$results = $checker->fullCheck($aParams);
		echo \sys\json(Array('results'=>$results));
		exit;
	}
	if($aParams['action']=='chkPrKeyRefsDelete'){
		if(!isset($_POST['table']) || !$_POST['table']){
			exit(\sys\json(Array('error'=>'not specified table')));
		}
		$checker = new \m_integrity();
		$results = $checker->chkPrKeyRefsDelete($_POST['table']);
		exit(\sys\json($results));
	}
}
?>
<html>
<head>
<title>Integrity</title>
<?php
	$sIncl = \Loader\Loader::loadResource(['jquery.calendar.js',	'jquery.calendar.css', 'reset.css',
		'winclose.js','evt.css','notes.js',
		'usrevt.js','common.js','rowselector.js',
		'wnd.css','skin.css','console.js'
	]);

	// core
	$sIncl .= \Loader\Loader::loadResource(['jsonquery.css', 'jquery.autocomplete.css', 'jquery-ui-1.7.2.custom.css',
		'jquery-1.4.2.min.js', 'jquery.autocomplete.js', 'jquery-ui-1.7.2.custom.min.js', 'jsonquery.js'], ['Encoder']);

	$sIncl .= \Loader\Loader::loadResource(['menuflex.css', 'menuflex.js'], ['Menu']);
	echo $sIncl;
?>
<style type="text/css">

.succsess{
	color: green;
	font-weight: bold;
}
.error{
	color: #FF5F5F;
}

.error, div.note_expand_div{
	font-weight: bold;
	cursor: pointer;
}

.skipped{
	color: #5F99FF;
	fontWeight: 'bold'
}

strong{
	font-weight: bold;
}

#checks{
	padding-left: 5px;
	margin-top: 25px;
}

div.headcontrol{
	padding-left: 10px;
	border-bottom: 2px solid black;
	position: fixed;
	width: 100%;
	left: 0;
	top: 0;
	height: 23px;
	z-index: 2;
}


div.checkblock > div{
	margin-bottom: 5px;
	margin-left: 5px;
	display: inline-block;
}

div.checkblock div.err{
	padding-left: 5px;
	display: block;
}

div.checkblock div.descr{
/*	margin-bottom: 15px;*/
	font-size: 12pt;
	font-weight: bold;
}

div.note_expand_div{
	color: #8F8F8F;
}

div.checkblock{
	border-bottom: 1px solid black;
}

div.errdescr{
	background-color: #FEFFBF;
	font-size: 10pt;
	padding: 5px;
}

div.note{
	display: none;
}

.checkblock input[type=text]{
	width: 35px;
	height: 15px;
	text-align: right;
}

body{
	overflow: auto;
}

#qryindicator{
	position: fixed;
	top: 40%;
	color: green;
	left: 42%;
	white-space: nowrap;
	background-color: white;
	padding: 13px;
	font-weight: bold;
	border: 3px double black;
	display: none;
}
</style>
</head>
<body>
<div id="qryindicator" class="layer_overdlg5" ></div>
<div class="headcontrol maincolor">
	<input type="checkbox" checked="checked" onclick="checkAll(this)">
	<button onclick="check()">Выполнить отмеченые проверки</button>
	<button onclick="expandcollapsenote()">Свернуть/развернуть все подробности </button>
	<span id="warn" class="skipped"></span>
</div>


<div id="checks">

<?php
foreach (\m_integrity::$register as $num=>$arr) {
	$mnam = $arr[0];
	$note = (isset(\m_integrity::$checkNote[$mnam])) ? (' - ' . \m_integrity::$checkNote[$mnam]) : '';
	echo "
	<div id='check$num' class='checkblock'>
		<div id='checkinfo$num' class='descr'><input type='checkbox' name='check$num'"
		.((isset($arr[2]) and $arr[2]) ? '' : "checked='checked' ")
		."> <button onclick='check($num)'>Выполнить</button> ".($num+1)." $arr[1]</div>
		<div id='checknote$num' class='note' style='display: none'>$note</div>
		<div id='checkres$num' class='succsess'></div>
	</div>";
}
?>
</div>

<script type="text/javascript">
var handler = 'integrity';
var entity = 'integrity';
var path = '?'+entity+'='+entity;
var loading = false;

function expandcollapsenote(){
	if(!$('.note:visible').slideToggle(300).length)
		$('.note_expand_div').click();
}

var noteEls = $('.checkblock .note:not(:empty)');//.hide();
$('<div class="note_expand_div">Подробности</div>').insertBefore(noteEls)
	.click(function(){toggleNext(this);});

function toggleNext(el){
	$(el).next().slideToggle(300);
}

//создание недостающих уведомлений для задач
function evtCreateTask(evt){
	var lnk = evt.target,
		ids = $(lnk).attr('data'),
		type = $(lnk).attr('datatype'),
		post = {control: 'task', mtd:'evtCreate', getwndnam: winevt.wndmain.name, ids:ids, type: type};
	postJSON("", post, function(obj){
		if(obj.ierror) return;
		dialog("Создание недостающих уведомлений", 'Уведомлений создано: ' + obj.code,	{
			"OK":	function(){
				dlg.dialog("close");
			}
		}, {cancel:false});
	});
}

//создание недостающих уведомлений для задач
function evtCreateOrd(evt){
	var post = {control: 'ord', mtd:'evtCreate', getwndnam: winevt.wndmain.name};
	postJSON("", post, function(obj){
		if(obj.ierror) return;
		dialog("Создание недостающих уведомлений", 'Уведомлений создано: ' + obj.code,	{
			"OK":	function(){
				dlg.dialog("close");
			}
		}, {cancel:false});
	}, true, {timeout: 600000});
}

function check(num, p, alterF){
	var arr = [];
	if(typeof num=='undefined') {
		$('#checks input:checked').each(function(){
			var nam = $(this).attr('name');
			if(nam.slice(0,5)=='check')
				arr.push(nam.slice(5));
		});
		$('.checkblock > .err').empty();
		if(!arr.length)
			return $('#warn').css({color: 'red', opacity: 1}).stop()
				.html('Не выбрана ни одна проверка').fadeTo(3000,0);

		blockInterface();
		return checkStack(arr);
	}

	var params = {action: 'check', checks: [num]};
	if(p)
		params.extra = p;

	$(':input', '#check'+num).each(function(){
		var name = $(this).attr('name');
		if(name)
			params[name] = (this.type == 'checkbox') ? ($(this).attr('checked')?1:0) : $(this).val();
	});
	if(typeof alterF!='function'){
		$('#checks .errdescr:visible').slideToggle(300);
		$('#checkres'+num).empty();
	}

	postJSON(path, params, (typeof alterF=='function') ? alterF : checksAnsw, true, {timeout: 0});
}

function checkStack(stack){
	if(!stack.length)
		return stopQuery();

	var curid = stack.shift();
	var params = {action: 'check', checks: [curid]};
	params['check' + curid] = 0;
	$('#qryindicator').html('Выполняется проверка №' + (parseInt(curid)+1) ).show();

	postJSON(path, params, function(answ){
		checksAnsw(answ);
		checkStack(stack);
	}, false, {timeout: 0});
}

function stopQuery(){
	unblockInterface();
	$('#qryindicator').html('Проверки выполнены').css({color: 'green', opacity: 1, fontWeight: 'bold'}).stop().fadeTo(3000,0);
}

function checksAnsw(answ){
	titl();

	if(answ.error){
		stopQuery();
		return $('#warn').html(answ.error).css({color: 'red', opacity: 1,fontWeight:'bold'})
			.stop().fadeTo(5000,0);
	}

	if(!answ.results)
		return;
	for(var checknum in answ.results){
		var r = answ.results[checknum];
		var id = '#checkres'+checknum;

		var timed = r.time ? (' ('+r.time+'c)') : '';


		if(!r.err){
			$(id).html('OK'+timed).addClass('succsess').removeClass('err');
		}else{
			r = "<span class='error' onclick='toggleNext(this)'>Ошибки:"+timed+"</span>"
				+ "<div class='errdescr'>" + r.err.join('<br>') + '</div>';
			$(id).html(r).addClass('err').removeClass('succsess');
		}
	}
}

function checkAll(el){
	if(el.checked)
		$('#checks input:checkbox').attr('checked', 'checked');
	else
		$('#checks input:checkbox').removeAttr('checked');
}

function chkPrKeyRefs(evt){
	var lnk = evt.target,
		table = $(lnk).attr('datatable'),
		post = {action: 'chkPrKeyRefsDelete', getwndnam: winevt.wndmain.name, table: table};

	dialog("Удаление неиспользуемых id сущностей", 'Удалить неиспользуемые сущности для таблицы ' + table + "?",	{
		"OK":	function(){
			dlg.dialog("close");
			postJSON("?integrity=integrity", post, function(obj){
				if(obj.ierror)
					return;
				dialog('Удалено записей: ' + obj.deleted, obj.html,	{
					"OK":	function(){
						dlg.dialog("close");
					}
				}, {cancel:false});
			}, true);
		}
	});
}

var titleTimer = false;
var defWndTitle = document.title;
function titl(stMess, progr){
	clearTimeout(titleTimer);
	var title = stMess ? stMess : defWndTitle;
	if(stMess!==undefined){
		progr = (!progr || progr>3) ? 1 : ++progr;
		title+= '.'.repeat(progr);
		titleTimer = setTimeout(function(){titl(stMess, progr);}, 500);
	}
	document.title = title;
}

String.prototype.repeat = function( num ){
    return new Array( num + 1 ).join( this );
};

</script>
</body>
</html>