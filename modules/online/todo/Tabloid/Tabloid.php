<?php
namespace Online\Tabloid;

define('zeroPrice', '0,00');

class Tabloid{
	public
		// использовать преобразование ответа в формате массива в формат JSON автоматически
		// при выдаче результата
		$useOnExitJSON = 0,
		// при подсчете кол-ва строк использовать пару SQL_CALC_FOUND_ROWS / FOUND_ROWS() вместо исходного способа
		$useCalcRows = 0,
		// автоматическое декодирование и выполнение mysql_escape для вхдящих параметров
		$decodeIncomParams = 0,
		// дополнительный пользов-ий JS при отрисовке HTML
		$customJS = '',
		// дополнительный пользов-ий CSS код в head при отрисовке HTML методом stdview
		$customCSS = '',
		// доп HTML инфа в инфо строке
		$bottomElse = false,
		// набор колонок основной/доп. таблицы
		$cols = Array(), 	$propcols = Array(),
		// название сущности текущего редактора (как часть имени фильтра, путь к редактору)
		$entity,
		// список полей необязательных для заполнения при добавлении записи
		$allowedEmptyInsert = Array(),
		// список полей необязательных для заполнения при изменении записи
		$allowEmptyContain = Array(),
		// поле сортировки основной/доп. таблицы по-умолчанию
		$defsort, 	$defpropsort,
		// список скрытых полей основной/доп. таблицы (должны быть описаны в запросе,
		// на клиентском уровне записываются в скрытые поля)
		$hidden = Array(),	$prophidden = Array(),
		// транслируемые переменные (на клиенте доступны в engine.params )
		$translateParams = Array(),
		// точка входа в редактор (для редакторов связаных с учетом это 'idwarehouse')
		$handler,
		// запрос выборки данных для основной/доп. таблицы
		$qry, 		$propqry,
		// длины сокращений для полей с типом 'descr' основной/доп. таблицы
		$descrLen = Array(),	$propDescrLen = Array(),
		// список зависимостей полей: дополнительной табл. между собой; осн. табл. м с; доп. таблицы от осн. таблицы
		$prop_prop_rely = Array(),	$ent_ent_rely = Array(), 	$ent_prop_rely = Array(),
		//  текущее датавремя (устанавливается в конструкторе)
		$now,
		// текущий день (устанавливается в конструкторе)
		$today,
		// читаемое название редактора (для описания фильтра), если не указано, берется название конечного класса
		$editorName,
		// список коррекции фильтров
		$extenFilters = Array(),
		// список коррекции сортировки
		$extendSort = Array(),
		// список полей для группировки (при формировании запроса, добавляется в конце с GROUP BY)
		$group = Array(),	$propGroup = Array(),
		// список дополнительных JOIN
		// (на случай если они нужны при наличии опр. фильров, ставятся вместо метки %externjoins%)
		$joins = Array(),
		// принудительно дописывание WHERE при добавлении фильтров к запросу выборки(true - дописываь,
		// false - не дописывать, по-умолчанию дописывать, если в запросе не встречается слово WHERE)
		$qryForceWhere=null, // для запросов с вложеностью
		// индексированый массив - нехранимые фильтры (хранимые на клиенте)
		$staticFilter = false,
		// список парвметров перехода к истории записи
		$historyParams = Array(),
		// параметры полученые на входе
		$params = Array(),
		// либо false, либо объект автовыхода
		$cashKicker = false,
		// флаг, считать что поле с датой 1 или 2 (период)
		$dateAsPeriod = false,
		// флаг, краткий ли просмотр, id-ы записей краткого просмотра
		$short = false,	$viewids = Array(),
		// пустой элемент в списке (параметр strict для HTMLOptions)
		$triggerSelectListEmpty = 0,
		// вырезать из запроса с количеством всё что между rowid и from
		$removeFields= 1,
		// подключать ли код требуемый для upload файлов
		$includeFileUpload= false,
		// подключать ли код требуемый для upload файлов
		$includeFileBuffer= false,
		// не использовать фильтры
		$skipFilters= false,
		// список фильтров, допустимых к применению, отсутствующих в колонках
		$allowAdditFilterNames = array(),
		// не проверять название фильтров на допустимость
		$filterNameNoCheck= false,
		// название полей, фильтры по которым не использовать контекстный поиск (всегда использовать "=", а не LIKE)
		$strictFilterNams = Array(),
		// дополнительные допустимые фильтры
		$allowFilters = array();

	protected
		// флаг уст-ся в методе checkAJAX, указывает что в двнный момент обрабатывается AJAX-запрос
		$isAJAX = false,
		// значение эл-та списка устанавливаемое выбраным в след. HTML списке, после обнуляется
		$triggerSelectListItem = 0,
		// текущая страница с данными, кол-во записей на странице
		$page, $onpage,
		// флаг, является ли номер страницы пересчитаным на сервере, либо взят без изменений с клиента
		$pageRecounted=false,
		// когда поле указано, то при взятии одной строки по id данное поле используется как фильтр,
		// при этом не используется обертка над всем запросом без limit
		$_rowDataSpecialFilter = '',
		// список полей, фильтр по которым исп-ся не "=" , а IN(...)
		$listFilters = Array(),
		// получены ли фильтры _GET, можно получить фильтры checkAJAXFilters, использовать их, а потом вызвать checkAJAX
		$isFilterCheck = false;

	/**
	 * массив с обязательными библиотеками для работы редактора
	 * @var array
	 */
	protected $includes = Array(
		"skin.css"
		//,"menu.css"
		,"wnd.css"
		,"jquery.autocomplete.css"
		,"jquery-ui-1.7.2.custom.css"
		//, "evt.css"
		,"jsonquery.css"
		,"jquery.calendar.css"
		,"reset.css"
			
		,"jquery-1.4.2.min.js"
		,"jquery-ui-1.7.2.custom.min.js"
		,"jquery.autocomplete.js"
		,"rowselector.js"
		,"jsonquery.js"
		//,"winclose.js"
		//,"html2canvas.min.js"
		//,"notes.js",  
		//,"usrevt.js"
		,'menuflex.css'
		,"menuflex.js"
		,"jquery.calendar.js"
		,"func.js"
			
		,"cl_tabloid_main.js"
		,"common.js",
		
		'jstorage.js', 
		'jstorageevents.js'
			
	);

	/**
	 * Запрос (формализованый) подтверждения на выполнение операции
	 * При вызове этого метода происходит (преривание) выполнения кода, с зпапоминанием места (его возникновения)
	 * и вызывается диалог на клиенте с опредиленным наполнением.
	 * Каждый конфирм имеет свой ключ, который присваевается автоматически для распознавания множества конфирмов
	 * После того, как нажато подтверждение(я) в диалоге(ах), код до вызова конфирма(ов) выполняется повторно
	 * и продолжет свою работу из данными которые были получены от конфирма(ов)
	 * @param $text
	 * @param string $title
	 * @param string $btn
	 * @param array $dialogSets
	 * @param bool $key
	 * @return bool
	 */
	public function confirm($text, $title = 'Подтверждение', $btn = 'Подтвердить', $dialogSets=Array(), $key=false){
		if(!$key){
			$arr = debug_backtrace();
			$keyString = '';
			foreach ($arr as $i=>$call)
				$keyString.= $i.$call['file'].$call['line'].$call['function'];
			$key = md5($keyString);
		}

		\Online\Helpers\JSONHelper::confirm($text, $title, $btn, $dialogSets, $key);
		$answ = isset($this->params['confirmvals'][$key]) ? $this->params['confirmvals'][$key] : true;
//   		if($this->decodeIncomParams)
//   			$answ = \Encoder\Coder::coding($answ, 1, 1); 

		return $answ;
	}

	/**
	 * добавление полей по которым фильтр используется как список
	 * @param $fields
	 */
	public function setFiltersAsList($fields){
		$this->listFilters = \Online\Helpers\ArrayHelper::makeArray($fields);
	}

	/**
	 * перенаправление
	 * @param $loc
	 */
	public function redirectTo($loc=''){
		if(!$loc)
			$loc = "?$this->handler=$this->entity";
		header("Location: $loc");
		exit;
	}

	/**
	 *
	 */
	public function inclhtml(){
		if($this->includeFileUpload){
			include_once 'aview/v_fileupload.php';
			echo '<input type="file" id="uploadfilechoose" onchange="engine.sendFile(this)"
					style="visibility: hidden; position: fixed; width: 10px; height: 10px; left: -1000px;" >
				<iframe style="display: none;" id="loadFileFrame" name="loadFileFrame" src="htm/fileframe.htm"></iframe>';
		}
		if($this->includeFileBuffer){
			include_once 'aview/v_filebuffer.php';
		}
	}

	/**
	 * формирование включений библиотек в раздел <head>
	 * @param array/string $incls доп библиотеки помимо $includes
	 * @return string
	 */
	public function incl($incls=''){
		$incls = \Online\Helpers\ArrayHelper::makeArray($incls);

		if($this->includeFileUpload){
			$incls[] = 'fileupload.css';
			$incls[] = 'uploaderobject.js';
			$incls[] = 'fileupload.js';
		}

		$colOpts = $this->getsets();
		$chk = Array('filtertyp', 'addtyp', 'containtyp');
		$oincls = Array();
		foreach ($colOpts as $col){
			foreach ($chk as $on)
				switch($col[$on]){
					case 'dat':
					case 'datt':
					case 'sdat':
					case 'sdatt':
						$oincls[] = 'jquery.calendar.js';
						$oincls[] = 'jquery.calendar.css';
						break;
					case 'ac':
					case 'autocompl':
						$oincls[] = 'jquery.autocomplete.js';
						$oincls[] = 'jquery.autocomplete.css';
						break;
					case 'tree':
						$oincls[] = 'jquery.jstree.js';
						break;
				}
		}
		
		$incls = array_unique(array_merge($this->includes, $incls, array_unique($oincls)));
		
		//print_r($incls);
		return \Online\Helpers\FileSystem::loadPublic($incls, ['Online', 'Tabloid', 'Menu', '*']);
	}

	/**
	 * адаптированая функция для логирования операций
	 * @param int $typ тип записи в журнал (см. usrlogproptyp)
	 * @param string $val строковая (произвольная) информация об операции
	 * @param int/string $entity привязка к сущности
	 * @param int $entityId id в сущности
	 * @param bool $toLast стараться ли привязать к предыдущей записи в логе
	 */
	public function log($typ=0, $val='', $entity=false, $entityId=false, $toLast=false){
		\Encoder\Usrlog::logadd($typ, $val, $entity, $entityId, $toLast);
	}

	/**
	 * установка параметров просмотра истории (по сущности текущего редактора) в журнале
	 * @param string $nam название поля которым в журнал будет приходить значение. если указать
	 * rowid, передастся id из текущей сущности
	 * @param string $val 	название поля в текущем редакторе, значение которого будет уст-ся в фильтр
	 * @param $valIsConst флаг, указывающий что в качестве значения надо брать не значение поля $val, а само $val
	 */
	public function historyView($nam, $val='', $valIsConst=false){
		if(\Encoder\Opt::getopts('usrlog_accsess')) {
			if($valIsConst and $nam=='entity' and !is_int($val)){
				$val = \db::val("SELECT id FROM entity WHERE tablenam='$val'");
				$nam = 'e_id';
			}
			$this->historyParams[] = Array($nam,$val,$valIsConst);
		}
	}

	/**
	 * сохранение сортировок в базу
	 * @param array $params
	 * @return int
	 */
	public function savesort($params){
		$able = Array(
			$this->entity.'sortentfield'
			, $this->entity.'sortentdir'
			, $this->entity.'sortpropfield'
			, $this->entity.'sortpropdir'
		);

		if(in_array($params['fld'], $able))
			\Encoder\Opt::setopts($params['fld'], $params['val']);
		return 1;
	}

	/**
	 * при построении более сложных запросов, можно в зависимости от наличия определенных
	 * фильтров добавлять дополнительные таблицы в выборку. для этого в запросе, в месте
	 * где они должны появиться необходимо поставить маркер %externjoins%. тогда при наличии
	 * фильтра по полю $nam вместо %externjoins% будет появлятьс ястрока $join, иначе пусто
	 * @param string $nam название поля
	 * @param string $join описание join-a
	 */
	public function externJoins($nam, $join){
		$this->joins[]=Array($nam, $join);
	}

	/**
	 * небольшая коррекция названия по фильтру. Когда должен быть установлен фильтр по полю
	 * $nam, вместо него и знака (не)равенства подставится строка $filterStr
	 * @param $nam
	 * @param $filterStr
	 */
	public function extendFilter($nam, $filterStr=''){
		$this->extenFilters[$nam] = $filterStr;
	}

	/**
	 * корректировка сортировки, при установлении сортировки по полю $fieldnam, подставится $override
	 * @param string $fieldnam название поля сортировки
	 * @param string $override то чем будет заменено поле сортироки
	 */
	public function sort($fieldnam, $override){
		$this->extendSort[$fieldnam] = $override;
	}

	/**
	 * преобразование формата даты из дд.мм.гггг в гггг-мм-дд
	 * @param string $datt
	 * @return string
	 */
	public function toDbDatt($datt){
		$arr = explode(' ', $datt);
		$answ = implode('-', array_reverse(explode('.', $arr[0])));
		if(count($arr)==2) $answ.= ' '.$arr[1];
		return $answ;
	}

	/**
	 * формализованый вывод ошибки. возвращает ошибку в формате JSON. если ничего не передавать
	 * вернет {error: 'Ошибка данных'}. если передать 1 параметр, вернет {error: 'param'}.
	 * если 2 пар-ра, то {code: param1, error: param2}
	 * @param string $err
	 * @param int $code	код/текст ошибки
	 * @return array|string	текст ошибки
	 */
	protected function error( $err = 'Ошибка данных', $code = 0 ){
		$answ = Array('error'=>$err);
		if ($code)
			$answ['code'] = $code;
		$answ['timer'] = $this->CKtime();

		return $this->useOnExitJSON ? $answ : \Online\Helpers\JSONHelper::json($answ);
	}

	/**
	 * выборка уникальных идентификаторов с учетом установленых и временных фильтров
	 * применять только для сущностей с одним уникальным полем (выбираемом в rowid)
	 * @param array $params входящие параметры
	 * @return array
	 */
	public function getIds($params){
		$arr = $this->addfilters2qry($this->qry, $params, $this->defsort);
		return \db::col($arr['nolim']);
	}

	/**
	 * вывод запроса для вставки названий фильтров для текущего редактора
	 * @param string $redactor redactor name
	 * @return string
	 */
	private function showOptTyp($redactor){
		$inserts = $this->getsets('',false);
		$a= array();

		$descr = "Редактор $redactor, направление сортировки";
		$a[] = "('{$this->entity}sortentdir', 1, 'down', '$descr', '$descr')";

		$descr = "Редактор $redactor, поле сортировки";
		$a[] = "('{$this->entity}sortentfield', 1, '', '$descr', '$descr')";

		if (count($this->propcols)){
			$descr = "Редактор $redactor, направление сортировки второй таблицы";
			$a[] = "('{$this->entity}sortpropdir', 1, 'down', '$descr', '$descr')";

			$descr = "Редактор $redactor, поле сортировки второй таблицы";
			$a[] = "('{$this->entity}sortpropfield', 1, '', '$descr', '$descr')";
		}

		foreach($inserts as $row)
			if($row['filterid']) {
				$fid = $row['filterid'];
				if($row['filtertyp'] == 'dat' || $row['filtertyp'] == 'datt'){
					$fnam= $this->entity."filterst".$fid;
					$descr= "Редактор $redactor, Фильтр по полю $fnam, начало периода";
					$a[] = "('$fnam', 1, '', '$descr', '$descr')";

					$fnam= $this->entity."filterend".$fid;
					$descr= "Редактор $redactor, Фильтр по полю $fnam, конец периода";
					$a[] = "('$fnam', 1, '', '$descr', '$descr')";
				} else {
					$fnam= $this->entity."filter".$fid;
					$descr= "Редактор $redactor, Фильтр по полю $fnam";
					$a[] = "('$fnam', 1, '', '$descr', '$descr')";
				}

				$meth = 'textfilter'.$fid;
				if(method_exists($this, $meth)){
					$fnam= $this->entity."filter{$fid}_text";
					$descr= "Редактор $redactor, Фильтр по полю $fnam (по тексту)";
					$a[] = "('$fnam', 1, '', '$descr', '$descr')";
				}
			}
		$answ = "INSERT IGNORE INTO opt(nam, entityid, def, shortdescr, descr) VALUES
			".implode(",\n", $a) . ";";
		return $answ;
	}

	/**
	 * установка основного запроса выборки
	 * @param string $qry
	 */
	public function setqry($qry)	{
		$this->qry = $qry;
	}

	/**
	 * установка запроса для выборки свойств (данных для дополнительной таблицы)
	 * в месте где должен стоять идентификатор из основной таблицы, нужно поставить метку %owner%
	 * @param string $qry
	 */
	public function setpropqry($qry){
 		$this->propqry = $qry;
	}

	/**
	 * установка правила необязательного заполнения полей (перечисленых) при добавлении записи
	 * @param $insertNams
	 */
	public function allowEmptyInsert($insertNams){
		$this->allowedEmptyInsert = array_merge($this->allowedEmptyInsert, \Online\Helpers\ArrayHelper::makeArray($insertNams));
	}

	/**
	 * установка правила необязательного заполнения полей (перечисленых) при сохранении записи
	 * @param array/string $containNams
	 */
	public function allowEmptyContain($containNams){
		$this->allowEmptyContain = array_merge($this->allowEmptyContain, \Online\Helpers\ArrayHelper::makeArray($containNams));
	}

	/**
	 * выборка наполнения дополнительной таблицы
	 * @param $params
	 * @return array
	 */
	public function getpropcontain($params){
		$answ= $this->getrows($this->propqry, $params, $this->defpropsort, true);
		if(method_exists($this, "additiondata"))
			$answ["additiondata"]= $this->additiondata($params);

		return $answ;
	}

	/**
	 * выборка наполнения основной таблицы
	 * @param $params
	 * @return array
	 */
	public function getcontain($params){
		$answ= $this->getrows($this->qry, $params, $this->defsort);
		return $answ;
	}

	/**
	 * установка группировки по указаным полям (если необходима и группировка и фильтры)
	 * @param string $grp названия полей
	 * @param bool $isprop для запроса по дополнительной таблицы
	 */
	public function setGroup($grp, $isprop=false){
		if(!$isprop)
			$this->group[] = $grp;
		else
			$this->propGroup[] = $grp;
	}
	
	/**
	 * get id and row from table 
	 * @param array $params
	 * @param string $tablenam
	 * @param boolean $force
	 * @return boolean|array
	 */
	protected function paramId(array $params, $tablenam, $force = false){
		$parameter = array_key_exists($tablenam."id", $params) ? $tablenam."id" 
			: (array_key_exists("id", $params) ? "id" : "");
		if(!$parameter){
			\Encoder\Log::onlog('checkParamId: '.$tablenam, ['full'=>1]);
			return FALSE;
		}

		if($force){
			$id = $params[$parameter];
		} else {
			/** @noinspection SqlResolve */
			$id = \db::val("SELECT id FROM $tablenam WHERE id=".$params[$parameter]);
			/** @noinspection end */
		}
		$data = [];
		if($id){
			/** @noinspection SqlResolve */
			$data = \db::row("SELECT * FROM $tablenam WHERE id=$id");
			/** @noinspection end */
		} else {
			$id = 0;
		}



		return ['id' => $id, 'data' => $data];
	}

	/**
	 * если по фильтрам записи есть, а выборка дала 0 строк, номер страницы выборки пересчитывается
	 * @param string $qry
	 * @param array $params
	 * @param string $defsort
	 * @param bool $isprop
	 * @param int $filtered
	 * @return array
	 */
	protected function rePage($qry, $params, $defsort, $isprop, $filtered){
		$params['page'] = ceil($filtered/$this->onpage);
		$this->pageRecounted = true;
		return $this->getrows($qry, $params, $defsort, $isprop);
	}

	/**
	 * получить строку
	 * @param $rowid
	 * @param bool $isprop
	 * @return array
	 */
	protected function getRowData($rowid, $isprop=false){
		$prev = $this->useCalcRows;
		$this->useCalcRows = false;
		$a = $this->addfilters2qry($this->qry, array('rowid'=>$rowid), '', $isprop);
		$this->useCalcRows = $prev;

		if(!$a)
			return Array();

		/** @noinspection SqlResolve */

		// при наличии _rowDataSpecialFilter уникальный фильтр добавляется в запрос
		$qry = $this->_rowDataSpecialFilter ? $a['qry'] : "SELECT * FROM ($a[nolim])t WHERE t.rowid=$rowid";
		$answ= \db::row($qry);

		/** @noinspection end */

		$method = $isprop ? 'propAnswProcess' : 'answProcess';
		if(method_exists($this, $method) and $answ){
			$answ= $this->$method(array($answ));
			$answ= $answ[0];
		}

		return array_values($answ);
	}

	/**
	 * метод для выборки содержимого
	 * @param string $qry
	 * @param array $params
	 * @param string $defsort
	 * @param bool $isprop
	 * @return array
	 */
	protected function getrows($qry, $params, $defsort, $isprop=false){
		$arr = $this->addfilters2qry($qry, $params, $defsort, $isprop);
// 		$filtered = \db::val($arr['filteredqry']);
		$contain = \db::arr($arr['qry']);
		$filtered = \db::val($this->useCalcRows ? 'SELECT FOUND_ROWS()' : $arr['filteredqry']);
//showrw($arr['qry']);
		if($filtered and !$contain and !$this->pageRecounted)
			return $this->rePage($qry, $params, $defsort, $isprop, $filtered);

		$method = $isprop ? 'propAnswProcess' : 'answProcess';
		if(method_exists($this, $method))
			$contain = $this->$method($contain);

		if(!$isprop)
			$rowlen = count($this->cols)+ count($this->hidden);
		else
			$rowlen = count($this->propcols)+ count($this->prophidden);

		$answ = Array();
		for($i=0; $i<count($contain); $i++){
			$answ[$i]=Array();
			$lencnt = 0;
			foreach ($contain[$i] as $nam=>$val)
				if(++$lencnt>$rowlen+2)
					break;
				else {
					\Encoder\Coder::cleanData($val);
					$answ[$i][]=$val;
				}
		}

		$answ = Array(
			'filtered'		=>	$filtered
			,'contain'		=>	$answ
			,'timer'		=>	$this->CKtime()
		);

		if($this->bottomElse)
			$answ['bottomelse'] = $this->bottomElse;

		if($this->pageRecounted)
			$answ['setpage'] = $this->page;
		
		$method = $isprop ? 'extraPropAnswProcess' : 'extraAnswProcess';
		if(method_exists($this, $method))
			$answ = $this->$method($answ);

		return $answ;
	}

	/**
	 * @param $params
	 * @param array $joinsFor
	 * @return array|string
	 */
	protected function getFilters($params, array &$joinsFor){
		$filternams = $this->getsets('filter', 'id', 1, true);
		$ftnams = $this->getsets('filter', 'id', 1);
		foreach ($ftnams as $ftnam){
			if(isset($params['staticFilter'])){
				if(isset($params['staticFilter'][$ftnam])){
					if(!is_array($this->staticFilter))
						$this->staticFilter = Array();
					$this->staticFilter[$ftnam] = $params['staticFilter'][$ftnam];
					$joinsFor[] = $ftnam;
				}
				if(isset($params['staticFilter'][$ftnam."_text"])){
					if(!is_array($this->staticFilter))
						$this->staticFilter = Array();
					$this->staticFilter[$ftnam."_text"] = $params['staticFilter'][$ftnam."_text"];
					$joinsFor[] = $ftnam."_text";
				}
			}
			if(method_exists($this, 'textfilter'.$ftnam)){
				$filternams[] = $this->entity."filter{$ftnam}_text";
			}
		}

		if($this->staticFilter){
			$filters = Array();
			foreach ($this->staticFilter as $nam=>$val)
				$filters[$this->entity.'filter'.$nam] = $val;
		}else
			$filters = \Encoder\Coder::coding(\Encoder\Opt::getopts($filternams), 1, 0) ;

		// если фильтр в еденичном экз., то рез. вернется в ед. экземпляре, преобразуем его в массив
		if(count($filternams)==1 and !is_array($filters))
			$filters = Array($filternams[0]=>$filters);
		if(isset($params['propfilters'])){
			if(!$this->decodeIncomParams)
				$params['propfilters'] = \Encoder\Coder::coding($params['propfilters'],1,0);
			$possiblePropFilterNams = $this->getsets('filter', 'id', 0);
			foreach ($possiblePropFilterNams as $ftnam){
				if(method_exists($this, 'textfilter'.$ftnam)){
					$possiblePropFilterNams[] = "{$ftnam}_text";
				}
			}
			$possiblePropFilterNams = array_merge($possiblePropFilterNams, $this->allowFilters);
			foreach ($params['propfilters'] as $pfid=>$pfval){
				if($pfval and ($this->filterNameNoCheck or in_array($pfid, $possiblePropFilterNams) or in_array($pfid, $this->allowAdditFilterNames))){
					if(substr($pfid, -5)!='_text'){
						$meth = 'textfilter'.substr($pfid, 0, -5);
						if(method_exists($this, $meth)){
							if( !($pfval= $this->$meth($pfval, $params['propfilters'])) )
								continue;
						}
					}
					$fldnam = $this->entity.'filter'.$pfid;
					$joinsFor[] = $pfid;
					$filters[$fldnam] = $pfval;

					//$pfval = trim($pfval);
					// отделение фильтров по тексту АС
				}
			}
		}

		// +++
		foreach ($filters as $fkey => $fval){
			$key = substr($fkey, strlen($this->entity."filter"));
			$joinsFor[] = $key;
		}

		return $filters;
	}

	/**
	 * добавляет фильтры к запросу $qry исходя из текущих фильтров хранимых в базе
	 * и тех что проходят с запросом. Добавляет при необходимости JOIN-ы
	 * @param string $qry
	 * @param array $params
	 * @param boolean $isprop
	 * @return array
	 */
	protected function addFilters($qry, $params, $isprop){
		// получение фильтров из базы/запроса  строки с полными именами
		$joinsFor = $filters = Array();
		if(!$isprop and !$this->skipFilters && !isset($params['rowid'])) {
			$filters = $this->getFilters($params, $joinsFor);
		}
		$qry = $this->addJoinsToQry($qry, $joinsFor);
		// преобразование полных имен в сокращенные, экранирование значений для запроса
		$mfarr = $flttoapply = $fltincom = Array();
		foreach($filters as $nam => $val){
			$fltnam = substr($nam,strlen($this->entity.'filter'));

			// фильтры заканчивающиеся на "_text" являются фильтраму по тексту АС,
			// для их работы необходимо наличие метода c именем соответствующим
			// названию поля - 'textfilter%имя%', где %имя% - название поля. Метод
			// получает значение текста и массив фильтров с адаптироваными именами
			if(substr($fltnam, -5)=='_text'){
				$nam = substr($fltnam, 0, -5);
				$txtMeth = 'textfilter'.$nam;
				// если еще нет массива с адаптироваными названиями фильтров, он создается
				if(!$mfarr)
					foreach($filters as $cnam => $cval)
						$mfarr[substr($cnam, strlen($this->entity.'filter'))] = $cval;

				// если нет значения id по соотв полю, результат выполнения метода
				// вставляется в запрос без изменений, если он не пустая строка
				if(
					method_exists($this, $txtMeth)
					and (!isset($mfarr[$nam]) or !$mfarr[$nam])
					and $val = $this->$txtMeth($val, $filters)
				)
					$flttoapply[]= $val;

				// если соотв метода нет, фильтр не используется
				continue;
			}


// 			// если были установлены особые фильтры в массив staticFilter, то они используются
// 			if(isset($this->staticFilter[$fltnam]))
// 				$val = $this->staticFilter[$fltnam];

			// дальнейшая адаптация имени фильтра для вставки в запрос - замена "_" на "."
			// и наполнение массива $fltincom - предварительного контейнера фильтров
			if($val !== "")
				$fltincom[str_ireplace('_','.', $fltnam)] = $val;
		}

		// если в наследуемом классе был определен метод filtersProcess, то он вызывается
		// с параметром - предварительным контейнером фильтров. Чтобы эффект изменения возымел
		// действие, метод должен принимать параметр ссылкой
		if(method_exists($this, 'filtersProcess'))
			$this->filtersProcess($fltincom);

		if($this->_rowDataSpecialFilter && isset($params['rowid']) && $params['rowid'])
			$flttoapply[] = "$this->_rowDataSpecialFilter = '$params[rowid]'";

		// преобразование предварительного контейнера в массив вставки в запрос
		// и объединение со значениями сформироваными текстовыми фильтрами
		$flttoapply = array_merge($flttoapply, $this->_preapareFilters($fltincom));

		// фильтры вставляются в конец запроса, для этого надо учесть присувствие ключевого слова
		// WHERE в запросе, флаг qryForceWhere указывает на принудительную вставку/пропуск слова
		// если он не установлен, то вставка определяется наличием этого слова в запросе
		if($this->qryForceWhere === true)
			$conds = ' WHERE ';
		elseif($this->qryForceWhere === false)
			$conds = ' AND ';
		else
			$conds = stripos($qry, 'WHERE') ? ' AND ' : ' WHERE ';
		// если фильтры есть, то они добавляются
		$conds = $flttoapply ? ($conds . implode(" AND ", $flttoapply)) : '';
		return array('qry'=>$qry, 'conds'=>$conds, 'applyfilter'=>$flttoapply, 'filters'=>$filters);
	}

	/**
	 * определяет по входящим данным пределы выборки (постраничку)
	 * @param array $params
	 * @param int $defOnPage кол-во записей, если не найдены параметры
	 * @param $params
	 * @param int $defOnPage
	 * @return string
	 */
	public function getLimit($params, $defOnPage=20){
		$this->onpage = (isset($params['onpage']) and $params['onpage']>0) ? ((integer)$params['onpage']) : $defOnPage;
		$this->page =  (isset($params['page']) and $params['page']>0) ? ((integer)$params['page']) : 1;
		$answ = " LIMIT ".($this->page-1)*$this->onpage.",".$this->onpage;
		return $answ;
	}

	/**
	 * удаляет все поля кроме rowid из запроса
	 * @param $qry
	 * @return array
	 */
	protected function qryRemoveFields($qry){
		$pos1= strpos($qry, 'rowid');
		$pos2= strpos($qry, 'FROM');
		if($pos1 > 0 && $pos2 > 0){
			$sel= substr($qry, 0, $pos1+5);
			$qryd= substr($qry, $pos2);
			return array($sel, $qryd);
		} else
			return array();
	}

	/**
	 * согласно параметрам, добавляет к запросу фильтры, объединения, группировки, сортировки
	 * и постраничное ограничение, возвращает массив с полным запросом, запросом
	 * без постранички и сортировок, кол-м отобраных записей, кол-м примененных фильтров
	 * @param string $qry
	 * @param array $params
	 * @param string $defsort
	 * @param boolean $isprop
	 * @return array|bool
	 */
	protected function addfilters2qry($qry, $params, $defsort, $isprop=false){
		if(!$qry)
			return false;

		// добавление постранички
		$limit = $this->getLimit($params);
// 		if($isprop){
			$owner = (isset($params['owner']) and $params['owner']) ? intval($params['owner']) : '';
			$qry = str_replace('%owner%', $owner, $qry);
// 		}

		if($this->useCalcRows){
			$qry = explode(' ', $qry, 2);
			$qry = "SELECT SQL_CALC_FOUND_ROWS $qry[1]";
		}

		// добавление фильтров
		if ($this->short) {
			$nolim = $qry = str_ireplace('%externjoins%', '', $qry);

			$qry.= $this->addSorts($defsort, $isprop);

			if(!$isprop) {
				/** @noinspection SqlResolve */
				$nolim = $qry = "SELECT * FROM ($qry) t WHERE rowid IN('"
					. implode("','", $this->viewids) . "')";
				/** @noinspection end */
			}

			/** @noinspection SqlResolve */
			$filteredQry = "SELECT COUNT(rowid) FROM ($qry) totals";
			/** @noinspection end */
			return Array(
				'qry'			=>	$qry.$limit//.$this->addSorts($defsort, $isprop)
				,'filteredqry'	=> $filteredQry
				,'nolim'		=>	$nolim
			);
		}

		// добавление фильтров, дополнительных JOIN-ов к запросу
		$arr = $this->addFilters($qry, $params, $isprop);
		if(strpos($arr['qry'], '%conds%'))
			$qry = str_replace('%conds%', $arr['conds'], $arr['qry']);
		else
			$qry = $arr['qry'].$arr['conds'];

		// поскольку группировка указывается после WHERE, вместо запроса, поля группировки
		// хранятся в поле класса group и добавляется к запросу после  добавления WHERE
		$grp = ($isprop) ? $this->propGroup : $this->group;
		if(count($grp))
			$qry.= " GROUP BY ".implode(", ", $grp);

		// количество оставшихся записей после фильтрации (до применения постранички и сортировки)
		$arrs= array();
		if($this->removeFields and !$this->useCalcRows)
			$arrs= $this->qryRemoveFields($qry);
		if(count($arrs) > 0) {
			/** @noinspection SqlResolve */
			$filteredqry = "SELECT COUNT(rowid) FROM (" . $arrs[0] . " " . $arrs[1] . ") totals";
			/** @noinspection end */
		}	else {
			/** @noinspection SqlResolve */
			$filteredqry = "SELECT COUNT(rowid) FROM ($qry) totals";
			/** @noinspection end */
		}

		// также возвращается запрос без ограничения постраничкой для общих операций
		$nolimqry = $qry;

		// добавление поля и направления сортировки
		$qry.= $this->addSorts($defsort, $isprop);

		$a= Array(
			'qry'			=>	$qry.$limit
			,'filteredqry'	=>	$filteredqry
			,'nolim'		=>	$nolimqry
		);
		return $a;
	}

	/**
	 * замена имени поля в массиве для унификации работы функций
	 * @param $oldNam
	 * @param $newNam
	 * @param $params
	 * @return mixed
	 */
	public function replaceFieldName($oldNam, $newNam, &$params){
		foreach($params as $nam=>$val)
			if($nam == $oldNam){
				$params[$newNam] = $val;
				unset($params[$nam]);
			}else if($nam == "old$oldNam"){
				$params["old$newNam"] = $val;
				unset($params[$nam]);
			}
		return $params;
	}

	/**
	 * создает часть запроса-сортироку, исходя из установленой в базе сортировки и направлении
	 * @param string $defsort
	 * @param bool $isprop
	 * @return string
	 */
	protected function addSorts($defsort, $isprop){
		$suff = $isprop?'prop':'ent';
		$sortnams = Array(
			$this->entity. 'sort'.$suff.'field',
			$this->entity. 'sort'.$suff.'dir',
		);
		$sorts = \Encoder\Opt::getopts($sortnams);

		$sortfld = substr($sorts[$sortnams[0]], 4);
		$dir = $sorts[$sortnams[1]];
		$dir = ($dir=="up") ? " DESC" : '';
		$sortfld = $sortfld ? $sortfld : $defsort;

		if(isset($this->extendSort[$sortfld])){
			$sortfld = $this->extendSort[$sortfld];
		}else{
			$ableSorts = $this->getsets('', 'sortid', $isprop);
			if(!in_array($sortfld, $ableSorts))
				 $sortfld = $defsort;
			$sortfld = str_replace('_', '.', $sortfld);
		}

		$sortfld= trim($sortfld);
		if(strpos($sortfld, "%dir%") !== FALSE){
			$sortfld= str_replace("%dir%", $dir, $sortfld);
		} elseif($sortfld) {
			$sortfld= "$sortfld $dir";
		}

if(method_exists($this, 'customSort')){
	$sortfld = $this->customSort(str_replace('_', '.', $sortfld), $dir, $sortfld);
}
		$answ = trim($sortfld) ? " ORDER BY $sortfld" : '';
		return $answ;
	}

	/**
	 * исходя из данных введенных с помощью метода externJoins, вставляет JOIN -ы
	 * @param $qry
	 * @param $fields
	 * @return mixed|string
	 */
	public function addJoinsToQry($qry, $fields){
		$joins = Array();
		for($i=0; $i<count($this->joins); $i++)
			if(in_array($this->joins[$i][0], $fields)){
				if(is_array($this->joins[$i][1])){
					$joins= array_merge($joins, $this->joins[$i][1]);
				} else
					$joins[] = $this->joins[$i][1];
			}

		$joins = implode("\n", array_unique($joins));
		$newqry = str_ireplace('%externjoins%', $joins, $qry);
		if($newqry==$qry and $joins)
			$newqry.= ' '.$joins;

		return $newqry;
	}

	/**
	 * сброс всех фильтров данного пользователя в текущем редакторе
	 */
	public function clearFilters(){
		$fltNams = $this->entity.'filter';
		$qry = "DELETE usr_opt
			FROM usr_opt
			INNER JOIN opt ON usr_opt.optid=opt.id
			WHERE usrid=".\Encoder\Opt::$id." AND nam LIKE '%$fltNams%'";
		if(\db::query($qry))
			\Encoder\Opt::flushCache();
	}

	/**
	 * подготовка фильтров перед вставкой в запрос
	 * @param $fltincom
	 * @return array
	 */
	private function _preapareFilters($fltincom){
		// поля для которых поиск будет выполняться по частичному совпадению
		$context = (isset($this->params['contextsearch'])) ? $this->params['contextsearch'] : Array();
		$flttoapply = Array();
		for($i=0; $i<count($context); $i++)
			$context[$i] = str_ireplace('_','.',$context[$i]);

		foreach($fltincom as $nam => $val){
			$method = 'extendFilter'.str_ireplace('.','_',$nam);
			if(method_exists($this, $method)) {
				$val = $this->$method($val, $fltincom);
				if($val)
					array_push($flttoapply, $val);
			}elseif (isset($this->extenFilters[$nam])) {
				if($this->extenFilters[$nam])
					array_push($flttoapply, '(' . $this->extenFilters[$nam]." '$val')");
			}elseif (stripos($nam,'dat')){
				$pref = '';
				if(substr($nam,0,2)=='st')
					$pref = 'st';
				elseif(substr($nam,0,3)=='end')
					$pref = 'end';

				$time = '';
				if(stripos($nam,'datt') and count(explode(' ', $val))==1 )
					$time = $pref=='end' ? " 23:59:59" : " 00:00:00";
				$val = "'$val"."$time'";
				$nam = substr($nam, strlen($pref));

				if($this->dateAsPeriod)
					$flt = ($pref=='end') ? "IF(st$nam, st$nam<$val, 1)" : "IF(end$nam, end$nam>$val, 1)";
				else
					$flt = "$nam ".($pref=='end' ? '<=' : '>=')." $val";
				array_push($flttoapply, $flt);
			}elseif (in_array(str_ireplace('.','_',$nam), $this->listFilters)){
				$arr = \Online\Helpers\ArrayHelper::makeArray($val);
				foreach ($arr as &$v)
					if(!($v = intval($v)))
						unset($v);
				if($arr)
					array_push($flttoapply, $nam. ' IN('.implode(',', $arr).')');

			}else{// если поле заканчивается на 'id' или num, применяется точный поиск, иначе контекстный
				if(in_array(str_ireplace('.','_',$nam), $this->strictFilterNams) || $this->isStrictFilter($nam, $fltincom))
					$flt = "$nam = '".$val."'";
				else
					$flt = "$nam LIKE '%".str_replace(Array('_','%'),Array('\_','\%'),$val)."%'";

				array_push($flttoapply, $flt);
			}
		}
		return $flttoapply;
	}

	/**
	 * @param $field
	 * @param $filters
	 * @return bool
	 */
	private function isStrictFilter($field, $filters){
		$typ = $answ = false;
		$field = str_replace('.', '_', $field);

		$relys = array_merge($this->ent_ent_rely, $this->ent_prop_rely, $this->prop_prop_rely);
		foreach($relys as $i=>$rel)
			if($rel[1]==$field){
				$masterName = $rel[0];
				$masterValue = isset($filters[$masterName]) ? $filters[$masterName] : 0;
				$typ = isset($rel[2][$masterValue]) ? $rel[2][$masterValue] : $rel[2][0];
			}

		if(!$typ){
			$typs = $this->getsets('filter', false, 1);
			foreach($typs as $i=>$colsets)
				if(isset($colsets['id']) and $colsets['id']==$field){
					$typ = $colsets['typ'];
					break;
				}
		}
		if($typ=='sel' or $typ=='chkb' or $typ=='chkb3' or $typ=='ac')
			$answ = true;
		elseif($typ=='inp'){
			$l2 = substr($field,strlen($field)-2,2);
			$l3 = substr($field,strlen($field)-3,3);
			$answ = (in_array($l2,Array('id','sm')) or in_array($l3,Array('num','cnt','sum')));
		}
		return $answ;
	}

	/**
	 * @return string
	 */
	private function getImgPath(){
		return \APP::getConfig()->getPathPublicURL()."/Tabloid/img/";
	}

	/**
	 * возвращает настройки редактора для клиентского уровня
	 * return ассоциативный массив в формате JSON
	 * @return array
	 */
	private function gettabloidsets(){
		$dtoverride = Array();
		$colnams = $this->getsets('contain', 'id');
		$dt = $this->getsets('contain', 'dt');
		for($i=0; $i<count($dt); $i++) if($dt[$i] and $colnams[$i]) $dtoverride[$colnams[$i]] = $dt[$i];

		$pdtoverride = Array();
		$colnams = $this->getsets('contain', 'id', true);
		$dt = $this->getsets('contain', 'dt', true);
		for($i=0; $i<count($dt); $i++)
			if($dt[$i] and $colnams[$i])
				$pdtoverride[$colnams[$i]] = $dt[$i];

		$srtnams = Array(
			$this->entity.'sortentdir'
			,$this->entity.'sortentfield'
		);

		if(count($this->propcols)){
			$srtnams[] = $this->entity.'sortpropdir';
			$srtnams[] = $this->entity.'sortpropfield';
		}

		$srts = \Encoder\Opt::getopts($srtnams);
		$sorts = Array();
		foreach ($srts as $nam=>$val){
			$fldnam = substr($nam, strlen($this->entity));
			$sorts[$fldnam] = $val;
		}
		

		
		$timer = 0;

		return 	Array(
			'rowSets'		=>	$this->getsets('contain', 'typ')
			,'propRowSets'	=>	$this->getsets('contain', 'typ', true)
			,'leadval'		=>	$this->ent_ent_rely
			,'propleadval'	=>	$this->prop_prop_rely
			,'entleadprop'	=>	$this->ent_prop_rely
			,'valNams'		=>	array_merge($this->getsets('contain', 'id'), $this->hidden)
			,'propValNams'	=>	array_merge($this->getsets('contain', 'id', true), $this->prophidden)
			,'emptyInsert'	=>	$this->allowedEmptyInsert
			,'emptyContain'	=>	$this->allowEmptyContain
			,'descrLen'		=>	$this->descrLen
			,'propDescrLen'	=>	$this->propDescrLen
			,'dtOverride'	=>	$dtoverride
			,'propDtOverride'=>	$pdtoverride
			,'translate'	=>	$this->translateParams
			,'sorts'		=>	$sorts
			,'history'		=>	$this->historyParams
			,'cashtimer'	=>	$timer
			,'staticFilter'	=>	$this->staticFilter
			,'imgpath' => $this->getImgPath()
		);
	}

	/**
	 * выборка набора списков
	 * @param $params
	 * @return array
	 */
	public function getselpack($params){
		$answ = Array();
		$answ['indep'] = Array();
		$gfather = (isset($params['owner'])) ? ($params['owner']) : (-1);
		$caller = (isset($params['caller'])) ? ($params['caller']) : (-1);

		foreach($params as $nam=>$val){
			if($nam=='indep') for($i=0; $i<count($val); $i++)
				$answ['indep'][$val[$i]] = $this->getsel($val[$i]);
			else if(is_array($val)) for($i=0; $i<count($val); $i++) {
				if(!isset($answ[$nam])) $answ[$nam] = Array();
				$answ[$nam][$val[$i]] = $this->getsel($nam, $val[$i], $gfather, $caller);
			}
		}
		return $answ;
	}

	/**
	 * ретрансляция данных на клиентский уровень, переданый массив на клиентском уровне будет
	 * находиться в engine.params
	 * @param array $params
	 */
	public function setTranslateParams($params){
		$this->translateParams = $params;
	}

	/**
	 * установка размеров сокращений строк для ячеек с типом descr. устанавливает длину
	 * последовательно
	 * @param array/string $lens длины
	 * @param bool $isprop для доп таблицы
	 */
	public function setDescrLen($lens, $isprop=false){
		$lens = \Online\Helpers\ArrayHelper::makeArray($lens);
		if($isprop)
			$this->propDescrLen = $lens;
		else
			$this->descrLen = $lens;
	}

	/**
	 * формирование html содержимого ячейки, согласно установленым типам ячеек и зависимостям
	 * @param array $params
	 * @return array
	 */
	public function getcell($params){
		$prop = (isset($params['prop']) and $params['prop']=='true');
		$relyarr = ($prop) ? (array_merge($this->prop_prop_rely,$this->ent_prop_rely))
			: $this->ent_ent_rely;
		$valnam = substr($params['cellid'], 4);
		$masterval = $params['father'];
		$parent = (isset($params['gfather'])) ? $params['gfather'] : -1;
		$caller = (isset($params['caller'])) ? $params['caller'] : -1;
		$typ='sel';
		foreach($relyarr as $rel)
			if ($rel[1] == $valnam) {
				$typ = isset($rel[2][$masterval]) ? $rel[2][$masterval] : $rel[2][0];
				break;
			}

		$fc = isset($params['forcecelltyp']) ? $params['forcecelltyp'] : '';
		if(in_array($fc, Array('sel', 'ac', 'inp', 'nobr', 'chkb', 'chkb3')))
			$typ = $fc;

		$relyarr = ($prop) ? $this->prop_prop_rely : $this->ent_ent_rely;
		$onchange = Array();
		foreach($relyarr as $rel)
			if ($rel[0]==$valnam)
				$onchange[]= 'loadCell("cell'.$rel[1].'", this.value, this.name);';

		$onchange = $onchange ? (" onchange='" . implode(' ', $onchange)."'") : '';
		$val = '';
		if($typ=='chkb' or $typ=='chkb3' or $typ=='nobr' or $typ=='inp')
			$val = $this->getselqry($valnam, $masterval, $parent, $caller);
		$answ = Array(
			'html' => $this->formCellHtml($typ, $valnam, $valnam, $val, $onchange, $masterval, $parent)
			,'cellid' => $params['cellid']
		);
		if($typ=='ac')
			$answ['ac']= 1;
		return $answ;
	}

	/**
	 * сохранение нескольких строк
	 * @param $params
	 * @param bool $extData
	 * @return array|int|string
	 */
	public function saveall($params, $extData=false){
		$owner = false;
		$cnt = 0;

		foreach($params as $nam=>$val)
			if(!is_array($val)){
				if($nam=='owner') $owner = $val;
			}else if(!$cnt)
				$cnt=count($val);

		if(!$cnt)
			return $this->error('Изменения не найдены');

		$eparams = [];
		for($i=0; $i<$cnt; $i++){
			$vals = array();
			foreach ($params as $nam=>$val)
				if(!is_array($val) and $nam!='owner')
					$vals[$nam] = $val;
				else if(isset($val[$i]))
					$vals[$nam] = $val[$i];
			if($owner)
				$vals['owner'] = $owner;

			$eparams[] = $vals;
		}

// 		if(count($eparams)==1)
// 			return isset($eparams[0]['owner']) ? $this->saveprop($eparams[0]) : $this->save($eparams[0]);

		$answ = [];
		$saved = 0;
		foreach ($eparams as $vals){
			$sAnsw = isset($vals['owner']) ? $this->saveprop($vals) : $this->save($vals);
			$success = $this->answIsGood($sAnsw);

			if($extData)
				$answ[]= ['params' => $vals, 'result' => $sAnsw, 'code' => $success];
			if($success){
				$saved++;
			}
		}

		return $extData ? $answ : $saved;
	}

	/**
	 * аналог addcol, объект колонки создает самостоятельно
	 * @param int $id
	 * @param string $title
	 * @param string $width
	 * @param mixed $contain
	 * @param mixed $add
	 * @param mixed $filter
	 * @param bool $isprop
	 */
	public function newcol($id, $title, $width='', $contain=true, $add=true, $filter=true, $isprop=false){

		$col = new col($id, $title, $width, $contain, $add, $filter);
		$this->addcol($col, $isprop);
	}

	/**
	 * @param $id
	 * @param $title
	 * @param string $width
	 * @param bool $contain
	 * @param bool $add
	 * @param bool $filter
	 */
	public function newPropCol($id, $title, $width='', $contain=true, $add=true, $filter=true){
		$col = new col($id, $title, $width, $contain, $add, $filter);
		$this->addcol($col, true);
	}

	/**
	 * добавление колонки в редактор
	 * @param $col
	 * @param $prop
	 */
	public function addcol($col, $prop=false){
		if ($prop)
			$this->propcols[] = $col;
		else
			$this->cols[] = $col;
	}

	/**
	 * установка зависимости одной ячейки от другой. при этом надо учитывать что это
	 * влияет на ячейки добавления и содержимого (включая скрытые значения), для того чтобы
	 * разграничить зависимости, необходимо давать им разные названия
	 * @param string $master_col_nam название ведущего значения
	 * @param string $slave_col_nam название ведомого значения
	 * @param bool $master_isprop принадлежность ведущего значения к нижней таблице
	 * @param bool $slave_isprop принадлежность ведомого значения к нижней таблице
	 * @param array $types зависимости типа ведомой ячейки от ведущего значения
	 */
	public function setRely($master_col_nam, $slave_col_nam, $master_isprop=false, $slave_isprop=false, $types=Array(0=>'sel')){
		$params = Array($master_col_nam, $slave_col_nam, $types);
		if (!$master_isprop and !$slave_isprop) $this->ent_ent_rely[] = $params;
		else if ($master_isprop and $slave_isprop) $this->prop_prop_rely[] = $params;
		else $this->ent_prop_rely[] = $params;
	}

	/**
	 * добавление к строке неотображаемых параметров. в запросе они должны выбираться
	 * в том же порядке что и указываются
	 * @param $nams
	 * @param $prop
	 */
	public function addhidden($nams, $prop=false){
		$nams = \Online\Helpers\ArrayHelper::makeArray($nams);
		for($i=0; $i<count($nams); $i++)
			if($prop) $this->prophidden[]=$nams[$i];
			else $this->hidden[]=$nams[$i];
	}

	/**
	 * формирование html списка исходя из параметров. значения для списка берутся из
	 * метода getselqry, который должен выдавать значения для всех списков, либо зависимых
	 * ячеек, которые должны содержать какую-либо инфу
	 * @param string $id имя ячейки
	 * @param int $father значение ведущей ячейки (если это запрос ведомого)
	 * @param int $gfather значение основной строки (если это запрос ведомого доп. таблицы)
	 * @param string $selected контекст поиска для AC
	 * @return string
	 */
	public function getsel($id, $father=-1, $gfather=-1, $selected=''){
		$qry = $this->getselqry($id, $father, $gfather);
		if (!$qry)
			return '';
		if(is_array($qry))
			$opts = $qry;
		else{
			if(!stripos($qry, 'ORDER'))
				$qry.= ' ORDER BY nam';
			$opts = \db::arr($qry);
		}

		if(!$selected)
			$selected = $this->triggerSelectListItem;

		$strict= $this->triggerSelectListEmpty;
		$this->triggerSelectListEmpty= 0;
		$this->triggerSelectListItem = 0;
		return \Online\Helpers\FormHelper::selectOptions($opts, $strict, $selected);
	}

	/**
	 * выборка значений для AC
	 * @param array $params
	 * @return array|string
	 */
	public function getac(array $params){
		if(!$this->decodeIncomParams)
			$params = \Encoder\Coder::coding($params, 1, 0);
		$q = isset($params['q']) ? $params['q'] : '';
		$q = preg_replace("/\s+/", '%', $q);
		$father = (isset($params['father'])) ? $params['father'] : -1;
		$gfather = (isset($params['gfather'])) ? $params['gfather'] : -1;

		$data = $this->getselqry($params['nam'], $father, $gfather, $q);

		if(!$data)
			return '';
		if(!is_array($data)) {
			$qry = $data;
// 			$data = \db::arr($qry);
$data = '';
			//*sk для сокращения списка автокомплита
			if ( ($spos = stripos($qry, 'SELECT')) !== FALSE ) {
				$qry = substr($qry, $spos, 6).' SQL_CALC_FOUND_ROWS'.substr($qry, ($spos+6));
				$ltpos = strripos($qry, 'LIMIT');
				$lim = (isset($params['limit']) and $l = intval($params['limit']) and $l>0) ? $l : 100;
				if ( $ltpos === FALSE ){
					$qry .= ' LIMIT ' . $lim;
				}elseif ( $ltpos !== FALSE ) {
					$k=0;
					$sbstr = substr($qry, ($ltpos+5));
					for($i=0; $i<strlen($sbstr); $i++) {
						if ($sbstr[$i] == '(')
							$k++;
						elseif ($sbstr[$i] == ')')
							$k--;
					}
					if($k)
						$qry .= ' LIMIT ' . $lim;
				}
				if ( $data = \db::arr($qry) and $total = \db::val('SELECT FOUND_ROWS()') )
					$data['total'] = $total;
			}
		}

		return $data;
	}

	/**
	 * вызывается при запросе на формирование HTML редактора, проверяет наличие необходимых фильтров
	 * при их отсутствии добавляет, удаляет некорректные, сигнализирует в текстовый лог
	 */
	private function checkFilters(){
		$filternams = $this->getsets('filter', 'id', false, 1);

		if(false !== ($ii = array_search($this->entity.'filter', $filternams))){
			unset($filternams[$ii]);
			sort($filternams);
		}

		// по полям по которым предусмотрен контекстный поиск (наличие метода textfiter... )
		// также должно быть поле
		$arr = $this->getsets('filter', 'id');
		foreach ($arr as $fld)
			if(method_exists($this, 'textfilter'.$fld))
				$filternams[] = $this->entity . 'filter'.$fld.'_text';

		$filternams[] = $this->entity . "sortentdir";
		$filternams[] = $this->entity . "sortentfield";
		if (count($this->propcols)){
			$filternams[] = $this->entity . "sortpropdir";
			$filternams[] = $this->entity . "sortpropfield";
		}
		$fltCnt = count($filternams);

		$qry = "SELECT id, nam FROM opt WHERE nam IN('".implode("','", $filternams)."')";
		$dbExists = \db::select($qry);

		$dbCnt = count($dbExists);

		if($dbCnt!=$fltCnt){
			$skipIds = implode(',', array_keys($dbExists));
			$qry  = "SELECT id FROM opt
				WHERE (nam LIKE '".$this->entity."filter%' OR nam LIKE '".$this->entity."sort%')";
			if($skipIds)
				$qry.= " AND id NOT IN($skipIds)";
			$delIds = implode(',', \db::col($qry));

			if(!$this->editorName)
				$this->editorName = get_class($this);
			$inserts = $this->showOptTyp($this->editorName);
			$added = \db::query($inserts);
			$txt = "В редакторе $this->editorName найдены не все фильтры. Добавлено фильтров - $added";

			\Online\Helpers\sys\show('БД: ' . implode(', ', $dbExists));
			\Online\Helpers\sys\show('в фильтрах :' . implode(', ', $filternams) );

			if($delIds){
				$qry = "DELETE FROM opt WHERE id IN($delIds)";
				$opts = \db::query($qry);
				if($opts)
					$txt.= "\nУдалено некорректных фильтров - $opts";

				$qry = "DELETE FROM usr_opt WHERE optid IN($delIds)";
				$links = \db::query($qry);
				if($links)
					$txt.= "\nУдалено ссылок на некорректные фильтры - $links";
			}
			\Encoder\Log::logg($txt, false);
		}
	}

	/**
	 * формирование html таблицы исходя из установленных параметров
	 * @param int $otherInfo
	 * @return string
	 */
	public function getHTML($otherInfo=0){
		$this->checkFilters();

		// трансляция инициализационных переменных для JS
		$answ = '<script>var entity="'.$this->entity.'";var handler ="'.$this->handler
			.'";var shortview="'.implode(',',$this->viewids).'";
			var tabloidSets = ' . \Online\Helpers\JSONHelper::json($this->gettabloidsets()) . ';';
		$answ.= '</script>';

		// формирование HTML кода
		if (count($this->propcols)) {

			$pdispl = $otherInfo ? 'style="display: inline-block;"' : '';
			$otherInfo = $otherInfo ? '<div style="display: inline-block;" id="otherinfo"></div>' : '';

			$answ.=
				'<div id="tablescontainer">'.
					'<table id="entcontainer"><tr>'.
						'<td id="entsubcontainer">'.$this->form_table($this->cols).'</td>'.
						'<td id="minfo"></td>'.
					'</tr></table>'.
					'<div id="propcontainer" '.$pdispl.'>'.$this->form_table($this->propcols, true) . $otherInfo . '</div>'.
				'</div>';

// 				'<div id="tablescontainer">'.
// 					'<div id="entcontainer"><div id="entsubcontainer">'
// 						.$this->form_table($this->cols).'</div></div>'.
// 					'<div id="propcontainer" '.$pdispl.'>'.$this->form_table($this->propcols, true) . $otherInfo . '</div>'.
// 				'</div>';
		}else
			$answ.= $this->form_table($this->cols);

		return $answ;
	}

	/**
	 * проверка на наличие во входящем массиве всех запрашиваемых данных
	 * @param $params
	 * @param $mustHave
	 * @param bool $autoExit
	 * @return bool
	 */
	public function paramsHas($params, $mustHave, $autoExit=false){
		$mustHave = \Online\Helpers\ArrayHelper::makeArray($mustHave);
		$answ = true;
		foreach($mustHave as $nam)
			if(!isset($params[$nam])){
				$answ = false;
				break;
			}

		if($autoExit and !$answ){
			echo \Online\Helpers\JSONHelper::json(Array('error'=>'Некорректные данные'));
			exit;
		}
		return $answ;
	}

	/**
	 * удаление выделеных на клиентском уровне строк. если в поле id установлено значение all
	 * то удаляются значения в пределах текущей выборки
	 * @param $params
	 * @return array|int|string
	 */
	public function removeselected($params){
		if(!isset($params['ids']) or !$params['ids'])
			return $this->error();
		$removed = 0;
		$owner = @intval($params['owner']);
		$meth = $owner ? 'removeprop' : 'remove';
		if($params['ids']=='all'){
			$arr = $this->addfilters2qry($owner ? $this->propqry : $this->qry, $params, '', $owner);
			$result = \db::result($arr['nolim']);
			$nams = array_merge($this->getsets('contain', 'id', $owner>0), $owner ? $this->prophidden : $this->hidden);
			while ( $r = $result->fetch_assoc() ){
				$curParams = Array();
				$i=0;
				foreach ($r as $tnam=>$val){
					if($i<2)
						$curParams[$tnam] = $val;
					else if(isset($nams[$i-2]) and $nam = $nams[$i-2])
						$curParams["old$nam"] = $curParams[$nam] = $val;
					$i++;
				}
				if($owner)
					$curParams['owner'] = $owner;

				if(isset($curParams['rowid']))
					$curParams['id'] = $curParams['rowid'];

				// +++
				if($this->decodeIncomParams)
					$curParams = \Encoder\Coder::coding($curParams, 1);

				if($ra = $this->$meth($curParams) and $this->answIsGood($ra))
					$removed++;

			}
		}else
			foreach ($params['ids'] as $i=>$sparams){
				if($ra = $this->$meth($sparams, 0) and $this->answIsGood($ra))
					$removed++;
			}

		return $removed;
	}

	/**
	 * выделение из ответа ошибки (return текст ошибки)
	 * @param mixed $answ
	 * @return
	 */
	protected function answGetErr($answ){
		if(is_array($answ) and isset($answ['error']))
			return $answ['error'];
		elseif(is_string($answ))
			return $answ;
		else
			return '';
	}

	/**
	 * по ответу метода определяет признаки успешности выполнения операции
	 * @param $answ
	 * @return array|int|mixed
	 */
	public function answIsGood($answ){
		if(!$this->useOnExitJSON and !is_array($answ) and !is_int($answ) and $answ){
			$answ = \Encoder\Coder::toUtf($answ);
			$answ = json_decode($answ, 1);
			//$answ = \Encoder\Coder::coding($answ, 0, 1);
		}

		$cnt = 0;
		$cansw = (
			($answ == intval($answ) and $answ > 0 and $cnt=$answ) or
			(is_array($answ) and isset($answ['code']) and ($cnt = intval($answ['code'])) > 0)
		);

		return $cansw ? $cnt : 0;
	}

	/**
	 * внутренний метод формирования основной либо дополнительной таблицы
	 * @param $cols
	 * @param bool $prop
	 * @return string
	 */
	private function form_table($cols, $prop=false){
		$sortrow = '';
		$insertrow = '';
		$tblid='maintbl';
		$containid='contain';

		if ($prop) {
			$tblid='proptbl';
			$containid='propcontain';
		}

		for($i=0; $i<count($cols); $i++){
			$col = $cols[$i];
			$sets = $col->getsets();

			$sortrow.= $this->formsortcell($sets);
	        $insertrow.= $this->formaddcell($sets, $prop);
		}

		$fcols = $this->getsets('filter', 'id', $prop);

		if ($this->short) {
			$sortrow.= '<th class="last_cell"></th>';
		}else {
			$onclick = 'engine.clearInserts(event);';

			$title = $prop?'Применить фильтр по содержимому':'Применить строку ввода как фильтр';
			$fltbtn = '';
			if($fcols)
				$fltbtn = '<input type="button" style="width: 75%" onclick="engine.applyFilters(this)" value="Найти" title="'.$title.'">';

			$sortrow.=
				'<td class="last_cell" style="text-align: center;">'
					.'<a class="theta big" href="javascript:void(0)" onclick="'.$onclick
					.'" title="Очистить строку ввода">&Theta;</a>'.$fltbtn
				.'</td>';
		}

		if((($prop or !$this->short) and ($this->getsets('add', 'id', $prop))))
		//if(($prop or !$this->short) and ($this->getsets('add', 'id', $prop)))
// 			$insertrow.= '<td style="min-width: 80px;"><input type="button" value="Добавить"'
// 				.' onclick="engine.tryAdd(event)" class="stdbutton" style="width: 100%"></td>';
			$insertrow.= '<td style="min-width: 80px;"><button onclick="engine.tryAdd(event)" style="height: 19px;margin: -1px; width: 100%;" >'
					.'<div style="height: 100%;line-height: 1.3;overflow: visible;" >Добавить</div></button></td>';
		else
			$insertrow.= '<td class="last_cell discell">&nbsp;</td>';

		$sortrow = '<tr class="body_color sort_row" onclick="engine.sortClick(event);">'
			.$sortrow.'</tr>';
		$insertrow = '<tr class="insert_row botbrd add_row">'.$insertrow.'</tr>';

		$answ='<table class="tablestyle'.(($this->short)?(' is_short_view'):('')).'" id="'
// 			.$tblid.'"><caption>'.$sortrow.$insertrow.'</caption>'
			.$tblid.'">'.$sortrow.$insertrow
			.'<tbody style="border-top: 2px solid black;" id="'.$containid.'"'
			.'></tbody></table>';

		return $answ;
	}

	/**
	 * выборка набора параметров колонок
	 * @param string $setstyp
	 * @param bool $setnam
	 * @param bool $prop
	 * @param bool $withprefix
	 * @return array
	 */
	public function getsets($setstyp='', $setnam=false, $prop=false, $withprefix=false){
		if ($setstyp=='contain')
			$method='getcontainsets';
		else if ($setstyp=='add')
			$method='getaddsets';
		else if ($setstyp=='filter')
			$method='getfiltersets';
		else
			$method='getallsets';

		if($prop === false)
			$cols = $this->cols;
		else if($prop===true)
			$cols = ($this->propcols);
		else
			$cols = array_merge($this->cols, $this->propcols);

		$prefix = ($withprefix)?($this->entity.$setstyp):('');
		$answ = Array();
		foreach($cols as $col)
			if (!$setnam)
				$answ[]= $col->$method();
			else{
				$sets = $col->$method();
				if (isset($sets[$setnam]))
					if ($method=='getfiltersets' and $setnam=='id' and ($sets['typ']=='dat' or $sets['typ']=='datt') ){
						$answ[]= $prefix.'st'.$sets[$setnam];
						$answ[]= $prefix.'end'.$sets[$setnam];
					}else if($method=='getallsets' and $setnam!='sortid' and ($sets['filtertyp']=='dat' or $sets['filtertyp']=='datt')){
						$answ[]= $prefix.'st'.$sets[$setnam];
						$answ[]= $prefix.'end'.$sets[$setnam];
					}else
						$answ[]= $prefix.$sets[$setnam];
			}

		return $answ;
	}

	/**
	 * формирование ячейки-сортировки (заголовок колонки)
	 * @param $sets
	 * @return string
	 */
	private function formsortcell($sets){
		$title= 'Сортировать по этому полю';
		if(isset($sets['titlelong']) and $sets['titlelong'])
			$title = $sets['titlelong'].' (сортировать)';

		if(strpos($sets['id'],'dat')!==false and isset($sets['addsets']['typ']) and $sets['addsets']['typ']!='sdat')
			$widthifis = ' style="width: 112px;"';
		else
			$widthifis = ($sets['width']!='') ? (' style="width: '.$sets['width'].';"') : ('');
		$answ = '';
		if ($sets['id']!='')
			$answ ='<img style="visibility:hidden" src="'.$this->getImgPath().'up.jpg" id="sort'.$sets['id'].'">';

		$theta = '';
		if($sets['filtersets'] || $sets['addsets'])
			$theta = '<a class="theta" title="Убрать фильтр по этому полю" href="#" id="clear'.$sets['id'].'">&Theta;</a>';

		$answ ='<nobr>'.$answ.$sets['title'].$theta.'</nobr>';
		return '<th'.$widthifis.' title="'.$title.'">'.$answ.'</th>';
	}

	/**
	 * формирование ячейки строки ввода
	 * @param $sets
	 * @param $prop
	 * @return string
	 */
	private function formaddcell($sets, $prop){
		$fltsets = $sets['filtersets'];
		$addsets = $sets['addsets'];

		// определение функциональности ячейки (добавления, фильтров, для обеих операций, неактивная)
		if (isset ($fltsets['id'], $addsets['id']))
			$cssClass = "inscell";
		elseif (isset($fltsets ['id']))
			$cssClass = "fltcell";
		elseif (isset($addsets ['id']))
			$cssClass = "addcell";
		else
			return '<th class="discell">&nbsp;</th>';

		// если активен короткий просмотр, возврат пустой ячейки
		if ($this->short and !$prop)
			return '<th class="discell">&nbsp;</th>';

		// определения типа данных (формата, представления)
		if ( isset($addsets['typ']) )
			$typ = $addsets['typ'];
		elseif( isset($fltsets['typ']) )
			$typ = $fltsets['typ'];
		else
			$typ = '';

		// выборка из фильтров текущих значений для формируемых ячеек
		$val = '';
		if( isset($fltsets['id']) ) {
			$fid = $fltsets['id'];
			if (in_array($fltsets['typ'], Array('dat', 'datt'))){
				$stdatnam = $this->entity.'filterst'.$fid;
				$enddatnam = $this->entity.'filterend'.$fid;
				$setsnam = Array($stdatnam,$enddatnam);
			}else
				$setsnam = $this->entity.'filter'.$fid;

			if($this->staticFilter){
				$val = '';
				if(isset($this->staticFilter[$fid]))
					$val = $this->staticFilter[$fid];
				else if($typ == 'datt' || $typ == 'dat'){
					$val = array();
					if(isset($this->staticFilter['st'.$fid])){
						$val['filterst'.$fid] = $this->staticFilter['st'.$fid];
					}
					if(isset($this->staticFilter['end'.$fid])){
						$val['filterend'.$fid] = $this->staticFilter['end'.$fid];
					}
				}
			}else
				$val = \Encoder\Opt::getopts($setsnam);
		}

		// имя значения (и ячейки)
		$id = isset($addsets['id']) ? $addsets ['id'] : $fltsets ['id'];

		$acText = '';
		if($typ == 'ac'){
			$fieldnam= $fid.'_text';
			$acText = \Encoder\Opt::getopts($this->entity.'filter'.$fieldnam);
			if(!$acText && isset($this->staticFilter[$fieldnam]))
				$acText= $this->staticFilter[$fieldnam];
		}

		// формирование текста действий при изменении ведущих ячеек
		$father = $gfather = -1;
		$onchange = Array();
		$relyarr = $prop ? $this->prop_prop_rely : $this->ent_ent_rely;
		foreach($relyarr as $rel){
			// если это ведущее значение - формируется текст скрипта
			if ($rel[0]==$id)
				$onchange[]= "loadCell('cell".$rel[1]."', "
					.( ($typ=='chkb' or $typ=='chkb3')  ? '(this.checked)?1:0' : 'this.value' ) .', this.name);';
			// если это ведомое значение, из фильтров загружаются ведущие значения
			if ($id==$rel[1]) {
				$gfather = $rel[0];
				$isfather = false;

				$arrSt = isset($_GET['staticfilter']) ? $_GET : (isset($_POST['staticfilter']) ? $_POST : []);
				if($arrSt){
					if(isset($arrSt['staticfilter'][$gfather]) && $arrSt['staticfilter'][$gfather]){
						$father = $arrSt['staticfilter'][$gfather];
						$isfather = true;
					}
					if(isset($arrSt['staticfilter']['st'.$gfather]) && $arrSt['staticfilter']['st'.$gfather]){
						$father = $arrSt['staticfilter']['st'.$gfather];
						$isfather = true;
					}
					if(isset($arrSt['staticfilter']['end'.$gfather]) && $arrSt['staticfilter']['end'.$gfather]){
						$father = $arrSt['staticfilter']['end'.$gfather];
						$isfather = true;
					}
				}
				if(!$isfather)
					$father = \Encoder\Opt::getopts($this->entity.'filter'.$gfather);

				if(isset($rel[2])){
					$typIndex = isset($rel[2][$father]) ? $father : 0;
					$typ = $rel[2][$typIndex];
				}
			}
		}
		$onchange = $onchange ? (' onchange="'.implode(' ', $onchange).'"') : '';

		// формирование содержимого ячейки
		$cellHtml = $this->formCellHtml($typ, $id, $id, $val, $onchange, $father, $gfather, $acText);

		// для указания ширины, т.к. ширина при указании в ячейке сортировки не всегда срабатывает
		if(strpos($sets['id'],'dat')!==false and isset($sets['addsets']['typ']) and $sets['addsets']['typ']!='sdat')
			$widthifis = '';
		else
			$widthifis = ($sets['width']!='') ? (' style="width: '.$sets['width'].';"') : ('');

		// обертка содержимого в тег th и добавление дополнительных скриптовых вставок
		return $this->wrapInsertCell($cellHtml, $typ, $id, $cssClass, $father, $widthifis);
	}

	/**
	 * обертывание html ячейки добавления в тег th с добавлением необходимых классов, стилей, скриптов
	 * @param string  $html
	 * @param string $typ
	 * @param string $id
	 * @param string $cssClass
	 * @param $f
	 * @param string $stwidth
	 * @return string
	 */
	private function wrapInsertCell($html, $typ, $id, $cssClass, $f, $stwidth = ''){
		switch ($typ){
			case 'textarea':
				$cssClass .= " posRel";
			case 'pwd':
			case 'inp':
			case 'sel':
				return '<th '.$stwidth.' id="cell'.$id.'" class="'.$cssClass.'">'.$html.'</th>';
			case 'chkb':
			case 'chkb3': //*sk
				return '<th id="cell'.$id.'" class="'.$cssClass.'" style="text-align: center;">'
					.$html.'</th>';
			case 'week':
				return '<th id="cell'.$id.'" style="text-align:left;" class="weekcell '
					.$cssClass.'">'.$html.'</th>';
			case 'ac':
			case 'autocompl':
				$data = "'?$this->handler=$this->entity', {onchangekey:tep.onautocompletechangekey,matchContains:true,width:'auto'"
					.",minChars:0,extraParams:{action:'getac',nam:'$id'"
					.( ($f and $f>0) ? ",father:'$f'" : '')."}}";
				return "<th $stwidth id='cell$id'  class='$cssClass' isdef='1'>$html"
					."<script>$('#cell$id input:first').autocomplete($data)"
					.'.result(function(e,v,t){$("#'.$id.'").val(v).change(); tep.onAcResult(e,'.$id.',v,t); });</script></th>';

			case 'sdat':
				return '<th id="cell'.$id.'"  class="aligndat '.$cssClass.'" style="width: 65px;">'
						."$html<script>$('#$id').calendar();</script></th>";
			case 'sdatt':
				return '<th id="cell'.$id.'"  class="aligndat '.$cssClass.'" style="width: 130px;">'
						."$html<script>$('#$id').calendar({
							useTime:function(inp, dt){
								return engine.calendarDateTime(inp, dt);
							}
						});</script></th>";

			case 'dat':
			case 'datt':
				return '<th id="cell'.$id.'"  class="aligndat '.$cssClass.'" style="width: 111px;">'
						.$html.'<script>$("#st'.$id.',#end'.$id.'").calendarPair({onchoose: function(el){$(el).change();}});</script></th>';
			default:
				return '<th id="cell'.$id.'" class="discell">&nbsp;</th>';
		}
	}

	/**
	 * формирование html ячейки добавления
	 * @param $typ
	 * @param $id
	 * @param $nam
	 * @param $val
	 * @param string $onchange
	 * @param int $f
	 * @param int $gf
	 * @param string $actext
	 * @return string
	 */
	protected function formCellHtml($typ, $id, $nam, $val, $onchange='', $f=0, $gf=0, $actext=''){
		$namid = " name='$nam' id='$id' ";
// showrw("$typ, $id, $nam, $val, $onchange, $f, $gf, $actext");
		switch ($typ){
			case 'textarea':
				return "<textarea class='font' style='width:100%;border:0 none;'
					$onchange $namid >$val</textarea>";
			case 'pwd':
				return "<input $onchange type='password'  $namid value='$val' >";
			case 'inp':
				$val = str_replace("'", '&#39;', stripslashes($val));
				return "<input $onchange type='text' $namid value='$val' >";
			case 'chkb':
			case 'chkb3': //*sk
				return "<input $onchange type='checkbox' $namid "
					.( $val ? ' CHECKED' : '' ).( $val==='' ? ' class="thirdState"' : '').'>';
			case 'sel':
				$opts = $this->getsel($nam, $f, $gf, $val) ;
				return "<select $onchange $namid  class='addsel' >$opts</select>";
			case 'week':
				$brkr = '<div class="weekbreaker"></div>';
				return '<input type="checkbox" name="mon">'.$brkr
					.'<input type="checkbox" name="tue">'.$brkr
					.'<input type="checkbox" name="wed">'.$brkr
					.'<input type="checkbox" name="thu">'.$brkr
					.'<input type="checkbox" name="fri">'.$brkr
					.'<input type="checkbox" name="sat">'.$brkr
					.'<input type="checkbox" name="sun">';
			case 'ac':
			case 'autocompl':
				if($val && method_exists($this, $meth = 'getacval' . $id)){
					$actext = $this->$meth($val, $f, $gf);
				}elseif($val and !$actext and $sq= $this->getselqry($id, $f, $gf, '')){
					$actext = '';
					if(!is_array($sq)){
						/** @noinspection SqlResolve */
						$actext= \db::val("SELECT nam FROM ($sq)qdr WHERE id='$val'");
						/** @noinspection end */
						//$sq= \db::arr($sq);
					} else {
						if($sq and is_array($sq))
							foreach ($sq as $v)
								if(isset($v['id']) && $v['id'] == $val){
									$actext= $v['nam'];
									break;
								}
					}
				}

				$actext= str_replace("'", '&#039;', $actext);

				$nam = $nam ? " name='{$nam}_text' " : '';
				return "<input type='text' value='$actext' $nam><input type='hidden' $namid value='$val' $onchange>";
			case 'sdat':
				$val = $val ? \Online\Helpers\DateHelper::userDate($val) : '';
				return "<input type='text' style='width:65px;' $namid value='$val' maxlength='10' >";
			case 'sdatt':
				$val = $val ? \Online\Helpers\DateHelper::userDatetime($val) : '';
				return "<input type='text' style='width:130px;' $namid value='$val' maxlength='19' >";

			case 'dat':
			case 'datt':
				$stval = $endval = '';
				if(is_array($val))
					foreach ($val as $k=>$v){
						$k = (count($arr = explode('filter', $k))==2) ? $arr[1] : $arr[0];
						if(substr($k, 0, 2)=='st' and $v)
							$stval = \Online\Helpers\DateHelper::userDate($v);
						elseif(substr($k, 0, 3)=='end' and $v)
							$endval = \Online\Helpers\DateHelper::userDate($v);
					}
				$stid = $id ? "st$id" : '';
				$endid = $id ? "end$id" : '';

				$ml = "maxlength='".($typ=='dat' ? 10 : 19)."'";

				$answ = "<div style='white-space:nowrap; overflow: hidden;width: 111px;'>
					<input type='text' style='width:65px; border-right:1px solid black;' $ml name='st$nam'
					id='$stid' value='$stval' $onchange ><input type='text' style='width:65px;' $ml name='$endid'
					id='end$nam' value='$endval'></div>";
// 				if($stid and $stval)
// 					$answ.='<script>$("#'.$stid.',#'.$endid.'").calendarPair();</script></th>';
				return $answ;
			// nobr и descr только для типов ячеек содержимого
			case 'nobr':
				return "<nobr name='$id'>$val</nobr>";
			case 'descr':
				return '<span style="font-weight: 100;" title="">'.$val.'</span>';

			case 'file':
				return '<a class="btn" href="#" onclick="$(\'#uploadfilechoose\').click();" >...</a>';

			default:
				return '&nbsp;';
		}
	}

	/**
	 * выборка из входных данных пар которые изменились (пары вида val - oldval)
	 * они оформляются в виде массива для вставки в БД либо в лог (флаг $forLog)
	 * поля перечисеные в $skip пропускаются
	 * @param $params
	 * @param array $skip
	 * @param int $forLog
	 * @param int $nullDate
	 * @param int $indexed
	 * @return array|string
	 */
	protected function pickupdates($params, $skip = Array(), $forLog=0, $nullDate=0, $indexed=0){
		$skip = \Online\Helpers\ArrayHelper::makeArray($skip);
		$arr = $this->_pickChanges($params, $skip, $nullDate);
		if(!is_array($arr))
			return $arr;
		$answ = Array();
		foreach($arr as $fldnam=>$val){
			if(!$forLog){
				$val = ($val == 'NULL' or $indexed) ? $val : "'$val'";
				$indexed ? $answ[$fldnam] = $val : $answ[] = "$fldnam=$val";
			}else
				for($i=0; $i<count($this->cols); $i++){
					$col = $this->cols[$i];
					if(  isset($col->addsets['id']) and $fldnam==$col->addsets['id']  ){
						if(\Online\Helpers\DateHelper::isDatetime($val) or \Online\Helpers\DateHelper::isDateDb($val))
							$val = \Online\Helpers\DateHelper::userDate($val);
						$answ[] = "$col->title = $val";
					}
				}
		}

		return $answ;
	}

	/**
	 * выборка только измененных полей, без форматирования
	 * @param $params
	 * @param $skip
	 * @param bool $nullDate
	 * @return array|string
	 */
	private function _pickChanges($params, $skip, $nullDate=false) {
		$answ = Array();
		$skip = \Online\Helpers\ArrayHelper::makeArray($skip);
		foreach($params as $nam=>$val)
			if(in_array($nam, $skip))
				continue;
			else if (isset($params['old'.$nam]) and $params['old'.$nam]!=$val){
				if(strstr($nam, 'datt')) {
					if(!$val and $nullDate)
						$val = 'NULL';
					else if(!\Online\Helpers\DateHelper::isDatetime($val))
						return 'Неверный формат даты';
				}else if (strstr($nam, 'dat')){
					if(!$val and $nullDate)
						$val = 'NULL';
					else if(!\Online\Helpers\DateHelper::isDateDb($val))
						return 'Неверный формат даты!';
				}
				$answ[$nam] = $val;
			}
		return $answ;
	}

	/**
	 * получение фильтров из _GET
	 */
	public function checkAJAXFilters(){
		if($this->isFilterCheck)
			return;

		// фильтры для верхней таблицы
		if(isset($_GET['setfilters'])){
			$this->checkFilters();
			$this->clearFilters();
			if(is_array($_GET['setfilters'])){
				$nams = Array();
				$vals = Array();
				foreach($_GET['setfilters'] as $nam=>$val){
					$nams[] = $this->entity.'filter'.$nam;
					$vals[] = trim($val);
				}
				\Encoder\Opt::setopts($nams, $vals);
			}

			$loc = "?";
			unset($_GET['setfilters']);
			$loc.= http_build_query($_GET);
			$this->redirectTo($loc);
		}

		// фильтры хранимые на клиенте (при наличии замещают хранимые)
		$arrSt = isset($_GET['staticfilter']) ? $_GET : (isset($_POST['staticfilter']) ? $_POST : []);
		if(isset($arrSt['staticfilter']) and is_array($arrSt['staticfilter'])){
			// возможность по $_GET отфильтровывать по
			$filternams = $this->getsets('filter', 'id', 1);
			foreach($arrSt['staticfilter'] as $k => $v)
				if($this->filterNameNoCheck or in_array($k, $filternams) or in_array($k, $this->allowAdditFilterNames))
				$this->staticFilter[$k]= \Encoder\Coder::coding($v);
		}
		$this->isFilterCheck = true;
	}

	/**
	 * валидация введенных значений
	 * @link https://github.com/vlucas/valitron
	 * @param string $action
	 * @return array|bool
	 */
	private function checkValidate($action){
		//include_once 'phplib/validator.php';

		$mtd = '';
		if(in_array($action, ['add', 'addprop'])){
			$mtd = 'addsets';
		}
		if(in_array($action, ['save', 'saveprop', 'remove', 'removeprop'])){
			$mtd = 'containsets';
		}


		$v = new \Valitron\Validator($this->params);
		$needValidate = 0;
		$cols = in_array($action, ['add', 'save', 'remove']) ? $this->cols : $this->propcols;
		foreach($cols as $col){
			if(isset($col->{$mtd}['validate']) && is_array($col->{$mtd}['validate'])){
				$fieldnam = $col->{$mtd}['id'];

				foreach($col->{$mtd}['validate'] as $rules){
					$rulparams = [$rules[0], $fieldnam];
					if(isset($rules[1]))
						$rulparams []= $rules[1];
					$vv = call_user_func_array([$v, 'rule'], $rulparams);
					if($col->title)
						$vv->label($col->title);
					$needValidate = true;
				}
			}
		}

		if($needValidate && !$v->validate()){
			return $v->errors();
		}
		return [];
	}

	/**
	 * обработка AJAX запроса с выдачей результата и выходом, либо пропуск с выводом HTML
	 */
	public function checkAJAX(){
		$this->checkAJAXFilters();
		if(!isset($this->params['action'])){
			return;
		}
		$action = $this->params['action'];
		if($this->decodeIncomParams)
			$this->params = \Encoder\Coder::coding($this->params, 1, 0);

		if (method_exists($this, $action)){
			$this->isAJAX = true;
			if(substr($action, 0, 4)=='save' or substr($action, 0, 3)=='add')
				\Encoder\Coder::cleanData($this->params);

			// валидация данных
			if(in_array($action, ['add', 'addprop', 'save', 'saveprop', 'remove', 'removeprop'])){
				$errs = $this->checkValidate($action);
				if($errs){
					$flds = array_keys($errs);
					exit(\Online\Helpers\JSONHelper::json(Array('error'=> $errs[$flds[0]][0], 'blink' => $flds)));
				}
			}

			// вызов метода action
			try {
				$answ = $this->$action($this->params);
			}catch (\Exception $e){
				$answ = $e->getMessage();
			}

			if($this->useOnExitJSON)
				self::makeJSON($answ);
			echo $answ;
		}else
			echo \Online\Helpers\JSONHelper::json(Array('error'=> 'Ошибка данных'));
		exit;
	}

	/**
	 * @param $var
	 * @return string
	 */
	public static function makeJSON(&$var){
		if(is_string($var))
			$var = Array('error'=>$var);
		elseif ($var==intval($var))
			$var = Array('code'=>$var);
		return $var = \Online\Helpers\JSONHelper::json($var);
	}

	/**
	 * инициализация автовыхода
	 */
	public function initCashKicker(){

	}

	/**
	 * возвращает кол-во секунд до выполнения автовыхода, либо off, если автовыход не активирован
	 */
	protected function CKtime(){
		return 'off';
	}

	/**
	 * сохранение фильтров в БД
	 * @param array $params
	 */
	public function savefilters($params){
		$this->clearFilters();

		if(isset($params['filters'])){
			//if(!$this->decodeIncomParams)
			//	$params['filters']= \Encoder\Coder::coding($params['filters'], 0, 0);
			$setNams = $setVals = Array();
			foreach ($params['filters'] as $nam=>$val){
				$val = trim($val);
				// отделение фильтров по тексту АС
				if(substr($nam, -5)=='_text'){
					$meth = 'textfilter'.substr($nam, 0, -5);
					if(method_exists($this, $meth) and $this->$meth($val, $params['filters'])){
						$setNams[] = $this->entity.'filter'.$nam;
						$setVals[] = stripslashes($val);
					}
				// обычное применение фильтров
				}else{
					$setNams[] = $this->entity.'filter'.$nam;
					$setVals[] = $val;
				}
			}

			if($setNams){
				\Encoder\Opt::setopts($setNams, $setVals);
			}
		}

		return $this->getcontain($params);
	}

	/**
	 * вывод стандартного HTML кода редактора и выход
	 * *upd включает в себя проверку на запрос AJAX (@method checkAJAX )
	 * @param string $editorNam название редактора (в title)
	 * @param string/array $moreIncludes дополнительные css/javascript файлы
	 * @param int $otherInfo
	 */
	public function stdView($editorNam, $moreIncludes=Array(), $otherInfo = 0){
		$this->checkAJAX();
		$moreIncludes = \Online\Helpers\ArrayHelper::makeArray($moreIncludes);
		if($this->customCSS)
			$this->customCSS = "<style>$this->customCSS</style>";
		echo '<!DOCTYPE HTML>
			<html>
			<head>
				<title>'.$editorNam.'</title>'.$this->incl($moreIncludes).'
			'.$this->customCSS.'
			</head>
			<body>'.$this->getHTML($otherInfo).'
			<script type="text/javascript">
			var engine=new tabloid_engine(entity, handler);
			engine.init();
			'.$this->customJS.'
			</script>';

		$this->inclhtml();
/*		ob_start();
		return ob_get_clean ();*/
		echo '</body></html>';
		exit;
	}

	/**
	 * Tabloid constructor.
	 * @param $handler
	 * @param $entity
	 * @param string $defsort
	 * @param string $defpropsort
	 * @param array $short
	 */
	function __construct($handler, $entity, $defsort='', $defpropsort='', $short=Array()) {
		if(isset($_GET['action']) and substr($_GET['action'], 0, 5)=='print')
			$this->params = $_GET;
		else
			$this->params = $_POST;

		$this->entity = $entity;
		$this->handler = $handler;
		$this->defsort = $defsort;
		$this->defpropsort = $defpropsort;
		$this->now = date('Y-m-d H:i:s');
		$this->today = date('Y-m-d');

		$this->viewids = (isset($_GET["shortview"])) ? (explode(',', urldecode($_GET["shortview"]))) : Array();
		$this->short = count($this->viewids) > 0;
	}
}

/**
 * класс col является состовляющим класса tabloid (grid_editor - а)
 * он содержит настройки для конкретной колонки из tabloid
 * а именно:
 * 		$id - идентификатор колонки (идентификатор ячейки сортировки, также по-умолчанию для ячейки фильтра,
 * 		добавления и содержания), добавляется к запросам как имя поля сортировки
 * 		$title - отображаемое название колонки (или ассоциативный массив ('title'=>'','titlelong'=>''))
 * 		$width - желаемая ширина для ячейки (если содержимое больше возможно колонка растянется)
 * 		$filtersets	- настройки ячейки фильтра. принимаемое значение: Array([id], [typ]). например
 * 			Array('userid', 'nobr'). о возможных типах позже. userid - идентификатор который будет прикручиваться
 * 			к запросам выборки содержимого из базы при применении фильтра по этому полю. например если
 * 			id фильтра = userid и запрос выглядит так: "SELECT userid, usernam, status FROM usr", то при применении фильтра
 * 			запрос дополнится и станет следующим:"SELECT userid, usernam, status FROM usr WHERE usrid=n",
 * 			где n это значение фильтра. все значения берутся в кавычки и экранируются. допустимые значения
 * 			например чтобы в числовые поля не писали текст) проверяются	на уровне JS. правила проверки описаны
 * 			в классе tabloid и зависят от именования полей, не от содержимого.
 * 			значение по-умолчанию для этого поля - true, что означает что идентификатор будет такой же как и у
 * 			колонки (ячейки сортировки), а тип 'inp'. если указать false то ячейка будет неактивна.
 *		$addsets - настройки ячейки добавления. принимаемые значения и умолчание идентичны $filtersets. Ячейка служит
 * 			для добавления новой записи. обработку запроса на добавление необходимо описывать самостоятельно. tabloid
 * 			только предоставляет входные данные. для этого необходимо в объект класса tabloid добавить метод
 * 			add($params) с доступом public. индексированый массив $params на входе содержит добавляемые данные.
 * 			подробнее смотри описание метода.
 * 		$containsets - настройка ячеек содержания в конкретной колонке табличного редактора. значения с индексом
 *	 		указаным здесь в качестве id будут использоваться при изменении и удалении строк из редактора.
 * 			соответственно в функциях save и remove
 *
 */
class col{
	public $id, $title, $titlelong='', $width, $filtersets, $addsets, $containsets;

	/**
	 * @param bool $setnam
	 * @return mixed
	 */
	public function getfiltersets($setnam=false){
		return $setnam ? $this->filtersets[$setnam] : $this->filtersets;
	}

	/**
	 * @param bool $setnam
	 * @return mixed
	 */
	public function getaddsets($setnam=false){
		return $setnam ? $this->addsets[$setnam] : $this->addsets;
	}

	/**
	 * @param bool $setnam
	 * @return mixed
	 */
	public function getcontainsets($setnam=false){
		return $setnam ? $this->containsets[$setnam] : $this->containsets;
	}

	/**
	 * @return array
	 */
	public function getallsets()					{
		return Array(
			'filterid'=>	(isset($this->filtersets['id'])) ? $this->filtersets['id'] : ''
			,'filtertyp'=>	(isset($this->filtersets['typ'])) ? $this->filtersets['typ'] : ''
			,'filterdt'=>	(isset($this->filtersets['dt'])) ? $this->filtersets['dt'] : ''
			,'addid'=>		(isset($this->addsets['id'])) ? $this->addsets['id'] : ''
			,'addtyp'=>		(isset($this->addsets['typ'])) ? $this->addsets['typ'] : ''
			,'adddt'=>		(isset($this->addsets['dt'])) ? $this->addsets['dt'] : ''
			,'containid'=>	(isset($this->containsets['id'])) ? $this->containsets['id'] : ''
			,'containtyp'=>	(isset($this->containsets['typ'])) ? $this->containsets['typ'] : ''
			,'containdt'=>	(isset($this->containsets['dt'])) ? $this->containsets['dt'] : ''
			,'sortid'=>		(isset($this->id)) ? $this->id : ''
			,'name'=>		(isset($this->title)) ? $this->title : ''
			,'titlelong'=>	(isset($this->titlelong)) ? $this->titlelong : ''
		);
	}

	/**
	 * @return array
	 */
	public function getsets(){
		return Array(
			'id'=>$this->id,
			'title'=>$this->title,
			'titlelong'=>$this->titlelong,
			'width'=>$this->width,
			'filtersets'=>$this->filtersets,
			'addsets'=>$this->addsets,
			'containsets'=>$this->containsets
		);
	}

	/**
	 * @param $params
	 * @param $values
	 * @param $def
	 */
	private function setParams(&$params, $values, $def){
		if (is_array($values)) {
			$sets = Array ('id'=>$values[0], 'typ'=>$values[1]);
			$sets['validate'] = (isset($values[2]) && is_array($values[2])) ? $values[2] : [];
			$params = $sets;
		}elseif($values === false)
			$params = Array();
		else
			$params = $def;
	}

	/**
	 * col constructor.
	 * @param $id
	 * @param $title
	 * @param string $width
	 * @param bool $contain
	 * @param bool $add
	 * @param bool $filter
	 */
	function __construct($id, $title, $width='', $contain=true, $add=true, $filter=true) {
		$this->id = $id;
		if(is_array($title)){
			$this->title= $title[0];
			if(isset($title[1]))
				$this->titlelong= $title[1];
		} else
			$this->title= $title;
		$this->width = $width;

		$this->setParams($this->containsets, $contain, Array('id'=>$id, 'typ'=>'inp', 'dt'=>''));
		$this->setParams($this->addsets, $add, $this->containsets);
		$this->setParams($this->filtersets, $filter, $this->addsets);
	}

	/**
	 *
	 */
	function __destruct() {}
}