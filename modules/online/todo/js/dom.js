function ddom(){
	this.d = document;
}
ddom.prototype = {
	id: function(id){
		return this.d.getElementById(id);
	},
	createNode: function(str){
		return this.d.createTextNode(str);
	},
	addEvent: function(obj, type, listener){
		if(obj.addEventListener)
			obj.addEventListener(type, listener, false);
		else if(obj.attachEvent)
			obj.attachEvent('on' + type, listener);
	},
	closest: function(el, tagName, className){
		var elem = el,
			cnttry = 100,
			tnam = '';
		while(cnttry > 0){
			tnam = elem.tagName.toLowerCase();
			if(tnam == 'html')
				return false;
			if((tagName && tnam == tagName) && (!className || className == elem.className))
				return elem;
			if(!tagName && className && className == elem.className)
				return elem;
			elem = elem.parentNode;
			cnttry--;
		}
		return false;
	},
	hasClass: function(elem, className){
		return new RegExp("(^|\\s)" + className + "(\\s|$)").test(elem.className);
	},
	addClass: function(elem, className){
		var tp = Object.prototype.toString.call(elem),
			arr = (tp === "[object Array]" || tp === "[object NodeList]") ? elem : [elem];
		for(var i = 0, len = arr.length; i < len; i++){
			if(!dom.hasClass(arr[i], className)){
				arr[i].className = arr[i].className ? [arr[i].className, className].join(' ') : className;
			}
		}
	},
	removeClass: function(elem, className){
		var tp = Object.prototype.toString.call(elem),
			arr = (tp === "[object Array]" || tp === "[object NodeList]") ? elem : [elem];
		for(var i = 0, len = arr.length; i < len; i++){
			if(dom.hasClass(arr[i], className)){
				var c = arr[i].className;
				arr[i].className = c.replace(new RegExp("(?:^|\\s+)" + className + "(?:\\s+|$)", "g"), " ");
			}
		}
	},
	/* Функция для создания элементов HTMLElement; пример
	 * @param {element} par	родитель, куда добавить элемент
	 * @param {String} tag - имя (напр. div, input...)
	 * @param {Object} attr - аттрибуты // или null, пар-р textNode - добавить текст внутрь
	 * @param {Object} styles - стили
	 * @param {Object} vars - объект, содержащий переменные элемента // или null
	 var elem = ce("input",
	 {id:"uname", type:"text", name:"username", value:"", size:"20"},
	 {className:"my_class", onkeypress:function(){alert(this._my_var_);}, _my_var_ : 12345}
	 );*/
	createElement: function(par, tag, attr, styles, vars){
		if(!tag)
			return null;
		var elem = document.createElement(tag),
			name,
			value;

		if(attr){
			for(name in attr){
				value = attr[name];
				if(typeof value != "undefined"){
					if(name == 'textNode'){
						elem.appendChild(document.createTextNode(value));
					}else if(name == 'class' || name == 'for'){
						name = {"for": "htmlFor", "class": "className"}[name] || name;
						elem[name] = value;
					}else{
						elem.setAttribute(name, value);
					}
				}
			}
		}

		if(styles){
			for(name in styles){
				value = styles[name];
				if(typeof value != "undefined")
					elem.style[name] = value;
			}
		}

		if(vars){
			for(var i in vars){
				elem[i] = vars[i];
			}
		}

		if(par){
			par.appendChild(elem);
		}

		return elem;
	},
	innerText: function(el, txt){
		// TODO проверить работу
		if(txt != undefined)
			el.innerText != undefined ? el.innerText = "" : el.textContent = "";
		else
			return el.innerText ? el.innerText : el.textContent;
	},
	clientSize: function(){
		var doc = document,
			elem = doc.compatMode == 'CSS1Compat' ? doc.documentElement : doc.body;
		return {h: elem.clientHeight, w: elem.clientWidth};
	}
};

//показать всплывающую подсказку
ddom.prototype.tooltip = function(e, msg){
	var w = 250,
		elid = "floatTip";
	if(!this.d.getElementById(elid)){
		this.createElement(this.d.body, 'div', {id: elid}, {
			position: 'absolute',
			width: w + 'px',
			display: 'none',
			border: '1px solid #000',
			padding: '4px',
			fontFamily: 'sans-serif',
			fontSize: '9pt',
			color: '#333',
			background: '#ffe5ff'
		}, undefined);
	}
	var floatTip = this.d.getElementById(elid),
		x = e.pageX,
		stl = floatTip.style;
	stl.left = ((x + w + 10) < this.d.body.clientWidth) ? x + 'px' : x - w + 'px';
	stl.top = e.pageY + 20 + 'px';
	stl.display = msg ? "block" : "none";
	floatTip.innerHTML = msg;
};