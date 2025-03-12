/** различные функции */

/**
 * спрятать элемент
 * @param idinfo
 */
function info_hide(idinfo){
	d.getElementById(idinfo).style.display= "none";
}

/**
 * показать idinfo элемент на определённой позиции со смещением offset
 * @param idinfo	
 * @param elem
 * @param offset	смещение {top:0,left:0,right:0,bottom:0}
 */
function info_show(idinfo, elem, params){
	var elinfo= d.getElementById(idinfo);
	var visibl= 0;
	if (elinfo) 
		if (elinfo.style.display == "block")
			visibl= 1;
				
	if(visibl == 0){
		var flpos= getPosition(elem);
		
		var deltaTop= 0;
		var deltaLeft= 0;
		if(params != undefined){
			if(params.top)
				deltaTop= params.top;
			if(params.left)
				deltaLeft= params.left;
		}
		
		d.getElementById(idinfo).style.left= (flpos.x + elem.clientWidth + deltaLeft) + "px";
		d.getElementById(idinfo).style.top= (flpos.y + elem.clientHeight + deltaTop) + "px";
		d.getElementById(idinfo).style.display= "block";
		return 1;
	}	else {
		info_hide(idinfo);
		return 0;
	}
}

/**
 * уникальность элементов массива
 * @param arr
 * @returns {Array}
 */
function array_unique(arr){
	var newArray = [];
	var existingItems = {};
	var prefix = String(Math.random() * 9e9);
	for (var ii = 0; ii < arr.length; ++ii)	{
    if (!existingItems[prefix + arr[ii]])   {
      newArray.push(arr[ii]);
      existingItems[prefix + arr[ii]] = true;
    }
	}
	return newArray;
}

/**
 * offsetX и offsetY в Firefox undefined
 */
function offsetYFix(evt){
	if(evt.offsetY==undefined){
		var offsetY = evt.pageY-$(evt.target).offset().top;
		return offsetY;
	} else
		return evt.offsetX;
}

/**
 * проверка ip на правильность ввода
 * @param value
 * @returns 0 or 1
 */
function ip_validate(value){
	if(!value)
		return 0;
	var tested_ip = /\b(([01]?\d?\d|2[0-4]\d|25[0-5])\.){3}([01]?\d?\d|2[0-4]\d|25[0-5])\b/; 
	var chk= (value.search(tested_ip) == -1) ? 0 : 1;
	return chk;
  //return value.match(tested_ip);
}

function setSelectedText(sel, txt)
{ // set new selected value
 	txt= txt.toString().toLowerCase();
  var iCnt= sel.options.length - 1;
  for(var i= iCnt; i > -1; i--)
    if(sel.options[i].text.toLowerCase() == txt) {
      sel.selectedIndex = -1;
      sel.selectedIndex= i;
      return i;
    }
  return -1;
}

function toFixedFix(n, prec) {
  var k = Math.pow(10, prec);
  return '' + Math.round(n * k) / k;
}

function Size2Str(size, isspeed) {
	var kb = 1024,
		mb = 1024 * kb,
		gb = 1024 * mb,
		tb = 1024 * gb;
	if (size < kb) {
		var pref= (isspeed == 1) ? ' бт/с' : ' Бт';
		return size + pref;
	} else if (size < mb) {
		var pref= (isspeed == 1) ? ' кб/с' : ' Кб';
		return toFixedFix(size / kb, 2)+pref;
	} else if (size < gb) {
		var pref= (isspeed == 1) ? ' мб/с' : ' Мб';
		return toFixedFix(size / mb, 2)+pref;
	} else if (size < tb) {
		var pref= (isspeed == 1) ? ' гб/с' : ' Гб';
		return toFixedFix(size / gb, 2)+pref;
	} else {
		var pref= (isspeed == 1) ? ' тб/с' : ' Тб';
		return toFixedFix(size / tb, 2)+pref;
	}
}

/*** 	отмена всплывания события клавиатуры для элемента */
function noKeyAction(idel){
	var el= d.getElementById(idel);
	if(!el)
		return;

	$(el).keydown(function(evt){
		var ev = evt || window.event;
		if (ev.stopPropagation)
			ev.stopPropagation();
	});
	
	$(el).keypress(function(evt){
		var ev = evt || window.event;
		if (ev.stopPropagation)
			ev.stopPropagation();
	});
}

function htmlspecialchars(string, quote_style, charset, double_encode) {
  var optTemp = 0,
      i = 0,
      noquotes = false;
  if (typeof quote_style === 'undefined' || quote_style === null) {
      quote_style = 2;
  }
  string = string.toString();
  if (double_encode !== false) { // Put this first to avoid double-encoding
      string = string.replace(/&/g, '&amp;');
  }
  string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');
 
    var OPTS = {
        'ENT_NOQUOTES': 0,
      'ENT_HTML_QUOTE_SINGLE': 1,
      'ENT_HTML_QUOTE_DOUBLE': 2,
      'ENT_COMPAT': 2,
      'ENT_QUOTES': 3,
      'ENT_IGNORE': 4
  };
  if (quote_style === 0) {
      noquotes = true;
  }
  if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
      quote_style = [].concat(quote_style);
      for (i = 0; i < quote_style.length; i++) {
          // Resolve string input to bitwise e.g. 'PATHINFO_EXTENSION' becomes 4
          if (OPTS[quote_style[i]] === 0) {
              noquotes = true;
          } else if (OPTS[quote_style[i]]) {
              optTemp = optTemp | OPTS[quote_style[i]];
          }
      }
      quote_style = optTemp;
  }
  if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
      string = string.replace(/'/g, '&#039;');
  }
  if (!noquotes) {
      string = string.replace(/"/g, '&quot;');
  }
  return string;
}

// из строки типа:	Одесский форум <admin@forum.od.ua>
function mailFromStr(str){
	var pos= str.lastIndexOf(" ");
	var sname= "", smail= "";
	if(pos == -1){
		smail= str;
	} else {
		sname= str.substr(0, pos);
		sname= sname.replace(/,/g, "");
		smail= str.substr(pos+1);
	}
	sname=  $.trim(sname);
	smail=  $.trim(smail);
	smail= smail.replace(/</g, "");
	smail= smail.replace(/>/g, "");
	return {sname:sname,mail:smail};
}

function array_unique(arr){
  var newArray = [];
  var existingItems = {};
  var prefix = String(Math.random() * 9e9);
  for (var ii = 0; ii < arr.length; ++ii) {
    if (!existingItems[prefix + arr[ii]]){
      newArray.push(arr[ii]);
      existingItems[prefix + arr[ii]] = true;
    }
  }
  return newArray;
}

// рисуем страницы для перелистывания
function fillPages(cntMsg, cntinpage, cntstart){
	// TODO переделать с idelem, чтобы весь html лить
	// если нету сообщений очищаем
	if(cntMsg == 0){
		d.getElementById("pagesbef").innerHTML = '';
		d.getElementById("pagesaft").innerHTML = '';
		d.getElementById("pagecurr").style.display = 'none';
		return {pages:0, currpage:0};
	}
	
	var befstr = ' ', aftstr = '';	
	// число страниц
	var pagecnt= Math.ceil(cntMsg/cntinpage);
	// текущая страница
	var pagecurr= Math.ceil(cntstart/cntinpage) + 1;
	if(pagecurr > pagecnt)
		pagecurr-= 1;
	
	for (var i=1; (i<pagecurr && i<3); i++) 
		befstr+='<a id=idpage'+i+'>'+i+'</a>';      //  1 2
	if (pagecurr>4) 
		befstr+='<span style="padding: 0px; padding-left: 5px; padding-right: 5px; margin: 0px;">…</span>';
	if (pagecurr>3) 
		befstr+='<a id=idpage'+(pagecurr-1)+'>'+(pagecurr-1)+'</a>';            // 3 c
	if ((pagecnt-pagecurr)>0) 
		aftstr+='<a id=idpage'+(parseInt(pagecurr) + 1)+'>'+(parseInt(pagecurr) + 1)+'</a>';		// c 6
	if ((pagecnt-pagecurr)>3) 
		aftstr+='<span style="padding: 0px; padding-left: 5px; padding-right: 5px; margin: 0px;">…</span>';
	if ((pagecnt-pagecurr)>2) 
		aftstr+= '<a id=idpage'+(pagecnt-1)+'>'+(pagecnt-1)+'</a>';
	if ((pagecnt-pagecurr)>1) 
		aftstr+= '<a id=idpage'+pagecnt+'>'+pagecnt+'</a>';
	
	d.getElementById("pagesbef").innerHTML = befstr;
	d.getElementById("pagesaft").innerHTML = aftstr;
	d.getElementById("pagecurr").style.display = 'inline';
	d.getElementById("pagecurr").value = pagecurr;
	
	return {pages:pagecnt, currpage:pagecurr};
}

/* Получение значения стиля * elem - элемент * name - имя стиля*/
//$().height();
function getStyle(elem, name) {
	// Если необходимое свойство содержится в аттрибуте style[] тогда, оно является текущим
	if (elem.style[name])
		return elem.style[name];
	else if (elem.currentStyle)		// Вычисляем значение стиля используя метод IE
		return elem.currentStyle[name];
	else if (document.defaultView && document.defaultView.getComputedStyle) {
		// или W3C метод, если таковой имеется
		name = name.replace(/([A-Z])/g,"-$1");
		name = name.toLowerCase();
		var s = document.defaultView.getComputedStyle(elem,"");
		return s && s.getPropertyValue(name);
	} else
		return null;
}

function assoclen(arr){
	var cnt= 0;
	if(arr)
		for(var k in arr)
			if(k != '')	
				cnt++;
	return cnt;
}

function isInArray(arr, elem){
  if(arr)
    for (var i = 0; i < arr.length; i++)
      if(arr[i] == elem)
        return i;
  return -1;
}

function clientW(){
	return document.documentElement.scrollLeft+document.documentElement.clientWidth;
}
function clientH(){
	return document.documentElement.scrollTop+document.documentElement.clientHeight;
}

function dateParse(dttime){
	if(!dttime)
		return '';
	// бьем на дату и время
	var arrDat= dttime.split(" ");
	// разделяем дату
	var arrDat2= arrDat[0].split("-");
	var sDat= arrDat2[2]+"."+arrDat2[1]+"."+arrDat2[0];
	var res= (arrDat[1]) ? sDat+" "+arrDat[1] : sDat;
	return res;
}
function checkDate(dt) {
	var r = /(([0][1-9])|([1,2]\d)|([3][0-1])).(([0][1-9])|([1][0-2])).(([1][9](([1][7-9])|([2-9]\d)))|([2]{1}[0]{1}[0,1]{1}\d))/;
 	if (dt.search(r) >= 0)
  	return 1;
	return 0;
}
function checkStaffDate(dt) {
  var r= /[0-9]{4}-(([0][1-9])|([1][0-2]))-(([0][1-9])|([1,2]\d)|([3][0-1]))/;
 	if (dt.search(r) >= 0)
  	return 1;
	return 0;
}

function mailerr(inputvalue){
	var patr = /[а-яА-Я]/,
		pattern = /^([a-zA-Z0-9_.-])+@([a-zA-Z0-9_.-])+\.([a-zA-Z])+([a-zA-Z])+/,
		res = "";
	if(patr.test(inputvalue)) 
		res = "Русские буквы в почтовом адресе";
	else if(!pattern.test(inputvalue)) 
		res = "Неправильный формат почтового ящика";
	return res;
}

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

//функция для событий и подписчиков событий
var Eventer = new function(){
  function on(eventName, handler){
    // создать свойство obj.eventer_handlers[eventName], если его нет
    if(!this._eventerHandlers){
      this._eventerHandlers = {};
    }
    if(!this._eventerHandlers[eventName]){
      this._eventerHandlers[eventName] = [];
    }
    // добавить обработчик в массив
    this._eventerHandlers[eventName].push(handler);
  }

  function trigger(eventName, args){
    if(!this._eventerHandlers || !this._eventerHandlers[eventName]){
      return; // обработчиков для события нет
    }
    // вызовать обработчики
    var handlers = this._eventerHandlers[eventName];
    for( var i = 0; i < handlers.length; i++){
      handlers[i].apply(this, args);
    }
  }

  this.extend = function(obj){
    obj.on = on;
    obj.trigger = trigger;
  };
};