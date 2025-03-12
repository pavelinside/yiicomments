function tabloid_engine(entity, handler, ratio){
	var tep = tabloid_engine.prototype;
	tep.allSelected = false;
	tep.allPropSelected = false;
	tep.rowH = 18;
	tep.headH = 115;
	tep.propHeadH = 55;
	tep.mess='';
	tep.defDat = 'дд-мм-гггг';
	tep.entity = entity;
	tep.handler = handler;
	tep.shortView = shortview;
	tep.path = '?'+handler+'='+entity;
	if(shortview)
		tep.path+= "&shortview="+shortview;
	tep.propLoadTimer;
	tep.propWarnTimer;
	tep.warnTimer;
	tep.reqWaitTimer;
	tep.allowSelectFirst = true;
	tep.selectFirst = false;
	tep.selectLast = false;
	tep.tbl=d.getElementById('maintbl');
	tep.propTbl=d.getElementById('proptbl');
	tep.bottomElse='';
	tep.bottomPropElse='';
	tep.afterDraw = null;
	tep.afterPropDraw = null;
	tep.requestBlock = 0;
	tep.propLoadDelay = 300;
	tep.instant = false;
	tep.lastSelectedRowNams = [];
	tep.lastSelectedRowNam = '';
	tep.cacheAble = true;
	tep.currentPropFilters = {};
	tep.inited = false;
	tep.updateAfterPackSave = false;
	tep.pageDiff = 0;
	tep.nowLoading = false;
	tep.updateOnSave = false;
	tep.fltIndicator = '(Фильтр)';
	tep.propRequest = false;
	tep.reqParams = {};
	tep.prevReqParams = {};
	tep.extraACParams = {};
	tep.kicker = false;
	tep.disInpToStr = true;
	
	tep.delTitle = 'Удалить';
	
	// jQuery объект содержащий ссылку на дерево
	tep.treeSelector = false;
	// элемент, при нажатии на который открывается дерево, служит для обратной связи текущего дерева
	tep.treeCaller = false;
	// содержит id конечного элемента выборки части дерева
	tep.loadDownToId = false;
	
	// индексы ячеек с типом даты
	tep.datCellIndex = []; tep.propDatCellIndex = [];
	tep.hidePropCommonButtons = false;
	tep.hideCommonButtons = false;
	
	tep.warnid = 'warnid';
	
	tep.customCellDraw = {};
	tep.customGlobOps={};

	var arr = tep.tbl.getElementsByTagName('TBODY');
	tep.contain = arr[1];
	arr = arr[0].getElementsByTagName('TR');

//	tep.filterRow = arr[0];
	tep.sortRow = arr[0];
	tep.insertRow = arr[1];
	tep.filters = [];
	tep.sort = [];
	tep.curPage = 1;
	tep.totalPages = 1;
	tep.onPage = 50;
	
	tep.propQueue = false;

	if(tep.propTbl){
		arr = tep.propTbl.getElementsByTagName('TBODY');
		tep.propContain = arr[1];
		arr = arr[0].getElementsByTagName('TR');
		tep.propSortRow = arr[0];
		tep.propInsertRow = arr[1];
	}
	arr=null;

	tep.nowUpdating = null; // строка которую обновляем
	tep.nowDeleting = null; // строка которую удаляем
	tep.nowSelected = null; // строка которая выбрана (верхняя таблица)
	tep.unsavedRows = {};	// массив несохраненных свойств
	tep.unsavedPropRows = {};	// массив несохраненных свойств

	//var wndH = $('document').height();//
//	var wndH = d.documentElement.clientHeight;
	var wndH = window.innerHeight;
	
	// установка размеров верхней и нижней таблиц
	if (!tep.propContain) {
		tep.onPage = Math.round((wndH-tep.headH)/tep.rowH);
	} else {
		ratio = (ratio) ? ratio : 5/10;
	  if (wndH<450)
	    	var upH = '250';
		else
			var upH = Math.round(wndH*ratio);//"75%";//
	    
	  $('#entcontainer').css('max-height', upH);//height(upH);
		tep.onPage = Math.round((wndH*ratio-tep.headH)/tep.rowH); // вычисление максимального колл-ва строк на странице
		$('#entsubcontainer').height('100%');//css('height', '100%');
		$('#propcontainer').height(Math.round(wndH*(1-ratio)));
		$('#proptbldiv').width($('#proptbl').width());

		tep.curPropPage = 1;
		tep.totalPropPages = 1;
		tep.onPropPage = 8;
	}
}
//// конструктор 
function ttt(){} ttt.prototype = {
	
//очистка особых фильтров
clearStaticFilters : function(evt){
	tep.staticFilter=false;
	$("[class*='staticfilter']").remove();
},

//при изменении в автокомплите нужно очищать value следующего за ним hidden input
onautocompletechangekey : function(inp, newval, oldval){
	$(inp).parent().find("input[type='hidden']").val("");
},

//для поля sdatt, можно в зависимости от условий подставлять разное время
calendarDateTime : function(inp, dt){
	return ' 00:00:00';
},

//pb при выборе результата из ac
onAcResult : function(evt, fieldid, val, text){
  if(typeof engine.onAcResult == "function")
    engine.onAcResult(evt, $(fieldid).attr('id'), val, text);
},

initCashKicker : function(timer){
	if(typeof timeKicker == 'undefined')
		return;
	tep.kicker = new timeKicker({
		timeout: (parseInt(timer) ? timer : 300) * 1000
		,onExit: function(){
			$('#contain, #propcontain').empty();
			tep.appendInfoRows(tep.contain, 0, 0, 1);
		}
		,onContinue: function(){
			tep.unsavedRows = {};
			tep.unsavedPropRows = {};
			engine.update();
		}
		
	}, timer);
},

hideProp : function(){
	if(tep.propContain){
		$(tep.propContain).empty();
		tep.makeDefCells(true);
	}
	$('#otherinfo,#minfo').empty();
},

showProp : function(){
	$('#propcontainer').css('display', 'block');
},

////////////////////////
////инициализатор
init : function (){
	engine.hideProp();
	if(tep.shortView!='') {
	d.title = d.title+' (сокр.)';
	tep.selectFirst = true;
	}
	
	// ctrl+enter - добавить + fix для Opera
	if($.browser.opera && $.browser.version < 12.10){
		$(tep.insertRow).keypress(function(evt){
			engine.enterListen(evt,0);
		});
		if(tep.propInsertRow)
			$(tep.propInsertRow).keypress(function(evt){
				engine.enterListen(evt,1);
			});
	} else {
		$(tep.insertRow).keydown(function(evt){
			engine.enterListen(evt,0);
		});
		if(tep.propInsertRow)
			$(tep.propInsertRow).keydown(function(evt){
				engine.enterListen(evt,1);
			});
	}
	
	$('#tablescontainer').height(d.documentElement.clientHeight);
	
	if(!$('th.inscell, th.addcell', tep.insertRow).length) 
	$('input:button', tep.insertRow).attr('disabled', 'disabled').val('');
	
	var sets = tabloidSets;
	tep.valNams = sets['valNams'];
	tep.propValNams = sets['propValNams'];
	tep.rowSets = sets['rowSets'];
	tep.propRowSets = sets['propRowSets'];
	tep.leadVal = sets['leadval'];
	tep.propLeadVal = sets['propleadval'];
	tep.entLeadProp = sets['entleadprop'];
	tep.imgpath = sets['imgpath'];
	
	tep.emptyInsert = sets['emptyInsert'];
	tep.emptyContain = sets['emptyContain'];
	tep.descrLen = (sets['descrLen']) ? (sets['descrLen']) : ([]);
	tep.propDescrLen = (sets['propDescrLen']) ? (sets['propDescrLen']) : ([]);
	
	tep.params = sets['translate'];
	tep.sort = sets['sorts'];
	tep.history = sets['history'];
	tep.cashTimer = sets['cashtimer'];
	
	tep.staticFilter = sets['staticFilter'];
	
	engine.drawSort();
	
	var datTyps = ['dat', 'datt', 'sdat', 'sdatt'];
	for(var i in tep.rowSets)
	if(in_array(tep.rowSets[i], datTyps))
		tep.datCellIndex.push(i);
	
	for(var i in tep.propRowSets)
	if(in_array(tep.propRowSets[i], datTyps))
		tep.propDatCellIndex.push(i);
	
	tep.initCashKicker(tep.cashTimer);
	
	engine.entUpdate();
	
	$('input:checkbox', tep.insertRow).click(threeState);
	
	$(d.body).css({overflow: 'hidden'});
	
	$(tep.contain).add(tep.propContain).keyup(engine.checkKeyUp).bind('click', function(e){
		if(tep.nowLoading)
			return;
		var row = tep.riseTo('tr', e.target);
		if(engine.changed(row))
			engine.showButtons(row);
	}).keypress(function(e){
	if(e.keyCode==27)
		return engine.cancel(e.target);
	});
	
	tep.main = new rowSelector(tep.contain, {
		isSelected:		true
		,nextPage:		engine.selectNextPage
		,prevPage:		engine.selectPrevPage
		,onSelect:		engine.propUpdate
		,unSelect:		engine.propUnselect
		,beforeSelect:	engine.selectAble 
	});
	
	// если редактор двухтабличный, ставим обработчик на вторую таблицу 
	if(tep.propContain){
		tep.prop = new rowSelector(tep.propContain, {
			onSelect:	function(row){engine.markPropRow(row);}
			,unSelect:	function(){tep.dropTotalSelection(1);}
		});
	}
	
	tep.inited = true;
	
	if(typeof engine.afterInit=='function')
		engine.afterInit();
},
////инициализатор
////////////////

title : function(cellindex, text, isprop){
	var row = isprop ? tep.propSortRow : tep.sortRow;
	var tnode = $(row.cells[cellindex].firstChild).contents().filter(function(){return this.nodeType == 3;})[0];
	var answ = tnode.data;
	if(text !== undefined)
		tnode.data = text;
	return answ;
},

checkKeyUp : function(e){
	if(tep.nowLoading)
		return;

	var el = (e.target) ? e.target : e.srcElement,
		isTextArea= (el && el.tagName == "TEXTAREA") ? 1 : 0;

	if(e.keyCode==13 && (isTextArea == 0 || (e.ctrlKey && isTextArea)))
		return engine.trySave(e.target);
	if(e.keyCode==27)
		return false;
	var row = tep.riseTo('tr', e.target);

	if($(row).hasClass('body_color'))
		return;

	if(engine.changed(row))
		engine.showButtons(row);

	if(row!=tep.nowSelected && !tep.isProp(row)){
		engine.cancel(row,true);
		$('input:visible:first', tep.nowSelected).focus();
		return;
	}
},

sendFile : function(el){
	return false;
},

/**
 * получает на входе предыдущую строку, новую, флаг выделения/снятия, event
 * если выделить/снять можно, возвращает true и строка выделяется/снимается 
 */
selectAble : function(was, to, unselecting, e){
	if(tep.nowSelected && (to!=tep.nowSelected || unselecting))
		if(array_sum(tep.unsavedPropRows)){
			engine.cancel(to,true);
			$('input:visible:first', was).focus().blur();

			dialog('Несохраненные изменения', 'В наполнении остались несохраненными изменения. Применить?', {
				'Применить': function(){
					dlg.dialog('close');
					engine.saveAll(tep.propContain);
					tep.selectAfter = to;
				}
				,'Не применять':	function(){
					dlg.dialog('close');
					tep.unsavedPropRows = {};
					engine.cancelAll(tep.propContain);
					$(to).click();
				}
				//,'Отмена':	
			});
			return false; 
		}
	tep.nowSelected = unselecting ? null : to;
	
	//	если текущая строка уже выделена, не подгружаем ее повторно
	if(tep.nowSelected == was && $(was).hasClass('selected_row'))
		return false;
	
	// вывод информации о строке выделенной на данный момент (номер строки)
	if(tep.nowSelected && was!=tep.nowSelected){
		var rowNum = tep.nowSelected.rowIndex-1+((tep.curPage-1)*tep.onPage);
		$('#currow').html("запись "+rowNum+ " из ");
	}

	
	// при снятии выделения прячем свойства предыдущей записи
	if(unselecting) 
		engine.hideProp();

	tep.warn('');
	tep.dropTotalSelection();
	return true;
},

////////////////////////
// перезагрузка таблицы с установкой предыдущей страницы
selectPrevPage : function(){
	if(tep.curPage==1)
		tep.warn('Это первая страница');
	else {
		tep.pageDiff = -1;
		tep.selectLast = true;
		tep.update();
	}
},

// перезагрузка таблицы с установкой следующей страницы
selectNextPage : function(a){
	if(tep.curPage==tep.totalPages) 
		tep.warn('Это последняя страница');
	else {
		tep.pageDiff = 1;
		tep.selectFirst = true;
		tep.update();
	}
},

////////////////////////
//// фильтры

// применение введенных фильтров и перезагрузка значений
applyFilters : function(obj, mess, color, afterProp){
	if(tep.staticFilter)
		return tep.update(obj);
		//return tep.warn('Установлены особые фильтры. Сделайте общий сброс фильтров');
	var isprop = tep.isProp(obj);
	var row = isprop ? tep.propInsertRow : tep.insertRow;
	
	$('.ac_input' ,row).each(function(){
		if(this.value=='') $('input:hidden', this.parentNode).val('');
	});
	
	var vals = getvals($('th.fltcell, th.inscell', row));
	var errors = tep.hasErrors(vals,'filter');
	if (errors)
		return tep.blink(errors);
	tep.allSelected = false;
	tep.allPropSelected = false;

	if(isprop){
		tep.currentPropFilters = vals;
		tep.filtersToTitle();
		return tep.applyFilters(tep.insertRow, mess, color, 1);
	}else if(!afterProp){
		engine.clearPropFliters();
	}
	
	tep.showPropFilters();
	engine.saveFilters(vals, mess, color);
	return vals;
},

// запрос на сохранение фильров в базе
saveFilters : function(flt, mess, color){
	tep.curPage = 1;
	var params = engine.conds();
	if(params === false)
		return false;
	params.action = 'savefilters';
	params.filters = {};
	for(var nam in flt){
		if(flt[nam] || (flt[nam]===0)){
//			if(nam.indexOf('_text') == -1)
				params.filters[nam] = tep.isDat(nam) ? reverseDat(flt[nam], true) : flt[nam];
		}
	}
	
	// не преобразует даты в нижней таблице
	if(params.propfilters)
		for(var nam in params.propfilters){
			if(params.propfilters[nam] && tep.isDat(nam)){
				params.propfilters[nam] = reverseDat(params.propfilters[nam], true);
			}
		}

	tep.mess = mess;
	tep.messColor = color;
	
	tep.lastSelectedRowNams = tep.main.getNams();
	tep.lastSelectedRowNam = (tep.nowSelected) ? tep.nowSelected.name : null;

//	tep.prevReqParams = clone(tep.reqParams);
	tep.reqParams = clone(params);
	postJSON(tep.path, params, engine.drawRows, true);
},

showPropFilters : function(){
	//$(input)tep.currentPropFilters
},

filtersToTitle : function(clear){
	$scells = $('th', tep.propSortRow);
	
	var i=0;
	if(tep.propInsertRow)
		$('th', tep.propInsertRow).each(function(){
			var arr = tep.cellparams(this);
			var val = arr.vis;
			var title = 'Сортировать по этому полю';
			$cell = $($scells.get(i++));
			if(val && !clear) {
				title+= ' (фильтр: "'+val+'")';
				$cell.addClass('fltcell');
			}else
				$cell.removeClass('fltcell');
			$cell.attr('title', title);
		});
},

clearPropFliters : function(){
	tep.currentPropFilters = {};
	tep.filtersToTitle(1);
},

// формирования объекта с параметрами выборки учитывая контекст таблицы
conds : function(isprop){
	var answ = {};
	if(isprop){
		answ.sortfield = tep.sort['sortpropfield'];
		answ.sortdir = tep.sort['sortpropdir'];
		answ.page = tep.curPropPage;
		answ.onpage = tep.onPropPage;
		if(tep.nowSelected){
			answ.owner = tep.nowSelected.name;
			answ.nowselected= tep.main.getNams('name');
		}
	}else {
		if(tep.staticFilter){
			answ.staticFilter = tep.staticFilter;
		}else if(tep.propInsertRow){
			var byentry = [];
			$('input[name]', tep.propInsertRow).each(function(){
				var nam = $(this).attr('name');
				if(!$(this).val()) return;
				if (
					nam.slice(-2)!='id' 
					&& nam.slice(-3)!='num' 
					&& nam.slice(-3)!='ind' 
					&& nam.indexOf('cnt')==-1
				) byentry.push(nam);
			});
			if(byentry.length)
				answ.contextsearch = byentry;
			
			answ.propfilters = tep.currentPropFilters;
		}

		tep.curPage+= tep.pageDiff;
		answ.page = tep.curPage;
		answ.onpage = tep.onPage;
	}
	return answ;
},

// очистка строки ввода учитывая в пределах какой таблицы вызов
clearInserts : function(e){
	engine.clearPropFliters();
	el = e ? e.target : tep.insertRow;
	var row = $('tr.insert_row', tep.riseTo('TABLE', el));

	$('input:not(:button),select,textarea', row).val('').change();
	$('input:checkbox', row).removeAttr('checked').addClass('thirdState');
	tep.staticFilter = false;
},
//// фильтры
////////////////////////


////////////////////////
//// общие функции
enterListen : function(evt, prop){
	if(evt.keyCode!=13)
		return true;
	if(evt.ctrlKey){
		engine.tryAdd(evt);
		return preventEvent(evt);
	}
	if(!prop){
		var el = evt.target;
		if($(el).is('button') || $(el).is('textarea'))
			return false;
		setTimeout(function(){engine.applyFilters(el);}, 10);
		if($.Calendar)
			$.Calendar.hideGrid();
	}
},

// установка индикации выполнения запроса (курсор "часики") 
reqBlock : function(){
	if(tep.requestBlock<0)
		tep.requestBlock=0;
	tep.requestBlock++;
	$(d.body).css('cursor', 'wait');
},

// снятие индикации выполнения запроса
reqUnBlock : function(instant){
	tep.requestBlock--;
	if(tep.requestBlock<0 || instant)
		tep.requestBlock=0;
	$(d.body).css('cursor', 'default');
},

// выбирается ближайший старший к el DOM элемент с тегом tagNam, не имеющий класс 'skip', возвращает null, если не найден
riseTo : function(tagNam, el){
	if (!el || el==null) 
		return null;
	tagNam = (''+tagNam).toUpperCase();
	
	while (el.tagName!=tagNam || $(el).hasClass('skip')) 
		if (el.tagName=="HTML") 
			return null; 
		else{
			el=el.parentNode;
			if(el==null)
				return null;
		}
	return el;
},

// по событию - клику по ссылке на страницу, загружает страницу с выбраным номером
changePage : function(e){
	var el = e.target;
	var isprop = tep.isProp(el);
	
	if (el.tagName=="A"){
		var page = parseInt(el.innerHTML);
		if(isNaN(page))
			return;
		
		if (isprop)
			tep.curPropPage = page;
		else
			tep.curPage = page;
		
		return tep.update(el);
	}

	
	if (e.keyCode!=13)// || (el.id!='currpage' && el.id!='propcurrpage') ) 
		return false;//$(el).focus();
	
	var page = parseInt($(el).val());
    if (isNaN(page)) {
    	el.value = (tbl==tep.propContain) ? tep.curPropPage : tep.curPage;
    	return tep.warn("Неверно указан номер страницы");
    }

	var tbl = tep.riseTo('TBODY', el);
	if (tbl==tep.propContain){
	    if (page>tep.totalPropPages)
	    	tep.curPropPage=tep.totalPropPages;
	    else
	    	tep.curPropPage = (page<1) ? 1 : page;
	}else {
	    if (page>tep.totalPages)
	    	tep.curPage=tep.totalPages;
	    else
	    	tep.curPage = (page<1) ? 1 : page;
	}
	tep.update(tbl);
},

smartWarn : function(mess, color){
	if(!smartWarning(mess, color)){
		tep.warn(mess, '', color);
	}
},

// вывод сообщений. Принимает на входе сообщение, контекст таблицы, цвет сообщения, длительность отображения
warn : function (mess, tblobj, color, time) {
	if(!time || time>0){
		time = time ? (time*1000) : 4000;
		time+= mess.length*100;
	}
	
	var isprop = tep.isProp(tblobj);
	var lbl = $('#'+(isprop ? 'prop' : '')+tep.warnid);
	if(!lbl.length) 
		return alert(mess);
	
	clearTimeout(isprop ? tep.propWarnTimer : tep.warnTimer);

    if (mess!='') {
    	lbl.html(mess)[color=='green' ? 'removeClass' : 'addClass']('error');
    	if(time>0){
			if(isprop) 
				tep.propWarnTimer = setTimeout("engine.warn('',engine.propContain)", time);
			else 
				tep.warnTimer = setTimeout("engine.warn('',engine.contain)", time);
    	}
	}else 
		lbl.empty();
},

// проверка на валидность значений
hasErrors : function (vals, context) {
	var errs = [];
	var errFields = [];
	var err = '';
	var lastNam = '';
	
	for(var nam in vals){
		if(err){
			if(!in_array(err, errs)) 
				errs.push(err);
			errFields.push(lastNam);
		}
		err = '';

		if (!nam) 
			continue;
		lastNam = nam;

		if (!vals[nam])
			if (context=='filter') 
				continue;
			else if(context=='add' && (in_array(nam, tep.emptyInsert) || nam.split('_').pop() == 'text' ) )
				continue;				
			else if(context=='save' && (in_array(nam, tep.emptyContain) || nam.indexOf('old') == 0))
				continue;
			else {
				err = "Не все поля заполнены";
			}

		if(err)	
			continue;
		
		if (nam.indexOf('price')!=-1){
			if(!/^\d+.?\d+$/.test(vals[nam])) 
				err = vals[nam]+" - неправильный формат для нецелого числа";
		}else if(tep.isDat(nam)){
			if (!likeDat(vals[nam]) && !likeDatt(vals[nam]))
				err = vals[nam]+" - неверный формат даты";
		}
	}
	
	if(err){
		if(!in_array(err, errs))
			errs.push(err);
		errFields.push(lastNam);
	}
	if(!errFields.length && !errs.length) 
		return false;
	else 
		return {fields: errFields, errors: errs.join(', ')};
},

blink : function(errs, row){
	if(!row) 
		row = tep.insertRow;
	tep.warn(errs.errors);
	
	for(var i=0; i<errs.fields.length; i++){
		errs.fields[i] = '[name="'+errs.fields[i]+'"],[name="'+errs.fields[i]+'_text"]';
	}

	errs.fields = errs.fields.join(',');

	if(errs.fields) 
		blink($(errs.fields, row));
},

// преобразование даты и нецелых чисел в условленый формат
preapare : function (vals) {
	for(var nam in vals) 
		if (tep.isDat(nam))
			vals[nam] = reverseDat(vals[nam], true);
		else if(nam.slice(-2)=='sm' || nam.indexOf('price')>-1)
			vals[nam] = vals[nam].replace(',','.');
	return vals;
},
//// общие функции
////////////////////////

isDat : function(nam){
	if(nam.indexOf('dat')==-1)
		return false;
	return (nam.length-nam.lastIndexOf('dat')==3 || nam.length-nam.lastIndexOf('datt')==4);
},

////////////////////////
//// сортировки

// очистка ячейки ввода
clearInsertCell : function(cell){
	var cellIndex = cell.cellIndex;
	var insCell = $('.insert_row', tep.riseTo('TBODY', cell)).get(0).cells[cellIndex];
	$('input,select,textarea', insCell).val('');
	$('input:checkbox', insCell).removeAttr('checked').addClass('thirdState');
	tep.instant = true;
	$('select', insCell).change();
	return void(0);
},


// обработчик изменения сортировок. сохраняет сортировки в куках, перезагружает данные с учетом сортировок
sortClick : function(e){
	var el = (e.target) ? e.target : e.srcElement;
	if(el.tagName=='INPUT') 
		return;
	if(!(cell = tep.riseTo("TH",el))) 
		return;

	if(el.tagName=='A'){
//		if(e.altKey)
//			return $(cell).parent().filter('th').not(cell).each(function(){tep.clearInsertCell(this);});
		return tep.clearInsertCell(cell);
	}

	var img = cell.querySelectorAll("img[class*='img_sort']")[0];
	//var img = cell.getElementsByTagName("IMG")[0];
	if (!img){
		img = cell.getElementsByTagName("IMG")[0];
		if (!img){
			return;
		}
	}

	var row = tep.riseTo("TR",cell);
	tep.curPage = 1;

	if (row==tep.propSortRow){
		var tbl = tep.propContain;
		if (img.id==tep.sort['sortpropfield'])
			if (tep.sort['sortpropdir']=="up") 
				tep.setSort('sortpropdir','down', tbl);
			else 
				tep.setSort('sortpropdir','up', tbl);
		else 
			tep.setSort('sortpropfield',img.id, tbl);
	}else{
		var tbl = tep.contain;
		if (img.id==tep.sort['sortentfield'])
			if (tep.sort['sortentdir']=="up") 
				tep.setSort('sortentdir','down', tbl);
			else 
				tep.setSort('sortentdir','up', tbl);
		else
			tep.setSort('sortentfield',img.id, tbl);
	}
},

setSort : function(fld, val, el, callback){
	syncJSON(tep.path, {action: 'savesort', fld: tep.entity+fld, val: val}, function(answ){
		if(answ.error) 
			return tep.warn(answ.error);
		tep.sort[fld] = val;
		if(typeof callback == 'function'){
		  callback(fld, val, el);
		} else {
      tep.update(el);
      tep.drawSort();		  
		}
	});
},

// отображение текущих сортировок
drawSort : function(){
	var imgarr = tep.sortRow.querySelectorAll("img[class*='img_sort']");
	for(var i=0; i<imgarr.length; i++)
		if (imgarr[i].id == tep.sort["sortentfield"]) {
			imgarr[i].style.visibility="visible";
			if (tep.sort["sortentdir"]=="up") 
				imgarr[i].src= tep.imgpath + "up.jpg";
			else 
				imgarr[i].src= tep.imgpath + "down.jpg";
		}else 
			imgarr[i].style.visibility="hidden";
	if(!tep.propContain) 
		return;
	imgarr = tep.propSortRow.querySelectorAll("img[class*='img_sort']");
	for(var i=0; i<imgarr.length; i++)
		if (imgarr[i].id == tep.sort["sortpropfield"]) {
			imgarr[i].style.visibility="visible";
			if (tep.sort["sortpropdir"]=="up") 
				imgarr[i].src= tep.imgpath + "up.jpg";
			else 
				imgarr[i].src= tep.imgpath + "down.jpg";
		}else 
			imgarr[i].style.visibility="hidden";
},

//// сортировки
////////////////////////





////////////////////////
//// добавление
tryAdd : function(e){
	var el = (e.target) ? e.target : e.srcElement;
	var row = tep.riseTo("TR",el);
	var tbl = tep.riseTo("TABLE", row);
	var vals = getvals($('th.inscell, th.addcell', row), true);




	var errors = tep.hasErrors(vals,'add');
	console.log(vals, errors);
	if (errors)
		return tep.blink(errors);

	tep.addVals = vals = tep.preapare(vals);

	return (tbl==tep.propTbl) ? engine.addProp(vals) : engine.add(vals);
},

add : function(vals){
	vals.action = 'add';
	postJSON(tep.path, vals, engine.addAnswer, true);
//	syncJSON(tep.path, vals, engine.addAnswer, true);
},

addAnswer : function(answ){
//	setTimeout(function(){
		var err= false;
		if(answ.blink)
			blink("#"+answ.blink);
		
		if(tep.kicker && answ.timer && !tep.kicker.answIsGood(answ))
			return;
		
		if (answ.error)
			err = answ.error;
		else if (!answ.code)
			err = 'Неизвестный ответ сервера';
		if(err)
			return engine.smartWarn(err);
		
		tep.addedid = answ.addedid ? answ.addedid : false;
		if (answ.code>0) 
			engine.rowAdded(answ);
		else if (answ.code=='0') 
			engine.rowNotAdded();
		else if (answ.code<0) 
			engine.rowAddError(answ);
		tep.addVals = null;
//	}, 50);
},

addProp : function(vals){
	if (tep.nowSelected) 
		vals.owner = tep.nowSelected.name;
	vals.action = 'addprop';
	postJSON(tep.path, vals, engine.addPropAnsw, true);
},

addPropAnsw : function (answ){
	if (answ.error) 
		return tep.smartWarn(answ.error);
	if(!answ.code)
		return tep.warn('Неизвестный ответ сервера.', engine.propContain);
		
	var code = parseInt(answ.code);
			
	if (code>0)
		engine.propRowAdded(answ);
	else if (code==0)
		engine.propRowNotAdded(answ);
	else if (code<0)
		engine.propRowAddError(answ);
},

rowAddError : function(answ)	{ 
	tep.defRowAddError(answ,tep.contain);
},
rowNotAdded : function()		{ 
	tep.defRowNotAdded(tep.contain);
},
rowAdded : function(answ)		{
	dlg.dialog('close');
	var msg = 'Запись добавлена';
	if(answ.info)
		msg+= '. '+answ.info;
	tep.applyFilters(tep.contain, msg, 'green');
},

propRowAddError : function(answ)	{ 
	tep.defRowAddError(answ,tep.propContain);
},
propRowNotAdded : function(answ)	{ 
	tep.defRowNotAdded(answ, tep.propContain);
},
propRowAdded : function(answ)   	{ 
	tep.defRowAdded(answ,tep.propContain);
},

defRowAddError : function(answ, tbl)	{ 
	tep.smartWarn( answ.error ? answ.error : 'Неизвестный ответ сервера.');
	if (answ.status)
		engine.statusUpdate(answ.status);
},

defRowNotAdded : function(answ, tbl)	{ 
	tep.smartWarn('Не удалось добавить запись. Возможно повторяются ключевые поля');
	if (answ.status)
		engine.statusUpdate(answ.status);
},

defRowAdded : function(answ, tbl)   	{
	dlg.dialog('close');
	var info = answ.info ? answ.info : 'Запись добавлена';
	var warn = (answ.warn) ? (' <span class="error">'+answ.warn+'</span>') : '';
	info+=warn;
	
	tep.update(tbl, info, 'green');
	if (answ.status)
		engine.statusUpdate(answ.status);
},
//// добавление
////////////////////////


////////////////////////
//// сохранение/применение
trySave : function(el) {
	var row = tep.riseTo("TR", el);
	
	// если нет никаких изменений - выход
	if (!engine.changed(row)) 
		return engine.cancel(row, true);

	var vals = getvals(row, true);

	var errors = tep.hasErrors(vals,'save');
	if (errors) 
		return tep.blink(errors, row);
	vals = tep.preapare(vals);

	if (row.name && row.name!='0') 
		vals.id=row.name;
	if (tep.isProp(row)) 
		if (tep.nowSelected) 
			vals.owner = tep.nowSelected.name;
		else 
			return;
	tep.nowUpdating = row;
	
	tep.lastUpdateVals = vals;
	
	if(tep.isProp(row)) 
		engine.saveProp(vals);	
	else 
		engine.save(vals);
},

save : function(vals) {
	vals.action = 'save';
	postJSON(tep.path, vals, engine.saveAnswer, true);
},

saveProp : function(vals) {
	vals.action = 'saveprop';
	postJSON(tep.path, vals, engine.savePropAnswer, true);
},

redrawRow : function(row, rowobj){
	if(!rowobj)
		rowobj= tep.nowUpdating;
	if(!rowobj)
		rowobj= tep.nowSelected;
	if(!rowobj)
		return;
	
	tep.currentDescrLen = tep.descrLen;
	tep.descrlencounter = 0;
	$(rowobj).html(tep.formRow(row, tep.rowSets, tep.leadVal, tep.valNams, 0, 1));

	tep.main.setSelectedTable({target: rowobj});
	
	var datIndexes = tep.isProp(rowobj) ? tep.propDatCellIndex : tep.datCellIndex;
	if(datIndexes.length){
		var set = [];
		var $tds = $(rowobj).children('td');
		for(var i in datIndexes){
			var $inp = $('input:visible', $tds[datIndexes[i]]);
			if($inp.length)
				set.push($inp.get(0));
		}
		if($().calendar)
			$(set).calendar({onchoose: function(el){$(el).keyup();}});
	}
	$(rowobj).attr('name', row[0]);
},

saveAnswer : function(answ)	{	
	engine.defSaveAnswer(answ);
},
savePropAnswer : function(answ){	
	engine.defSaveAnswer(answ, true);
},

defSaveAnswer : function(answ, isprop){
	var row = tep.nowUpdating;
	var isprop = tep.isProp(row);
	answ.code = parseInt(answ.code);
	if($.Calendar)
		$.Calendar.hideGrid();
	
	if(tep.kicker && answ.timer && !tep.kicker.answIsGood(answ))
		return;
	
	if(answ.discard){
		engine.cancel(tep.nowUpdating, true);
		tep.nowUpdating = null;
		return tep.smartWarn(answ.discard);
	}
	
	if(answ.blink){
		var kk= $(row).find("[name='"+answ.blink+"']");
		if(kk.length > 0)
			blink(kk);
	}

	var err = false;
	if (answ.error) 
		err = answ.error;
	else if(!answ.code) 
		err = 'Не удалось применить изменения. Возможно повторяются ключевые поля';
	
	if(err)
		return tep.smartWarn(err);

	dlg.dialog('close');

	(isprop ? tep.unsavedPropRows : tep.unsavedRows)[row.rowIndex] = 0;
	var warn = (answ.warn) ? (' <span class="error">'+answ.warn+'</span>') : '';
	var mess = 'Изменения сохранены. '+warn;
	var color = 'green';
	
	if(answ.row){
		tep.redrawRow(answ.row, row);
		tep.warn(mess, null, color);
	} else if(tep.updateOnSave || answ.update){
		isprop ? tep.propUpdate(row, mess, color) : tep.update(row, mess, color);
	}else if (answ.status) 
		engine.statusUpdate(answ.status);
	else{ 
		tep.warn(mess, null, color);
		tep.setUpdated(row);
	}

	tep.hideButtons(row);
	
	tep.nowUpdating = null;
	tep.lastUpdateVals = null;
	
},

setUpdated : function(row)   	{
	var vals = getvals(row);
	$('input[name^=old]', row).each(function(){
		var nam = $(this).attr('name').substr(3);
		if(typeof(vals[nam])!='undefined')
			$(this).val(vals[nam]);
	});
	tep.hideButtons(row);
},

saveAll : function(el, selectEl){
	var isProp = tep.isProp(el);
	var tbl = isProp ? tep.propContain : tep.contain;
	
	if ( isProp && !tep.nowSelected ) 
		return tep.warn('Не выбрана основная запись.');
		
	var data= {action:'saveall'};
	if( !(isProp && engine.changed(tep.nowSelected)) )
		data.complete=1;
	tep.savePackRows = [];
	$('tr:not(.body_color)', tbl).each(function(){
		var row = $(this).get(0);
		if (engine.changed(row)){
			var vals = getvals(row, true);
			if (row.name) 
				vals.id=row.name;

			errors = tep.hasErrors(vals,'save');
			if (errors) 
				return tep.blink(errors, row);
			vals = tep.preapare(vals);
			
			for(var nam in vals){
				if(!data[nam])
					data[nam] = [];
				data[nam].push(vals[nam]);
			}
			
//			if(!isProp){
//				if(data.id==undefined || data.id=='')
//					data.id = [];
//				data.id.push(row.name);
//			}
			tep.savePackRows.push(row);
		}
	});
	if(isProp) 
		data.owner=tep.nowSelected.name;
		
	postJSON(tep.path, data, function (answ){
		if (answ.error) 
			tep.warn(answ.error);
		else 
			if(!answ.code || !parseInt(answ.code))
				tep.warn('Не удалось применить изменения. Возможно повторяются ключевые поля');
			else {
				if(answ.code==tep.savePackRows.length){
					var mess = 'Записи обновлены';
					$(tep.savePackRows).each(function(){
						tep.setUpdated($(this).get(0), true);
					});
					if(engine.updateAfterPackSave) 
						engine.update(tbl, mess, 'green', selectEl);
					else{
						tep.warn(mess, null, 'green');
						$(selectEl).click();
					}
				}else 
					tep.update(tbl, 'Не все записи были обновлены');
			}
		if (answ.status)
			engine.statusUpdate(answ.status);
	}, true);
},
//// сохранение/применение
////////////////////////



statusUpdate : function(status){
	return;
},



editStart : function(){
	return false;
},
////////////////////////
//// отслеживание изменений
showButtons : function(row){
	var mess = '';
	
	if(!$(row).attr('nowchanging')) 
		if(mess = engine.editStart(row)){ 
			engine.cancel(row, true);
			return tep.warn(mess);
		}
    var tbl = tep.riseTo("TBODY",row);
	$("input:button", row).css("display", "inline");
	(tep.isProp(tbl) ? tep.unsavedPropRows : tep.unsavedRows)[row.rowIndex] = 1;
	$('tr.body_color input[name=saveall],[name=cancelall]', tbl).css("display", "inline");
	$(row).attr('nowchanging', 1);
},

hideButtons : function(row){
	if(!row) 
		return;

	var tbl = this.riseTo("TBODY", row);
	$('[name=save],[name=cancel]',row).css('display', 'none');
	$(row).removeAttr('nowchanging');
	(tep.isProp(tbl) ? tep.unsavedPropRows : tep.unsavedRows)[row.rowIndex] = 0;
	
	// проверка, есть ли несохраненные строки
	if($('td.buttonscell input[name=save]:visible,[name=cancel]:visible', tbl).length==0)
		$('tr.body_color input[name=saveall],[name=cancelall]', tbl).css('display', 'none');
},

changed : function(row){
	if(!row)
		return false;
	var vals = getvals(row);
	for (var nam in vals)
		if( typeof(vals['old'+nam])!='undefined'  ) 
			if (vals['old'+nam] != vals[nam]) //*sk значения сравниваются как сторки  
				return true;
	return false;
},

markPropRow : function(row){
	if(engine.onpropSelect)
		engine.onpropSelect(row);
	
	$('#propcurrow').html('запись '+(row.rowIndex-1)+' из ');
	return true;
},

showHistory : function(el){
	var row = tep.riseTo('tr', el);
	var vals = getvals(row);
	var str = "?usrlog";
	for(var i = 0; i<tep.history.length; i++){
		var hstr = tep.history[i];
		if(hstr[0]=='rowid') 
			str+= '&setfilters[id]=' + (row.name);
		else 
			str+= '&setfilters['+hstr[0]+']='+ ( (hstr[2]==1) ? (hstr[1]) : (vals[hstr[1]]));
	}
	tep.wndhistory = window.open(str, '_blank', '');
},

highLightRow : function(row){
	var tbl = row.parentNode;
	$("tr", tbl).removeClass('selected_row');
	$(row).addClass('selected_row');
},

isProp : function(el){
	if(!el)
		return false;
	var table = tep.riseTo('TABLE', el);
	if(table) 
		if(table.id=='proptbl') 
			return true; 
	return false;
},


//// отслеживание изменений
////////////////////////



makeDefCells : function(isprop){
	var row = isprop ? tep.propInsertRow : tep.insertRow;

	$('th',row).each(function(){
		$cell = $(this);
		if($cell.hasClass('inscell'))
			$cell.attr('defclass', $cell.attr('class')).attr('class', 'fltcell');

		var id = $(this).attr('id');
		if(defCells[id]) {
			var arr = tep.cellparams('#'+id);
			$el = $('#'+id);
			$el.empty().html(defCells[id]);
			$('input:visible', $el).val(arr.vis);
			$('input:hidden, select', $el).val(arr.hid);
		}
	});
	
	$('td:last input:button', row).attr('disabled', 'disabled');
},

unmakeDefCells : function(isprop){
	var row = isprop ? tep.propInsertRow : tep.insertRow;
		

	$('th', row).each(function(){
		var $el = $(this);
		if($el.attr('defclass'))
			$el.attr('class', $el.attr('defclass')).removeAttr('defclass');
	});
	$(tep.propInsertRow).show();
	$('td:last input:button', row).removeAttr('disabled');
},


///////////////
// удаление
paramsToRemove : function(row){
 	var params = {};
	if(row.name) 
		if(row.name!=0) 
			params.id = row.name;
	else 
		params = tep.preapare(getvals(row));

	if(params.length==0) 
		return null;
	tep.nowDeleting = row;
	if (tep.isProp(row)){
		if (!tep.nowSelected)
			return null;
		params.owner = tep.nowSelected.name;
//*sk не перегружает на первую страницу при удалении наполнения (свойств)		
//		tep.curPropPage = 1;
	}
	return params;
},

tryRemove : function(el){
	var row = tep.riseTo('TR',el);
	dialog('Удаление записи.', 'Вы уверены что хотите удалить запись?', {
		"Удалить запись": function(){
			tep.removeParams = tep.paramsToRemove(row);
			if(tep.isProp(row))
				engine.removeProp(tep.removeParams);
			else 
				engine.remove(tep.removeParams);
		}
	});
},

removeProp : function(vals){
	vals.action = 'removeprop';
	postJSON(tep.path, vals, engine.removePropAnswer, true);
},

remove : function(vals){
	vals.action = 'remove';
	postJSON(tep.path, vals, engine.removeAnswer, true);
},

removeAnswer : function(answ){
	if(tep.defRemoveAnswer(answ, tep.nowDeleting) && tep.propContain) 
		engine.hideProp();
},

removePropAnswer : function(answ){
	tep.defRemoveAnswer(answ, tep.nowDeleting, true);
},

defRemoveAnswer : function(answ, row, isprop){
	dlg.dialog('close');

	tep.nowDeleting = null;

	if (answ.status) 
		engine.statusUpdate(answ.status);
	var code = (answ.code)?(answ.code):0;
	if (answ.error || code<0)
		var error = (answ.error)?(answ.error):( (code==-1)?'Ошибка данных':'Неизвестная ошибка' );
	else if(code==0) {
		engine.warn('Не удалось удалить запись'); 
		return false;
	}

	if (error)
		if(code<-1) 
			engine.update(row, error);
		else 
			engine.warn(error);
	else{
		var info = 'Запись удалена';
		if(answ.info)
			info+= '. '+answ.info;
		engine.update(row, info, 'green');
	}
	return true;
},

removeSelected : function(el, dontAsk){
	var isProp = tep.isProp(el);
	var tbl = isProp ? tep.propContain : tep.contain;
	var allSelected = isProp ? tep.allPropSelected : tep.allSelected;
	
	var rows = [];
	if(allSelected)
		rows = 'all';
	else{
		$((isProp ? tep.prop : tep.main).getSelectedRows()).each(function(){
			var rparams = engine.paramsToRemove(this);
			if(rparams)
				rows.push( rparams );
		});
		if(!rows.length) 
			return tep.warn('Не выбрано ни одной записи');
	}

	// необходимо для учёта фильтра по второй таблице (removeselected)
	tep.removeParams =tep.conds(isProp);
	tep.removeParams.action= 'removeselected';
	tep.removeParams.ids= rows;
	if(isProp)
		tep.removeParams.owner = tep.nowSelected.name;
	
	if(dontAsk)
		engine.removeSelectedNow();
	else{		
		var trows = isProp ? tep.totalPropRows : tep.totalRows;
		var rowsCnt = allSelected ? trows : rows.length;

		dialog('Удаление записей.', 'Вы действительно хотите удалить выбранные записи ('
			+ rowsCnt +' шт.)?', {"Удалить записи": engine.removeSelectedNow});
	}
},

removeSelectedNow : function(){
	postJSON(tep.path, tep.removeParams, engine.removeSelectedAnswer, true);
},

removeSelectedAnswer : function(answ){
	if (answ.error) 
		tep.warn(answ.error);
	else{
		var info = 'Удалено записей: '+answ.code;
		if(answ.info)
			info+= '. '+answ.info;
		engine.update(null, info, 'green');
	}
	dlg.dialog( 'close' );
},
// удаление
/////////////////////





//////////////////////
// загрузка значений
update : function(obj, mess, color){
	if(tep.nowLoading)
		return false;

	if (mess)
		tep.mess = mess;
	if (color)
		tep.messColor = color;

	if (!obj) 
		return tep.fullUpdate();
	while(obj.tagName!='TBODY') 
		if (obj.tagName=='BODY') 
			return tep.fullUpdate(); 
		else 
			obj=obj.parentNode;

	return (obj==tep.propContain) ? engine.propUpdate() : engine.entUpdate();
},

fullUpdate : function(mess, color) {
	if (mess) 
		tep.mess = mess;
	if (color)
		tep.messColor = color;
	return tep.entUpdate();
},

entUpdate : function(n){
	if(n===undefined)
		n = 10;
	if(!tep.main)
		return setTimeout(function(){engine.entUpdate(n-1);} , 100);
	tep.lastSelectedRowNams = tep.main.getNams();
	tep.lastSelectedRowNam = (tep.nowSelected) ? tep.nowSelected.name : null;

	if(tep.prop && array_sum(tep.unsavedRows)){
		dialog('Изменения не сохранены', 'Изменения не сохранены, Применить?', {
			'Применить': function(){dlg.dialog('close'); engine.saveAll();}
			,'Не применять': function(){dlg.dialog('close'); engine.cancelAll();}
		});
		return false;
		tep.pageDiff = 0;
	}
	
	tep.nowSelected = null;
//	engine.hideProp();
	tep.getRows();
	return true;
},

propUnselect : function(){
	if(!tep.propContain) 
		return false;
	
	if(tep.entLeadProp.length)
		for(var i=0; i<tep.entLeadProp.length; i++) {
			loadCell('cell'+tep.entLeadProp[i][1], false, false, false, engine.extraGetCellParams);
		}
	
	if(!tep.nowSelected){
		var ar= tep.main.getSelectedRows();
		if(ar)
			if(ar.length){
				tep.nowSelected= ar[0];
				tep.getRows(true);
			}
	}
	return true;
},


propUpdate : function(){
	tep.allPropSelected = false;
	if(!tep.propContain || !tep.nowSelected) 
		return false;

	tep.getRows(true);
	return true;
},
// загрузка значений
//////////////////////






//////////////////////
// отмена изменений
cancel : function(el, ignore){
	var row = tep.riseTo('tr', $(el).get(0));
	if (!row) 
		return;

	var vals=getvals(row);
	if(!vals) 
		vals=[];
	$('input, select, textarea', row).each(function(){
		var el = $(this).get(0);
		if (typeof(vals['old'+el.name])!='undefined')
			if(el.tagName=='SELECT') 
				el.value = vals['old'+el.name];
			else if (el.type=="checkbox") {
				if(vals['old'+el.name]==1)
					$(el).attr('checked', 'checked');
				else
					$(el).removeAttr('checked');
			}else
				el.value = vals['old'+el.name];

 		var cl = $(this).attr('removedcl');
 		if(cl)
 			$(this).addClass(cl);
 		var cl = $(this).attr('addedcl');
 		if(cl)
 			$(this).removeClass(cl);
 		
 		$(this).removeAttr('addedcl').removeAttr('removedcl');
	});
 	engine.hideButtons(row);
},

cancelAll : function(el){
	if(tep.isProp(el)){
		tep.unsavedPropRows = [];
		engine.propUpdate();
	}else
		$('tr', tep.contain).each(function(){engine.cancel(this);});
},
// отмена изменений
//////////////////////







//////////////////////
// прорисовки
drawRows : function(answ, isprop){
	$('body > .ac_results').hide();
	
	// сброс состояния загрузки значения (курсор переходит из "часиков" в обычный)
	tep.nowLoading	= false;
	tep.propRequest = false;
	
	if(isprop){
		if(tep.propQueue){
			engine.getPropRows(tep.propQueue);
			tep.propQueue = false;
			return;
		}
		tep.propQueue = false;
	}
	
	if(tep.kicker && answ.timer && !tep.kicker.answIsGood(answ))
		return ;
	
	if (answ.error){
		tep.smartWarn(answ.error);
		return isprop ? $(tep.propContain).empty() : engine.hideProp();
	}
	
	var afterDraw, datIndexes, sets, table, leadvals, valnams, page;

	// подбор настроек соответственно контексту таблицы
	tep.unsavedPropRows = {};
	if(isprop) {
		tep.showProp();
		afterDraw = engine.afterPropDraw;
		datIndexes = tep.propDatCellIndex;
		sets = tep.propRowSets;
		table = tep.propContain;
		leadvals = tep.propLeadVal;
		valnams = tep.propValNams;
		tep.currentDescrLen = tep.propDescrLen;
		page = tep.curPropPage;
		if(typeof engine.additionData == "function"){
			engine.additionData(answ.additiondata, isprop);
		}
		
		var activateProp = true;
		if(typeof engine.activatePropChk=='function')
			activateProp = engine.activatePropChk();
		if(activateProp)
			tep.unmakeDefCells(isprop);
	}else {
		dlg.dialog('close');
		tep.main.flushSelection();
		afterDraw = engine.afterDraw;
		datIndexes = tep.datCellIndex;
		sets = tep.rowSets;
		table = tep.contain;
		leadvals = tep.leadVal;
		valnams = tep.valNams;
		tep.unsavedRows = {};
		tep.currentDescrLen = tep.descrLen;
		page = tep.curPage;
	}
	
	// rowsCnt - счетчик кол-ва строк в таблице
	var rowsCnt = 0;
	
	// очистка таблицы перед заполнением (очищаются строки данных, строка ввода находится в др. TBODY)
	$(table).empty();
	
	// HTML код новой таблицы
	var newTableHTML = '';
	
	// проход по данным и наполнения HTML кода согласно ностройкам типов ячеек
	// поле filtered содержит кол-во записей отобраных учитывая фильтры, без учита постранички.
	// поле contain содержит сами данные упорядоченые согласно настроек установленых при инициализации.
	if(answ.filtered>0)
		for(var i=0; i<answ.contain.length; i++){
			if (tep.currentDescrLen.length>0) 
				tep.descrlencounter = 0; 
			else 
				tep.descrlencounter = null;
			// formRow(...) - формирует HTML код конкретной строки
			newTableHTML+= tep.formRow(answ.contain[i], sets, leadvals, valnams, isprop);
			rowsCnt++;
		}

	// после удаления последней записи с N-ой страницы, загружаем предыдущую 
	if(!rowsCnt && answ.filtered>0 && page>1){
		if(isprop) 
			tep.curPropPage--;
		else 
			tep.curPage--;
		return tep.update(table);
	} 
	
	// прорисовка собраного HTML кода
	$(table).html(newTableHTML);
	
	//*sk добавление к input type=checkbox с класом triple обработчика события click	
	$('input:checkbox', table).each(function(){
		if($(this).hasClass("triple"))
			$(this).click(threeState);
	});
	
	// обработчики на input type=password (при фокусе - очищаем, потеря фокуса - восстанавливаем)
	$('input:password', table).each(function(){
		// сохраняем текущее значение пароля
		$(this).data({'oldval': $(this).val(),cng:0})
		.focus(function(){
			if($(this).val() == $(this).data('oldval'))
				$(this).val('');
		}).blur(function(){
			if($(this).val() == "" && $(this).data("cng") != 1)
				$(this).val($(this).data('oldval'));
		});
	});
	// заполняет имеющиеся селекты в загруженых записях
	tep.fillSelect(table);
	
	if(datIndexes.length){
		var set = [];
		$(table).children('tr').each(function(){
			var $tds = $(this).children('td');
			for(var i in datIndexes){
				var $inp = $('input:visible', $tds[datIndexes[i]]);
				if($inp.length)
					set.push($inp.get(0));
			}
		});
		if($().calendar)
			$(set).calendar({onchoose: function(el){$(el).keyup();}});
	}

	// добавляет информационные строки, где выводится информация о примененных фильтрах, 
	// тэги для постранички (пустые)
	engine.appendInfoRows(table, rowsCnt, answ.filtered, answ.hasfilters, answ.bottomelse);	

	// если была прорисована доп таблица, загружаются зависимые от основной таблицы ячейки ввода. 
	if (isprop && tep.entLeadProp.length && tep.nowSelected && !isEqual(tep.prevReqParams, tep.reqParams)) {
		var vals = getvals(tep.nowSelected);
		for(var i=0; i<tep.entLeadProp.length; i++) {
			var masternam = tep.entLeadProp[i][0];
			var slavenam = tep.entLeadProp[i][1];
			loadCell('cell'+slavenam, vals[masternam], false, false, engine.extraGetCellParams);
		}
	// в сокращенном режиме просмотра основной таблице в информационную строку приписывается "Сокращенный режим"
	}else if(tep.shortView!='')	
		$('#filtersinfo').html('.  &nbsp;<b>Фильтр по идентификаторам</b> ');
	
	if(engine.staticFilter){
		var title= [];
		for(var nm in tep.staticFilter)
			title.push(nm);
		title = 'Установлены: '+title.join(',') + '. Сбрасывются по нажатию';
		$('#filtersinfo').html('.  &nbsp;<a href="#" class="btn staticfilter" title="'+title
				+'" onclick="engine.clearStaticFilters(event)" >Особые фильтры</a> ');
	}
		

	// по необходимости отображает постраничку, если отобраных (filtered) больше чем на странице (onPage/onPropPage)
	tep.showPages(answ.filtered, isprop, answ.setpage ? answ.setpage : false);

	// изменяется высота нажней таблицы
	if (tep.propContain) 
		$('#propcontainer').height($('#tablescontainer').height()-$('#entcontainer').height());


	// выделение строки

	// hasSelection - флаг, что строка уже выделена
	var hasSelection = false;
	
	// если всего одна строка, то выделять ее
	if(rowsCnt==1)		
		tep.selectFirst = true;
	
	// addedid - id добавленой строки, соотв. строка выделяется, если приходит это поле
	if(tep.addedid){
		var addedrow = $('tr[name='+tep.addedid+']' ,table);
		if(addedrow.length>0) {
			hasSelection = true;
			addedrow.mousedown();
		}
	}
	
	// если не выбрана строка по addedid, и стоит флаг selectFirst, выбирается первая строка на странице (при переходе на сл. страницу)
	if(engine.allowSelectFirst && tep.selectFirst && !hasSelection && !isprop) {
		$('tr[name]:not(.body_color):first', table).mousedown();
		hasSelection = true;
	}

	// если стоит флаг selectLast и строка еще не выбрана, выбирается последняя строка на странице  (при переходе на пред. страницу)
	if(tep.selectLast && !hasSelection && !isprop){
		$('tr[name]:not(.body_color):last', table).mousedown();
		hasSelection = true;
	}
	
	// если строка еще не выбрана, и есть значение lastSelectedRowNam (id выбраной строки до перезагрузки осн. таблицы), 
	// то выделяется эта строка, если она есть на странице (при измене)
	if (!isprop && tep.lastSelectedRowNams.length && !hasSelection) {
		if($('tr[name='+tep.lastSelectedRowNam+']', table).mousedown().length)
			hasSelection = true;
		$('tr', table).each(function(){
			var nam = $(this).attr('name');
			if(in_array(nam, tep.lastSelectedRowNams) && nam!='0'){ 
				$(this).addClass('selected_row');
				hasSelection = true;
			}
		});
	}
	
	if(!hasSelection && !isprop) 
		engine.hideProp();
	
	if(tep.selectAfter){
		$(tep.selectAfter).mousedown();
		hasSelection = true;
	}

	// сброс последнего запомненого id-а строка
	tep.lastSelectedRowNam = [];

	// сброс флагов
	tep.addedid		= false;
	tep.selectFirst	= false;
	tep.selectLast	= false;
	tep.selectAfter	= false;
	tep.pageDiff	= 0;
	tep.prevReqParams = clone(tep.reqParams);
	tep.reqParams	= {};


	// если в update() был передан параметр mess, это сообщение выводится
	if (tep.mess) 
		tep.warn(tep.mess, null, tep.messColor);
	else if(answ.info)
		tep.warn(answ.info, false, 'green');
	
	// сброс вывода сообщения после перезагрузки
	tep.mess='';
	tep.messColor='red';
	tep.tryPropUpdate=false;
	
	if(tep.propInsertRow){
		var len = $('th.inscell, th.addcell', tep.propInsertRow).length;
		var $btn = $('input:button', tep.propInsertRow);
		if(len) 
			$btn.removeAttr('disabled').val('Добавить');
		else
			$btn.attr('disabled', 'disabled').val('');
	}

	if(answ.otherinfo)
		$('#otherinfo').html(answ.otherinfo);
	if(answ.minfo)
		$('#minfo').html(answ.minfo);
	
	// если были установлены методы afterDraw/afterPropDraw, то они выполняются
	if(typeof(afterDraw)=='function') 
		afterDraw(table, answ);
},

// запрос значений списков для отрисованой таблицы и их заполнение
fillSelect : function(tbl){
	var requestedSelArr = {};
	var isprop = (tbl==tep.propContain) ? true : false;
	var leadval = (isprop) ? (tep.propLeadVal) : (tep.leadVal);

	var reqObj = {};
	reqObj.indep = [];
	var rows = tbl.getElementsByTagName('TR');
	for (var i=0; i<rows.length; i++){
		var row = rows[i];
		row.name = $(row).attr('name'); 
		var sels = row.getElementsByTagName('SELECT');
		var vals = getvals(row);
		for(var j=0; j<sels.length; j++){
			var isSlave = false;
			var slavenam = sels[j].name;
			// проверка, зависимый ли список. если нет, его ид добавляется в сортированый массив indep
			for(var nam in leadval) if (leadval[nam][1]==slavenam) {
				var masternam = leadval[nam][0];
				var masterval = vals[masternam];
				if(!reqObj[slavenam])
					reqObj[slavenam] = [];
				if(!in_array(masterval, reqObj[slavenam]))
					reqObj[slavenam].push(masterval);
				if(!requestedSelArr[slavenam])
					requestedSelArr[slavenam] = [];
				requestedSelArr[slavenam].push(sels[j]);
				isSlave = true;
			}
			// если зависимый, до в массив с еменем=имени списка добавляется значение от которого он зависит
			if (!isSlave) {
				if(!requestedSelArr.indep)
					requestedSelArr.indep = [];
				// для ускорения заполнения списков, ссылки на них сохраняются в массив requestedSelArr
				requestedSelArr.indep.push(sels[j]);
				if (!in_array(slavenam, reqObj.indep))
					reqObj.indep.push(slavenam);
			}
			// параллельно ссылки на все списки добавляются в массив requestedSelArr, для ускорения их прорисовки
		}
	}

	var selectExist = false;
	var params = {action: 'getselpack'};
	for(var nam in reqObj){
		params[nam] = [];
		for(var nam2 in reqObj[nam]){
			selectExist = true;
			params[nam].push(reqObj[nam][nam2]);
		}
	}
	if(!selectExist) 
		return;
	if (isprop && tep.nowSelected)
		params.owner = tep.nowSelected.name;

	postJSON(tep.path, params, function (answ){
		// заполнение списков
		// indep - элемент содержащий значения независимых списков
		for(var nam in requestedSelArr){
			var emptyOpt = '';
			if (nam=='indep') 
				for (var i=0; i<requestedSelArr.indep.length; i++){
					var sel = requestedSelArr.indep[i];
					$(sel).html(emptyOpt+answ.indep[sel.name]).val(getvals(sel.parentNode)['old'+sel.name]);
				}
			// остальные носят имя соответствуещего им списка, индексы - значения от которых они зависят
			else 
				for (var i=0; i<requestedSelArr[nam].length; i++){
					var sel = requestedSelArr[nam][i];
					var row = tep.riseTo('TR',sel); 
					var vals = getvals(row);
	
					for (var j=0; j<leadval.length; j++) 
						if(leadval[j][1]==sel.name) {
							var masternam = leadval[j][0];
							break;
						}
					var selnam = $(sel).attr('name');
					$(sel).html(emptyOpt+answ[selnam][vals[masternam]]).val(vals['old'+selnam]);
				}
		}
		requestedSelArr = null;
		// у селектов в дисабленых строках удаляются все оптионсы кроме текуще выбраного
		$('tr.disabled select', tbl).each(function(){
			var opt = $('option:selected', this);
			$(this).empty().append(opt).removeAttr('disabled').addClass('disabled');
		});

		if(typeof engine.onfillSelect == 'function'){
			engine.onfillSelect(tbl, answ);
		}


	});
},

// отображение постранички при необходимости
showPages : function(filteredCnt, isprop, setpage){
	var pg='Страница ';
	if (filteredCnt==0)
		return;
	if (isprop) {
		if(setpage)
			tep.curPropPage = setpage;
		var onpage = tep.onPropPage;
		var curpage = tep.curPropPage;
		tep.totalPropPages = Math.ceil(filteredCnt/onpage);
		var totalpages = tep.totalPropPages;
		tep.totalPropRows = filteredCnt;
	}else{
		if(setpage)
			tep.curPage = setpage;
		var onpage = tep.onPage;
		var curpage = tep.curPage;
		tep.totalPages = Math.ceil(filteredCnt/onpage);
		var totalpages = tep.totalPages;
		tep.totalRows = filteredCnt;
	}
	if (onpage<filteredCnt){
		var befstr = ' страница ';	var aftstr = '';
		for (var i=1; (i<curpage && i<3); i++)
			befstr+='<a title="'+pg+i+'" class="btn">'+i+'</a>';      //  1 2
		if (curpage>4)
			befstr+='<span style="padding: 0px; padding-left: 5px; padding-right: 5px; margin: 0px;">&#133</span>';

		if (curpage>3)
			befstr+='<a class="btn" title="'+pg+(curpage-1)+'">'+(curpage-1)+'</a>';            // 3 c
        if ((totalpages-curpage)>0)
        	aftstr+='<a class="btn" title="'+pg+(parseInt(curpage) + 1)+'">'+(parseInt(curpage) + 1)+'</a>';		// c 6

		if ((totalpages-curpage)>3)
			aftstr+='<span style="padding: 0px; padding-left: 5px; padding-right: 5px; margin: 0px;">&#133</span>';
		if ((totalpages-curpage)>2)
			aftstr+= '<a class="btn" title="'+pg+(totalpages-1)+'">'+(totalpages-1)+'</a>';
		if ((totalpages-curpage)>1)
			aftstr+= '<a class="btn" title="'+pg+totalpages+'">'+totalpages+'</a>';

		var prefix = ''; if (isprop) prefix = 'prop';
		$("#"+prefix+"pagesbef").html(befstr);
		$("#"+prefix+"pagesaft").html(aftstr+' ');
		$("#"+prefix+"currpage").css("display", "inline").val(curpage);
		if(tep.allSelected)
			$('a', $('#pagesaft').parent()).addClass('selected_page');
	}else {
		$("#"+prefix+"pagesbef").html(befstr);
		$("#"+prefix+"pagesaft").html(aftstr);
		$("#"+prefix+"currpage").css("display", "none");
		if (isprop) 
			tep.curPropPage=1; 
		else 
			tep.curPage=1;
	}
},

// запрос данных для осн/доп таблицы согласно контексту и вызов отрисовки данных
getRows : function(isprop){
	$('#otherinfo').empty();
	//$('#otherinfo,#minfo').empty();
	if (!isprop) {
		var conds = engine.conds();
		conds.action = 'getcontain';
//		tep.prevReqParams = clone(tep.reqParams);
		tep.reqParams = clone(conds);
		postJSON(tep.path, conds,  function(answ){
			engine.drawRows(answ, false);
		}, true);

	// при перемещении по основной таблице стрелочками, данные для дополнительной таблицы запрашиваются с задержкой в propLoadDelay мс.
	// при клике по основной таблице мышкой, устанавливается флаг instant и запрос отправляется сразу.
	}else {
		if(tep.propLoadTimer)
			clearTimeout(tep.propLoadTimer);

		if(tep.instant)
			engine.getPropRows();
		else
			tep.propLoadTimer = setTimeout("engine.getPropRows()", tep.propLoadDelay);
	}
},

// запрос данных для доп. таблицы
getPropRows : function(params){
	if (!tep.nowSelected) 
		return;
	if(array_sum(tep.unsavedPropRows))
		return alert('несохраненные свойства');
	
	tep.onPropPage = Math.round(($('#propcontainer').height()-tep.propHeadH) / tep.rowH );
	if(tep.onPropPage<2) 
		tep.onPropPage = 2;

	if(tep.propRequest){
//		tep.propRequest.abort();
		tep.propQueue = params;
		return;
	}
	var conds = engine.conds(true);
	conds.action = 'getpropcontain';
//	tep.prevReqParams = clone(tep.reqParams);
	tep.reqParams = clone(conds);
	
	// дополнительные параметры для запроса
	if(params != undefined && typeof params.conds == 'object'){
		conds = $.extend(true, conds, params.conds);
	}
	
	tep.propRequest = postJSON(tep.path, conds, function(answ){
		engine.drawRows(answ, true);
	}, '#propcontainer');
},

// формирование HTML кода строки загруженых записей
formRow : function(vals, sets, leadvals, valnams, isprop, noTRwrap) {
	var disabled = vals[1];	// отключена ли строка. 0-нет, 1-можно редактировать, но не удалять, 2-нельзя ред-ть
	var row = '';
	var buttonscell = '<td name="buttonscell" class="buttonscell">';//</td>');

	var rowvals = {};
	for(var j=0; j<valnams.length; j++) 
		rowvals[valnams[j]] = vals[j+2];
	
	rowvals['rowid'] = vals[0];

	for(var i=2; i<vals.length; i++){
		var typ = sets[i-2];
		if(!typ)
			typ = 'hid';
		var valnam = valnams[i-2];
		if(valnam == undefined)
			continue;

		// проверка зависимости и установка типа значения от других в строке 
		for(var j=0; j<leadvals.length; j++)
			if (leadvals[j][1]==valnam) {
				var mastername = leadvals[j][0];
				for(k=0; k<valnams.length; k++)
					if (valnams[k]==mastername) {
						typ = (leadvals[j][2][vals[k+2]]) ? (leadvals[j][2][vals[k+2]]) : (leadvals[j][2][0]);
						break;
					}
				break;
			}

		// формирование HTML кода ячейки
		var cell = tep.formCell(valnam, vals[i], typ, disabled, rowvals);
		if (typ!='hid')
			row+= cell;
		else 
			buttonscell+= cell;
	}

	// добавление столбца управления (с кнопками применить/отменить/удалить)
	if(disabled<2) {
		buttonscell+=
			 '<input type="button" class="control_button" value="+" name="save" style="display: none" title="Применить" onclick="engine.trySave(this);">'
			+'<input type="button" class="control_button" value="x" name="cancel" style="display: none" title="Отменить" onclick="engine.cancel(this);">';
	}
	if (disabled==0 || disabled==3) 
		buttonscell+= '<input type="button" class="control_button" value="-" name="remove" style="color: red" title="'+engine.delTitle+'" onclick="engine.tryRemove(this)">';
	else 
		buttonscell+= '<input type="button" class="control_button" value="-" name="remove" style="color: grey" title="Нельзя удалять" onclick="engine.warn('+"'"+'Эту запись удалять нельзя.'+"'"+');">';

	if(tep.history.length && !isprop) 
		buttonscell+= '<input type="button" class="control_button" value="i" name="history" style="color: blue" title="Посмотреть историю" onclick="engine.showHistory(this);">';

	row+=buttonscell+'</td>';
	if(!noTRwrap){
		row = ( '<tr name="'+(vals[0]===null ? '' : vals[0]).replace('"','\"')+'"'+( (disabled==2) ? ' class="disabled"' : '')+'>' + row + '</tr>');
	}
	return row;
},

// формирование HTML кода ячейки в зависимости от параметров
formCell : function(valnam, value, valtyp, disabled, rowvals) {
	var cl = '';
	if (value==null)
		value = ''; 
	if(valtyp=='nobrr')
		cl = 'alignprice';
	else if(!valnam)
		cl = 'aligntext';
	else if (tep.isDat(valnam)){
		value=reverseDat(value.replace(/-/g,'.'));
		cl = 'aligndat';
	}else if (valnam.slice(-2)=='sm' || valnam.indexOf('price')>-1){
		cl = 'alignprice';
		value = value.replace('.', floatDelimeter);
	}else if (
			valnam.slice(-2)=='id' 
			|| valnam.slice(-3)=='num' 
			|| valnam.slice(-3)=='ind' 
			|| valnam.indexOf('cnt')>-1)
		cl = 'alignnum';
	else
		cl = 'aligntext';
	
	
	cl = ' class="'+cl+'"';
	var hiddenHTML = '<input type="hidden" value="'
		+(value.replace ? value.replace(/"/g, "&quot;") : value) + '" name="old'+valnam+'">';
	
	//var cell = '<td style="position:relative;"'+cl+'>';
	
	if(valtyp == 'textarea')
		cl += 'style="position:relative;"';

	var cell = '<td'+cl+'>';

	var dattr = disabled==2 ? ' readonly="readonly"' : '';
	
	if(typeof engine.customCellDraw[valtyp] == 'function'){
		var answ = engine.customCellDraw[valtyp](valnam, value, disabled, hiddenHTML, rowvals);
		if(answ)
			return answ;
	}

	if(disabled==2 && in_array(valtyp, ['inp', 'dat', 'sdat', 'datt', 'sdatt']))
		if(typeof engine.disInpToStr == 'function' ? engine.disInpToStr.apply(null, arguments) : engine.disInpToStr)
			valtyp = 'nobr';

	switch (valtyp){
		case "hid":
			value = value.replace(/"/g, "&quot;");
			return '<input type="hidden" name="'+valnam+'" value="'+value+'">';

		case "dat":
		case "sdat":
		case "sdatt":
		case "datt":
		case "inp":
			value = value.replace(/"/g, "&quot;");
			cell+= '<input type="text" name="'+valnam+'" value="'+value+'"'+dattr+'>'+hiddenHTML;
			break;
		case "pwd":
			value = value.replace(/"/g, "&quot;");
			cell+= '<input type="password" name="'+valnam+'" value="'+value+'"'+dattr+'>'+hiddenHTML;
			break;
		case "true":
		case "chkb":
			if(disabled==2 || disabled==3)
				dattr+= ' disabled="disabled"';
			cell+= '<input '+dattr+' type="checkbox" name="'+valnam+'"'+((value=='1')?' CHECKED':'')
				+'><input type="hidden" value="'+value+'" name="old'+valnam+'">';
			break;
		case "chkb3": //*sk
			cl = '';			
			if(!value)
				cl = "thirdState ";
			if(disabled==2 || disabled==3)
				dattr+= ' disabled="disabled"';
			cell+= '<input '+dattr+' type="checkbox" class="'+cl+'triple" name="'+valnam+'"'+((value=='1')?' CHECKED':'')
				+'><input type="hidden" value="'+value+'" name="old'+valnam+'">';
			break;
		case "sel":
			cell+= '<select name="'+valnam+'" '+dattr+' onchange="engine.checkKeyUp(event)"></select>'+hiddenHTML;
			break;
		case "textarea":
			//var cnt= value.split('\n').length;
			var hid = '<textarea style="display:none;" name="old'+valnam+'" >'+value+'</textarea>';
			var show= '<textarea rows=1 class="font" name="'+valnam+'" >'+value+'</textarea>';
			cell+= show+hid;
			break;
		case "s":
		case "str":
		case "span":
		case "nobr":
		case "nobrr":
			cell+= '<nobr name="'+valnam+'">'+value+'</nobr>'+((valnam!='')?hiddenHTML:'');
			break;
		case "descr":
			var shortlen = (tep.descrlencounter!=null) ? (tep.currentDescrLen[tep.descrlencounter++]) : 20;
			
			if(typeof(value)=='object'){
				var val = value[0];
				value = value[1];
			} else
				var val = makeShort(value, shortlen);

			value = value.replace(/"/g, "&quot;");
			
			cell+= '<nobr name="'+valnam+'" title="'+value+'">'+val+'</nobr>';

			break;
		case "tree":
			var def = (value[3]==undefined) ? '' : ' def="'+value[3]+'"';
			cell+= '<input type="hidden" name="old'+valnam+'" value="'+value[0]+'" />'
				+'<input type="hidden" name="'+valnam+'" value="'+value[0]+'" />'
				+'<a href="#" class="btn" onclick="engine.chTree(this)" treenam="'+value[2]+'" '+def+' >'+value[1]+'</a>';
			break;

		case "file":
			var fid=0, fnam='';
			if(parseInt(value)){
				fid = parseInt(value);
				fnam = 'Файл';
			}else if(typeof value=='object'){
				fid = value.id;
				fnam = value.nam;
			}else
				return '<td></td>';
			cell+= "<a target='loadFileFrame' href='?control=file&mtd=download&links[0][fileid]="+fid+"&links[0][show]' >"+fnam+"</a>"
				+'<input type="hidden" name="old'+valnam+'" value="'+fid+'">';
			break;
		default:
			engine.warn("Программная ошибка. "+valtyp); 
			return '<td></td>';
	}
	return cell+'</td>';
},













// закрытие блока выбора по дереву (выполняется после выбора или нажатия ESC)
closeTree : function(){
	unblockInterface(false);
	$(document).unbind('keydown');
	if(engine.treeSelector)
		engine.treeSelector.jstree("destroy").remove();
	engine.treeSelector = false;
	$('#treecontainer').css('display', 'none');
	$('#treecontainer input:first').val('');
},


// инициализация окна выбора значения по дереву
chTree : function(el){
	var treenam = $(el).attr('treenam');
	var def = $(el).attr('def');
	// загружать часть дерева от топа до текущего значения строки
	engine.loadDownToId = $(el).prev().val();

	engine.treeCaller = $(el).prev();
	
	if($('#treecontainer').length==0){
		$(document.body).append('<div id="treecontainer" class="treeselector maincolor" style="display: none"><input name="treesearch" /><a href="#" class="btn" style="float: right; padding: 4px 6px;" onclick="engine.closeTree();">X</a></div>');
		//выбор по дереву - прикрутка АС для поиска по дереву
		$('#treecontainer input').autocomplete('', {
			matchContains: true,
			width:"auto",
			minChars:0,
			max:100,
			extraParams: {mtd: 'findnode', control: 'tree', treenam: 'rub', root: 1}
			
		}).result(function(evt, val, text){
			// при выборе из АС, сразу подставляем id и название
			var id = $.trim(val.split(',').pop());
			var nam = text.split(' > ').pop();
			treeCaller.val(id).next().text(nam);
			engine.closeTree();
		});
	}

	$('#treecontainer input[name=treesearch]').extendExtraParams({root: def});
	blockInterface(false, {bgClick: engine.closeTree, timeout: 0, imgAfter: 0});
	$('body > .hiddenpreloadbg').removeClass('hiddenpreloadbg');

	engine.treeSelector = $('<div></div>').appendTo('#treecontainer')
	.jstree({
		"core": {'animation': 0},

		"ui" : {"initially_select": [engine.loadDownToId], select_limit: 1, "selected_parent_close": false},
		
		"json_data" : {
			"ajax" : { 
				url : '',
				type: "post",
				// параметры запроса дерева
				data: function(cur){
					var id = cur.length ? cur.attr('id').split('_')[1] : 0;
					return {mtd: 'getsubtree', control: 'tree', treenam: treenam, id: id, downto: engine.loadDownToId, root: def};
				}
			}
		},
//		"hotkeys":{
//			"return": function(evt){
//				treeSelector.jstree('select_node', $('li', evt.target.parentNode));
//				return false;
//			}
//		},
		"plugins" : [ "json_data", "ui" ]//, "hotkeys"

	// срабатывает когда ветка загружена
	})
	.bind('load_node.jstree', function(a){
		// если загрузка выполнялась с указанием конечного элемента, то он выбирается
		var setSelected = engine.treeCaller ? engine.treeCaller.val() : engine.loadDownToId;
		if(setSelected){
			$('#t_'+setSelected+' a:first', engine.treeSelector).addClass('jstree-clicked');
		}
		// сброс переменной для отработки функции по выбраному элементу
		engine.loadDownToId = false;
	// при выборе элемента из дерева пользователем (кликом) выполняется подстановка значения в строку
	})
	.bind('select_node.jstree', engine.nodeSelected )
	.bind("loaded.jstree", function (evt, data) {
		if($('li', this).length == 1)
			engine.treeSelector.jstree('open_node', $('li', this));
	});

	// закрытие дерева по ESC
	$(document).keydown(function(evt){
		if(evt.keyCode==27)
			engine.closeTree();
	});
	$('#treecontainer').css('display', 'block').find('input:first').focus();
},

// когда элемент выбран подставляем id в скрытое поле, а название - в отображаемое и закрываем дерево
nodeSelected : function(){
	var el = engine.treeSelector.jstree('get_selected');
	if(!el[0])
		return false;
	// если выбор текущего значения (при открытии), то ничего не делать
	if(engine.loadDownToId)
		return false;
	
	var id = $(el[0]).attr('id').split('_')[1];
	var nam = $.trim($('a:first', el[0]).text());
	engine.treeCaller.val(id).next().text(nam).keyup();
	engine.closeTree();
//	return false;
},














// добавление информационных строк с информацией о примененных фильтрах
appendInfoRows : function(table, rowsCnt, filteredCnt, hasFilters, tmpBottom) {
	var row = $('<tr name="info_row" class="body_color"></tr>');
	var cellcnt = $('td,th', $('tr', tep.riseTo('TABLE',table))[0] ).length;

    var prefix = '';
	var isProp = (table==tep.propContain);
	
	if (isProp) {
		prefix = 'prop';
		var bottomElse = engine.bottomPropElse;
		var hideButtons = engine.hidePropCommonButtons;
	}else{
		var bottomElse = engine.bottomElse;
		var hideButtons = engine.hideCommonButtons;
	}
	
	if(tmpBottom)
		bottomElse+= tmpBottom;
	if(rowsCnt==0){
		$('<td colspan="'+cellcnt+'" style="text-align: center; border: 0px solid black;">Нет записей<font id="'+prefix+'filtersinfo"></font></td>').appendTo(row);
		$(row).appendTo(table);
	}else {
		$('<td colspan="'+(cellcnt-1)+'" class="blacktext"><nobr onclick="engine.changePage(event)" style="float: left;"><font id="'+prefix+'pagesbef" class="pageanchor"></font><input type="text" value="1" id="'+prefix+'currpage" onkeypress="engine.changePage(event)" style="display:none; width:18px; height: 16px; text-align: center; padding: 0px; margin: 0px; border: 1px solid black;"><font id="'+prefix+'pagesaft" class="pageanchor"> &nbsp; </font> '
			+'<a href="javascript:void(0)" class="btn" id="'+prefix+'selectall"> Все </a><font id="'+prefix+'currow">отобрано записей </font><font id="'+prefix+'totalselected"> '+filteredCnt+((parseInt(hasFilters))?(engine.fltIndicator):(''))+'</font><font id="'+prefix+'filtersinfo"></font></nobr><span style="float: right;" id="'+prefix+'info">'+bottomElse+'</span></td>')
		.appendTo(row);

		var cell = $('<td style="text-align: right;"></td>');
		if(!hideButtons)
			$('<input type="button" value="+" name="saveall" style="display:none;" class="control_button" title="Сохранить все изменения" onclick="engine.saveAll(this)"><input class="control_button" type="button" value="x" onclick="engine.cancelAll(this);" name="cancelall" style="display:none;" title="Отменить все несохраненные изменения"><input class="control_button" type="button" name="removeselected" value="-" style="color:red;" onclick="engine.removeSelected(this);" title="Удалить все выбранные записи">').appendTo(cell);
		$(cell).appendTo(row);
		$(row).appendTo(table);
	}
	
	if(!isProp)
		$('<tr name="warn_row" class="body_color"><td style="border: 0px solid black" colspan='+cellcnt+'><font id="'+prefix+'warnid" style="float:left;">&nbsp;</font></td></tr>')
		.appendTo(table);

	var alls = isProp ? tep.allPropSelected : tep.allSelected;

	if(alls)
		tep.selectAll(isProp);
	else
		$('#'+prefix+'selectall').click(tep.selectAll);
},
// прорисовки
////////////////////// 


selectAll : function(){
	var isprop = this.id=='selectall' ? 0 : 1;
	if(isprop)
		tep.allPropSelected = true;
	else
		tep.allSelected = true;
	
	var prefix = isprop ? 'prop' : '';
	var id = '#'+prefix+'selectall';
	var tid = '#'+prefix+'contain';
	
	$(id).unbind('click').bind('click', function(){
		tep.dropTotalSelection(isprop); 
		$(tid+' > tr').removeClass('selected_row');
		if(tep.nowSelected && !isprop)
			$(tep.nowSelected).addClass('selected_row');
	});
	$(tid+' > tr:not(.body_color)').addClass('selected_row');
	$(id+', #'+prefix+'pagesaft, #'+prefix+'pagesbef').addClass('selected_page');
},

dropTotalSelection : function(isprop){
	if(isprop)
		tep.allPropSelected = false;
	else
		tep.allSelected = false;
	var prefix = isprop ? 'prop' : '';
	var id = '#'+prefix+'selectall';
	$(id).unbind('click').bind('click', tep.selectAll);
	$(id+', #'+prefix+'pagesaft, #'+prefix+'pagesbef').removeClass('selected_page');
},

cellparams : function(id){
	$el = $(id);
	var visVal = $('input:visible', $el).val();
	if(!$('input:visible', $el).length)
		visVal = $('select option:selected', $el).text();
	
	var hidVal = $('input:hidden, select', $el).val();
	return {vis: visVal, hid: hidVal};
}


};

var tep = $.extend(true, tabloid_engine.prototype, ttt.prototype);
  


// загрузчик ячеек, вызывается при изменении полей от которых зависят значения/типы других полей
var loadCellCache = {};
/**
 * cellId - ID перезагружаемой ячейки (зависимой)
 * curVal	- значение ведущей ячейки (от которой зависит cellId)
 * masterVal - опционально значение строки(rowid) верхней таблицы
 * setVal - index строки, устанавливается после перезагрузки ячейки для select
 * extra	- дополнительные праметры
 */
function loadCell(cellId, curVal, masterVal, setVal, extra){
	var cell = d.getElementById(cellId);
	if (!cell)
		return;
	var isprop = tep.isProp(cell);
	if(!curVal)
		curVal = -1;
	
	var params = (typeof extra== 'object') ? extra : {};
	params.action = 'getcell';
	params.father = curVal;
	params.cellid = cellId;
	params.prop = isprop;
	if (isprop && tep.nowSelected)
		params.gfather = tep.nowSelected.name;
	else if(masterVal) 
		params.gfather = masterVal;

	if(engine.cacheAble){
		var cacheindex = '';
		for(var nam in params)
			cacheindex+= nam+params[nam];
		if( typeof (loadCellCache[cacheindex]) != 'undefined') 
			return loadCellAnsw(loadCellCache[cacheindex], setVal, params);
	}

	postJSON(tep.path, params, function(answ){
		loadCellAnsw(answ, setVal, params);
		if(engine.cacheAble)
			loadCellCache[cacheindex] = answ;
	}, cell);
}

var defCells = {};

function loadCellAnsw(answ, setVal, params){
	var id = '#'+answ.cellid;
	var $el = $(id);
	var runChange = false;
	
	if($el.attr('isdef')=='1')
		defCells[answ.cellid] = $el.html();

	if (answ.error)
		tep.warn(answ.error);

	var visVal = $('option:selected', id).text();
	if(!visVal)
		visVal = $('input:visible', id).val();
//	if(!visVal && params.possibletext)
//		visVal = params.possibletext;

	var hidVal = $('select, input:not(:visible)', id).val(),
	  isFocus = $('input:visible', $(id))[0] === document.activeElement;
	
	if($().unautocomplete)
		$(id+' input').unautocomplete();
	
	var arr = tep.cellparams($el);
	$(id).empty().html(answ.html);
	
	if(answ.html){
		var opts = $('option', id);
		$('select', $el).removeAttr('disabled').val(setVal ? setVal : hidVal);
		if(opts.length<2 && opts.val()==''){
			$('select', $el).val(opts.get(1).value);
			runChange = true;
		}else if(setVal)
			runChange = true;
		
		if(answ.ac){
			var extraparams = {
				father: params.father
				,gfather: params.gfather
				,action:"getac"
				,nam: id.substr(5)
			};
			for(var i in engine.extraACParams)
				extraparams[i] = engine.extraACParams[i];
			
			var el = $('input:visible', $el);
			el.autocomplete(tep.path, {
				matchContains:true
				,width:"auto"
				,minChars:0
				,max:100
				,extraParams: extraparams
				,onchangekey:tep.onautocompletechangekey
			})
			.result(function(evt,val,text){
				$('input:hidden', id).val(val).change();
				$('input:visible', id).attr('oldvalue', text);
				setTimeout(function(){
					$(evt.target).parent().next().find('input:visible,select').focus().select();
				}, 25);
			});
			
			if(isFocus)
			  el.focus();

//			if(setVal && visVal){
//			if(!setVal || setVal==hidVal){
//				$('input:visible', id).val(visVal);
//				$('input:hidden', id).val(hidVal);
//			}
		}
	}

	//*sk изменение ширины поля названия обекта для ОС
//	if(in_array(params.father, engine.params.invtyps))
//		$('input:visible', $el).css('min-width','500px');

	//$('input:visible', $el).val(arr.vis);
	
	if(!setVal)
		$('input:hidden, select', $el).val(arr.hid);

//	if (arr.txt && !$('input:visible', $el).val())
//		$('input:visible', $el).val(arr.txt);
//
//	// при закоменченых след двух строках не запоминались значения списков при их перезагрузке
//	// при изменении ведомого списка
	if (arr.vis && !$(':visible', $el).val())
		$(':visible', $el).val(arr.val);
//	
//	
// такой вариант не допустим - чистит созданые непустые инпуты
//	for(var i in vals){
//	$el = $('[name='+i+']', $el);
//	if($el.val()=='' && vals[i]!='')
//		$el.val(vals[i]);
//}
	
	$('input:checkbox', id).click(threeState);
	$('select', $el).change();
		
	if($().calendar)
		$('input.calendar', id).calendar({onchoose: function(el){$(el).keyup();}});
	
	if(typeof engine.afterLoadCellAnsw == 'function')
		engine.afterLoadCellAnsw(params);
};