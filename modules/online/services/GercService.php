<?php
namespace app\modules\online\services;

/**
 * парсинг сайта Gerc
 */
class GercService {

    /**
     * этот метод запускался по крону для парсинга
     */
    public function cronGercResurection(){
        $row = \db::row("SELECT stopped, datt, flatid FROM gercstatus WHERE id=1");
        if(!$row || !isset($row['stopped']) || $row['stopped'])
            return FALSE;

        $dbTime = strtotime($row['datt']);
        $servTime = strtotime(date("H:i:s"));

        // if 5 minutes not parsing - start parsing
        if($servTime - $dbTime > 299){

            // try stop other process
            \db::query("UPDATE gercstatus SET stopped=1 WHERE id=1");
            sleep(3);
            \db::query("UPDATE gercstatus SET stopped=0 WHERE id=1");


            $flayid = $row['flatid'];

            \db::query("UPDATE gercstatus SET datt=CURRENT_TIMESTAMP WHERE id=1");

            $gerc = new m_gerc();

            $gerc->parseNanimatels($flayid);

            return TRUE;
        }
        return FALSE;
    }

    function curl_primer(){
        $crl = curl_init();
        curl_setopt($crl, CURLOPT_URL, 'pandy.myftp.org');//"http://www.kino.odessa.ua"
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLOPT_PROXY, '125.163.201.71:8080');
        return curl_exec($crl);
    }

    function file_post_contents22($url,$headers=false) {
        $url = parse_url($url);

        if (!isset($url['port'])) {
            if ($url['scheme'] == 'http') { $url['port']=80; }
            elseif ($url['scheme'] == 'https') { $url['port']=443; }
        }
        $url['query']=isset($url['query'])?$url['query']:'';

        $url['protocol']=$url['scheme'].'://';
        $eol="\r\n";

        $headers =  "POST ".$url['protocol'].$url['host'].$url['path']." HTTP/1.0".$eol.
            "Host: ".$url['host'].$eol.
            "Referer: ".$url['protocol'].$url['host'].$url['path'].$eol.
            "Content-Type: application/x-www-form-urlencoded".$eol.
            "Content-Length: ".strlen($url['query']).$eol.
            $eol.$url['query'];
        $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30);
        if($fp) {
            fputs($fp, $headers);
            $result = '';
            while(!feof($fp)) { $result .= fgets($fp, 128); }
            fclose($fp);
            if (!$headers) {
                //removes headers
                $pattern="/^.*\r\n\r\n/s";
                $result=preg_replace($pattern,'',$result);
            }
            return $result;
        }
    }


    /**
     * post ������
     * @param string $url
     * @param array $data
     * @param string $cookie
     * @return boolean|string
     */
    private function file_post_contents($url, $data, $cookie="") {
        // ����� ��������� ������� ��������� ����������� ��������
        $port = "443";
        $timeout = "5";
        $url_array = parse_url($url);
        $kanal = @fsockopen ($url_array['host'], $port, $errno, $errstr, $timeout);
        if(!$kanal) return FALSE;
        if($kanal) fclose($kanal);

        $data = http_build_query($data);
        $header= ($cookie == "") ?
            "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: ". strlen($data). "\r\n" :
            "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: ". strlen($data). "\r\nCookie: ".$cookie."\r\nUser-Agent:	Mozilla/5.0 (Windows NT 5.1; rv:32.0) Gecko/20100101 Firefox/32.0\r\n";

// 		if($cookie){
// 			$headers = [
// 				"Accept:	application/json, text/javascript, */*",
// 				"Accept-Encoding:	gzip, deflate",
// 				"Accept-Language:	ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3",
// 				"Content-Length:	".strlen($data),
// 				"Content-Type:	application/x-www-form-urlencoded; charset=UTF-8",
// 				"Cookie:	PHPSESSID=45en8beincqj5kq1svvub72k63; __utma=101514135.1491633926.1411546728.1411546728.1411546728.1; __utmb=101514135.22.10.1411546728; __utmc=101514135; __utmz=101514135.1411546728.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); __gercudata=5e9ebd14d4cd5b326ee604b71e8ee903",
// 				"DNT:	1",
// 				"Host:	www.gerc.ua",
// 				"Referer:	https://www.gerc.ua/infocenter/house",
// 				"User-Agent:	Mozilla/5.0 (Windows NT 5.1; rv:32.0) Gecko/20100101 Firefox/32.0",
// 				"X-Requested-With:	XMLHttpRequest",
// 			];
// 			$header = implode("\r\n", $headers);
// 		}

        $context_options = array(
            "http" => array(
                "method" => "POST",
                "header" => $header,
                "content" => $data,
                "timeout" => 15
            )
        );

        $context = stream_context_create($context_options);
        return file_get_contents($url, false, $context);
    }

    /**
     * ��������� ��������� ���, ��� ...
     */
    private function generateAlp(){
        $_alp = [168=>'�',192=>'�',193=>'�',194=>'�',195=>'�',196=>'�',197=>'�',198=>'�',199=>'�',200=>'�',201=>'�',202=>'�',203=>'�',204=>'�',205=>'�',206=>'�',207=>'�',208=>'�',209=>'�',210=>'�',211=>'�',212=>'�',213=>'�',214=>'�',215=>'�',216=>'�',217=>'�',218=>'�',219=>'�',220=>'�',221=>'�',222=>'�',223=>'�'];
        $res = [];
        foreach($_alp as $key1 => $simv1){
            foreach($_alp as $key2 => $simv2){
                foreach($_alp as $key3 => $simv3){
                    $res[] = "$simv1$simv2$simv3";
                }
            }
        }
        return $res;
    }

    /**
     *	������ ����
     * @param string $fromPattern
     */
    public function parseStreets($fromPattern = ""){
        $url = "https://www.gerc.ua/street2/?";
        $params = [
            'limit'=>10000,
            'q'=>'���',
            'timestamp'=>time() . "123"
        ];

        $isfind = false;
        $ar = $this->generateAlp();
        foreach($ar as $str){
            if($str == $fromPattern || $fromPattern == ''){
                $isfind = true;
            }
            if(!$isfind)
                continue;
            //\sys\show($str);

            $params['q'] = \Encoder\Coder::coding(strtolower($str), 0, 2);
            $data = http_build_query($params);
            $html = \Encoder\Coder::coding(file_get_contents($url.$data), 0, 1);

            $streets = explode("\r\n", $html);
            foreach ($streets as $key => $val){
                $streetinfo = explode('|', trim($val), 2);
                if(count($streetinfo) != 2)
                    continue;
                $qry = "INSERT IGNORE INTO gercstreet(id,nam)
				VALUES('{$streetinfo[0]}','{$streetinfo[1]}') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)";
                \db::query($qry);
            }
        }
    }

    /**
    ������ ����� �� ������
     */
    public function parseHouses($fromPattern = ''){
        $url = "https://www.gerc.ua/service/";
        $params = [
            'street_id'=>"010486",
            'ac'=>'get',
            'obj'=>'House'
        ];

        $isfind = false;
        $qry = "SELECT id, nam FROM gercstreet ORDER BY nam ASC";

        /*$qry = "SELECT gs.* FROM `gercstreet` gs LEFT JOIN gerchouse gh ON gs.id=gh.gercstreetid
            WHERE gh.gercstreetid IS NULL
        GROUP BY gs.id";*/

        $streets = \db::select($qry);

        foreach($streets as $streetid => $streetnam){
            if($streetid == $fromPattern || $fromPattern == ''){
                $isfind = true;
            }
            if(!$isfind)
                continue;

            \sys\show('nextid', $streetid);

            $params['street_id'] = $streetid;
            $streetdata = $this->file_post_contents($url, $params, '');
            $ar = \Encoder\Coder::coding(json_decode($streetdata, true), 0, 1);

            if(isset($ar['record'])){
                $keys = array_keys($ar);
                foreach($ar['record'] as $row){
                    if(!isset($row['house_id']) || !isset($row['value'])){
                        //\sys\show('row', $row);
                        continue;
                    }
                    $keys = array_keys($row);
                    //if(count($keys) > 2)
                    //	\sys\show('keys2', $keys);

                    $houseid = substr($row['house_id'], 6);

                    $qry = "INSERT IGNORE INTO gerchouse(id,gercstreetid,nam)
					VALUES('$houseid','{$params['street_id']}','{$row['value']}')";
                    \db::query($qry);
                }
            } else {
                //\sys\show('nokey', $ar);
            }
        }
    }

    /**
     * ��������� ������� ��� �����
     * @param string $fromPattern
     */
    public function parseFlats($fromPattern = ''){
        $url = "https://www.gerc.ua/service/";
        $params = [
            'house_id'=>"012623034000000",
            'ac'=>'get',
            'obj'=>'Flat'
        ];

        $isfind = false;
        $qry = "SELECT concat(gercstreetid, id), nam FROM gerchouse ORDER BY id ASC";

        // 		$qry = "SELECT DISTINCT concat(gh.gercstreetid, gh.id) FROM gerchouse gh
        // LEFT JOIN gercflat gf ON gf.gercstreetid=gh.gercstreetid AND gf.gerchouseid=gh.id
        // WHERE gf.gerchouseid IS NULL";

        $houses = \db::col($qry);
        foreach($houses as $houseid){
            if($houseid == $fromPattern || $fromPattern == ''){
                $isfind = true;
            }
            if(!$isfind)
                continue;

            \sys\show('nextid', $houseid);

            $params['house_id'] = $houseid;
            $housedata = $this->file_post_contents($url, $params, '');
            $ar = \Encoder\Coder::coding(json_decode($housedata, true), 0, 1);

            if(isset($ar['record'])){
                $keys = array_keys($ar);
                if(count($keys) > 2)
                    \sys\show('keys', $keys);
                foreach($ar['record'] as $row){
                    if(!isset($row['flat_id']) || !isset($row['value']) || $row['value'] == '-'){
                        if(isset($row['value']) && $row['value'] != '-')
                            \sys\show('row', $row);
                        continue;
                    }
                    $keys = array_keys($row);
                    if(count($keys) > 2)
                        \sys\show('keys2', $keys);

                    $streetid = substr($row['flat_id'], 0, 6);
                    $hhouseid = substr($houseid, 6);
                    $flatid = substr($row['flat_id'], 15);

                    $qry = "INSERT IGNORE INTO gercflat(code,gerchouseid,gercstreetid,nam)
					VALUES('$flatid','$hhouseid','$streetid','{$row['value']}')";
                    \db::query($qry);
                }
            } else {
                \sys\show('nokey', $ar);
            }
        }
    }

    /**
     * ��������� ����������� ���� ������������ ��� 1 �� 3
     * @param string $fromPattern
     */
    public function parseNanimatels($flatID = 0){
        $url = "https://www.gerc.ua/service/";
        $params = [
            'ac'=>'getPlatCode',
            'captcha' => 'undefined',
            'obj' => 'ServicesApi',
            'house'=>"010486001000000",
            'flat'=>'0104860010000000030',
            'user_id'=>'0104860010000000030'
        ];
        // => {"plat_code":"01048600700000000200","success":true} OR
        // => nanimatels [
        // {NANIMAT="3",FIO="� � �", PLAT_CODE=""},
        // { NANIMAT="2", FIO="������ ���� ��������", PLAT_CODE="01048600100000000303"},
        //count	3
        //success	true

        $result = \db::result("SELECT id, gercstreetid, gerchouseid, code FROM gercflat WHERE process=0 AND id > $flatID ORDER BY id ASC");

        // 		$houses = [[
        // 			'id' => '192318',
        // 			'gercstreetid' => '010486',
        // 			'gerchouseid' => '001000000',
        // 			'code' => '0030'
        // 		]];
        //		foreach($houses as $rrow){


        while($rrow = $result->fetch_assoc()){
            $houseid = $rrow['gercstreetid'].$rrow['gerchouseid'];
            $flatid = $rrow['gercstreetid'].$rrow['gerchouseid'].$rrow['code'];

            \sys\show('nextid nam', $flatid);

            $params['house'] = $houseid;
            $params['flat'] = $flatid;
            $params['user_id'] = $flatid;

            $housedata = $this->file_post_contents($url, $params, '');
            $ar = \Encoder\Coder::coding(json_decode($housedata, true), 0, 1);

            if(isset($ar['nanimatels'])){
                $keys = array_keys($ar);
                //if(count($keys) > 2)
                //	\sys\show('keys', $keys);
                foreach($ar['nanimatels'] as $row){
                    if(!isset($row['NANIMAT'], $row['FIO'], $row['PLAT_CODE'])){
                        \sys\show('row', $row);
                        continue;
                    }
                    $nanimatel = intval(trim($row['NANIMAT']));
                    $fio = trim($row['FIO']);
                    if(!$fio){
                        \sys\show('badfio', $row);
                        continue;
                    }
                    $plat = \db::escape_string(
                        str_replace($flatid, '', trim($row['PLAT_CODE']))
                    );
                    if(strlen($plat) > 1){
                        \sys\show('bigcode', $row);
                        continue;
                    }

                    // gercusr
                    $fios = explode(' ', $fio, 3);
                    $family = isset($fios[0]) ? \db::escape_string($fios[0]) : "";
                    $name = isset($fios[1]) ? \db::escape_string($fios[1]) : "";
                    $otchestvo = isset($fios[2]) ? \db::escape_string($fios[2]) : "";
                    $qry = "INSERT INTO gercusr(family, name, otchestvo)
					VALUES('$family', '$name', '$otchestvo') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)";
                    $gercusrid = \db::query($qry, true);
                    if(!$gercusrid){
                        \sys\show('badnanimatel', $row);
                        continue;
                    }

                    // gercflatusr
                    $qry = "INSERT IGNORE INTO gercflatusr(gercflatid, gercusrid, code, nanimatel)
					VALUES({$rrow['id']}, $gercusrid, '$plat', $nanimatel)";
                    \db::query($qry);
                }
            } else if(!isset($ar['success'])){
                \sys\show('nokey', $ar);
            }
        }
        $result->close();
    }

    /**
     * ���������� ����, ��������� ���������� � ��������, �������� ����
     * ��������� html �������� �� ������ �� 10000 id 000 (� 1 �� 10000) ...
     */
    public function addHouse($flatID = 0, $path = "", $cookie = ''){
        $url = "https://www.gerc.ua/service/";
        $urlpay = "https://www.gerc.ua/infocenter/detailbill/";
        $params = [
            'ac'=>'addHouse',
            'account' => '',
            'captcha' => 'undefined',
            'obj' => 'Gerc',
            'security_code' => '',
            'user_id' => 133036,
            'nanimatel' => 'null',

            'house'=>"010486001000000",
            'flat'=>'0104860010000000030'
        ];

        $paramsdel = [
            'ac' => 'delete',
            'obj' => 'Gerc',
            'user_id' => 133036,
            'conn_id' => 'feb22e0d90012a273b59a97b595d02b2',
        ];
        if(!is_dir($path))
            return FALSE;

        \sys\show('begin', $flatID);

        $currflatid = 0;
        $result = \db::result("SELECT id, gercstreetid, gerchouseid, gf.code, group_concat(distinct nanimatel) nanimatel
			FROM gercflat gf LEFT JOIN gercflatusr gfu ON gf.id=gfu.gercflatid
			WHERE gf.process=0 AND gf.id>$flatID
			GROUP BY gf.id
			ORDER BY id ASC");
        while($rrow = $result->fetch_assoc()){
            $houseid = $rrow['gercstreetid'].$rrow['gerchouseid'];
            $currflatid = $rrow['gercstreetid'].$rrow['gerchouseid'].$rrow['code'];

            if($rrow['nanimatel'] != ''){
                \sys\show('nanimatel: ', $rrow['id']);
                continue;
            }

            $needStop = \db::val("SELECT stopped FROM gercstatus WHERE id = 1");
            if($needStop)
                break;

            //\sys\show('nextid nam', $rrow['id']);

            // ��������� ������
            $params['house'] = $houseid;
            $params['flat'] = $currflatid;

            $housedata = $this->file_post_contents($url, $params, $cookie);
            $ar = \Encoder\Coder::coding(json_decode($housedata, true), 0, 1);
            if(!isset($ar['record']) || !isset($ar['record']['conn_id'])){
                // , $params['house'], $params['flat']
                \sys\show('nokey', $rrow);
                continue;
            }

            //\sys\show($params, $urlpay.$ar['record']['conn_id']);

            // �������� �������� ����������
            $html = $this->file_post_contents($urlpay.$ar['record']['conn_id'], [], $cookie);
            $dir = $path."/".sprintf("%03d", floor($rrow['id'] / 10000));
            if(!is_dir($dir)){
                mkdir($dir);
                @chmod($dir, 0770);
                @chown($dir, 'www-data');
                @chgrp($dir, 'www-data');
            }
            file_put_contents($dir."/".$rrow['id']."-".$currflatid.".html", $html);

            // ������� ������
            $paramsdel['conn_id'] = $ar['record']['conn_id'];
            $html = $this->file_post_contents($url, $paramsdel, $cookie);

            $needStop = \db::query("UPDATE gercstatus 
				SET datt = CURRENT_TIMESTAMP, flatid={$rrow['id']}
				WHERE id = 1");
            //sleep(5);
        }
        $result->close();

        \sys\show('stop:', $currflatid);

    }

    public function parseHTMLHouses($dir){
        if(!is_dir($dir))
            return FALSE;
        $dh = opendir($dir);
        if(!$dh)
            return FALSE;

        $cnt = 0;
        $no = [];
        $yes = [];

        $toremove = [];

        while (($file = readdir($dh)) !== false) {
            if(in_array($file, ['.', '..']))
                continue;
            $filepath = $dir . DIRECTORY_SEPARATOR . $file;
            $html = file_get_contents($filepath);

            preg_match_all( '/<tr class="gray">(?:.*?)<span(?:.*?)>(.*?)<\/span>/is' , $html , $links);

            $arfl = explode('-', $file);
            if(!isset($links[1], $links[1][0])){
                $no []= $arfl[0];
                preg_match_all( '/<p class="gerc_adress">(?:.*?)<span(?:.*?)>(.*?)<\/span>/is' , $html , $links2 );
                if(isset($links2[1], $links2[1][0])){
                    $no []= \Encoder\Coder::coding($links2[1][0], 0, 1);
                }
                \sys\show("errorr parse $file");
                continue;
            }

            $isfio = false;
            foreach($links[1] as $text){
                // �� "������-������", �������� �.�.. (������� ����: 000044314)
                $pos = strrpos($text, ',');
                $tmp = explode(',', $text, 2);
                $company = \Encoder\Coder::coding(substr($text, 0, $pos), 0, 1);
                $qry = "INSERT INTO gerccompany(nam)
				VALUES('$company') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)";
                $gerccompanyid = \db::query($qry, true);

                $tmp = explode('(', substr($text, $pos+1), 2);
                $fio = \Encoder\Coder::coding(trim($tmp[0]), 0, 1);
                $fios = explode(' ', $fio);
                $surname = isset($fios[0]) ? \db::escape_string($fios[0]) : "";
                $name = isset($fios[1]) ? \db::escape_string($fios[1]) : "";
                $patronymic = isset($fios[2]) ? \db::escape_string($fios[2]) : "";
                $qry = "INSERT INTO gercusr(family, name, otchestvo)
				VALUES('$surname', '$name', '$patronymic') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)";
                $gercusrid = \db::query($qry, true);
                if($gercusrid && $surname != '���'){
                    //if($gercusrid){
                    $isfio = true;
                }

                $tmp = explode(' ', $tmp[1], 3);
                $account = str_replace(')', '', $tmp[2]);
                $qry = "INSERT IGNORE INTO gercaccount(gercusrid, gerccompanyid, account)
			VALUES($gercusrid, $gerccompanyid, '$account')";
                \db::query($qry);

                $qry = "INSERT IGNORE INTO gercflatusr(gercflatid, gercusrid, code, nanimatel)
				VALUES({$arfl[0]}, $gercusrid, '0', 0)";
                \db::query($qry);
            }
            if($isfio){
                $qry= "UPDATE gercflat SET process=1 WHERE id={$arfl[0]}";
                \db::query($qry);
                $toremove []= $filepath;
            } else {
                \sys\show("error insert $file");
            }

            //break;
            $cnt++;
            //if($cnt > 100)
            //	break;
        }
        closedir($dh);

        foreach ($toremove as $filepath){
            @unlink($filepath);
        }
    }
}

/*
class wcgerc extends \Tabloid\Tabloid {
	protected function getselqry($id, $father=-1, $gfather=-1, $q=''){
		switch ($id){
			case 'gercusrid': {
				$qry = "SELECT id, CONCAT(family, ' ', name, ' ', otchestvo) nam FROM gercusr";
				if($q)
					$qry .= " WHERE CONCAT(family, ' ', name, ' ', otchestvo) LIKE '%$q%'";
				return $qry;
			}
			case 'gercstreetid': {
				$qry = "SELECT id, nam FROM gercstreet";
				if($q)
					$qry .= " WHERE nam LIKE '%$q%'";
				return $qry;
			}
			case 'gerchouseid': {
				$qry = "SELECT gh.id, gh.nam FROM gerchouse gh";
				if($father)
					$qry .= " INNER JOIN gercstreet gs ON gs.id=gh.gercstreetid AND gs.id=$father";
				if($q)
					$qry .= " WHERE gh.nam LIKE '%$q%'";
				return $qry;
			}
			case 'gercflatid': {
				$qry = "SELECT gf.id, gf.nam FROM gercflat gf";
				if($father)
					$qry .= " INNER JOIN gerchouse gh ON gh.id=gf.gerchouseid AND gh.id=$father";
				if($q)
					$qry .= " WHERE gf.nam LIKE '%$q%'";
				return $qry;
			}
			default:	return '';
		}
	}

	public function textfiltergercflatid($val){
		if($val == "")
			return false;
		return "gf.nam LIKE '%$val%'";
	}
	public function textfiltergerchouseid($val){
		if($val == "")
			return false;
		return "gh.nam LIKE '%$val%'";
	}
	public function textfiltergercstreetid($val){
		if($val == "")
			return false;
		return "gs.nam LIKE '%$val%'";
	}

	public function textfiltergercusrid($val){
		if($val == "")
			return false;

		$qry = "SELECT id FROM gercusr WHERE CONCAT(family, ' ', name, ' ', otchestvo) LIKE '%$val%'";
		$ids = \db::col($qry);
		if($ids){
			return "gfu.gercusrid IN (".implode(',', $ids).")";
		}
		return false;
	}

	public function saveprop($params){
		return 'TODO';
	}

	public function removeprop($params){
		return 'TODO';
	}
}
*/