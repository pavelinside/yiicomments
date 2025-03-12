//убирает выделение со всей страницы
function clearSelection(){
	if (document.selection && document.selection.empty) 
		document.selection.empty();
	else
		if (window.getSelection) {
			var sel= window.getSelection();
			if (sel && sel.removeAllRanges) 
				sel.removeAllRanges();
		}
}

function rowSelector(tbl, params){
	tobj = $(tbl).get(0); 
	// доступные параметры
	var defParams = {
		// класс который будет присваиваться выбраной строке, по-умолчанию 'selectedrow'
		selectClass:		'selected_row'	,
		// строка с данным классом будет игнорироваться
		skipClass:			'body_color' ,
		// выделена ли таблица изначально
		isSelected:			false ,
		// время через которое сработает функция selectRow(), после перехода по строкам клавишами
		selectTime:		350 ,
		// резерв
		tabReact:			false ,
		// срабатывание selectRow мгновенно при выделении клавишами
		keyInstant:			false ,
		// срабатывание selectRow мгновенно при выделении мышкой
		mouseInstant:		true ,
		// выделять уже выделенную строку на mouseUp (нужно, например для правильной работы drag and drop)
		selectOnMouseUp:		false,
		// время последнего выделения
		timeSelect: 0,
		// вызывается при нажатии PgUp или стрелочки вверх при выделеной первой строке
		prevPage:			function(){return true;} ,
		// вызывается при нажатии PgDown или стрелочки вниз при выделеной последней строке
		nextPage:			function(){return true;} ,
		// функция вызывается при выделении строки
		onSelect:			function(row, tbl, evt){return true;} ,
		// функция вызывается при снятии выделения со строки
		unSelect:			function(row, tbl, evt){return true;} ,
		//	обработчик перед выделением
		beforeSelect:		function(was, to, unsel, e){return true;} ,
		//	клик по невыделяемой строке
		skipRowClick:		function(row){return;},
		//	клик по невыделяемой строке
		enable:		function(evt){return 1;}
	};
	
	if(params){
		for(var nam in defParams) 
			if(typeof(params[nam]) != 'undefined') 
				defParams[nam] = params[nam];
		if(params.isDef) 
			rowSelector.prototype.defTable = tobj;
	}
	
	// внутренние переменные
	defParams.obj=tobj;
	defParams.lastSelected = null; 
	defParams.firstSelected = null;
	defParams.timer = null;

	this.tbl=tobj;
	
	if ( rowSelector.prototype.tables.push(defParams) == 1 ){
		var brow = $.browser;
		if(brow.opera && brow.version < 12.10){
			$('html').bind('keypress', function(e){rowSelector.prototype.kdwn(e);});
		} else {
			$('html').bind('keydown', function(e){rowSelector.prototype.kdwn(e);});
		}
		$('html').bind('mousedown', function(e){ 
			if(e.which == 1 || e.which === undefined)
				rowSelector.prototype.mdwn(e);
		});
		if(defParams.selectOnMouseUp)
			$('html').bind('mouseup', function(e){ 
				if(e.which == 1 || e.which === undefined)
					rowSelector.prototype.mdup(e);
			});
	}
}

rowSelector.prototype.tables = new Array();
rowSelector.prototype.defTable = null;
rowSelector.prototype.skipElements = ['select', 'textarea'];

rowSelector.prototype.kdwn = function(e){
	// обработчик нажатия клавиш
	if($.browser.opera && e.shiftKey)
		return;

	// при фокусе на select/input/textarea ничего не делать
	var el = e.target;
	if(in_array(el.tagName.toLowerCase(), this.skipElements))
		return;
	
	// реагировать только на стрелку вверх/вниз и клавиши pageUp, pageDown
	if(!in_array(e.keyCode, [33,34,38,40])) 
		return;

	var p = this.getTParams();
	if(!p || !p.enable(e))
		return;

	var row = null;

	switch (e.keyCode) {
	case 33:	return p.prevPage(p);
	case 34:	return p.nextPage(p);
	case 38: {
		if(!p.lastSelected) {
			p.row = $('tr:not(.'+p.skipClass+'):last', p.obj).get(0);
		} else{
			row = $(p.lastSelected).prev();	
			if(row.hasClass(p.skipClass))
				return p.prevPage(p);
			p.row = row.get(0);	
		}
		
		if(p.row){
		  if(e.shiftKey){
		    return this.selectRange(p, p.row, e);
		  } else {
		    var i = $(el.tagName, p.lastSelected).index(el);
		    return this.reselect(p, p.keyInstant, e, i );
		  }
		} else
		  return p.prevPage(p);
	}

	case 40: {
		if(!p.lastSelected) {
			p.row = $('tr:not(.'+p.skipClass+'):first', p.obj).get(0);
		} else{
			row = $(p.lastSelected).next();	
			if(row.hasClass(p.skipClass))
				return p.nextPage(p);
			p.row = row.get(0);	
		}

		if(p.row){
      if(e.shiftKey){
        return this.selectRange(p, p.row, e);
      } else {
        var i = $(el.tagName, p.lastSelected).index(el);
        return this.reselect(p, p.keyInstant, e, i );          
      }
		} else
		  return p.nextPage(p); 

	}
	}
};

rowSelector.prototype.mdup = function(e){
	// обработчик нажатия мышкой
	var p = this.setSelectedTable(e);
	if(!p || !p.enable(e)) 
		return;
	if($(p.row).hasClass(p.skipClass))
		return p.skipRowClick(p.row);
	// если выделена строка, то оставляем её одной выделенной только после mouseup
	if(!e.shiftKey && !e.ctrlKey && p.selectOnMouseUp && $(p.row).hasClass(p.selectClass)){
		var now = new Date();
		if(now.getTime() - p.timeSelect > 1000){		// чтобы не выделялось два раза на нажатие мыши
			this.reselect(p, p.mouseInstant, e);
			p.timeSelect= now.getTime();
		}
	}
};

rowSelector.prototype.mdwn = function(e){
	// обработчик нажатия мышкой
	var p = this.setSelectedTable(e);
	if(!p || !p.enable(e)) 
		return;
	if($(p.row).hasClass(p.skipClass))
		return p.skipRowClick(p.row);

	if(e.shiftKey && p.firstSelected) 
		this.selectRange(p, p.mouseInstant, e);
	else 
		if(e.ctrlKey) 
			this.toggleSelect(p, p.mouseInstant, e);
		else {
			if(!p.selectOnMouseUp)
				this.reselect(p, p.mouseInstant, e);
			else {
				// если не выделена строка, выделяем на mousedown, чтобы сразу можно было перетаскивать её
				if(!$(p.row).hasClass(p.selectClass)){
					this.reselect(p, p.mouseInstant, e);
					if(p.selectOnMouseUp){
						var now = new Date();
						p.timeSelect= now.getTime();
					}
				}
			}
		}
};

rowSelector.prototype.toggleSelect = function(p, instant, e){
	if(!p.beforeSelect(p.lastSelected, p.row, $(p.row).hasClass(p.selectClass), e))
		return;

	if($(p.row).toggleClass(p.selectClass).hasClass(p.selectClass)){
		p.lastSelected = p.row;
		if(!p.firstSelected) 
			p.firstSelected = p.row;
		this.preSelect(p, instant, e);
	}else{
		if(p.lastSelected == p.firstSelected)
			p.lastSelected = null;
		 else
			p.lastSelected = p.firstSelected;
		this.preUnselect(p, instant, e);
	}
};

rowSelector.prototype.reselect = function(p, instant, e, i){
//	if($(p.row).hasClass(p.skipClass))
//		return;
	if(!p.beforeSelect(p.lastSelected, p.row, false, e))
		return;
	var isdeselectall= e.which == 1 || !$(p.row).hasClass(p.selectClass);
	if(isdeselectall)
		$('tr', p.obj).removeClass(p.selectClass);
	$(p.row).toggleClass(p.selectClass, true);
	if(isdeselectall)
		p.firstSelected = p.lastSelected = p.row;
	else {
	  if(p.lastSelected)
	    $(p.lastSelected).toggleClass(p.selectClass, false);
	  p.lastSelected = p.row;
	}
	this.preSelect(p, instant, e);
	
// TODO i - индекс фокуса в предыдущей строке
//	if(i>=0)
		
};

rowSelector.prototype.selectRange = function(p, instant, e){
	var r1 = p.firstSelected;
	var r2 = p.row;
	if(clearSelection) 
		clearSelection();	
	if(r1==r2) 
		return this.reselect(p, instant, e);
	
	var i1 = r1.rowIndex;
	var i2 = r2.rowIndex;

	if(i1>i2){
		i1=i2;
		i2=r1.rowIndex;
	}

	var toSelect = [];
	var toDeselect = [];
	
	for(var i=0; i<p.obj.rows.length; i++){
		var row = p.obj.rows[i];
		if($(row).hasClass(p.skipClass))
			continue;
		
		var inRange = ( row.rowIndex >= i1 && row.rowIndex <= i2 );
		if($(row).hasClass(p.selectClass) && !inRange)
			toDeselect.push(row);
		if(!$(row).hasClass(p.selectClass) && inRange)
			toSelect.push(row);
	}
	
	if(!p.beforeSelect(p.lastSelected, p.row, false, e))
		return;

	p.lastSelected = p.row;
	$(toSelect).addClass(p.selectClass);
	$(toDeselect).removeClass(p.selectClass);
	this.preSelect(p, true, e);
};

rowSelector.prototype.preUnselect = function(p, instant, e){
	// добавление задержки, при необходимости, перед выполнением selectRow
	if(p.timer) clearTimeout(p.timer);
	
	if(instant || !p.selectTime)
		p.unSelect(p.row, p.obj, e);
	else 
		p.timer = setTimeout(function(){p.unSelect(p.row, p.obj, e);}, p.selectTime);
};

rowSelector.prototype.preSelect = function(p, instant, e){
	// добавление задержки, при необходимости, перед выполнением selectRow
	if(p.timer) clearTimeout(p.timer);

	if(instant)
		p.onSelect(p.row, p.obj, e);
	else 
		p.timer = setTimeout(function(){p.onSelect(p.row, p.obj, e);}, p.selectTime);
};

rowSelector.prototype.getSelectedRowsParam = function(attrName){
	// возвращает набор параметров указаного названия у выделеных строк
	var rows = this.getSelectedRows();
	var answ = new Array();
	$(rows).each(function(){
		var id = $(this).attr(attrName);
		if(id)
			answ.push(id);
	});
	return answ;
};

rowSelector.prototype.getSelectedRowsData = function(fNam){
	// возвращает массив значений элементов с указаным именем в выделеных строках, или все значения
	// если не указано имя
	var rows = this.getSelectedRows();
	var answ = [];
	
	for(var i in rows){
		var vals = getvals(rows[i]);
		answ.push( fNam ? vals[fNam] : vals);
	}
	return answ;
};

rowSelector.prototype.getNams = function(){
	return this.getSelectedRowsParam('name');
};


rowSelector.prototype.getSelectedRows = function(){
	// возвращает набор выделеных строк указаной таблицы, либо выбраной на данный момент (если не указана)
	var p = this.getTParams(this.tbl);
	if(!p) 
		return null;
	return $('tr.'+p.selectClass, p.obj).get();
};

rowSelector.prototype.getLastSelected = function(){
	// возвращает последнюю выделеную строку
	var p = this.getTParams(this.tbl);
	if(!p.lastSelected) 
		return null;
	if($(p.lastSelected).hasClass(p.selectClass)) 
		return p.lastSelected;
	else 
		return null;
};

rowSelector.prototype.unselectRows = function(){
	// снимает выделение со строк в указаной/сейчас выделеной таблицы
	var p = this.getTParams(this.tbl);
	$('tr', this.tbl).removeClass(p.selectClass);
};

rowSelector.prototype.setSelectedTable = function(e){
	// снимает фокус с др. таблиц и устанавливает на ту по которой был совершон клик
	var arr = rowSelector.prototype.tables;

	var el = e.target;
	var row = false;
	var ti = false;

	while (el && el.tagName != 'BODY'){
		if(el.tagName=='TR') row = el;
		ti = false;
			
		if(el.tagName=='TBODY' || el.tagName=='TABLE')
			for(var i=0; i<arr.length; i++)	
				if(arr[i]['obj']==el){
					ti = i;
					break;
				}
		if(ti !== false) 
			break;
			
		el = el.parentNode;
	}

	for(var i=0; i<arr.length; i++)	
		arr[i].isSelected = false;

	if(ti !== false && row) {
		answ = arr[ti];
		answ.isSelected = true;
		answ.row = row;
		return answ;
	}
	return null;
};


rowSelector.prototype.getTParams = function(tbl){
	// возвращает таблицу выделеную в данный момент, или выделеную по-умолчанию, а также соп. параметры
	var arr = rowSelector.prototype.tables;
	var defSets = false;
	if(tbl){
		for(var i=0; i<arr.length; i++)
			if(arr[i].obj==tbl) 
				return arr[i];
		return null;
	} 

	for(var i=0; i<arr.length; i++)
		if(arr[i].isSelected) 
			return arr[i];
		else 
			if(arr[i]['obj']==rowSelector.prototype.defTable) 
				defSets = arr[i];

	return defSets;
};

rowSelector.prototype.flushSelection = function(){
	var p = this.getTParams(this.tbl);
	p.lastSelected = null; 
	p.firstSelected = null; 
};