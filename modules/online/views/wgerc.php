<?php // ����

$handler = $entity = "gerc";
$table = new wcgerc($handler, $entity, '');
$table->decodeIncomParams = 1;
$table->useOnExitJSON= 1;

//$table->newcol('streetid',	['id', 'id �����'], '30px', array('streetid', 'inp'), array('streetid', 'inp'), array('streetid', 'inp'));
$table->newcol('id',	'id', '50px', ['id', 'nobr'], ['id', 'inp'], ['id', 'inp']);
$table->newcol('street',	'�����', '100px', ['street', 'inp'], ['gercstreetid', 'ac'], ['gercstreetid', 'ac']);
$table->newcol('house',	'���', '100px', ['house', 'inp'], ['gerchouseid', 'ac'], ['gerchouseid', 'ac']);
$table->newcol('flat',	'��������', '100px', ['flat', 'inp'], ['gercflatid', 'ac'], ['gercflatid', 'ac']);

$table->extendFilter("gercstreetid", "gs.id=");
$table->extendFilter("gerchouseid", "gh.id=");
$table->extendFilter("gercflatid", "gf.id=");

//$table->newcol('level',	'�������', '100px',
//	array('level', 'inp', [['min', 0], ['max', 100], ['integer']]));

$table->qry = 
"SELECT gf.id AS rowid, 0 AS disabled, gf.id, gs.nam street, gh.nam house, gf.nam flat, 
	gs.id gercstreetid, gh.id gerchouseid
FROM gercflat gf
	LEFT JOIN gerchouse gh ON gh.id=gf.gerchouseid
	LEFT JOIN gercstreet gs ON gs.id=gh.gercstreetid";
$table->setGroup('gf.id');

$table->newPropCol('family', '���', '100px', ['loginview', 'nobr'],	false, ['gercusrid', 'ac']);
$table->newPropCol('name', '���', '100px', ['name', 'nobr'], false, ['name', 'inp']);
$table->newPropCol('otchestvo', '���-��', '100px', ['otchestvo', 'nobr'],	false, ['otchestvo', 'inp']);

$table->propqry = "SELECT gu.id rowid, 0 disabled, gu.family, gu.name, gu.otchestvo
FROM gercflatusr gfu
	INNER JOIN gercusr gu ON gu.id=gfu.gercusrid
WHERE gfu.gercflatid=%owner%";

$table->externJoins('gercusrid', "LEFT JOIN gercflatusr gfu ON gf.id=gfu.gercflatid");
$table->externJoins('gercusrid_text', "LEFT JOIN gercflatusr gfu ON gf.id=gfu.gercflatid");

$table->addhidden('gercstreetid, gerchouseid');

$table->setRely('gercstreetid', 'gerchouseid', false, false, ['ac']);
$table->setRely('gerchouseid', 'gercflatid', false, false, ['ac']);
$table->useOnExitJSON= 1;

$table->checkAJAX();
?>
<!DOCTYPE html><html><head><title>����</title>
<?php echo $table->incl(); ?>
</head><body>

<?php echo $table->getHTML(); ?>

<script type="text/javascript">
var engine=new tabloid_engine(entity, handler);
$(function(){
	//postJSON(tep.path, {action:'usrgetall' }, function(sText){	usrs= sText; });
});

$(document).click(function(evt) {
	return true;
});

engine.init();
</script></body></html>