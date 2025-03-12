<!-- файловый буфер -->
<div id="filebuffer" class="filebufferwindow layer_overdlg3" style="position: absolute; display: none; max-width: 706px; left: 321px; top: 165px;">
  <div class="sjs_wtop">
    <div class="filebufferwtitle">
    	<span>Файловый буфер</span>
    	<span class="hoverchild" style="width:14px;height:14px;left: 5px;">
      		<img id="filebufferdisk" class="hoverelem" src="/img/btnfile.png" height="14" width="14" alt="Прикрепить" title="Прикрепить файл с диска"/>
			</span>
    </div>

    <div class="filebufferaction">
      <input id="filebufferadd" class="filecontrolbutton" type="button" title="Прикрепить выбранные файлы" value="+">
      <input id="filebufferremove" class="filecontrolbutton" type="button" title="Удалить выбранные файлы из списка" value="-">
      <!-- <input id="filebufferclose" class="filecontrolbutton" type="button" title="Закрыть" value="x">  -->
    </div>
  </div>

  <div>
    <div class="bbottom"></div>
    <div class="bleft"></div>
    <div class="bright"></div>
    <div class="filebuffercontent">
      <div>
        <form class="sjs_form" name="sjs_form" action="" method="post" enctype="multipart/form-data">
          <div id="sjFilemanager">
						<table class="filebufferlist no_sel">
						<tbody id="tblfilelist">
          	</tbody>
						</table>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
function filemethods(){
	this.processFileOwn = true;
}

// add file to fileown
filemethods.prototype.usrfileadd = function(fid) {
    if (! this.processFileOwn)
        return false;
	syncJSON("usrfile/add", {id: fid}, function(obj) {
		if(obj.error != '' && obj.error){
			dialog("Добавление файла в мои файлы", obj.error);
		} else if(!obj.filenam){
			dialog("Добавление файла в мои файлы", "Не удалось добавить файл в мои файлы");
		} else {
			var fnam = encodeURIComponent(obj.filenam);
			var fnam = obj.filenam.substring(0, 31);
			window.open("?usrfile&setfilters[nam]="+fnam, 'usrfile' + obj.usrid);
		}
	});
};

function filebuf_initdefault(canattach){
	if(typeof flbuffer != "undefined" && !flbuffer){
		flbuffer = new filemanager();
		flbuffer.onremove = winevts.prototype.filebufOnRemove;
		flbuffer.onadd = winevts.prototype.filebufOnAdd;
		flbuffer.singlemode = true;

		flbuffer.canattach = function(){
			if(typeof canattach == "function")
				return canattach();
			return canattach;
		};
	};
}
function filebuf_addfile(el){
	if (typeof flbuffer == "undefined")
		return true;

	var fid   = el.getAttribute('data-fid'),
		fnam  = decodeURIComponent(el.getAttribute('data-fnam')),
		fsiz  = el.getAttribute('data-fsiz'),
		ffull = decodeURIComponent(el.getAttribute('data-fullnam'));
		//ffull = decodeURIComponent(el.getAttribute('data-fullnam'))+" "+fsiz;

	//console.log(fid, fnam, fsiz, ffull);
	flbuffer.add(fid, fnam, fsiz, ffull);
}

$(document).click(function(evt){
	if(typeof flbuffer == "undefined"){
		return true;
	}
	// правую и среднюю кнопку не обрабатываем
	if(evt.which > 1){
		return true;
	}

	var el = (evt.target) ? evt.target : event.srcElement;

	// remove from filebuffer
	if(el.id && el.id.indexOf("fbufrem") == 0){
		var fid = el.id.substr("fbufrem".length),
			todel = {};
		todel[fid] = 1;
		flbuffer.remove(todel);
		if(typeof flbuffer.onremove == "function")
			flbuffer.onremove(todel);
		return false;
	}

	// attach file
	/*
	if(el.id && el.id.indexOf("fbufatt") == 0 && flbuffer.canattach()){
		var fid = el.id.substr("fbufatt".length),
			res = {},
			isfind = false;
		for(var j = flbuffer.data.length - 1; j > -1; j--){
			if(fid == flbuffer.data[j].id){
				isfind = true;
				res[fid] = flbuffer.data[j];
				break;
			}
		}
		if(isfind){
			flbuffer.attach(res);
		}
		return false;
	}
	*/

	// add file to filebuffer
	// <span id="idfilebuf" data-fid="1656861" data-fullnam="header" data-fnam="header" data-fsiz="733.00 Бт">Буф</span>
	if (el.id && el.id.indexOf("idfilebuf") > -1) {
		filebuf_addfile(el);
	}
	return true;
});

document.addEventListener('click', function(evt){
	if(typeof flbuffer == "undefined"){
		return true;
	}
	// правую и среднюю кнопку не обрабатываем
	if(evt.which > 1){
		return true;
	}
	return true;
});

//файловый буфер, мои файлы
var flbuffer = 0,
	filemtd = new filemethods();
</script>