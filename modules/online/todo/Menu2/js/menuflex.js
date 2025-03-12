function menuflex(){

/*
	//$(dd).bind("dragstart", function (evt) { evt.preventDefault(); } );

	$(document).bind('keydown.hidemenu', function(evt){
		if(evt.keyCode && evt.keyCode==27){
			menuHide();
			$(document).unbind('keydown.hidemenu');
			return false;
		}
	});

	// обработка нажатия на кнопки меню
	$(document).bind('click.ggg', function(evt){
		var allelems = ['idquit'];
		if(evt.target.id != undefined && $.inArray(evt.target.id.toString(), allelems) != -1 && evt.which == 1){
			switch(evt.target.id){
				case 'idquit':
					// quit
					vkquit();
					break;
			}
		}
		$(document).unbind('keydown.hidemenu');
		//при щелчке прятать меню
		menuHide();
	});

	*/

}

$(document).click(function(evt) {
  var el = evt.target,
  	elid = el.id;

	switch (elid) {
	case "menuquit":
		if(typeof $.jStorage === 'object'){
			$.jStorage.set('quit', 1, {TTL:900});
		}
		return false;
	}
	return true;
});

menuflex.prototype.className = "menuflex";

menuflex.prototype.hide = function(){
	$("."+this.className).css('display', 'none');
};

menuflex.prototype.show = function(){
	var post= {wndnam: window.name},
			mnstart = Date.now();
	postJSON("menu/get", post, function(obj){
		$("." + this.className).css({display:'block'}).html(obj.html);
		var mnend = Date.now(),
				res = mnend - mnstart;
		$("." + this.className + " b:first").attr('title', 'Duration boot menu: ' + res);
	}.bind(this));
};

$(function(){

	// нажатие меню
	function onMenuClick(el){
		// создаём контейнер для меню
		if($("#menumaincontainer").length == 0){
			var dd = $("<div id='menumaincontainer'></div>");
			$(document.body).append(dd);
			$(dd).bind("dragstart", function (evt) { evt.preventDefault(); } );
		}
		if($("#menumaincontainer").css('display') == 'block'){
			menuHide();
		} else {

		}
	}

	//mmenu.click(function(evt){
	//	onMenuClick($(evt.target).closest("div[class*='mainmenubg']")[0]);
	//});

	//alert(345);

});