var defDat = 'дд-мм-гггг';
var d = document;
var maxFloatLen = 2;
var floatDelimeter = ',';
var listCache = {};
var ableListCache = true;
var ajaxListFormat = 'json';

blockInterfaceSmallSqaresColor = 'silver';
stopOnEmptyAjaxAnswer = true;

var messTarget = '#mess';
var messTimer = false;
function mess(msg, green, time) {
	var time = time ? time*1000 : 4000;
	time+= mess.length*100;
	
	clearTimeout(messTimer);

	msg = msg ? msg : '';
	$(messTarget).html(msg).css('color', green ? 'green' : 'red');
    if (msg)
   		messTimer = setTimeout(function(){mess();}, time);
};

Date.prototype.getWeek = function() {
	var year = this.getFullYear();
	if(!year)
		year = (new Date()).getFullYear();
	// if this is last week of year, it might have number from first week of the next year
	if((new Date(year, this.getMonth(), this.getDate()+7)).getFullYear() != year){
		var lastDow = ((new Date(year,11,31)).getDay()+6)%7+1;
		if(lastDow<4)
			return 1;
	}
	
	var onejan = new Date(year,0,1);
	var dow = (onejan.getDay()+6)%7 + 1;
	var answ = Math.ceil((((this - onejan) / 86400000) + dow)/7);
	if(dow>4)
		answ--;
	if(!answ)
		answ = (new Date(year-1,11,31)).getWeek();
	return answ;
};

//получить понедельник и воскресенье
Date.prototype.getWeekRange = function() {
	var mon = new Date(),
		sun = new Date(),
		curDat = this.getDay(),
		curDate = this.getDate();
	
	if(curDat == 0){
	  mon.setDate(curDate - 6);
	} else {
	  mon.setDate(curDate - curDat + 1);
	  sun.setDate(curDate + (7 - curDat));
	}
	return {mon: mon, sun: sun};
};

Date.prototype.getMonthLastDay = function() {
	var date = new Date(this.getFullYear(), this.getMonth()+1, 0);
	return date.getDate();	
};

//получить первый и последний день месяца
Date.prototype.getMonthRange = function() {
	var first = new Date(this.getFullYear(), this.getMonth(), 1, 11, 11, 11);
	this.setDate(this.getMonthLastDay());
	return {first: first, last: this};
};

//отображает дату в виде yyyy-mm-dd hh:mm:ss c ведущими 0
Date.prototype.usershow = function() {
	// дата
	var sY= this.getFullYear().toString(),
		sM= (this.getMonth()+1).toString();
	if(sM.length == 1)
		sM= "0" + sM;
	var sD= this.getDate().toString();
	if(sD.length == 1)
		sD= "0" + sD;
	var dt= sD + "." + sM + "." + sY,
		sH= this.getHours().toString();
	if(sH.length == 1)
	  sH= "0" + sH;
	var sMnt= this.getMinutes().toString();
	if(sMnt.length == 1)
	  sMnt= "0" + sMnt;
	var sS= this.getSeconds().toString();
	if(sS.length == 1)
	  sS= "0" + sS;
	var tm= sH + ":" + sMnt + ":" + sS,
		res= dt + " " + tm; 
	return {'date':dt,'time':tm, 'full':res};
};

//определение высоты и ширины клиентской части экрана
function screenSize() {
	var w, h;
	w = (window.innerWidth ? window.innerWidth : (document.documentElement.clientWidth ? document.documentElement.clientWidth : document.body.offsetWidth));
	h = (window.innerHeight ? window.innerHeight : (document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.offsetHeight));
	return {w:w, h:h}; 
}

// ограничение числа символов в textarea
function inputLimit(input, maxlen, errid, msg){
	var inputstr = input.value;
	if (inputstr.length > maxlen)
		input.value = inputstr.substring(0, maxlen);
	$("#"+errid).html((input.value.length - maxlen > -1) ? 
			maxlen + " " + declension(maxlen, Array("сиивол", "символа", "символов")) + " - " + msg : "");
}

function declension(num, arrDays){
	num= Math.abs(num) % 100;
	var nl= num % 10;	
	if(num > 10 && num < 20)
		return arrDays[2];
  if(nl > 1 && nl < 5) 
		return arrDays[1];
	if(nl == 1) 
		return arrDays[0];
	return arrDays[2];
}

function preventEvent(e) {
	var ev = e || window.event;
	// убрать реакцию браузера на событие
	if (ev.preventDefault) 
		ev.preventDefault();
	else 
		ev.returnValue = false;
	// отмена всплывания события
	if (ev.stopPropagation)
		ev.stopPropagation();
	return false;
	//e.cancelBubble = true;
}

function isEqual(a,b,strict){
	var atyp = (typeof a).toLowerCase();
	var btyp = (typeof b).toLowerCase();

	if(a===b)
		return true;
	
	if(strict && atyp!='object' && atyp!='array')
		return false;

	if(a==b)
		return true;

	if(atyp != btyp)
		return false;

	if(atyp=='object' || atyp=='array')
		for(var nam in a){
			if(b===null || typeof b[nam] == 'undefined')
				return false;
			if(!isEqual(a[nam], b[nam], strict))
				return false;
		}
	else return false;

	return true;
}

function removeFromArray(what, from, strict){
	for(var i=0; i<from.length; i++)
		if(isEqual(what, from[i], strict))
			from.splice(i--,1);
	return from;
}

String.prototype.pad = function(len, symb){
	symb = symb || '0';
	return (this.length < len) ? (symb + this.pad(len-1, symb)) : this;
}; 

String.prototype.rpad = function(len, symb){
	symb = symb || '0';
	return (this.length < len) ? (this.rpad(len-1, symb) + symb) : this;
};

function likeDat(str){
	var pattern = /^([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4})$/;
	return pattern.test(str);
}
function likeDatt(str){
	var pattern = /^(\d{1,2}\.\d{1,2}\.\d{4} [0-2]{1}\d{1}:[0-5]{1}\d{1}:[0-5]{1}\d{1})$/;
	if(pattern.test(str)) return true; else return false;
}

function likeNumber(val) {
	return !isNaN(parseFloat(val)) && isFinite(val);
}

function likeFloat(val){
	return /^\d+(.\d{0,2}){0,1}$/.test(val);
}
function reverseDat(datt, replaceDot){
	if(!datt) return '';
	var dt=(datt+'').split(' ');
	if (!dt) return datt;
	var datarr = dt[0].split('.');
	dat = datarr[2]+'.'+datarr[1]+'.'+datarr[0];
	if (dt[1])
		dat+=' '+dt[1];
	if(replaceDot)
		dat = dat.replace(/\./g, '-');
	return dat;
}

// хак для селектов в IE. при раскатывании списка он растягивается чтобы опции было видно во всю ширину
// при выборе или потере фокуса ширина возвращается в исходную.
function setIEselWidthHack(el){
	if($.browser.msie)
		$("select", el).mousedown(function(){ var ow = $(this).attr("ow"); var w=$(this).width(); if(!ow)$(this).attr("ow",w).css({width:"auto", minWidth:w})})
		.blur(function(){var ow = $(this).attr("ow");if(ow) $(this).width(ow).attr("ow", null);})
		.change(function(){var ow = $(this).attr("ow");if(ow) $(this).width(ow).attr("ow", null);});
}

function threeState(e){
	var el = $(e.target);
	if(el.attr('checked')){
		if(el.hasClass('thirdState')){
			el.removeClass('thirdState');
		}else{
			el.addClass('thirdState').removeAttr('checked');
		}
	}else if(el.hasClass('disableOff')){
		el.addClass('thirdState').removeAttr('checked');
	}
}

function blink(els, times){
	if(typeof times == 'undefined' || times<0) times=5;
	if(!times)
		return;
	
	$(els).addClass('blink-color');
	setTimeout(function(){
		$(els).removeClass('blink-color');
		setTimeout(function(){
			blink(els, times-1);			
		}, 500);
	}, 500);
}

array_sum = function(arr){
	var sm = 0;
	if(typeof arr=='object')
		for(var i in arr)
			sm+= array_sum(arr[i]);
	else 
		sm = parseFloat(arr);
	return sm;
};

function objDeep(obj){
	if(typeof obj=='object')
		for(var i in obj)
			return objDeep(obj[i])+1;
	return 0;
}

function makeShort(val, len, ends){
	if(!ends) ends = '...';
	val = val+'';
	
	if(val.length<=len) return val;
	
	val = val.slice(0, len);
	return val+ends;
}

lg = function(a){
	$('#console').html(a);
};

function loadSel(nam, sets, dontchange){
	var strict = 0;
	if(typeof sets!='undefined')
		if(typeof sets.strict!='undefined')
			strict = sets.strict;
	
	var def = sets.def != null ? sets.def : 'Все' ;
	var defVal = (typeof sets.defVal != 'undefined') ? sets.defVal : null ;
	
	var cacheIndex = nam;
	if(ableListCache){
		for(var iNam in sets)
			cacheIndex+= iNam + sets[iNam];
		if(listCache[cacheIndex])
			return fillSel('#'+nam, listCache[cacheIndex], strict, dontchange, def);
	}
	if(sets.action == null)
		sets.action = 'getsel';
	sets.name = nam;
	sets.format = ajaxListFormat;
	postJSON(path, sets, function(answ){
		listCache[cacheIndex] = answ;
		fillSel('#'+nam, answ, strict, dontchange, def, defVal);
	}, ('#'+nam));
}

function fillSel(el, data, strict, dontchange, def, defVal){
	var oldval = $(el).val();
	if(oldval==null)
		oldval = '';
	$(el).html(makeOpts(data, strict, defVal, def)).val('').val(defVal ? defVal : oldval);
	if(!dontchange)
		$(el).change();
}

function makeOpts(arr, strict, selected, def){
	if(arr.total)
		delete arr.total;
	var len = arr.length===undefined ? Object.keys(arr).length : arr.length;
	var answ = ((len>1 && strict==0) || strict==1 || def=='Любой') ? '<option value="">'+ (def ? def : '') +'</option>' : '';
	for(var iNam in arr){
		var
				id = iNam,
				nam = arr[iNam],
				attrs = '';
		if(typeof nam == 'object'){
			id = (arr[iNam]['id']) ? arr[iNam]['id'] : arr[iNam][0];
			nam = (arr[iNam]['nam']) ? arr[iNam]['nam'] : arr[iNam][1];
			if( arr[iNam].disabled !== undefined )
				attrs+= ' disabled="disabled"';

			if( arr[iNam]['class'] )
				attrs+= ' class="'+arr[iNam]['class']+'"';
		}
		if(selected==id)
			attrs+= ' selected';
		answ+= '<option value="'+id+'"'+attrs+'>'+nam+'</option>';
	}
	return answ;
}

function preloadFade(el){
	$(el).each(function(){
		var pos = this.getClientRects()[0];
		this.preloader = $('<div class="preloadbg">&nbsp;</div>').appendTo('body')
		.css({position: 'absolute', left: pos.left-1, top: pos.top-1})
		.width(pos.width+2).height(pos.height+2).get(0);
	});
}

function preloadUnfade(el){
	$(el).each(function(){
		var fgr = this.preloader;
		if(!fgr)
			return;
		$(fgr).remove();
		delete(this.preloader);
	});
}

function showControls(row){
	$('.save, .cancel', row).show().keyup(function(e){return (e.keyCode==27 || e.keyCode==13 );});
}

function hideControls(row){
	$('.save, .cancel', row).hide().unbind('keyup');
}

function changeCheck(evt){
	var el = evt.target;
	var row = $(el).closest('tr');

	if(evt.keyCode){
		if(evt.keyCode==27)
			return $('.cancel', row).click();
		else if(evt.keyCode==13)
			return $('.save', row).click();
	}

	if( $(el).val() != $(el).attr('def') ){
		showControls(row);
	}else{
		hideControls(row);
	}
}

function cancel(evt){
	var row = tep.riseTo('tr', evt.target);
	$('input,select', row).each(function(){
		var def = $(this).attr('def');
		if(def!=undefined)
			$(this).val(def);
	});
	hideControls(row);
}