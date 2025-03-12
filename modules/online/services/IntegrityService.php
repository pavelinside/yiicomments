<?php
namespace app\modules\online\services;

class IntegrityService
{
    public $curIndex=0, $tablenams, $tables, $noDecor = false;
    private $returnAsIs = 0;

    // TODO ����������� �����
    // TODO ����������� ������
    // TODO ������������� ������������ ������� � ���������� ���� family REGEXP "[a-zA-Z]"
    // TODO ���������� ��������(email,�����) � ������ ���������

    // программное название(метода), описание, отключен по-умолчанию
    public static $register = Array(
        ['chkExistTable',		'Существование необходимых таблиц']
    ,['chkExternIds',		'Корректные ссылки внешних id']
    ,['chkIndirectLinks',	'Корректные коственные ссылки']
    ,['chkUniqueKey',		'Уникальность записей']
    ,['chkDats',			'Нулевые даты, корректность периода']
    ,['chkPrKeyRefs',		'Неиспользуемые id сущностей <input placeholder="Все" title="Таблицы для проверки" name="chktable">', true]

    ,['chkAccordUniRel',	'Соответствие 1:1 таблиц usr и usractive,usrlastactive,usrsignal']
    ,['chkEmail', 'E-mail']
    ,['chkForeignVal',		'Неиспользуемые выделеные значения']
    ,['chkJournal',		'Сверка с журналом']
    ,['chkTree',	'Деревья']
    ,['chkTreeUniqe',		'Деревья - уникальность значений']
    ,['chkTelCodeGis',		'Корректность ГИС у telcode', true]

    ,Array('chkExistFile',		'Файлы')
    ,Array('chkTaskIntegrity',	'Задачи', true)
    );

    // ���������
    public static $checkNote = Array(
        'chkExistTable'		=>	'проверяются все имеющиеся таблицы в базе и наличие соответствующих им записей в таблице entity'
    ,'chkExternIds'			=>	"Проверяется чтобы у всех полей типа depid была соответствующая id в тбл. dep;"
    ,'chkIndirectLinks'		=>	'Проверяются косвенные связки согласно entityid в связках usr_opt (кроме 1 или 2 entityid).'
    ,'chkUniqueKey'			=>'Проверка уникальности таблиц где нет уникальных ключей: usrevt'
    ,'chkDats'				=>	'Ненулевые значения полей даты[/времени]'
    ,'chkPrKeyRefs'			=>	'Ищутся записи id, на которые нет ссылок ни в одной из связных таблиц.
			<br> - для таблицы email c дополнительным условием usrid=0
			<br> - не проверяются таблицы folder, opt, entity
			<br> - не выполняется поиск по "confmess" в "confmess_file", и "edndat" в "edndat_subj" и "edndatprice"'
    ,'chkAccordUniRel'		=>	'Проверяет что в таблицах usractive,usrlastactive,usrsignal есть все значения usrid, какие есть в таблице usr(id)'
    ,'chkEmail'				=>	'Проверка почтовых адресов на валидность'
    ,'chkForeignVal'		=>	'Наличие ссылок на значения в выделеных таблицах значений'
    ,'chkJournal'			=>	'проверятся наличие в соотв. таблицах записей с id отмеченых в журнале как добавленых и отсутствие id помеченых как удаленные'
    ,'chkTree'				=>	'проверка структур деревьев (таблицы rub, gis, dep)'
        /*,'chkTree'				=>	'Общая целостность деревьев. Для поиска провала в ключах (напр. в рубриках) можно использовать запрос:
SELECT t.lft, val, COUNT(r.id) lvl FROM(
    SELECT r1.rubvalid id, COUNT(r2.id) cnt, SUM(r2.rgt) + SUM(r2.lft) AS bsm
        , r1.rgt + r1.lft AS csm, r1.lft, r1.rgt
    FROM rub r1
    LEFT JOIN rub r2 ON r1.lft<r2.lft AND r1.rgt>r2.rgt
    GROUP BY r1.id
    HAVING IF(cnt, csm<>bsm/cnt, (lft+1)<>rgt)
)t
LEFT JOIN rubval rb USING(id)
LEFT JOIN rub r ON t.lft>r.lft AND t.rgt<r.rgt
GROUP BY t.id
ORDER BY t.lft'*/
    ,'chkTreeUniqe'			=>	'В структурах деревьев (rub,gis,dep) проверяется наличие записей с одинаковым названием [и типом] в пределах одного родителя'
    ,'chkTelCodeGis'		=>'Находится ли gis из telcode в ветке где root=gis из countrycode'

    ,'chkTaskIntegrity'		=>'Пересечение задач (datt-runtime);
			<br> - все вложенные задачи должны быть внутри главной задачи;
			<br> - все зависимые задачи должны идти после тех, от которых они зависят;
			<br> - taskrel - taskid не должно быть равно taskfromid;
			<br> - для контрольных дат должны быть указаны Время окончания и Длительность;
			<br> - если указан Тип времени окончания, то обязательно должна быть Время окончания;
			<br> - уникальность очереди у исполнителей; queue должно быть уникальным (кроме queue=0);
			<br> - проверка очереди у задач с контрольной датой;
			<br> - Периодические задачи с вложенность более 1;
			<br> - Периодические задачи с двумя родителями;
			<br> - Исполнитель недоступен (исполнитель не работает и состояние задачи не (завершена, удалена, исполнитель недоступен);
			<br> - Cоздать уведомления о начале задач;
			<br> - Cоздать уведомления об окончании задач;
			'

    ,'chkExistFile'			=>	'Сверка размеров файлов в БД с размерами файлов на диске
			<br>- Наличие записей в таблице file и отсутствия их в таблице mess_file
			<br>- Сверка файлов на жёстком диске с записями в БД
			<br>- Сверка записей в usr_filelink и ссылок на диске
			<br>- Проверка на правильность имён файлов (и пустые имена файлов)
			<br>- Проверка присутствие записи в filedis и отсутствие в file
			<br>- Сжатый файл не может иметь размер 0 байт'
    );

    function __construct($params=Array()){
        $ar= \db::tables(\dbdef::DBNAME);
        $this->tablenams= $ar['tablenams'];
        $this->tables= $ar['tables'];
    }

    /**
     * группирование ошибок для проверки количества свойств
     * @param $arr
     * @param $err
     * @param $index
     */
    private function addErr(&$arr, $err, $index){
        if(!isset($arr[$index]))
            $arr[$index] = Array();
        if(!in_array($err, $arr[$index]))
            $arr[$index][]=$err;
    }

    public function fullCheck($params=Array()){
        if(isset($params['checks']))
            $checkList = $params['checks'];
        else
            $checkList = array_keys(self::$register);

        $answ = [];
        foreach($checkList as $i){
            $method = isset(self::$register[$i]) ? self::$register[$i][0] : 'a';
            if(!method_exists($this, $method))
                continue;

            $sttime = explode(' ', microtime());

            $this->curIndex = $i;
            $errs = $this->$method($params);

            $endtime =  explode(' ', microtime());
            $time = ($endtime[1] - $sttime[1]) + round($endtime[0] - $sttime[0], 4);

            if($this->returnAsIs)
                return $errs;

            $answ[$i] = Array('time'=>$time, 'err'=> ($errs ? $errs : false) );
        }
        return $answ;
    }

    public function chkExistTable($params){


        return \encoder\Entity::checkExistTables();


        $qry = "SELECT tablenam FROM entity WHERE id>2";
        $entTables = \db::col($qry);
        $absence = [];
        for($i=0; $i<count($entTables); $i++)
            if(!in_array($entTables[$i], $this->tablenams))
                $absence[] = $entTables[$i];

        $answ = [];
        if(count($absence))
            $answ[] = "В базе отсутствуют таблицы: " . implode(', ', $absence);

        $absence = [];
        for($i=0; $i<count($this->tablenams); $i++)
            if(!in_array($this->tablenams[$i], $entTables))
                $absence[] = $this->tablenams[$i];

        if(count($absence))
            $answ[] = "В таблице сверки нет таблиц: " . implode(', ', $absence);

        return $answ;
    }

    public function chkExternIds($params){
        $limit = 30;
        $answ = [];

        $exceptions = [
            'taskstateusrid' => 'usrid',
            'taskfromid' => 'taskid'
        ];

        // ������ �������������� ����������� ��� ��������
        $extraWhere = [
            'email.usrid' => 't1.usrid <> 0',
            'contacttyp.entityid' => 't1.entityid <> 0',
            'opt.entityid' => 't1.entityid > 2',
            'diarytyp.entityid' => 't1.entityid <> 0',
        ];



        // ������������ ����
        $skipList = Array(
            'usrlog.entityidid',
            'usrevt.entityidid',
            'diary.entityidid'
        );

        // ����� ���� �������� ������������
        foreach($this->tables as $tnam => $fields)
            foreach($fields as $fnam => $fparams){

                $xcptfnam = (isset($exceptions[$fnam])) ? $exceptions[$fnam] : $fnam;
                if(substr($fnam, - 2) != 'id' || $fnam == 'id')
                    continue;
                $reltnam = substr($xcptfnam, 0, - 2);
                $checkfld = $tnam . '.' . $fnam;

                if(in_array($checkfld, $skipList))
                    continue;

                if(! in_array($reltnam, $this->tablenams)){
                    $answ[]= "По полю <strong>$fnam</strong> таблицы <strong>$tnam</strong>
						не найдена внешняя таблица (<i>$reltnam</i>)";
                    continue;
                }

                $qry = "SELECT DISTINCT t1.$fnam
			FROM $tnam t1
			LEFT JOIN $reltnam t2 ON t1.$fnam=t2.id
			WHERE t2.id IS NULL AND t1.$fnam IS NOT NULL";

                // ограничение верки
                if(isset($extraWhere[$checkfld])){
                    $qry .= ' AND ' . $extraWhere[$checkfld];
                }

                $qry .= ' LIMIT ' . ($limit + 1);

                $ids = \db::col($qry);
                $cnt = count($ids);
                $ids = implode(',', $ids);
                if($ids !== ''){
                    $logLink = "<a target='_blank' href='?usrlog&staticfilter[id]=$ids&staticfilter[entity]=$reltnam'>������</a>";
                    $showcnt = ($cnt > $limit) ? ('����� ' . $limit) : $cnt;
                    $nam = \db::val("SELECT nam FROM entity WHERE tablenam='$tnam'");
                    $qry = "DELETE $tnam FROM $tnam LEFT JOIN $reltnam ON $reltnam.id = $tnam.$fnam WHERE $reltnam.id IS NULL AND $tnam.$fnam IS NOT NULL";

                    $answ[] = "В таблице <strong>'$tnam' ($nam)</strong> поле <strong>'$fnam'</strong>"
                        ." не имеет соответствующей записи (id) в таблице <strong>'$reltnam'</strong>"
                        ." (<strong>$showcnt</strong> шт.)<br>$logLink id: <i>$ids".($cnt>$limit ? '...' : '')."</i>"//;
                        ."<br>Удалить некорректные записи из таблицы <strong>'$tnam'</strong> можно запросом: <p><strong>$qry</strong></p><hr>";
                }
            }
        return $answ;
    }

    public static $indirectLinks = Array(
        'opt'			=>	['table'=>'usr_opt', 'val'=>'val'],
        'usrevttyp'		=>	['table'=>'usrevt', 'val'=>'entityidid']
    );

    public function chkIndirectLinks($params){
        $answ = [];
        $limit = 30;

        foreach(self::$indirectLinks as $mtbl=>$row){
            $dtbl = $row['table'];
            $val = $row['val'];

            if(!isset($this->tables[$dtbl])){
                $answ[] = "Отсутствует таблица '$dtbl'";
                break;
            }
            if(!isset($this->tables[$mtbl]['entityid'])){
                $answ[] = "Таблица '$mtbl' должна иметь поле 'entityid' согласно списку проверки";
                break;
            }

            $dfld = $mtbl.'id';
            $qry = "SELECT e.tablenam tnam, GROUP_CONCAT( mt.id SEPARATOR ',' ) dids, mt.id dtyp
			FROM $mtbl mt LEFT JOIN entity e ON mt.entityid=e.id
			WHERE e.id>2 GROUP BY e.id ORDER BY dids";
            $arr = \db::arr($qry);
            for($i=0; $i<count($arr); $i++){
                $tnam = $arr[$i]['tnam'];
                $ids = $arr[$i]['dids'];
                $dtyp = $arr[$i]['dtyp'];

                $qry = "SELECT DISTINCT dt.$dfld typ, GROUP_CONCAT(DISTINCT dt.$val) ids
				FROM $dtbl dt
				LEFT JOIN $tnam et ON dt.$val=et.id
				WHERE dt.$dfld IN($ids) AND et.id IS NULL AND dt.$val<>0
				GROUP BY dt.$dfld ORDER BY dt.$val";
                $valarr = \db::select($qry);
                if($valarr) {
                    foreach ($valarr as $typid=>$vals)
                        $answ[] = "Таблица <strong>'$dtbl'</strong> содержит строки с полями '$val', неправильно
							ссылающимися на таблицу <strong>'$tnam'</strong> ($dfld=$typid)
							<br>значения: <i>$vals</i>";
                }
            }
        }

        return $answ;
    }

    /**
     * уникальность записей в таблицах
     */
    public function chkUniqueKey($params){
        $limit = 200;
        $answ = [];
        $list = [
            'usrevt'
        ];

        if(isset($params['extra'])){
            $tnam = explode('_', $params['extra'], 2)[1];
            if(!in_array($tnam, $list))
                return ['Некорректные данные'];

            $tmpnam = 'asdasd';

            $qrys = [
                'CREATE TEMPORARY table '.$tmpnam.' AS(SELECT DISTINCT * FROM '.$tnam.')',
                'TRUNCATE ' . $tnam,
                'INSERT INTO '.$tnam.' SELECT * FROM '.$tmpnam,
            ];

            foreach ($qrys as $qry)
                \db::query($qry);
            return ['Дубликаты удалены'];
        }

        foreach ($list as $tnam){
            $cols = array_keys($this->tables[$tnam]);

            $countCol = '';
            foreach ($this->tables[$tnam] as $cnam=>$cparams){
                if($cparams['Null']=='YES')
                    continue;
                $countCol = $cnam;
                break;
            }

            $qry = 'SELECT COUNT('.$countCol.') cnt, '.implode(',', $cols).'
				FROM `'.$tnam.'`
				GROUP BY '.implode(',', $cols).'
				HAVING cnt > 1 LIMIT ' . $limit;
            $arr = \db::arr($qry);
            if(!$arr)
                continue;

            $answ[] = "<button onclick='check($this->curIndex, \"clean_$tnam\")'>Удалить дубликаты в $tnam</button>";
            $answ[] = 'Таблица <strong>'.$tnam.'</strong> содержит '.count($arr).' повотряющихся строк (лимит вывода - '.$limit.' строк): ';
            $str = '';
            foreach ($arr[0] as $col=>$val)
                $str.= '<th>'.$col.'</th>';

            foreach ($arr as $row){
                $str.= '</tr><tr>';
                foreach ($row as $col=>$val)
                    $str.= "<td>$val</td>";
            }
            $answ[] = '<table class="tablestyle" ><tr>'.$str.'</tr></table>';
        }
        return $answ;
    }

    public function chkDats($params){
        $limit = 50;
        $skip = [

        ];
        $answ = [];
        foreach($this->tables as $tnam=>$fields){
            foreach($fields as $fnam=>$fparams){
                $typ = $fparams['Type'];
                if($typ=='datetime' or $typ=='date' or $typ=='timestamp'){
                    if(in_array("$tnam.$fnam", $skip))
                        continue;

                    $qry = "SELECT COUNT($fnam) FROM $tnam WHERE $fnam=0";
                    $cnt = \db::val($qry);
                    if($cnt)
                        $answ[] = "В таблице <b>'$tnam'</b> присутствуют нулевые записи даты/времени в поле <b>'$fnam'</b> ($cnt шт.)";

                    $qry = "SELECT COUNT(DISTINCT $fnam) cnt, $fnam dt
					FROM $tnam
					WHERE IF(
					$fnam IS NOT NULL AND $fnam>0
					, MONTH($fnam)=0 OR YEAR($fnam)=0 OR DAY($fnam)=0
					, 0
					)";

                    $arr = \db::row($qry);
                    if($arr['cnt'])
                        $answ[] = "Таблица <b>'$tnam'</b>, поле <b>'$fnam'</b>. Есть $arr[cnt]
							записей даты с 0 годом, месяцем или датой (например $arr[dt])";
                }
                if(	($fnam=='stdatt' and isset($fields['enddatt']))
                    or ($fnam=='stdat' and isset($fields['enddat']))
                ){
                    $ednam = $fnam=='stdat' ? 'enddat' : 'enddatt';
                    $skiprule = "";
                    if(in_array("$tnam.$ednam", $skip)){
                        $skiprule = "OR $ednam = 0";
                    }
                    $fldnam = isset($fields['id']) ? 'id' : key($fields);
                    $qry = "SELECT GROUP_CONCAT(DISTINCT id) ids, COUNT(*) cnt
						FROM (
							SELECT $fldnam id FROM $tnam WHERE IF(ISNULL($ednam) $skiprule, 0, $ednam<$fnam) LIMIT $limit
						)a";
                    if($row = \db::row($qry) and $row['cnt'])
                        $answ[]= "Таблица $tnam, некорректных записей периода: $row[cnt], $fldnam: $row[ids]";
                }
            }
        }

        return $answ;
    }

    public function chkAccordUniRel($params){
        $checks = [
            ['usr', 'id', 'usractive', 'usrid'],
            ['usr', 'id', 'usrlastactive', 'usrid'],
            ['usr', 'id', 'usrsignal', 'usrid']
        ];

        $a= array();
        foreach($checks as $row){
            $qry= "SELECT {$row[1]}	FROM {$row[0]} mn	
				LEFT JOIN {$row[2]} scnd ON mn.{$row[1]}=scnd.{$row[3]}
			WHERE scnd.{$row[3]} IS NULL";
            $act= \db::col($qry);
            $ilen= count($act);
            if($ilen > 0){
                $a[]= "В таблице <strong>usractive</strong> отсутствуют записи 1:1 c таблицей <strong>usr</strong> ($ilen шт.)";
                $val= "";
                for($i= 0; $i<$ilen; $i++){
                    $a[]= "id: $act[$i]";
                    $val =($val == "") ? "($act[$i])" : $val.",($act[$i])";
                }
                $a[]= "Добавить отсутствующие записи можно запросом:";
                $a[]= "<strong>INSERT INTO {$row[2]}({$row[3]}) VALUES $val</strong><hr><br>";
            }
        }

        return $a;
    }

    // проверка sname на русские буквы и mail на корректность
    public function chkEmail($params){
        $arr= \db::arr("SELECT id, mail FROM email");
        $answ = [];
        foreach($arr as $id => $mail){
            if(!filter_var($mail, FILTER_VALIDATE_EMAIL)){
                $amail[]= "Валидность (id:$id, email:{$v['mail']})$lnk";
            }
        }
        return $answ;
    }

    /**
     * использование всех значений вынесеных в отдельную таблицу, удаление неиспользуемых
     * @param array $params
     */
    public function chkForeignVal($params){
        $tables = ['usrlogprop'];
        $answ = [];
        $limit = 50;

        $delShowArr = [];
        foreach ($tables as $table){
            $qry = "SELECT * FROM {$table}val ev WHERE ev.id NOT IN (
			SELECT {$table}valid FROM $table lp  WHERE {$table}valid IS NOT NULL)  LIMIT 50";

            $isDel = (isset($params['extra']) and is_array($params['extra']));
            $delCur = $isDel ? (isset($params['extra'][$table]) ? $params['extra'][$table] : 0) : 0;

            // при удалении на витке по таблице из которой не выполняется удаление
            if($isDel and !$delCur)
                continue;

            $arr = \db::select($qry);
            if($arr){
                // нажата кнопка удаления - сверяются отсылаемые иды с отобраными, удаляются пересечения
                if($delCur){
                    $delIds = array_intersect($delCur, array_keys($arr));
                    \db::query("DELETE FROM {$table}val WHERE id IN(".implode(',', $delIds).')');
                    $answ[]="$table";
                    continue;
                }

                // формирование отображаемого ответа
                $err = "В таблице <b>{$table}val</b> есть неиспользуемые значения (".count($arr)."): <br>";
                $separ = '';
                foreach ($arr as $id=>$val){
                    $err.= $separ." ($id) <i>'$val'</i>";
                    $separ = ', ';
                }

                // частная кнопка удаления
                $alterF = 'function(){$("#checkres'.$this->curIndex.' button[name='.$table.']").attr("disabled", "disabled")}';
                $cdel = [$table => array_keys($arr)];
                $err.= "<br><button name='$table' onclick='check($this->curIndex, ".\sys\json($cdel).", $alterF)' >Удалить</button><br>";
                $answ[] = $err;
                $delShowArr = array_merge($cdel, $delShowArr);
            }
        }

        // общая кнопка удаления
        if($delShowArr){
            $alterF = 'function(){$("#checkres'.$this->curIndex.' button").attr("disabled", "disabled")}';
            $answ[]= "<br><button onclick='check($this->curIndex, ".\sys\json($delShowArr).", $alterF)' >Удалить все</button>";
        }
        return $answ;
    }

    public function chkJournal($params){
        $limit = 100;
        $answ = [];
        $tablesHas = [];
        $tablesHasnt = [];

        $skipTableList = ['staff'];

        $skipTableList = "'" . implode("','", $skipTableList) . "'";
        $qry = "SELECT entityid tid, entityidid id, usrlogproptypid typ, e.tablenam tnam, l.datt
		FROM usrlog l
		INNER JOIN (
			SELECT entityid, entityidid, MAX(datt) datt, MAX(id) id
			FROM usrlog l
			INNER JOIN usrlogprop lp ON l.id=lp.usrlogid AND lp.usrlogproptypid IN(12,13)
			GROUP BY entityid, entityidid
		) lg USING(datt, entityid, entityidid, id)
		INNER JOIN usrlogprop lp ON l.id=lp.usrlogid
		LEFT JOIN entity e ON entityid=e.id
		WHERE lp.usrlogproptypid IN(12,13) AND entityidid
		AND e.tablenam NOT IN($skipTableList)
		GROUP BY tid, entityidid ASC
		ORDER BY l.datt DESC, l.id DESC";
        $res = \db::result($qry);
        while($arr = $res->fetch_assoc()){
            if(!$arr['tnam']){
                $answ[] = 'В таблице entity не найдена запись с id='.$arr['tid'];
                continue;
            }
            $tnam = $arr['tnam'];
            $id = $arr['id'];
            $typ = $arr['typ'];
            if(isset($tablesHas[$tnam]) and count($tablesHas[$tnam])>$limit+1)
                continue;

            if(isset($tablesHasnt[$tnam]) and count($tablesHasnt[$tnam])>$limit+1)
                continue;

            if(!key_exists($tnam, $this->tables) or !key_exists('id', $this->tables[$tnam]))
                continue;

            $qry = "SELECT id FROM $tnam WHERE id='$id'";
            $val = \db::val($qry);
            if($typ==12 and $val){
                if(!isset($tablesHas[$tnam]))
                    $tablesHas[$tnam] = Array();
                $tablesHas[$tnam][] = $id;
            }else if($typ==13 and !$val){
                if(!isset($tablesHasnt[$tnam]))
                    $tablesHasnt[$tnam] = Array();
                $tablesHasnt[$tnam][] = $id;
            }
        }
        $res->close();

        foreach ($tablesHas as $tnam=>$ids){
            $lrg = count($ids)>$limit;
            $ids = implode(',', array_unique($ids));
            $txt = "Таблица <b>$tnam</b> имеет существующие
				<a href='?usrlog&staticfilter[entity]=$tnam&staticfilter[id]=$ids' target='_blank'>
				записи</a>, отмеченые в журнале удаленными";
            if($lrg)
                $txt.= " (первые $limit записей)";
            $answ[]= $txt;
        }
        foreach ($tablesHasnt as $tnam=>$ids){
            $lrg = count($ids)>$limit;
            $ids = implode(',', array_unique($ids));
            $txt = "Таблица <b>$tnam</b> не имеет
				<a href='?usrlog&staticfilter[entity]=$tnam&staticfilter[id]=$ids' target='_blank'>
				записи</a>, отмеченые в журнале добавленными";
            if($lrg)
                $txt.= " (первые $limit записей)";
            $answ[]= $txt;
        }
        return $answ;
    }

    public function chkTree($params){
        // 	'rub'=>'lft,rgt,rubvalid,',
        // 'dep'=>'lft,rgt,nam,deptypid'
        $trees = [
            'gis'=>'lft,rgt,namua,gistypid',
        ];

        $answ = [];
        foreach ($trees as $treenam => $keys){
            $keys = \sys\makeArray($keys);
            $tree = new cl_tree($treenam, $keys[0], $keys[1]);
            $tree->setUniqFnam($keys[2]);
            $cerr = $tree->check();
            if($cerr){
                $answ[] = "Таблица '$treenam', ошибки:";
                $answ = array_merge($answ, $cerr);
            }
        }
        return $answ;
    }

    public function chkTreeUniqe($params){
        // 			'rub'=>'lft,rgt,rubvalid,,1',
        // 'dep'=>'lft,rgt,nam,deptypid,'
        $trees = [
            'gis'=>'lft,rgt,namua,gistypid,',
        ];
        $answ = [];

        $del = [];
        if(isset($params['extra'])){
            $arr = explode(';', $params['extra']);
            foreach ($arr as $sarr){
                list($t, $ids) = explode(':', $sarr);
                if(!isset($del[$t]))
                    $del[$t] = Array();
                $del[$t][] = explode('_', $ids);
            }
        }

        if($del){
            foreach ($del as $treenam=>$data){
                if(!isset($trees[$treenam]))
                    continue;

                list($l, $r, $uf, $af, $et) = \sys\makeArray($trees[$treenam]);
                $tree = new cl_tree($treenam, $l, $r, 'lvl', 'id', $uf);

                foreach ($data as $idsGrp)
                    for($i=1; $i<count($idsGrp); $i++)
                        $tree->remove($idsGrp[$i]);
            }
            return [];
        }

        foreach ($trees as $treenam=>$sets){
            $isFirst = true;
            list($l, $r, $uf, $af, $et) = \sys\makeArray($sets);
            $aff = $af ? ", t.$af" : '';

            $qry = "SELECT c.id, c.$uf val, t.id par, t.$r pr, t.$l pl
			FROM (
				SELECT tp.id, t.$uf, tp.$l, tp.$r, tp.lvl $aff FROM $treenam t
				LEFT JOIN $treenam tp ON t.$l > tp.$l AND t.$r < tp.$r AND t.lvl = tp.lvl + 1
				GROUP BY tp.id, t.$uf $aff HAVING COUNT(t.$uf)>1
			) t
				LEFT JOIN $treenam c ON t.$l < c.$l AND t.$r > c.$r AND t.$uf = c.$uf AND t.lvl + 1 = c.lvl";
            if($af)
                $qry.= " AND t.$af = c.$af";
            $arr = \db::iarr($qry);

            if($arr){
                $prevPar = $prev = 0;
                $simIds = Array();
                foreach ($arr as $id=>$vals){
                    $val = $vals['val'];

                    if($isFirst){
                        $isFirst = false;
                        $answ[]= "������� <b>$treenam</b>:";
                    }

                    if($prev!=$val){
                        if($simIds){
                            $sim_ = implode('_', $simIds);
                            $showVal = $et ? (\db::val("SELECT val FROM {$treenam}val WHERE id=$prev")." ($prev)") : $prev;
                            $bid = $treenam.'_'.$sim_;
                            $alterFunc = 'function(answ){
							$("#check'.$this->curIndex.' #'.$bid.'").attr("disabled", "disabled");
							}';

                            $answ[] = "<button id='$bid' onclick='check($this->curIndex, \"{$treenam}:{$sim_}\", $alterFunc)'>Удалить</button>"
                                ." ids: ".implode(',', $simIds).", значение - <b>$showVal</b>";

                            $simIds = Array();
                        }
                    }
                    $par = $vals['par'];
                    if($par != $prevPar){
                        $fld = $et ? "tv.val" : "t.$uf";
                        $join = $et ? "LEFT JOIN {$treenam}val tv ON t.$uf=tv.id" : '';
                        $qry = "SELECT GROUP_CONCAT($fld ORDER BY t.lvl SEPARATOR ', ') nam
						FROM $treenam t
						$join WHERE $vals[pl] >= t.$l AND $vals[pr] <= t.$r";
                        $parNam = \db::val($qry);
                        $answ[] = "Родитель: <i>$parNam</i> ($par)";
                    }

                    $simIds[]= $id;
                    $prev = $val;
                    $prevPar = $vals['par'];
                }

                $sim_ = implode('_', $simIds);
                $showVal = $et ? (\db::val("SELECT val FROM {$treenam}val WHERE id=$prev")." ($prev)") : $prev;
                $bid = $treenam.'_'.$sim_;
                $alterFunc = 'function(answ){
					$("#check'.$this->curIndex.' #'.$bid.'").attr("disabled", "disabled");
				}';

                $answ[] = "<button id='$bid' onclick='check($this->curIndex, \"{$treenam}:{$sim_}\", $alterFunc)'>Удалить</button>"
                    ." ids: ".implode(',', $simIds).", значение - <b>$showVal</b>";
            }
        }
        return $answ;
    }

    public function chkPrKeyRefsDelete($table){
        $params = ['chktable' => $table, 'delete' => 1];
        $answ = $this->chkPrKeyRefs($params);

        $html = isset($answ['rows']) ? $answ['rows'] : implode("<br>", $answ);
        $delete = isset($answ['delete']) ? $answ['delete'] : 0;

        return ['html' => $html, 'deleted' => $delete];
    }

    public function chkPrKeyRefs($params){
        $limit=30;
        $answ = [];

        $deleted = 0;
        // использование SQL_CALC_FOUND_ROWS
        $useFoundRows = 1;
        // фильтр проверки для имён таблиц
        $checks = isset($params['chktable']) ? \sys\makeArray($params['chktable']) : [];
        if(isset($params['delete'])){
            $useFoundRows = 0;
        }

        // дополнительные услови при проверке таблиц
        $extraConds = [];

        // непроверяемые таблицы
        $skipCheck = ['opt'	, 'entity'];

        // из каких таблиц не давать удалять
        $skipDel = [
            'entity',
            'gistyp',
            'optglob',
            'rubproptyp',
            'taskdat',
            'taskstate',
            'teltyp',
            'usrevttyp',
            'usrlogproptyp',
            'contacttyp',
            'telcode'
        ];

        // пропуск проверки по определенным соединяемым таблицам
        $partSkip = [];

        // целостность косвенных ссылок
        $links = \db::select("SELECT tablenam, id FROM entity");
        foreach(self::$indirectLinks as $maintbl => $childs){
            if(!isset($links[$maintbl])){
                $answ[] = "Запись о таблице '$maintbl' отсутствует в таблице entity";
                break;
            }
            if(!isset($this->tables[$maintbl]['entityid'])){
                $answ[] = "Таблица '$maintbl' должна иметь поле 'entityid' согласно списку проверки";
                break;
            }
        }
        if($answ)
            return $answ;

        // проверка наличия таблиц для фильтра
        for($len = count($checks), $i = $len - 1; $i > -1; $i--){
            if(!isset($this->tables[$checks[$i]])){
                $answ[]= "Таблицы {$checks[$i]} не найдена в БД";
                array_splice($checks, $i, 1);
            }
        }

        foreach($this->tables as $tnam=>$fields){
            if($checks && in_array($tnam, $checks) === false){
                continue;
            }
            if(!isset($links[$tnam])){
                $answ[] = "Запись о таблице '$tnam' отсутствует в таблице entity";
                continue;
            }
            if(!isset($fields['id']) || in_array($tnam, $skipCheck) !== false){
                continue;
            }
            if(isset($params['delete']) && in_array($tnam, $skipDel) !== false){
                $answ[] = "Нельзя удалять из таблицы '$tnam'";
                continue;
            }

            $rtables = [];
            foreach($this->tables as $ctnam=>$cflds){
                if(isset($partSkip[$tnam]) and in_array($ctnam, \sys\makeArray($partSkip[$tnam])))
                    continue;

                if(isset($cflds[$tnam.'id']) )
                    $rtables[]= $ctnam;
            }

            // таблицы "standalone" - без доп. таблиц типов и прочих, ссылающихся на их id
            if(!$rtables){
                continue;
            }

            $where = isset($extraConds[$tnam]) ? \sys\makeArray($extraConds[$tnam]) : [];
            $calc = $useFoundRows ? "SQL_CALC_FOUND_ROWS" : "";
            $qry = "SELECT DISTINCT $calc o.id FROM $tnam o WHERE ";
            foreach ($rtables as $rtnam){
                $where[] = "o.id NOT IN(SELECT {$tnam}id FROM $rtnam WHERE {$tnam}id IS NOT NULL)";
            }

            // проверять косвенные связи
            $wrs = [];
            foreach(self::$indirectLinks as $maintbl => $childs){
                $wrs []= "o.id NOT IN(
				SELECT chl.{$childs['val']} FROM $maintbl AS mt
				INNER JOIN {$childs['table']} AS chl ON mt.id=chl.{$maintbl}id
				WHERE chl.{$childs['val']} IS NOT NULL AND mt.entityid={$links[$tnam]}
				)";
            }
            if($wrs)
                $where = array_merge($where, $wrs);

            $qry.= implode(" AND ", $where);
            if(!isset($params['delete']))
                $qry .= " LIMIT ".($limit+1);
            if(isset($params['delete'])){
                $ids = \db::col($qry);
                if($ids){
                    $sids = implode(",", $ids);
                    $qry = "DELETE FROM $tnam WHERE id IN ($sids)";
                    $cntdel = \db::query($qry);
                    $answ[] = "$tnam �������: $cntdel";
                    $deleted += $cntdel;
                }
            } else if($arr = \db::col($qry)){
                $sdel = "<a title='Удалить неиспользуемые сущности' datatable='$tnam' style='cursor:pointer;' onclick='chkPrKeyRefs(event)'>$tnam</a>";
                if(in_array($tnam, $skipDel) !== FALSE)
                    $sdel = $tnam;
                $srows = "";
                if($useFoundRows){
                    $cntrows = \db::val('SELECT FOUND_ROWS()');
                    $srows = " ($cntrows) ";
                }
                $answ[] = "Таблица <b>$sdel</b> не имеет ни одной ссылки на id из таблиц "
                    .implode(',', $rtables).$srows.' по следующим значениям: '.implode(',', $arr);
            }
        }
        if(isset($params['delete']))
            return ['rows' => implode("<br>", $answ), 'delete' => $deleted];
        return $answ;
    }

    public function chkTelCodeGis($params){
        $limit = 30;
        $qry = "SELECT tc.*, cc.lang, cc.gisid ccgisid, (
			SELECT gp.id FROM gis g
				INNER JOIN gis gp ON g.lft>=gp.lft AND g.rgt<=gp.rgt
			WHERE g.id=tc.gisid ORDER BY gp.lvl LIMIT 1
		) tcrgisid
		FROM telcode tc
			INNER JOIN countrycode cc ON cc.id=tc.countrycodeid
		HAVING ccgisid<>tcrgisid
		LIMIT 1";
        $answ = \db::arr($qry);
        foreach($answ as &$row)
            $row = "Код $row[val] ($row[lang]), ГИС не соответствует";
        return $answ;
    }

    public function chkTaskIntegrity($params){
        $answ = array();
        $mtask= new m_task();

        // 2 - Пересечение дат (datt-runtime);
        $chk2= array();
        $qry= "SELECT DISTINCT doerstaffid FROM task WHERE taskdatid=1";
        $doers= \db::col($qry);
        foreach($doers as $doerstaffid){
            $data= $mtask->checkTaskDoerstaffid($doerstaffid, []);
            if($data && !$chk2)
                $chk2[]= "Пересечение дат (datt-runtime):";
            foreach($data as $v){
                if($v['error']){
                    $href="?task&setfilters[tskid]={$v['taskid']}";
                    $str= $v['error'];
                } else {
                    $href="?task&staticfilter[tskid]={$v['taskid']}";
                    $str= $v['taskid'];
                }
                $a= "<a title='Редактор задач' href='$href' target='task'>$str</a>";
                $chk2[]= $a;
            }
        }

        // 3 Вложенность задач (вложенные внутри родительской)
        $chk3= array();
        $data= $mtask->checkTaskNesting(array());
        if($data && !$chk3)
            $chk3[]= "Вложенность задач (вложенные внутри родительской):";
        foreach($data as $v){
            if($v['error']){
                $href="?task&setfilters[tskid]={$v['taskid']}";
                $str= $v['error'];
            } else {
                $href="?task&staticfilter[tskid]={$v['taskid']}";
                $str= $v['taskid'];
            }
            $a= "<a title='Редактор задач' href='$href' target='task'>$str</a>";
            $chk3[]= $a;
        }

        // 4 Зависимые задачи (после тех, от которых зависят)
        $chk4= array();
        $data= $mtask->checkTaskDepend(array());
        if($data && !$chk4)
            $chk4[]= "Зависимые задачи (после тех, от которых зависят):";
        foreach($data as $v){
            if($v['error']){
                $href="?task&setfilters[tskid]={$v['taskid']}";
                $str= $v['error'];
            } else {
                $href="?task&staticfilter[tskid]={$v['taskid']}";
                $str= $v['taskid'];
            }
            $a= "<a title='Редактор задач' href='$href' target='task'>$str</a>";
            $chk4[]= $a;
        }

        // 5 Таблица taskrel (taskid = taskfromid):
        $chk5= array();
        $col= \db::col("SELECT taskid FROM taskrel WHERE taskid=taskfromid");
        if($col){
            $chk5[]= "Таблица taskrel (taskid = taskfromid):";
            $chk5[]= implode(",", $col);
        }

        // 6 для контрольных дат должны быть указаны Время окончания и Длительность
        $chk6= array();
        $col= \db::col("SELECT id FROM task WHERE taskdatid=1 AND (datt IS NULL OR runtim IS NULL)");
        if($col)
            $chk6[]= "для контрольных дат не указаны datt или runtim:";
        foreach($col as $taskid){
            $href="?task&setfilters[tskid]=$taskid";
            $a= "<a title='Перейти в редактор задач' href='$href' target='task'>$taskid</a>";
            $chk6[]= $a;
        }

        // 7 если указан Тип времени окончания, то обязательно должна быть Время окончания
        $chk7= array();
        $col= \db::col("SELECT id FROM task WHERE taskdatid IS NOT NULL AND datt IS NULL");
        if($col)
            $chk7[]= "есть taskdatid, но нету datt:";
        foreach($col as $taskid){
            $href="?task&setfilters[tskid]=$taskid";
            $a= "<a title='Перейти в редактор задач' href='$href' target='task'>$taskid</a>";
            $chk7[]= $a;
        }

        // 8 уникальность очереди у исполнителей; queue должно быть уникальным (кроме queue=0);
        $chk8= array();
        // выбираем повторы очереди для всех исполнителей
        $arr= \db::arr("SELECT group_concat(id) ids, doerstaffid, COUNT(queue) cnt
			FROM task
			WHERE queue <>0 AND taskstateid NOT IN(6, 10)
			GROUP BY doerstaffid, queue
			HAVING cnt >1");
        if($arr)
            $chk8[]= "Уникальность очереди у исполнителей:";
        foreach($arr as $row){
            $href="?task&staticfilter[tskid]={$row['ids']}";
            $a= "<a title='Перейти в редактор задач' href='$href' target='task'>{$row['ids']}</a>";
            $chk8[]= $a;
        }

        // 9 очереди задач с контрольной датой
        $chk9= array();
        $qry= "SELECT t.id task1id, t2.id task2id, t.queue queue1, t2.queue queue2, t.datt datt1, t2.datt datt2
			FROM task AS t,
			(SELECT id, doerstaffid, queue, datt FROM task WHERE taskdatid=1)t2
			WHERE t.taskdatid=1 AND t.doerstaffid=t2.doerstaffid AND t.queue>t2.queue AND t.datt<t2.datt";
        $arr= \db::arr($qry);
        if($arr)
            $chk9[]= "Очереди задач с контрольной датой:";
        foreach($arr as $row){
            $tmp= "{$row['task1id']},{$row['task2id']}";
            $href="?task&staticfilter[tskid]=$tmp";
            $str= "$tmp: Очередь({$row['queue1']}) Дата({$row['datt1']}) -> Очередь({$row['queue2']}) Дата({$row['datt2']})";
            $a= "<a title='Перейти в редактор задач' href='$href' target='task'>$str</a>";
            $chk9[]= $a;
        }

        // 10 Периодические задачи с вложенность более 1:
        $chk10= array();
        $qry= "SELECT tr1.taskid topid, tr.taskid, tr.taskfromid
			FROM taskrel tr
			INNER JOIN taskrel tr1 ON tr1.typ=3 AND tr1.taskfromid=tr.taskid
			WHERE tr.typ=3";
        $arr= \db::arr($qry);
        if($arr)
            $chk10[]= "Периодические задачи с вложенность более 1:";
        foreach($arr as $row){
            $tmp= "{$row['taskfromid']},{$row['taskid']},{$row['topid']}";
            $href="?task&staticfilter[tskid]=$tmp";
            $str= "{$row['taskfromid']} -> {$row['taskid']} -> {$row['topid']}";
            $a= "<a title='Перейти в редактор задач' href='$href' target='task'>$str</a>";
            $chk10[]= $a;
        }

        // 11 Периодические задачи с двумя родителями:
        $chk11= array();
        $qry= "SELECT tr.taskfromid
			FROM taskrel tr
			WHERE tr.typ=3
			GROUP BY taskfromid
			HAVING count(tr.taskid) > 1";
        $arr= \db::arr($qry);
        if($arr)
            $chk11[]= "Периодические задачи с более 1 родителем:";
        foreach($arr as $row){
            $tmp= "{$row['taskfromid']}";
            $href="?task&staticfilter[tskid]=$tmp";
            $str= "{$row['taskfromid']}";
            $a= "<a title='Перейти в редактор задач' href='$href' target='task'>$str</a>";
            $chk11[]= $a;
        }

        // 12 исполнитель недоступен
        $chk12= array();
        $qry= "SELECT id, doerstaffid FROM task WHERE taskstateid NOT IN(6,8,10)";
        $tasks= \db::arr($qry);
        if($tasks){
            // получить всех работающих
            $staffids= array();
            $mdep = new m_dep();
            $arr= $mdep->qryUsers();
            foreach($arr as $v){
                $staffids[]= $v['id'];
            }
            foreach($tasks as $v){
                if(!in_array($v['doerstaffid'], $staffids)){
                    $href="?task&setfilters[tskid]={$v['id']}";
                    $a= "<a title='Перейти в редактор задач' href='$href' target='task'>{$v['id']}</a>";
                    $chk12[]= $a;
                }
            }
            if($chk12)
                $chk12 = array_merge(array("Исполнитель недоступен:"), $chk12);
        }

        // 13) Cоздать уведомления о начале задач
        $chk13= array();
        $dt= date("Y-m-d H:i:00");
        $inf= \db::iarr("SELECT id, datt, runtim FROM task WHERE datt > '$dt' AND taskstateid NOT IN(6,10)");
        $tids= array_keys($inf);
        // какие уже события созданы
        if($tids){
            $stids = implode(",", $tids);
            $tidsexist = \db::col("SELECT entityidid FROM usrevt WHERE usrevttypid=".uet::TASKRUNTIME." AND entityidid IN ($stids)");
            $tids = array_diff($tids, $tidsexist);
        }
        if($tids){
            // рабочие дни
            $dats= \db::col("SELECT dat FROM wrkdat ORDER BY dat ASC");
            foreach($inf as $taskid => $row){
                if(in_array($taskid, $tids) && $mtask->taskEvtCheckStartDatt($row['datt'], $row['runtim'], $dats)){
                    $href="?task&setfilters[tskid]=$taskid";
                    $a= "<a title='Перейти в редактор задач' href='$href' target='task'>$taskid</a>";
                    $chk13[]= $a;
                }
            }
            if($chk13){
                $stids = implode(",", $tids);
                $sa = "<a data='$stids' datatype='start' style='cursor:pointer;' onclick='evtCreateTask(event)'>Cоздать уведомления о начале задач:</a>";
                $chk13 = array_merge(array($sa), $chk13);
            }
        }

        // 14) Cоздать уведомления об окончании задач;
        $chk14= array();
        $dt= date("Y-m-d H:i:00");
        $tids= \db::col("SELECT id FROM task WHERE datt > '$dt' AND taskstateid NOT IN(6,10)");
        // какие уже события созданы
        if($tids){
            $stids = implode(",", $tids);
            $tidsexist = \db::col("SELECT entityidid FROM usrevt WHERE usrevttypid=".uet::TASKCOMPLETE." AND entityidid IN ($stids)");
            $tids = array_diff($tids, $tidsexist);
        }
        if($tids){
            foreach($tids as $taskid){
                $href="?task&setfilters[tskid]=$taskid";
                $a= "<a title='Перейти в редактор задач' href='$href' target='task'>$taskid</a>";
                $chk14[]= $a;
            }
            if($chk14){
                $stids = implode(",", $tids);
                $sa = "<a data='$stids' datatype='end' style='cursor:pointer;' onclick='evtCreateTask(event)'>Cоздать уведомления об окончании задач:</a>";
                $chk14 = array_merge(array($sa), $chk14);
            }
        }

        $answ= array_merge($chk2, $chk3, $chk4, $chk5, $chk6, $chk7, $chk8, $chk9, $chk10, $chk11, $chk12, $chk13, $chk14);
        return $answ;
    }

    // проверка файлов
    public function chkExistFile($params){
        $a0= $this->fileDiskChecker0();
        $a1= $this->fileDiskChecker1();
        $a2= m_file::fileDiskChecker2();
        $a3= $this->fileDiskChecker3();
        $a4= $this->fileDiskChecker4();
        $a5= $this->fileDiskChecker5();
        $a6= $this->fileDiskChecker6();
        $answ= array_merge ( $a0, $a1, $a2, $a3, $a4, $a5, $a6 );
        return $answ;
    }

    // Сжатый файл не может иметь размер 0 байт
    private function fileDiskChecker6(){
        $qry= "SELECT fd.id FROM filedisk fd LEFT JOIN file AS f ON fd.id=f.filediskid WHERE fd.compress=2 AND siz=0";
        $row= \db::col($qry);
        $row= array_unique($row);
        $answ= array();
        if($row)
            $answ[]= "Сжатый файл размером 0 байт:";
        foreach($row as $v){
            $answ[]= "<a target=_blank href='?filechecker=1&setfilters[fi_id]={$v}'>{filedisk: $v}</a>";
        }
        return $answ;
    }

    // наличие записи в filedisk и отсутствие в file
    private function fileDiskChecker5(){
        $qry= "SELECT fd.id FROM filedisk fd LEFT JOIN file AS f ON fd.id=f.filediskid WHERE f.id IS NULL";
        $row= \db::col($qry);
        $answ= array();
        foreach($row as $v){
            $answ[]= "Отсутствие записи в таблице file:<a target=_blank href='?filechecker=1&setfilters[fi_id]={$v}'>{filedisk: $v}</a>";
        }
        return $answ;
    }

    // проверка на правильность имён файлов
    private function fileDiskChecker4(){
        $answ= array();
        $qry= 'SELECT id, IF(typ="", nam, CONCAT(nam,".",typ)) nam, filediskid FROM file
		WHERE nam="" OR nam REGEXP "[*\\/:*?\"<>|]" OR typ REGEXP "[*\\/:*?\"<>|]"';
        $res = \db::result($qry);
        while($r = $res->fetch_assoc()){
            $answ[]= "Неправильное имя файла:<a target=_blank href='?filechecker=1&setfilters[fi_id]={$r['filediskid']}'>{$r['nam']}</a>";
        }
        return $answ;
    }

    // сверка наличия записей в таблице файл и отсутствия их в таблице mess_file
    private function fileDiskChecker1(){
        $answ= array();

        $mfile= new m_file();
        $fids= $mfile->integrityMessFile(Array(), false);
        if(!$fids)
            return $answ;
        $fids= implode(",", $fids);
        $qry= "SELECT f.id fid, f.nam, f.typ, fd.id fdid, fd.siz FROM file AS f
			LEFT JOIN filedisk AS fd ON f.filediskid=fd.id
			WHERE f.id IN ($fids)";

        $res = \db::result($qry);
        $ilen= $res->num_rows;
        $answ[]= "На файлы нет ссылок в других таблицах.";
        $answ[]= "Количество файлов: $ilen.";
        $fdids= array();
        $fids= array();
        $siz= 0;

        while($r = $res->fetch_assoc()){
            $siz+= $r['siz'];
            if($r['fdid'] != NULL)
                $fdids[]= $r['fdid'];
            if($r['fid'] != NULL)
                $fids[]= $r['fid'];
        }

        $fdids= array_unique($fdids);
        $fids= array_unique($fids);
        $answ[]= "Размер файлов: $siz (".m_func::Size2Str($siz).").";
        $lnk=implode(',', $fdids);
        $flidlnk= implode(',', $fids);
        $a1= "<a target='wndfileintegrity' href='?filechecker&fid=$lnk'>fileid: $flidlnk</a>";
        $a2= "<a target='wndfiledelete' href='?integrityfldel=$flidlnk&integrityfileid=1'>Удалить файлы</a>";
        $answ[]= $a1."&nbsp;&nbsp;&nbsp;".$a2;

        return $answ;
    }

    // проверка количество usr_file и количество ссылок на диске
    private function fileDiskChecker3(){
        $answ= array();

        // получаем с диска пути
        $pt= \APP::getConfig()->getPathLink();
        $dels= array('.', '..');
        $adisk= scandir($pt);
        $adisk= array_diff($adisk, $dels);
        $icnt= count($adisk);

        // получаем из базы пути
        $qry= "SELECT CONCAT(REPLACE(REPLACE(REPLACE(datt,':',''),'-',''), ' ', '_'), '_', filepath) FROM usr_file";
        $adb= \db::col($qry);

        $a1= array_diff($adisk, $adb);
        if($a1){
            $a2= array();
            // не учитывать в проверке при  наличии строки в .htaccess #eml attach, no record in database table file-filedisk
            foreach($a1 as $v){
                $hpath= "$pt/$v/.htaccess";
                if(file_exists($hpath)){
                    $flines= file($hpath);
                    if(strpos($flines[0], "#eml attach, no record in database table file-filedisk") === FALSE){
                        $a2[]= $v;
                    }
                }
            }
            if($a2){
                $answ[]= "usr_filelink. Количество записей, отсутствующих в БД:".count($a2);
                $answ[]= implode(',', $a2);
            }
        }

        $a1= array_diff($adb, $adisk);
        if($a1){
            $answ[]= "usr_filelink. Количество записей, отсутствующих на диске: ".count($a1);
            $answ[]= implode(',', $a1);
        }
        return $answ;
    }

    // сверка размеров файлов в БД с размерами файлов на диске
    private function fileDiskChecker0(){
        $a= array();
        $limit = 1000;

        $qry= "SELECT id, dat, siz, sizoryg, hash, compress FROM filedisk fi ORDER BY ID desc";
        $res= \db::result($qry);
        while($r = $res->fetch_assoc()){
            $fid= $r['id'];
            $fdat= $r['dat'];
            $fsiz= $r['siz'];
            $fsizoryg= $r['sizoryg'];
            $cmpr= $r['compress'];
            $siz= m_file::fsize($fid, $fdat);
            if($siz == -1)
                $siz= "Отсутствует";

            if($siz != $fsiz
                || ($fsizoryg != $siz && $cmpr == 1) // если файл не сжат и размеры отличаются
            ){
                $val= "<p>Не совпадают размеры.(ID:$fid;Размер в БД:$fsiz;Ориг.размер:$fsizoryg;Размер на диске:$siz)
				<a target='wndfileintegrity' href='?filechecker&setfilters[fi_id]=$fid'>Показать...</a>";

                if($fsiz > $siz && $siz > 0 && $cmpr < 2)
                    $val.= "&nbsp;&nbsp;&nbsp;<a target='wndfiledelete' href='?integrityfldel=$fid&integrityfldeldat=$fdat&integritymakecomp=2'>Сделать сжатым</a>";
                $val.= "</p>";
                $a[]= $val;
            }
            if(count($a) > $limit){
                $a[]= "...";
                break;
            }
        }
        return $a;
    }


}