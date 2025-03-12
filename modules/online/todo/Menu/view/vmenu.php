<?php
$handler = $entity = "menu";

$table = new \Menu\Menu($handler, $entity, '');

$table->decodeIncomParams = 1;
$table->useOnExitJSON= 1;
$table->qryForceWhere= true;
$table->descrLen= array(40);

$table->qry = "SELECT m.id AS rowid, 0 AS enabled, m.id, m.menuid menu, m.position, m.uri, m.href, m.name,
 mt.nam menutyp, m.attributes, m.hidden, m.menuid, m.menutypid
FROM menu m
	INNER JOIN menutyp mt ON mt.id=m.menutypid";

$table->newcol('id', 'id', '15px', ['id', 'nobr'], false, ['id', 'inp']);
$table->newCol('menu',	'Parent', '50px', ['menu', 'inp'], 
		['menuid', 'ac', [['integer'], ['required']]], ['menuid', 'ac']);
$table->newCol('position', ['pos', 'position'], '15px', ['position', 'inp', [['integer']]],
		['position', 'inp', [['integer']]], ['position', 'inp']);
$table->newcol('uri', 'uri', '150px', ['uri', 'inp']);
$table->newcol('href', 'href', '250px', ['href', 'inp']);
$table->newcol('name', 'name', '300px', ['name', 'inp']);
$table->newCol('menutyp',	'Typ', '50px', ['menutyp', 'nobr'], ['menutypid', 'ac'], ['menutypid', 'ac']);
$table->newcol('attributes', 'attributes', '150px', ['attributes', 'textarea']);
$table->newcol('hidden', ['hd', 'is hidden'], '10px', ['hidden', 'chkb']);

$table->allowEmptyInsert(['uri', 'href', 'attributes', 'hidden', 'menu', 'menutypid', 'position', 'menuid']);
$table->allowEmptyContain(['uri', 'href', 'attributes', 'hidden']);

$table->checkAJAX();
?>
<!DOCTYPE html><html><head>
  <title>Menu</title>
  <?php echo $table->incl(['jstorage.js', 'jstorageevents.js']); ?>
  <style>
    #maintbl{
      display: table-cell;
    }
    #otherinfo{
      display: table-cell;
    }
    .tablestyle{
      font-size: 15px;
    }
  </style>
</head>
<body>

<?php
  echo $table->getHTML();
?>

<script type="text/javascript">
  var d= document,
      engine=new tabloid_engine(entity, handler),
      stevent = new jstorageevents(0, {init:true});

  $(function(){
    $(document.body).append("<div id='otherinfo'>111</div>");
  });

  engine.drawRows = function(answ, isprop){
    tep.drawRows(answ, isprop);

    $("#otherinfo").html('<button id="checkMenu">Check menu</button>' +
    '<div id="infores"></div>'
    );
  };

  $(document).click(function(evt) {
    var el = evt.target,
        elid = el.id;
    if($('body > div.ui-dialog').length || evt.which > 1 || !elid)
      return true;
    switch (elid) {
      case "checkMenu":
        postJSON('', {action: 'checkMenu'}, function(data){
          $("#infores").html(data.code);
        }, false);
        return false;
    }
    return true;
  });

  engine.init();
</script>
</body>
</html>