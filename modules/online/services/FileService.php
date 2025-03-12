<?php
namespace app\modules\online\services;

class FileService {
    /**
     * какие файлы для $tableid изменены относительно массива id $fls
     *
     * @param array $fls
     * @param array $flsnam
     * @param integer $tableid
     * @param string $table
     */
    public function flsChanged(array $fls, array $flsnam, $tableid, $table = 'confmess')
    {
        // какие сейчас файлы
        $arr = \db::iarr("SELECT f.id, f.nam, f.typ FROM file AS f INNER JOIN {$table}_file AS jf ON f.id=jf.fileid WHERE jf.{$table}id=$tableid");
        // новые файлы
        $flsnew = array();
        foreach ($arr as $fileid => $row) {
            if (!in_array($fileid, $fls)) {
                $flsnew[] = ($row['typ'] == '') ? $row['nam'] : $row['nam'] . '.' . $row['typ'];
            }
        }
        // откреплённые файлы
        $flsdel = array();
        $dels = array_diff($fls, array_keys($arr));
        if ($dels) {
            foreach ($dels as $fileid) {
                $pos = array_search($fileid, $fls);
                if ($pos !== FALSE && isset($flsnam[$pos])) {
                    $flsdel[] = $flsnam[$pos];
                }
            }
        }
        return array(
            'new' => $flsnew,
            'del' => $flsdel
        );
    }

    /**
     * Сохранить файлы из буфера обмена, пришедшие в формате base64
     *
     * @param string $postInd
     */
    public function saveFilesFromClipboard($postInd = 'pstclipboard')
    {
        $res = array(
            'error' => '',
            'fileids' => array()
        );
        foreach ($_POST[$postInd] as $clipboardfile) {
            $ar = explode("=", $clipboardfile, 2);
            if (count($ar) == 2) {
                $content = base64_decode($ar[1]);
                unset($ar[1]);
                $arfls = $this->saveFile($ar[0], $content, true, true);
                if ($arfls !== FALSE && $arfls['idfile'] > 0)
                    $res['fileids'][] = $arfls['idfile'];
            }
        }
        $res['fileids'] = array_unique($res['fileids']);
        return $res;
    }

    /**
     *
     * @param integer $fileid
     * @param integer $filediskid
     * @param string $nam
     * @param string $tabletype
     *            conf, task)
     * @param integer $tableid
     * @return string
     */
    public function fileLink($fileid, $filediskid, $nam, $tabletype = '_', $tableid = 0)
    {
        if ($nam == '')
            return '';
        if (!in_array($tabletype, array(
            'mess',
            'conf',
            'task',
            '_'
        )))
            return '';

        $ar = explode(',', $nam);
        $fnam = $ar[0];
        $ilen = strlen($nam);
        \Encoder\Coder::cleanData($nam);
        if ($ilen > 70)
            $fshow = substr($nam, 0, 67) . '...';
        else
            $fshow = $nam;
        $fnam = rawurlencode($fnam);
        $href = "?filename=$fnam&fileid=$fileid";
        switch ($tabletype) {
            case 'mess':
                $href .= "&filemessid=$tableid";
                break;
            case 'task':
                $href .= "&filetaskid=$tableid";
                break;
            case 'conf':
                $href .= "&fileconfmessid=$tableid";
                break;
        }
        $link = "<a title='" . $nam . "' style='cursor:hand;' id='idfl$filediskid' href2='$href'>$fshow</a>";
        return $link;
    }

    // после закачки файла удаляем переменную из apc кеша
    public function apc_clean()
    {
        $pref = ini_get('apc.rfc1867_prefix');
        $ind = ini_get('apc.rfc1867_name');
        if ($ind && isset($_POST[$ind])) {
            $apckey = $pref . $_POST[$ind];
            if (apc_exists($apckey) !== FALSE) {
                apc_delete($apckey);
                // чистить кеш apc старше 1 часа
                $this->apc_cacheDelete();
            }
            // кеш для проверки антивирусом
        }
    }

    public static function apc_viruskey($key, $type)
    {
        return trim($type . $key . m_auth::$id);
    }

    /**
     * удаляет кеш apc старше 1 часа
     */
    function apc_cacheDelete()
    {
        $infs = apc_cache_info('user');
        if (count($infs['cache_list']))
            foreach ($infs['cache_list'] as $k => $v) {
                if ((time() - $v['access_time']) / 60 / 60 > 1) {
                    apc_delete($v['info']);
                }
            }
    }

    /**
     * В html файле заменяем наши ссылки (?getemlimage=) на жёсткие ссылки, возвращает текст html
     *
     * @param integer $uid
     * @param integer $messid
     * @param string $resFile
     *            путь к файлу
     */
    function getfile_htmlprocess($uid, $messid, $resFile, array $viracceptfdids = array())
    {
        // внутри html письма все наши ссылки ?getemlimage= меняем на жёсткие ссылки
        $ar = $this->getfiles($messid);

        // получаем все картинки
        $str = file_get_contents($resFile);
        $links = new cl_htmllinks();

        // убираем все ссылки на другие сайты
        // удаляем скрипты <script>
        $arr = $links->scriptFromTxt($str);
        $str = $links->clearMatch($str, $arr);
        // убираем <link>
        $arr = $links->linkFromTxt($str);
        $str = $links->clearMatch($str, $arr);
        // убираем <iframe>
        $arr = $links->iframeFromTxt($str);
        $str = $links->clearMatch($str, $arr);
        // убираем bgsound
        $arr = $links->bgsoundFromTxt($str);
        $str = $links->clearMatch($str, $arr);
        // убираем embed
        $arr = $links->embedFromTxt($str);
        $str = $links->clearMatch($str, $arr);
        // убираем video
        $arr = $links->videoFromTxt($str);
        $str = $links->clearMatch($str, $arr);
        // убираем audio
        $arr = $links->audioFromTxt($str);
        $str = $links->clearMatch($str, $arr);
        // убираем object
        $arr = $links->objectFromTxt($str);
        $str = $links->clearMatch($str, $arr);
        // убираем background-image (кроме встроенных)
        $arr = $links->backgroundImageFromTxt($str);
        foreach ($arr as $v) {
            if (strpos($str, "data:image") === FALSE)
                $str = str_replace($v, "", $str);
        }
        // убираем background-url
        $arr = $links->backgroundurlFromTxt($str);
        $str = $links->clearMatch($str, $arr);
        // убираем background атрибут
        $arr = $links->backgroundFromTxt($str);
        $str = $links->clearMatch($str, $arr);

        // если картинка ссылается на один из файлов сообщения, подставляем нашу ссылку
        $imgs = $links->imgFromTxt($str);
        if ($imgs) {
            foreach ($imgs[1] as $ik => $iv) {
                foreach ($ar as $k => $v) {
                    if ($v['nam'] == $iv) {
                        $tmp = $imgs[0][$ik];
                        $imgs[0][$ik] = str_replace($v['nam'], "?getemlimage=" . $v['fdid'], $imgs[0][$ik]);
                        $str = str_replace($tmp, $imgs[0][$ik], $str);
                    }
                }
            }
        }

        // узнать какие вирусы в файлах
        $fdids = array();
        foreach ($ar as $k => $v) {
            $fdids[] = $v['fdid'];
        }
        if ($fdids) {
            $wh = "";
            if ($viracceptfdids) {
                $sviracceptfdids = implode(",", $viracceptfdids);
                $wh = "AND fd.id NOT IN ($sviracceptfdids)";
            }
            $sfdids = implode(",", $fdids);
            $fdids = \db::col("SELECT fd.id FROM filedisk fd INNER JOIN vir v ON v.id=fd.virid
WHERE fd.id IN ($sfdids) AND v.id > 2 $wh");
        }

        // все наши ссылки заменяем на жёсткие сылки
        foreach ($ar as $k => $v) {
            if (strpos($str, "?getemlimage=" . $v['fdid']) !== FALSE) {
                if (in_array($v['fdid'], $fdids)) {
                    // ссылку на вирус не отображаем
                    $str = str_replace("?getemlimage=" . $v['fdid'], "", $str);
                } else {
                    $linkinfo = $this->getfilelink($v['ind'], array(
                        'usrid' => $uid
                    ));
                    if ($linkinfo) {
                        $lnk = $linkinfo['link'] . "?view";
                        $str = str_replace("?getemlimage=" . $v['fdid'], $lnk, $str);
                    }
                }
            }
        }

        // если ссылка без http: типа src="issue3/_logo.png", то она ссылается на наш сайт,
        // а у нас нет такой папки, поэтому заменяем src на alt
        $imgs = $links->imgFromTxt($str);
        if ($imgs) {
            foreach ($imgs[1] as $ik => $iv) {
                if ((strpos($imgs[1][$ik], "data:image") === FALSE) && strpos($imgs[1][$ik], "http:") === FALSE && strpos($imgs[1][$ik], "https:") === FALSE) {
                    $fromar = array(
                        "src=",
                        "src =",
                        "SRC=",
                        "SRC ="
                    );
                    $toar = array(
                        "alt=",
                        "alt=",
                        "alt=",
                        "alt="
                    );
                    $tmp = str_replace($fromar, $toar, $imgs[0][$ik]);
                    $str = str_replace($imgs[0][$ik], $tmp, $str);
                }
            }
        }

        // не отображать внешние изображения в main.html (защита от спама) no_spam_label_
        $sait = $this->subdomainpath();
        $imgs = $links->imgFromTxt($str);
        if ($imgs) {
            foreach ($imgs[1] as $ik => $iv) {
                if ((strpos($imgs[1][$ik], "data:image") === FALSE) && (strpos($imgs[1][$ik], "http:") !== FALSE || strpos($imgs[1][$ik], "https:") !== FALSE) && strpos($imgs[1][$ik], $sait) === FALSE) {
                    // добавляем no_spam_label_
                    $tmp = "no_spam_label_" . $imgs[1][$ik];
                    $to = str_replace($imgs[1][$ik], $tmp, $imgs[0][$ik]);
                    // заменяем src на alt
                    $fromar = array(
                        "src=",
                        "src =",
                        "SRC=",
                        "SRC ="
                    );
                    $toar = array(
                        "alt=",
                        "alt=",
                        "alt=",
                        "alt="
                    );
                    $to = str_replace($fromar, $toar, $to);
                    $str = str_replace($imgs[0][$ik], $to, $str);
                }
            }
        }
        return $str;
    }

    /**
     * возвращает протокол, http или https
     */
    function is_https()
    {
        return (!empty($_SERVER['HTTPS'])) ? true : false;
    }

    /**
     * возвращает путь для поддомена (напр.
     * http://fl.pborisov.local)
     */
    function subdomainpath()
    {
        $protocol = ($this->is_https()) ? "https://" : "http://";
        \sys\show($protocol . mydef::FILESUBDOMAIN . $_SERVER['HTTP_HOST']);
        return $protocol . mydef::FILESUBDOMAIN . $_SERVER['HTTP_HOST'];
    }

    /**
     * убирает из имени файла запрещённые символы \/:*?"<>|
     *
     * @param unknown_type $name
     */
    function nameCleanForbidden($name)
    {
        $afrom = array(
            '\\',
            '/',
            ':',
            '*',
            '?',
            '"',
            '<',
            '>',
            '|'
        );
        $ato = array(
            '',
            '_',
            '_',
            '_',
            '_',
            '_',
            '_',
            '_',
            '_'
        );
        return str_replace($afrom, $ato, $name);
    }

    /**
     * получить все пути к файлам ($filenam.$filetyp)
     *
     * @param string $filenam
     * @param string $filetyp
     */
    function file_paths($filenam, $filetyp)
    {
        $paths = array();
        $qry = "SELECT fd.id fdid, fd.dat, f.id fid FROM file AS f INNER JOIN filedisk AS fd ON fd.id=f.filediskid
		WHERE f.nam='$filenam' AND f.typ='$filetyp' ORDER BY fdid";
        $arr = \db::arr($qry);
        foreach ($arr as $row) {
            $resFile = $this->filepath($row['fdid'], $row['dat']);
            if (is_file($resFile)) {
                if (!isset($paths[$row['fdid']]))
                    $paths[$row['fdid']] = array();
                $paths[$row['fdid']]['path'] = $resFile;
                $paths[$row['fdid']]['fid'] = $row['fid'];
            }
        }
        return $paths;
    }

    // качаем файл
    function getfile($fileid, &$error, $isdownl, $messid, $filenam = '', $viracceptfdids = array())
    {
        // gettype: 0-get file content, 1-download file, 2-get image
        if ($fileid < 1) {
            $error = "<p>Файл не найден3</p>";
            return array();
        }

        // get file date
        $qry = "SELECT fd.dat, fd.id flid, f.nam fnam, f.typ, fd.virid, v.nam FROM file AS f
INNER JOIN filedisk AS fd ON fd.id=f.filediskid
INNER JOIN vir AS v ON v.id=fd.virid
WHERE f.id=$fileid";
        if (!($row = \db::row($qry))) {
            $error = "<p>Файл не найден</p>";
            \db::diedlog("Файл не найден", false, true);
            return array();
        }

        // если файл найден то сливаем его или качаем
        $resFile = $this->filepath($row['flid'], $row['dat']);
        if (!is_file($resFile)) {
            $error = "<p>Файл не найден</p>";
            return array();
        }

        $error = "";
        $lnkParams = array(
            'isdownload' => $isdownl,
            'filename' => $filenam
        );
        // заменяем наши ссылки на линки
        if ((strtolower($row['typ']) == 'htm' || strtolower($row['typ']) == 'html') and $messid) {
            $lnkParams['data'] = $this->getfile_htmlprocess(m_auth::$id, $messid, $resFile, $viracceptfdids);
        }

        $res = $this->getfilelink($fileid, $lnkParams);

        // инфо о вирусах
        if ($row['virid'] > 2 && $res) {
            $res['virid'] = $row['virid'];
            $res['virnam'] = $row['nam'];
        }

        return $res ? $res : array();
    }

    function htaccessWrite($path, $session, $fnam, $keyname, $isdownl, $sets = array())
    {
        if (is_file($path))
            unlink($path);

        $fnam = str_replace('%', '%%', $fnam);

        $f = fopen($path, "wb");
        @chmod($path, 0660);

        if ($isdownl == 0) {
            // по расширению файла пишем его тип
            $pinfo = pathinfo($fnam);
            $type = 'text/plain';
            if (isset($pinfo['extension'])) {
                $ext = $pinfo['extension'];
                $type = $this->mimeFromExt(strtolower($ext));
            }
            $fileheader = 'Header set Content-Disposition \'inline; filename=' . "\"" . \db::escape_string($fnam) . "\"" . '\'' . "\r\n";
            if ($type != '')
                $fileheader = "Header set Content-Type '$type'\r\n" . $fileheader;
        } else {
            $fileheader = 'Header set Content-Disposition \'attachment; filename=' . "\"" . \db::escape_string($fnam) . "\"" . '\'' . "\r\n";
            $fileheader .= 'Header set Content-Type \'application/octet-stream;\'' . "\r\n";
        }

        $s = "";

        if (isset($sets['emlattach'])) {
            $s = "#eml attach, no record in database table file-filedisk\n";
        }

        $s .= "" . "RewriteEngine on\n" . $fileheader . 'RewriteCond %{HTTP_COOKIE} !' . $keyname . '=' . $session . "\n" . 'RewriteRule .* - [F]' . "\n";

        fwrite($f, $s);
        fclose($f);
    }

    /**
     * создаёт папку для жёсткой ссылки
     *
     * @param string $lnkpath
     *            к папке с жёсткими ссылками
     * @param string $dt
     *            - дата date("Y-m-d H:i:s")
     */
    function getfilelinkfolder($lnkpath, $dt, $flnam = '', $fid = '')
    {
        $t = strtotime($dt);
        $dt = date("Ymd_His_", $t);
        for ($i = 0; $i < 50; $i++) {
            $pid = md5(microtime(1) . $flnam . $fid);
            $dirName = "/" . $dt . $pid;
            if (!is_dir($lnkpath . $dirName)) {
                mkdir($lnkpath . $dirName);
                @chmod($lnkpath . $dirName, 0770);
                break;
            }
        }
        return array(
            'pid' => $pid,
            'dir' => $dirName
        );
    }

    /**
     * хардлинки (если $issait == 1, то )
     *
     * @param
     *            $uid
     * @param
     *            $fileid
     * @param $resFile путь
     *            файлу
     * @param $session COOKIE
     * @param
     *            $issait
     * @param $isdownl качать
     *            файл
     */
    function getfilelink($fileid, array $params = array())
    {
        $uid = isset($params['usrid']) ? $params['usrid'] : m_auth::$id;
        $isdownl = isset($params['isdownload']) ? $params['isdownload'] : 0;
        $filedata = isset($params['data']) ? $params['data'] : '';
        $issait = (isset($params['site'])) ? 1 : 0;

        // папка для хранения хардлинков
        $sroot = mydef::$lnkpath;
        if (!is_dir($sroot)) {
            mkdir($sroot);
            @chmod($sroot, 0770);
        }

        $session = $issait ? md5(microtime(1)) : \Encoder\Opt::getopts('keyfile');

        if (!$filedata) {
            $resFile = "";
            $pts = \db::row("SELECT fd.dat, fd.id FROM filedisk fd INNER JOIN file f ON f.filediskid=fd.id WHERE f.id=$fileid");
            if ($pts) {
                $resFile = $this->filepath($pts['id'], $pts['dat']);
                if (!is_file($resFile)) {
                    return false;
                }
            }
        }

        $qry = "SELECT f.nam, f.typ, fd.compress FROM file f
			LEFT JOIN filedisk fd ON f.filediskid=fd.id WHERE f.id='$fileid'";
        $flinf = \db::row($qry);
        $flnam = $this->filenamget($flinf['nam'], $flinf['typ'], $flinf['compress']);
        if (isset($params['filename']) && $params['filename']) {
            $flnam = $this->filenamget($params['filename'], $flinf['typ'], $flinf['compress']);
        }

        if (strlen($flnam) > 300)
            die('Слишком длинное имя файла');

        // смотрм есть ли файл
        $filepath = "";
        $dbflnam = \db::escape_string($flnam);
        $qry = "SELECT filepath, datt FROM usr_file WHERE usrid='$uid' AND fileid='$fileid' AND filename='$dbflnam' ORDER BY datt DESC";
        $arr = \db::row($qry);

        if (count($arr)) {
            $t = strtotime($arr['datt']);
            $filepath = $sroot . '/' . date("Ymd_His_", $t) . $arr['filepath'] . '/link';
        }
        $flsize = 0;

        // создаем ссылку если нужно и добавляем в базу
        $ptHtaccess = "";
        $userpath = "";
        if (is_file($filepath)) {
            // если в htaccess другая кука нужно перезаписать htacess файл
            $ptHtaccess = $sroot . '/' . date("Ymd_His_", $t) . $arr['filepath'] . '/.htaccess';
            if (is_file($ptHtaccess)) {
                $content = file_get_contents($ptHtaccess);
                $this->htaccessWrite($ptHtaccess, $session, $flnam, 'keyfile' . $uid, $isdownl);
            }
            $flsize = filesize($filepath);

            $userpath = $this->subdomainpath() . str_replace($sroot, "", $filepath);
            $userpath = iconv("CP1251", "UTF-8", $userpath);
        } else {
            // создаем жесткие ссылки

            // текущая дата
            $dt2 = date("Y-m-d H:i:s");
            $ar = $this->getfilelinkfolder($sroot, $dt2, $flnam, $fileid);
            $dirName = $ar['dir'];
            $pid = $ar['pid'];

            // путь для пользователя
            $userpath = $this->subdomainpath() . $dirName . '/link';
            $newFile = $sroot . $dirName . '/link'; // путь на диске

            if ($filedata) {
                // если файл изменён, то не создаём жёсткую ссылку, а создаём файл
                file_put_contents($newFile, $filedata);
                @chmod($newFile, 0660);
            } else {
                $this->makehardlink($resFile, $newFile);
                @chmod($newFile, 0660);
            }

            $ptHtaccess = $sroot . $dirName . '/.htaccess';

            $flsize = filesize($newFile);
            if (file_exists($newFile)) {
                // create htaccess
                $this->htaccessWrite($ptHtaccess, $session, $flnam, 'keyfile' . $uid, $isdownl);

                $qry = "INSERT IGNORE INTO usr_file(usrid, filepath, fileid, filename, datt) VALUES($uid,'$pid',$fileid, '$dbflnam', '$dt2')";
                $cnt = \db::query($qry);
                if ($cnt == 0 && count($arr)) {
                    $qry = "UPDATE usr_file SET filepath='$pid', datt='$dt2' WHERE fileid='$fileid' AND usrid='$uid' AND filename='$dbflnam'";
                    $cnt = \db::query($qry);
                }
            }
        }
        // отдаем пользователю
        if ($issait == 0)
            return array(
                'link' => $userpath,
                'htaccesspath' => $ptHtaccess
            );
        else
            return $session . chr(5) . $userpath . chr(5) . chr(7) . chr(5) . $flsize . chr(5) . $uid;
    }

    function mimeFromExt($ext)
    {
        $type = '';
        switch ($ext) {
            case 'xml':
            case 'pdf':
                $type = "application/$ext";
                break;
            case 'txt':
                $type = "text/plain";
                break;
            case 'html':
            case 'htm':
                $type = "text/html";
                break;
            case 'bmp':
            case 'gif':
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'tiff':
                $type = "image/$ext";
                break;
        }
        return $type;
    }

    /**
     * ответ пользователю о файлах
     *
     * @param integer $tableid
     * @param string $type
     */
    public function filesAnsw($tableid, $type)
    {
        // Array (
        // [files] => <a style="cursor:default;text-decoration:none;margin-right:20px;" id="txtattach" class="clrgray">Присоединенные файлы:</a><a style="color:blue;cursor:pointer;margin-right:20px" id="idSaveAllFiles">Скачать все файлы</a><a style="text-decoration:none;margin-right:0px;"
        // href2="?filename=customall.log&amp;fileid=20885&amp;filetaskid=93" id="idfl20885">customall.log (54.42 Кб)</a><a style="text-decoration:none;margin-right:0px;"
        // href2="?filename=customnew.log&amp;fileid=20886&amp;filetaskid=93" id="idfl20886">customnew.log (54.41 Кб)</a>
        // [filesnam] => Array ( [0] => customall.log [1] => customnew.log )
        // [filesids] => Array ( [0] => 20885 [1] => 20886 )
        // )
        $a = array();
        $fl = $this->getfiles($tableid, $type);

        $txt = '';
        $fids = array();
        $fnams = array();

        if ($fl)
            $txt .= '<a style="cursor:default;text-decoration:none;margin-right:20px;" id="txtattach" class="clrgray">Присоединенные файлы:</a>';
        if (count($fl) > 1) {
            $txt .= '<a style="color:blue;cursor:pointer;margin-right:20px" id="idSaveAllFiles">Скачать все файлы</a>';
        }

        // Для задач отображать для прикреплённых файлов кто файл прикреплял в всплывающей подсказке
        $staffids = array();
        $staffidsinfo = array();
        foreach ($fl as $k => $v) {
            if ($v['exist'] != 0 && isset($v['staffid'])) {
                $staffids[] = $v['staffid'];
            }
        }
        if ($staffids) {
            $mdep = new m_dep();
            $staffidsinfo = $mdep->staffidsusr($staffids);
        }

        // html вариант письма
        $htmlview = "";
        // заголовки
        $hdrid = 0;
        if ($type == 'mess') {
            $hdrid = \db::val("SELECT hdrid FROM mess_hdr WHERE messid='$tableid'");
        }
        if ($hdrid) {
            $txt = '<a target="_blank" style="text-decoration:none;margin-right:20px;" href="?txtheadermid=0&txtheader=' . $tableid . '">Заголовки...</a>' . $txt;
        }

        foreach ($fl as $k => $v) {
            $view = "";
            if ($v['exist'] != 0) {
                $fids[] = $v['ind'];
                $fnams[] = $v['nam'];

                // просмотреть
                $nam = rawurlencode($v['nam']);
                if ($v['compress'] != 2) {
                    $href2 = '"?filename=' . $nam . '&amp;fileid=' . $v['ind'] . '&amp;file' . $type . 'id=' . $tableid;

                    switch ($v['typ']) {
                        case 'doc':
                        case 'xls':
                        case 'docx':
                        case 'xlsx':
                        case 'odt':
                        case 'ods':
                            $vtxt = $v['typ'] == 'doc' ? 'Просмотреть текст' : 'Просмотреть';
                            $view = '<a target="_blank" style="text-decoration:none;margin-right:20px;"
							href="?control=file&mtd=preview&fileid=' . $v['ind'] . '">&nbsp;' . $vtxt . ' ...</a>';
                            break;

                        case 'txt':
                        case 'xml':
                        case 'pdf':
                            $view = '<a id="flview" data-id="' . $v['ind'] . '" isimage="0" style="text-decoration:none;margin-right:20px;"
							href2=' . $href2 . '">&nbsp;Просмотреть ...</a>';
                            break;

                        case 'gif':
                        case 'jpg':
                        case 'jpeg':
                        case 'bmp':
                        case 'tiff':
                        case 'png':
                            $view = '<a id="flview" data-id="' . $v['ind'] . '" isimage="1" style="text-decoration:none;margin-right:20px;"
							href2=' . $href2 . '">&nbsp;Просмотреть ...</a>';
                            break;

                        case 'html':
                        case 'htm':
                            if ($type == 'mess' && $v['ismail'] == 1) {
                                $htmlview = '<a id="flview" data-id="' . $v['ind'] . '" isimage="0" style="text-decoration:none;margin-right:20px;font-weight:600;"
								href2=' . $href2 . '" title="Открыть HTML-вариант письма">&nbsp;Просмотреть ...</a>';
                                $a['htmlid'] = $v['ind'];
                            } else {
                                $view = '<a id="flview" data-id="' . $v['ind'] . '" isimage="0" style="text-decoration:none;margin-right:20px;"
								href2=' . $href2 . '">&nbsp;Просмотреть ...</a>';
                            }
                            break;

                        case 'eml':
                        case 'msg':
                        case 'mbox':
                            $view = '<a target="_blank" style="text-decoration:none;margin-right:20px;"
							href="?hdrview=' . $tableid . '&amp;hdrpath=self&amp;hdrfileid=' . $v['ind'] . '>&nbsp;Просмотреть ...</a>';
                            break;
                    }
                }

                $flnam = $v['nam'];
                $svirus = "";
                $puastr = "";
                if (isset($v['virid'])) {
                    $puastr = strpos($v['virnam'], "PUA.") !== FALSE ? "подозрение на вирус" : "вирус";
                    $svirus = "<span style='color:red;cursor:default;'>($puastr - {$v['virnam']})</span>";
                }
                $flnam .= ' (' . $v['len'] . ')';

                // скачать
                $href = '?filename=' . $nam . '&amp;fileid=' . $v['ind'] . '&amp;file' . $type . 'id=' . $tableid;
                // просмотреть в виде pdf(html)
                $doffice = '';
                // if($v['compress'] != 2 && in_array(strtolower($v['typ']), cl_docxview::$officeExt)){
                // $doffice = 'data-office=1';
                // }

                $ttl = "";
                if (isset($v['staffid'], $staffidsinfo[$v['staffid']])) {
                    $ttl = 'title="Прикреплено: ' . $staffidsinfo[$v['staffid']]['nam'] . '"';
                }
                $donw = '<a ' . $doffice . ' style="text-decoration:none;margin-right:0px;" ' . $ttl . '
					href2="' . $href . '" id="idfl' . $v['ind'] . '">' . $flnam . $svirus . '</a>';

                $fullnam = $flnam;
                if ($puastr)
                    $fullnam .= "($puastr - {$v['virnam']})";
                $fullnam = rawurlencode(\Encoder\Coder::toUtf($fullnam));

                // добавление в файловый буфер
                // $ttlbuf = 'title="Добавить в файловый буфер"';
                // $donw .= '&nbsp;<a data-siz='.$v['siz'].' data-fullnam='.$fullnam.' data-nam='.$nam.' style="text-decoration:none;margin-right:0px;" '.$ttlbuf.'
                // id="flbuf'.$v['ind'].'">+</a>';
                $donw .= '<input type="hidden" data-siz=' . $v['siz'] . ' data-fullnam=' . $fullnam . ' data-nam=' . $nam . ' style="" id="flbuf' . $v['ind'] . '">';
            } else {
                // удалён
                $donw = '<a style="color:black;margin-right:20px;">' . $v['nam'] . ' (удалён)</a>';
            }

            $txt .= $donw;
            if ($view)
                $txt .= $view;
            else
                $txt .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        }

        if ($htmlview)
            $txt = $htmlview . $txt;

        $a['files'] = $txt;
        $a['filesnam'] = $fnams;
        $a['filesids'] = $fids;
        if ($staffids)
            $a['staffids'] = $staffids;
        return $a;
    }

    /**
     * получаем список файлов по id сообщения(письма)
     *
     * @param integer $messID
     * @param string $type
     *            привязки файлов к сообщениям
     */
    function getfiles($messID, $type = 'mess')
    {
        $seladd = $type == 'task' ? ",staffid" : ""; // дополнительное поле staffid для задач

        $qry = "SELECT f.id fileid, fi.id, f.nam, f.typ, fi.dat, fi.virid, v.nam virnam, fi.compress $seladd
		FROM (SELECT fileid $seladd FROM " . $type . "_file WHERE " . $type . "id='$messID')m
		JOIN file AS f ON f.id=m.fileid
		JOIN filedisk fi ON fi.id=f.filediskid
		JOIN vir v ON v.id=fi.virid";
        $ar = \db::arr($qry);

        $a = array();
        $ilen = count($ar);
        for ($i = 0; $i < $ilen; $i++) {
            $fnam = $this->filenamget($ar[$i]['nam'], $ar[$i]['typ'], $ar[$i]['compress']);
            $filedt = $ar[$i]['dat'];
            $fileid = $ar[$i]['id'];
            $fid = $ar[$i]['fileid'];

            if ($fnam == "" || $filedt == "" || $fileid < 1)
                continue;
            $filedt = str_replace("-", "", $filedt);
            $filepath = mydef::$filepath . "/" . $filedt . "/" . $fileid;

            // проверка наличия файла (если файл удален, то exist=0 и len=0)
            $siz = 0;
            $len = 0;
            $exist = 0;
            if (is_file($filepath)) {
                $siz = filesize($filepath);
                $len = m_func::Size2Str($siz);
                $len = str_replace("0 бт", "пустой 0 бт", $len);
                $exist = 1;
            }

            \Encoder\Coder::cleanData($fnam);

            $aradd = array(
                'siz' => $siz,
                'len' => $len,
                'nam' => $fnam,
                'ind' => $fid,
                'exist' => $exist,
                'dat' => $filedt,
                'fdid' => $fileid,
                'compress' => $ar[$i]['compress'],
                'typ' => $ar[$i]['typ']
            );
            if ($type == 'task') {
                $aradd['staffid'] = $ar[$i]['staffid'];
            }
            // инфо о вирусах
            if ($ar[$i]['virid'] > 2) {
                $aradd['virid'] = $ar[$i]['virid'];
                $aradd['virnam'] = $ar[$i]['virnam'];
            }

            $a[] = $aradd;
        }

        // добавляем признак: html страница это или нет
        if ($type == 'mess' && count($a) > 0) {
            $ilen = count($a);
            $ml = 0;
            $ml2 = 0;
            for ($i = 0; $i < $ilen; $i++) {
                switch ($a[$i]['nam']) {
                    case mydef::MAILHTMLFILE:
                        $ml = $a[$i]['ind'];
                        break;
                    case mydef::MAILHTMLFILE2:
                        $ml2 = $a[$i]['ind'];
                        break;
                }
            }
            for ($i = 0; $i < $ilen; $i++) {
                switch ($a[$i]['ind']) {
                    case $ml:
                        $a[$i]['ismail'] = ($ml2 == 0) ? 1 : 0;
                        break;
                    case $ml2:
                        $a[$i]['ismail'] = 1;
                        break;
                    default:
                        $a[$i]['ismail'] = 0;
                        break;
                }
                unset($a[$i]['dat']);
            }
        }
        return $a;
    }

    /**
     * определяет, является ли файл файлом пользователя
     *
     * @param integer $iduser
     * @param string $idfiles
     * @param bool $isdel
     *            - флаг возможность удаления
     */
    function isUserFile($idfile, $iduser = null)
    {
        if (is_null($iduser))
            $iduser = m_auth::$id;

        // почта или Вестник
        $qry = "SELECT f.id
		FROM file AS f
			INNER JOIN mess_file AS mf ON mf.fileid=f.id
			INNER JOIN mess AS m ON m.id=mf.messid
		WHERE m.usrid='$iduser' AND f.id IN ($idfile)
			UNION
		SELECT f.id
		FROM file AS f
			INNER JOIN mess_file AS mf ON mf.fileid=f.id
			INNER JOIN mess AS m ON m.id=mf.messid
			INNER JOIN mess_usr AS mu ON m.id=mu.messid
		WHERE mu.usrid='$iduser' AND f.id IN ($idfile)";
        $a = \db::arr($qry);
        if ($a)
            return 1;

        // Конференции - пользователь должен иметь право читать конференцию
        $qry = "SELECT cm.confid
			FROM file AS f
				INNER JOIN confmess_file AS cmf ON cmf.fileid=f.id
				INNER JOIN confmess AS cm ON cm.id=cmf.confmessid
			WHERE f.id IN ($idfile)";
        $confs = \db::col($qry);
        if ($confs) {
            $mconf = new m_conf();
            $ar = $mconf->usr_confs($iduser);
            $uconfs = $ar['conf'];
            $a = array_intersect($uconfs, $confs);
        }
        if ($a)
            return 1;

        // Задачи, если могу просмотреть задачу - могу и скачать файл
        $qry = "SELECT t.id FROM task AS t INNER JOIN task_file AS tf ON t.id=tf.taskid
			WHERE tf.fileid IN($idfile)";
        $tasks = \db::col($qry);
        if ($tasks) {
            $mtask = new m_task();
            $utasks = $mtask->task_ids();
            $a = array_intersect($utasks, $tasks);
        }
        if ($a)
            return 1;

        // Рубрики
        $rubs = \db::col("SELECT rp.rubid FROM file f
		INNER JOIN rubprop rp ON rp.val=f.id INNER JOIN rubproptyp rpt ON rpt.id=rp.rubproptypid
		WHERE rpt.entityid=" . entity::FILE . " AND f.id=$idfile");
        if ($rubs && \Encoder\Opt::getopts('rubedit', $iduser)) {
            return 1;
        }

        // права доступа
        $opts = \Encoder\Opt::getopts('advlayoutpreap,advlayoutexport');

        // Верстка
        $typsettDeps = array_merge($opts['advlayoutpreap'] ?: [], $opts['advlayoutexport'] ?: []);
        if ($typsettDeps) {
            $typsettDeps = getSubDeps($typsettDeps, 1);
            $qry = "SELECT ednd.depid FROM file f
					INNER JOIN edndathistory edh ON edh.fileid=f.id
					INNER JOIN edndat ed ON ed.id=edh.edndatid
					INNER JOIN edn_dep ednd ON ed.ednid=ednd.ednid
					WHERE f.id=$idfile AND ednd.depid IN($typsettDeps) LIMIT 1";
            if (\db::val($qry))
                return 1;

            $qry = "SELECT STRAIGHT_JOIN edp.depid FROM file f INNER JOIN objpropint opi ON opi.val=f.id
					INNER JOIN mov_obj mo USING(objid)
					INNER JOIN movpropint mpi ON mpi.movid=mo.movid AND mpi.movproptypid IN(" . mpt::EDNDAT . "," . mpt::EDN . ")
					INNER JOIN edndat ed ON ed.id=mpi.otherid AND mpi.movproptypid=" . mpt::EDNDAT . "
					INNER JOIN edn_dep edp ON edp.ednid = IF(mpi.movproptypid=" . mpt::EDNDAT . ", ed.ednid, mpi.otherid)
					INNER JOIN obj o ON o.id=opi.objid INNER JOIN aopt ao ON o.objtypid=ao.objtypid AND ao.aopttypid=" . aot::COLOR . "
					WHERE edp.depid IN($typsettDeps) AND f.id=$idfile AND (
						SELECT edndatstateid FROM edndathistory WHERE edndatid=ed.id AND typ=ao.val ORDER BY datt DESC LIMIT 1
					) IN (3, 4) LIMIT 1";
            if (\db::val($qry))
                return 1;
        }
        // TODO для случая прикрепления файла из буфера если текущий метод передатья дополнительные параметры (objid, optid),
        // а затем их передать методу isAdvUserFile то он будет делать выборку данных быстрее
        // sk Проврка доступности файлов в объявлениях
        if ($this->isAdvUserFile($idfile))
            return 1;

        return 0;
    }

    /**
     * sk определяет имеет ли пользователь право читать удалять файл
     *
     * @param int $fileid
     *            - id файла
     * @param int $objid
     *            - если указано, то проверяется доступность удаления в контексте объявления $objid
     * @param ont $ptyp
     *            и св-ва $ptyp
     * @return bool
     */
    public function isAdvUserFile($fileid, $objid = 0, $ptyp = 0)
    {
        if (\Encoder\Opt::$row['isroot'])
            return true;

        $mt = new m_typesetting();
        $roles = $mt->_getRoles();

        $designTyps = array(
            opt::LAYOUT_AI,
            opt::LAYOUT_TIF,
            opt::LAYOUT_PSD,
            opt::LAYOUT_AI_V1,
            opt::LAYOUT_AI_V2,
            opt::LAYOUT_AI_V3
        );

        // дизайнер может видеть и удалять дизайнерские файлы, превью к ним удаляются автоматически
        if ($roles['design'] && in_array($ptyp, $designTyps))
            return true;

        // доступ к просмотру
        if (!$objid) {
            // корректор и дизайнер может видеть не дизайнерские файлы в любых объявлениях
            if ($roles['corr'] || $roles['design']) {
                $qry = 'SELECT opi.objid FROM objproptyp opt
					INNER JOIN objpropint opi ON opt.id=opi.objproptypid
					WHERE opi.val=' . $fileid . ' AND opt.id NOT IN(' . implode(',', $designTyps) . ') AND opt.entityid=' . entity::FILE . '
					LIMIT 1';
                if (\db::val($qry))
                    return true;
            }

            // объявление у сотрудников отделов по которым я могу создавать заказы (и файл не дизайнерского свойства)
            if ($roles['manager']) {
                $qry = 'SELECT STRAIGHT_JOIN edp.depid FROM objproptyp opt
					INNER JOIN objpropint opi ON opt.id=opi.objproptypid
					INNER JOIN mov_obj mo ON mo.objid=opi.objid
					INNER JOIN movpropint mped ON mped.movid=mo.movid AND mped.movproptypid=' . mpt::EDNDAT . '
					INNER JOIN edndat ed ON ed.id=mped.otherid
					INNER JOIN edn_dep edp ON ed.ednid=edp.ednid
					WHERE opi.val=' . $fileid . ' AND edp.depid IN(' . makeString($roles['manager']) . ')
						AND opt.id NOT IN(' . implode(',', $designTyps) . ') AND opt.entityid=' . entity::FILE . '
					LIMIT 1';
                if (\db::val($qry))
                    return true;
            }

            // объявление в моем заказе или заказе подчиненного (и файл не дизайнерского свойства)
            if ($roles['staffids']) {
                $qry = 'SELECT STRAIGHT_JOIN o.id
					FROM objproptyp opt
					INNER JOIN objpropint opi ON opt.id=opi.objproptypid
					INNER JOIN mov_obj mo ON mo.objid=opi.objid
					INNER JOIN movpropint mpi ON mpi.movid=mo.movid AND mpi.movproptypid=' . mpt::ORD . '
					INNER JOIN ord o ON mpi.otherid=o.id
					WHERE opi.val=' . $fileid . ' AND o.staffid IN(' . makeString($roles['staffids']) . ')
						AND opt.id NOT IN(' . implode(',', $designTyps) . ') AND opt.entityid=' . entity::FILE . '
					LIMIT 1';
                if (\db::val($qry))
                    return true;
            }
        }

        $wh = '';
        // доступ к удалению файла (в контексте объявления)
        if ($objid)
            // если объявление есть в проведенных подачах то резудьтат (0)
            $wh = " AND opi.objid =$objid AND opi.objproptypid=$ptyp
				AND (SELECT IF(COUNT(m.movstateid),0,1) mst FROM mov_obj mo
					LEFT JOIN mov m ON m.id=mo.movid AND m.movstateid>2 WHERE mo.objid=o.id)";

        // запрос получения данных по файлам объявления
        $qry = 'SELECT opt.id optid, opi.objid FROM objproptyp opt
				INNER JOIN objpropint opi ON opi.objproptypid=opt.id AND opt.entityid=' . entity::FILE . '
				INNER JOIN obj o ON o.id=opi.objid
				INNER JOIN objtyp ot ON ot.id=o.objtypid
				WHERE ot.objtypgrpid=' . otg::ADVT . ' AND opi.val=' . $fileid . $wh . '
				GROUP BY opt.id, o.id';

        $objPropArr = \db::arr($qry);
        if ($objPropArr) {

            $previewTyps = array(
                opt::LAYOUT_JPG,
                opt::LAYOUT_JPG_V1,
                opt::LAYOUT_JPG_V2,
                opt::LAYOUT_JPG_V3
            );

            $typerTyps = array(
                opt::PHOTO_JPG,
                opt::PHOTO_JPG_BW
            );

            foreach ($objPropArr as $val) {
                $objid = $val['objid'];
                $optid = $val['optid'];

                // дизайнер может видеть и удалять дизайнерские файлы, превью к ним удаляются автоматически
                if ($roles['design'] && in_array($optid, $designTyps))
                    return true;
                // наборщик, менеджер, корректор могут видеть предпросмотр дизайнерских файлы
                // запросов на удаление быть не должно, а если и удалят, то ничего страшного
                if (($roles['typer'] || $roles['corr'] || $roles['design']) && in_array($optid, $previewTyps))
                    return true;

                // наборщик может видеть/менять фото
                if ($roles['typer'] && in_array($optid, $typerTyps))
                    return true;

                // файлы клиента
                if ($optid == opt::ADV_GOAL_FILE) {
                    // могут смотреть корректоры и дизайнерв
                    if (!$objid && ($roles['corr'] || $roles['design']))
                        return true;

                    // смотреть и удалять могут владельцы заказа
                    if ($mt->isOrdOwner(intval($objid)))
                        return true;
                }
            }
        }
        return false;
    }

    /**
     * получить имя файла по полям nam, typ, compress
     *
     * @param string $nam
     * @param string $typ
     * @param integer $compress
     */
    function filenamget($nam, $typ, $compress)
    {
        $iscompress = ($compress == "") ? 1 : $compress;
        $flnam = $nam;
        if ($typ != '')
            $flnam .= '.' . $typ;
        if ($iscompress > 1)
            $flnam .= '.zip';
        return $flnam;
    }

    /**
     * при ошибке удаления папки
     *
     * @param string $error
     */
    public static function onDirectoryRemoveError($error)
    {
        \db::diedlog("Ошибка удаления папки: " . $error, true, false);
        return FALSE;
    }

    /**
     * удалить папку
     *
     * @param
     *            $dir
     */
    public static function directoryremove($dir)
    {
        if (is_dir($dir)) {
            $handle = opendir($dir);
            if (!$handle)
                return self::onDirectoryRemoveError("Ошибка открытия папки $dir");

            // получаем все файлы
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' or $file == '..')
                    continue;
                $srcFile = $dir . '/' . $file;

                // удаляем файл или папку
                if (is_file($srcFile)) {
                    $deleted = unlink($srcFile);
                    if (!$deleted)
                        self::onDirectoryRemoveError("Не удалось удалить файл $srcFile");
                } else if (is_dir($srcFile)) {
                    $deleted = self::directoryremove($srcFile);
                    if (!$deleted)
                        self::onDirectoryRemoveError("Не удалось удалить папку $srcFile");
                }
            }
            closedir($handle);
            $deleted = rmdir($dir);
            return $deleted ? TRUE : self::onDirectoryRemoveError("Не удалось удалить папку $dir");
        } else if (is_file($dir)) {
            $deleted = unlink($dir);
            if (!$deleted)
                return self::onDirectoryRemoveError("Не удалось удалить файл $dir");
        } else
            return self::onDirectoryRemoveError("Отсутствует папка $dir");
    }

    /**
     * поиск строки в файле с определённой позиции
     *
     * @param string $fpath
     * @param string $word
     * @param integer $frompos
     * @return boolean
     */
    public static function findWord($fpath, $word, $frompos = 0)
    {
        $readbytes = 2097152;
        if (!file_exists($fpath))
            return FALSE;
        $wordlen = strlen($word);
        if ($wordlen > $readbytes - 1)
            return FALSE;
        // открыть файл
        $handle = fopen($fpath, "rb");
        if (!$handle)
            return FALSE;
        // установить позицию курсора
        if ($frompos && filesize($fpath) < $frompos)
            fseek($handle, $frompos, SEEK_SET);

        $findpos = FALSE;
        while (!feof($handle)) {
            $currseek = ftell($handle);
            $content = fread($handle, $readbytes);
            $pos = strpos($content, $word);
            if ($pos !== FALSE) {
                $findpos = $currseek + $pos;
                break;
            } else if (!feof($handle)) {
                fseek($handle, -$wordlen, SEEK_CUR);
            }
        }

        fclose($handle);
        return $findpos;
    }

    /**
     * удаляет sfx модуль из архива (rar, zip)
     *
     * @param string $fpath
     */
    public static function archiveRemoveSfxStub($fpath)
    {
        if (!file_exists($fpath))
            return FALSE;

        $str = "PADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADD";

        $ps1 = self::findWord($fpath, "MZ");
        $ps2 = self::findWord($fpath, $str);

        if ($ps1 !== FALSE && $ps2 !== FALSE) {
            $posfile = false;
            $filetyp = '';

            $ps3 = self::findWord($fpath, "Rar!", $ps2); // rar архив
            $ps4 = self::findWord($fpath, "PK", $ps2); // zip архив

            if ($ps3 !== FALSE && ($ps2 + strlen($str)) == $ps3) {
                $posfile = $ps3;
                $filetyp = 'rar';
            } else if ($ps4 !== FALSE && ($ps2 + strlen($str)) == $ps4) {
                $filetyp = 'zip';
                $posfile = $ps4;
            }

            if (!$posfile || !$filetyp)
                return FALSE;

            $flinfs = self::fileGenerate(mydef::$uploadpath, m_auth::$id);
            if ($flinfs !== FALSE) {
                // сохраняем файл без sfx модуля
                $handle = fopen($fpath, "rb");
                fseek($handle, $posfile, SEEK_SET);
                while (!feof($handle)) {
                    $content = fread($handle, 2097152);
                    fwrite($flinfs['handle'], $content);
                }
                fclose($handle);
                fclose($flinfs['handle']);
                @chmod($flinfs['path'], 0660);
                return array(
                    'type' => 'rar',
                    'path' => $flinfs['path']
                );
            }
        }
        return FALSE;
    }

    /**
     * возвращает по массива fileid массив filediskid, которые связаны только с этими fileid
     *
     * @param array $fids
     *            массив fileid
     */
    function filediskidLink($fids)
    {
        $fids = (is_array($fids)) ? implode(",", $fids) : $fids;
        $qry = "SELECT filediskid FROM file WHERE id IN ($fids);";
        $fdexist = \db::col($qry);
        if ($fdexist === FALSE) {
            return FALSE;
        }
        $fdexist = array_unique($fdexist);
        $qry = "SELECT filediskid FROM file WHERE id NOT IN ($fids);";
        $fdnoexist = \db::col($qry);
        if ($fdnoexist === FALSE)
            return FALSE;
        $fdnoexist = array_unique($fdnoexist);
        $fds = array_diff($fdexist, $fdnoexist);
        return $fds;
    }

    /**
     * удаляет по массиву filediskid файлы с жёсткого диска
     *
     * @param array $fdids
     * @param boolean $showinfo
     *            если true, то не удалять с диска
     */
    function filediskidHardDriveDelete($fdids, $showinfo)
    {
        $fdids = (is_array($fdids)) ? implode(",", $fdids) : $fdids;
        $size = 0;
        $ainfo = array();
        // массив файлов filedisk
        $qry = "SELECT id, dat FROM filedisk WHERE id IN ($fdids);";
        $arrfd = \db::arr($qry);
        if ($arrfd === FALSE)
            return FALSE;
        // подсчитываем размеры удаляемых файлов (filedisk) (или удаляем)
        $needdelete = (!$showinfo) ? true : false;
        for ($i = 0; $i < count($arrfd); $i++) {
            $sz = self::removeFile($arrfd[$i]['id'], $arrfd[$i]['dat'], $needdelete);;
            $size += $sz;
            if ($sz) {
                $ainfo[$arrfd[$i]['id']] = $sz;
            }
        }
        return array(
            'size' => $size,
            'info' => $ainfo
        );
    }

    /**
     * удалить по массиву fileid записи из базы данных
     *
     * @param array $fids
     */
    function fileidDelete($fids)
    {
        $fids = (is_array($fids)) ? implode(",", $fids) : $fids;
        $qry = "DELETE FROM file WHERE id IN ($fids)";
        $cnt = \db::query($qry);
        if ($cnt === FALSE)
            return FALSE;
        return $cnt;
    }

    /**
     * удалить по массиву filediskid записи из базы данных
     *
     * @param array $fdids
     */
    function filediskidDelete($fdids)
    {
        $fdids = (is_array($fdids)) ? implode(",", $fdids) : $fdids;

        // при удалении из filedisk нужно удалять из vir кроме id=0 и id=1
        $virids = \db::col("SELECT DISTINCT virid FROM filedisk fd WHERE fd.id IN ($fdids)");
        $virids = array_diff($virids, array(
            0,
            1
        ));

        if ($virids) {
            $svirids = implode(",", $virids);
            $viridothers = \db::col("SELECT DISTINCT virid FROM filedisk fd
WHERE fd.id NOT IN ($fdids) AND virid IN ($svirids)");

            $virids = array_diff($virids, $viridothers, array(
                1,
                2
            ));
            if ($virids) {
                $svirids = implode(",", $virids);
                \db::query("DELETE FROM vir WHERE id IN ($svirids)");
            }
        }

        $qry = "DELETE FROM filedisk WHERE id IN ($fdids)";
        $cnt = \db::query($qry);
        if ($cnt) {
            \db::query("DELETE FROM filediskpwd WHERE filediskid IN ($fdids)");
        }

        if ($cnt === FALSE)
            return FALSE;
        return $cnt;
    }

    /**
     * удаляет из таблицы usr_file + удаляет c диска
     *
     * @param array $fids
     * @param array $fdids
     */
    function usr_filedel($fids = array(), $fdids = array())
    {
        if (count($fdids) > 0) {
            $sids = implode(',', $fdids);
            $qry = "SELECT id FROM file WHERE filediskid IN ($sids)";
            $a = \db::col($qry);
            $fids = array_merge($fids, $a);
        }
        if (count($fids) > 0) {
            $sids = implode(',', $fids);
            return $this->usr_filelinkcleaner("fileid IN ($sids)");
        }
        return 0;
    }

    /**
     * получить таблицы, связанные с таблицей file по fileid
     */
    public static function fileidTables()
    {
        $adds = array(
            'objpropint' => array(
                'field' => 'val',
                'where' => 'objproptypid IN(SELECT id FROM objproptyp WHERE entityid=' . entity::FILE . ')',
                'deltype' => 1,
                'join' => 'INNER JOIN objproptyp ON objproptypid = objproptyp.id AND entityid=' . entity::FILE
            ),
            'rubprop' => array(
                'field' => 'val',
                'where' => 'rubproptypid IN(SELECT id FROM rubproptyp WHERE entityid=' . entity::FILE . ')',
                'deltype' => 1,
                'join' => 'INNER JOIN rubproptyp ON rubproptypid = rubproptyp.id AND entityid=' . entity::FILE
            )
        );
        $tbls = m_func::tablesWithField('fileid', array(
            'usr_file'
        ));
        foreach ($adds as $tablenam => $row) {
            $tbls[$tablenam] = array(
                'field' => $row['field'],
                'where' => $row['where'],
                'deltype' => $row['deltype'],
                'join' => $row['join']
            );
        }
        return $tbls;
    }

    /**
     * удалить записи из связанных с таблицей file таблиц
     *
     * @param array $fileids
     * @param string $action
     *            форсировать событие: ('', 'update')
     */
    public function fileidTablesRemove(array $fileids, $updateto = '')
    {
        $sfileid = implode(",", $fileids);
        $tbls = m_file::fileidTables();
        $res = array();
        $setto = $updateto == '' ? 'NULL' : $updateto;
        foreach ($tbls as $tablenam => $params) {
            $wh = $params['where'] ? "AND {$params['where']}" : "";
            $deltype = $params['deltype'];
            if ($updateto != '')
                $deltype = 0;

            if ($deltype == 1)
                $qry = "DELETE FROM $tablenam WHERE {$params['field']} IN ($sfileid) $wh";
            else {
                $qry = "UPDATE IGNORE $tablenam SET {$params['field']} = $setto WHERE {$params['field']} IN ($sfileid) $wh";
            }
            $res[$tablenam] = array(
                'count' => \db::query($qry),
                'field' => $params['field']
            );
        }
        return $res;
    }

    /**
     * формирует JOIN и WHERE для запроса - связки file с таблицами
     *
     * @param boolean $nullmode
     *            IS NULL или IS NOT NULL
     */
    public static function fileidTablesQry($nullmode = true, $skiptables = array())
    {
        $tbls = m_file::fileidTables();
        if ($skiptables) {
            foreach ($skiptables as $table)
                if (isset($tbls[$table]))
                    unset($tbls[$table]);
        }
        if (!$tbls)
            return array();
        $nullwh = $nullmode ? "IS NULL" : "IS NOT NULL";
        $nullglue = $nullmode ? " AND " : " OR ";

        uksort($tbls, function ($a, $b) {
            if ($a == 'mess_file')
                return -1;
            if ($b == 'mess_file')
                return 1;
            if ($a == 'objpropint' || $a == 'rubprop')
                return 1;
            if ($b == 'objpropint' || $b == 'rubprop')
                return -1;
            return 1;
        });

        // формируем запрос
        $joins = array();
        $wheres = array();
        foreach ($tbls as $tablenam => $row) {
            $wh = '';
            $jn = '';
            if (isset($row['join']) && $row['join']) {
                $jn = $row['join'];
            } else
                $wh = $row['where'] ? "AND {$row['where']}" : '';
            $joins[] = "LEFT JOIN $tablenam $jn ON f.id=$tablenam.{$row['field']} $wh";
            $wheres[] = "$tablenam.{$row['field']} $nullwh";
        }
        $sjoin = implode("\n", $joins);
        $swhere = implode($nullglue, $wheres);
        return array(
            'join' => $sjoin,
            'where' => $swhere
        );
    }

    /**
     * id, которые есть в file, но нет в (mess_file, confmess_file, task_file, .
     * ..)
     *
     * @param array $fileids
     *            fileid, в которых поиск ищется
     * @param bool $autodelete
     *            автоматически удалять найденные файлы
     */
    function integrityMessFile($fileids = array(), $autodelete = true)
    {
        $inf = self::fileidTablesQry(true);
        if (!$inf)
            return array();

        $wh = $inf['where'];
        if ($fileids) {
            $wh = "f.id IN (" . implode(",", $fileids) . ") AND $wh";
        }

        $qry = "SELECT f.id FROM file AS f {$inf['join']} WHERE $wh";
        $a = \db::col($qry);
        if ($a && $fileids) {
            $a = array_intersect($fileids, $a);
        }

        if ($autodelete && $a)
            $this->fileDelete(implode(',', $a));

        return $a;
    }

    /**
     * удалить из file(+filedisk+usr_file+диск)
     *
     * @param string $sfids
     */
    function fileDelete($sfids)
    {
        if (!$sfids)
            return false;
        $fds = $this->filediskidLink($sfids);
        if ($fds) { // удалить с диска
            $fdids = implode(',', $fds);
            $this->filediskidHardDriveDelete($fdids, false);
        }
        // удаляем из базы данных
        $usrfile = $this->usr_filedel(array(
            $sfids
        ));
        $file = $this->fileidDelete($sfids);
        if ($fds)
            $filedisk = $this->filediskidDelete($fdids);
        return true;
    }

    /**
     * очистка lnk файлов - папки на диске и БД
     *
     * @param string $where
     */
    function usr_filelinkcleaner($where)
    {
        // удалить с диска
        $arr = \db::arr("SELECT filepath, datt FROM usr_file WHERE $where");
        for ($i = 0, $iLen = count($arr); $i < $iLen; $i++) {
            $t = strtotime($arr[$i]['datt']);
            $filepath = mydef::$lnkpath . '/' . date("Ymd_His_", $t) . $arr[$i]['filepath'];
            if (is_dir($filepath))
                self::directoryremove($filepath);
        }
        // удалить из базы
        $code = \db::query("DELETE FROM usr_file WHERE $where");
        return $code;
    }

    /**
     * чистка линков более $cntday, которые создаются для прикреплённых файлов почтовых писем
     * ("eml attach, no record in database table file-filedisk")
     *
     * @param integer $cntday
     *            дней
     * @param array $filepaths
     *            ключи для удаления из пути к файлу после даты
     */
    function usr_filelinkcleanerdb($cntday, $filepaths = array())
    {
        $dir = mydef::$lnkpath;
        $cntsec = $cntday * 86400;

        $dirs = array();
        $ex = array(
            ".",
            ".."
        );
        $dh = opendir($dir);
        while (false !== ($filename = readdir($dh))) {
            $filepath = substr($filename, 16);
            $filedir = $dir . "/" . $filename;

            if (in_array($filename, $ex) !== FALSE || !is_dir($filedir))
                continue;

            // если входит в массив filepath
            if (isset($filepaths[$filepath])) {
                $dirs[$filepath] = $filedir;
                continue;
            }

            // время папки
            $year = substr($filename, 0, 4);
            $month = substr($filename, 4, 2);
            $day = substr($filename, 6, 2);
            $hour = substr($filename, 9, 2);
            $minute = substr($filename, 11, 2);
            $second = substr($filename, 13, 2);
            $tm = mktime($hour, $minute, $second, $month, $day, $year);
            if (time() - $tm > $cntsec) {
                $dirs[$filepath] = $filedir;
            }
        }
        if ($dh)
            closedir($dh);

        $res = array(
            'folder' => 0,
            'db' => 0,
            'errors' => array()
        );

        // массив для удаления из БД
        $flpaths = array();
        // проверка - есть в базе ($filepaths) - нет на диске
        foreach ($filepaths as $filepath => $datt) {
            $dirpath = mydef::$lnkpath . '/' . date("Ymd_His_", strtotime($datt)) . $filepath;
            if (!is_dir($dirpath))
                $res['errors'][] = array(
                    'datt' => date('H:i:s'),
                    'error' => "Отсутствует папка $dirpath"
                );
            $flpaths[] = $filepath;
        }

        // удалить папки с диска
        foreach ($dirs as $dbkey => $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $isdel = self::directoryremove($dir); // чистим папку

            if ($isdel) {
                if (!in_array($dbkey, $flpaths))
                    $flpaths[] = $dbkey;
                $res['folder']++;
            }
        }

        // удалить записи из бд
        if ($flpaths) {
            $flpath = '"' . implode('","', $flpaths) . '"';
            $res['db'] = \db::query("DELETE FROM usr_file	WHERE filepath IN ($flpath)");
        }
        return $res;
    }

    // путь к файлу
    function filepath($flid, $fldat)
    {
        $fldat = str_replace('-', '', $fldat);
        $flpath = mydef::$filepath . '/' . $fldat . '/' . $flid;
        return $flpath;
    }

    // упаковывает файл по алгоритму gz
    function gzPack($pathFrom, $pathTo, $pack = false, $packlink = '')
    {
        // (если несколько файлов паковать, то сначала нужно создать файл tar)
        // tar -cvf name.tar file1.txt file2.txt file3.txt
        // gzip -ck /path/filediskid > /path/filediskid.zip
        // gzip -ckdS '' /path/filediskid > /path/filediskid.oryg
        if (!is_file($pathFrom))
            return "Не удалось открыть файл $pathFrom";

        if ($pack) {
            if ($packlink) {
                $this->makehardlink($pathFrom, $packlink);
                $from = $packlink;
            } else
                $from = $pathFrom;

            $s = `gzip -ck "$from" > "$pathTo"`;
            if ((!is_file($pathTo) || (filesize($pathTo) == 0 && filesize($from) > 0)) && !$s) {
                $s = 'err';
            }
            if ($packlink && is_file($packlink)) {
                unlink($packlink);
            }
        } else {
            $s = `gzip -ckdS '' '$pathFrom' > '$pathTo'`;
        }
        // меняем права на файл
        if (is_file($pathTo))
            @chmod($pathTo, 0660);
        return $s;

        /*
         * $ifh = fopen($pathFrom,'rb'); if(!$ifh) return "Не удалось открыть файл для упаковки $pathFrom"; $ofh = fopen($pathTo,'wb'); if(!$ofh) return "Не удалось создать файл для запаковки $pathTo"; // по чуть чуть сжимаем и записываем $str = ''; while (!feof($ifh)) { $str= fread($ifh, 8192);			//1048576 $encoded =
         * gzencode($str); if(-1 == fwrite($ofh,$encoded)) return "Не удалось дописать данные в файл для запаковки $pathTo"; } fclose($ofh); fclose($ifh); return "";
         */
    }

    // создает жесткую ссылку
    function makehardlink($srcFile, $newFile)
    {
        if (substr(PHP_OS, 0, 3) != "WIN") {
            link($srcFile, $newFile);
        } else {
            // для Windows нужна программа xln.exe
            $comm = '"' . mydef::$filepath . '/xln.exe" "' . $srcFile . '" ' . '"' . $newFile . '"';
            exec($comm);
        }
    }

    /**
     * проверка распакованого архива с паролем на вирусы
     *
     * @param string $pathencrdir
     */
    function virusencryptcheckvirus($pathencrdir)
    {
        $cmd = trim(mydef::CLAMDSCAN . " --fdpass --no-summary '$pathencrdir'");
        $res = `$cmd`;
        $resarr = explode("\n", $res);

        $viruses = array();
        foreach ($resarr as $row) {
            $vv = $this->virusanswerprocess($row);
            if ($vv !== FALSE && $vv) {
                $vtyp = $this->virustype($vv['virus']);
                $viruses[] = array(
                    'typ' => $vtyp,
                    'nam' => $vv['virus'],
                    'fname' => $vv['fname']
                );
            }
        }
        return $viruses;
    }

    /**
     * проверка архива с паролем на вирусы
     *
     * @param string $pathencr
     * @param string $pwd
     * @param string $replace
     *            папки
     */
    function virusencryptunrar($pathencr, $pwd, $hashfile, $virusname)
    {
        // создаём папку
        $inf = pathinfo($pathencr);
        $dir = $inf['dirname'] . "/" . $hashfile;
        if (is_dir($dir)) {
            self::directoryremove($dir);
        }
        mkdir($dir);
        @chmod($dir, 0770);

        $ret = array(
            'dir' => $dir,
            'code' => 1
        );
        $lwvirusname = strtolower($virusname);

        if (strpos($lwvirusname, 'rar') !== FALSE) {
            // rar архив Heuristics.Encrypted.RAR
            // распаковываем // Total errors: 2 // All OK
            $res = trim(`/usr/local/bin/unrar x -p$pwd '$pathencr' $dir`);
            $resarr = explode("\n", $res);
            // неудалось распаковать - неверный пароль
            if (strpos($resarr[count($resarr) - 1], "Total errors:") !== FALSE) {
                $ret['code'] = 0;
            } else {
                // назначить права всем извлечённым папкам и файлам
                `chmod -R 0770 $dir`;
            }
        } else if (strpos($lwvirusname, 'zip') !== FALSE) {
            // zip архив Heuristics.Encrypted.Zip
            $res = `/usr/local/bin/unzip -P $pwd '$pathencr' -d $dir`;
            `chmod -R 0770 $dir`;
        }
        return $ret;
    }

    /**
     * проверка пароля для архива с паролем
     *
     * @param string $pathencr
     * @param string $pwd
     * @param string $virusname
     */
    function virusencryptcheckpwd($pathencr, $pwd, $virusname)
    {
        $lwvirusname = strtolower($virusname);
        if (strpos($lwvirusname, 'rar') !== FALSE) {
            // rar архив Heuristics.Encrypted.RAR
            // проверяем пароль // Total errors: 2 // All OK
            $res = trim(`/usr/local/bin/unrar t -p$pwd '$pathencr'`);
            $resarr = explode("\n", $res);
            if (strpos($resarr[count($resarr) - 1], "Total errors:") !== FALSE) {
                return false;
            }
            return true;
        } else if (strpos($lwvirusname, 'zip') !== FALSE) {
            // zip архив Heuristics.Encrypted.Zip
            $res = `/usr/local/bin/unzip -t -P $pwd '$pathencr'`;
            $resarr = explode("\n", $res);
            array_shift($resarr);
            foreach ($resarr as $row) {
                if (strpos($row, 'unable to get password') !== FALSE || strpos($row, 'incorrect password') !== FALSE) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * получить информацию о вирусах в файлах по их id
     *
     * @param array $fileids
     */
    public function virusesinfo(array $fileids, $isfdids = false, $messid = 0)
    {
        $mfile = new m_file();

        $wh = $isfdids ? "fd.id" : "f.id";

        $jn = "";
        $wrm = "";
        if ($messid) {
            $jn = "INNER JOIN mess_file mf ON mf.fileid=f.id";
            $wrm = "AND mf.messid=$messid";
        }

        $sfileids = implode(",", $fileids);
        $qry = "SELECT f.nam, f.typ, fd.compress, f.id fileid, v.nam virnam, fd.virid, fd.hash FROM file AS f
		INNER JOIN filedisk AS fd ON fd.id=f.filediskid INNER JOIN vir AS v ON v.id=fd.virid
		$jn
		WHERE $wh IN ($sfileids) AND v.id > 2 $wrm";
        $vinfo = \db::arr($qry);

        $viruses = array();
        foreach ($vinfo as $row) {
            $virtype = $this->virustype($row['virnam']);
            $fnam = $this->filenamget($row['nam'], $row['typ'], $row['compress']);
            $viruses[] = array(
                'fname' => $fnam,
                'vname' => $row['virnam'],
                'type' => $virtype,
                'hash' => $row['hash']
            );
        }
        return $viruses;
    }

    /**
     * занесение/получение(pwd=FALSE) в бд правильных паролей для зашифрованных архивов
     *
     * @param integer $filediskid
     * @param string $pwd
     */
    public function filediskpwdaction($filediskid, $pwd = FALSE)
    {
        if ($pwd === FALSE) {
            $res = array();
            $arr = \db::col("SELECT val FROM filediskpwd WHERE filediskid = $filediskid");
            foreach ($arr as $row) {
                $res[] = m_email::encodepwd($row, 'JHSDF455DFGD');
            }
            return $res;
        } else {
            $pass = m_email::encodepwd($pwd, 'JHSDF455DFGD');
            $pass = \db::escape_string($pass);
            return \db::query("INSERT IGNORE INTO filediskpwd(filediskid, val) VALUES($filediskid, '$pass')");
        }
    }

    /**
     * по названию вируса получить его тип: 1-вирус; 2-зашифрованный архив; 3-подозрение на архив
     *
     * @param string $virnam
     */
    public function virustype($virnam)
    {
        $type = 1;
        if (strpos($virnam, 'PUA') === 0) {
            $type = 3;
        } elseif (strpos($virnam, 'Heuristics.Encrypted') !== FALSE) {
            $type = 2;
        }
        return $type;
    }

    /**
     * обработка одной строки ответа антивируса
     *
     * @param string $str
     */
    public function virusanswerprocess($str)
    {
        if ($str == "") {
            return FALSE;
        }
        $ar = explode(": ", $str, 2);
        $tmp = trim($ar[1]);

        if ($tmp == "OK")
            return "";
        $pos = strpos($tmp, "FOUND");
        if ($pos !== FALSE && $pos == strlen($tmp) - 5) {
            $pinf = pathinfo($ar[0]);
            return array(
                'virus' => trim(substr($tmp, 0, $pos - 1)),
                'fname' => $pinf['filename']
            );
        }
        return FALSE;
    }

    /**
     * проверка файла на вирусы
     *
     * @param string $filepath
     *            return array(
     *            code:    0 - нет вирусов, 1 - вирус, 2 - error;
     *            virus: имя вируса;
     *            type: 1 вирус, 2 зашифр. архив, 3 подозрение на вирус
     *            )
     */
    public function viruscheck($filepath, $deletefile = false)
    {
        $ret = array(
            'code' => 2,
            'virus' => '',
            'type' => 0
        ); // type: (visur, archiv, pua)
        $cmd = trim(mydef::CLAMDSCAN . " --fdpass --no-summary '$filepath'");
        $res = `$cmd`;

        $virap = $this->virusanswerprocess($res);
        if ($virap !== FALSE) {
            if (!$virap) {
                $ret['code'] = 0;
            } else {
                $ret = array(
                    'code' => 1,
                    'type' => 1,
                    'virus' => $virap['virus']
                );
                $ret['type'] = $this->virustype($virap['virus']);
            }

            // если exe файл с паролем (sfx), антивирус не выдаёт этого; убираем sfx модуль и вновь проверяем антивирусом
            $inf = m_file::archiveRemoveSfxStub($filepath);
            if ($inf !== FALSE) {
                if ($deletefile && file_exists($filepath)) {
                    unlink($filepath);
                }
                return $this->viruscheck($inf['path'], true);
            }
        }
        if ($deletefile && file_exists($filepath)) {
            unlink($filepath);
        }
        return $ret;
    }

    /**
     * создаёт файл в файловой структуре /Ymd/filediskid
     *
     * @param integer $filediskid
     * @param string $date
     * @param string $filepath
     *            файл перемещать или
     * @param string $content
     *            записать в файл или
     * @param string $postInd
     *            переменной $_POST, в которой лежит файл
     */
    public function adfiletodisk($filediskid, $date, $frompath, &$content = "", $postInd = "")
    {
        $resfile = "";
        if (!$filediskid || !$date)
            return $resfile;

        // куда сохранять файл
        $sdate = str_replace("-", "", $date);
        $uploaddir = mydef::$filepath . "/" . $sdate;
        if (!is_dir($uploaddir)) {
            mkdir($uploaddir);
            @chmod($uploaddir, 0770);
        }
        if (!is_dir($uploaddir)) {
            return $resfile;
        }
        $tofile = $uploaddir . "/" . $filediskid;

        // перемещаем файлы в папку
        if (!file_exists($tofile)) {
            if ($frompath) {
                if (is_file($frompath)) {
                    rename($frompath, $tofile);
                } else
                    \db::diedlog("Ошибка добавления файла adfiletodisk", false, true, false);
                // move_uploaded_file($frompath, $tofile);
                @chmod($tofile, 0660);
            } else {
                $f = fopen($tofile, 'wb');
                // при передачи больших файлов чтобы не копировать переменную из $_POST
                if ($postInd != '') {
                    if (isset($_POST[$postInd]))
                        fwrite($f, $_POST[$postInd]);
                } else {
                    fwrite($f, $content);
                }
                fclose($f);
                @chmod($tofile, 0660);
            }
        }
        $resfile = $tofile;
        return $resfile;
    }

    /**
     * заносим файл в бд
     *
     * @param string $fname
     * @param string $typ
     * @param string $date
     * @param integer $siz
     * @param array $fdinfo
     *            параметры для filedisk (id,hash,vir,virnew,virid,compress,siz)
     *            id    filediskid файла, если он уже есть на диске
     *            hash    hash файла
     *            vir,virnew,virid есть virnew, но нет vir - добавлять запись в vir; иначе virid использовать
     *            compress,siz    необ. сжатие если сжат compress=2, то ещё параметр siz
     * @param string $filepath
     */
    public function addfiletodb($fname, $typ, $date, $siz, array $fdinfo)
    {
        $fdiskid = (isset($fdinfo['id']) && $fdinfo['id']) ? $fdinfo['id'] : 0;
        if (!$fdiskid) {
            // информация о вирусе
            $virid = 1;
            if (!isset($fdinfo['vir']) && isset($fdinfo['virnew']) && $fdinfo['virnew']) {
                $vname = trim($fdinfo['virnew']);
                $virid = \db::query("INSERT INTO vir(nam) VALUES('{$vname}') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)", true);
            } else {
                if (!isset($fdinfo['virid']))
                    $virid = 1;
                else
                    $virid = $fdinfo['virid'];
            }
            $hash = (isset($fdinfo['hash']) && $fdinfo['hash']) ? $fdinfo['hash'] : '';
            $compress = isset($fdinfo['compress']) && $fdinfo['compress'] ? $fdinfo['compress'] : 'NULL';
            $sizoryg = $siz;
            if ($compress == 2) {
                $siz = $fdinfo['siz'];
            }
            $qry = "INSERT INTO filedisk(dat, siz, sizoryg, hash, virid, compress) VALUES('$date', $siz, $sizoryg, '$hash', $virid, $compress) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)";
            \db::query($qry);
            $filediskid = \db::insert_id();
            // пароль для зашифрованного архива
            if (isset($fdinfo['pwd']) && $fdinfo['pwd']) {
                $this->filediskpwdaction($filediskid, $fdinfo['pwd']);
            }
        } else {
            $filediskid = $fdiskid;
        }

        // очищаем имя файла от недопустимых символов
        $fname = $this->nameCleanForbidden($fname);
        $fname = \db::escape_string(trim($fname));
        if (!trim($fname))
            $fname = 'unknown';

        // если уже есть такая запись в file, не добавлять её
        $idfile = 0;
        if ($fdiskid) {
            $idfile = \db::val("SELECT id FROM file WHERE filediskid=$fdiskid AND nam='$fname' AND typ='$typ' LIMIT 1");
        }
        if (!$idfile) {
            $qry = "INSERT INTO file(nam, typ, filediskid) VALUES('$fname', '$typ', $filediskid) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)";
            $idfile = \db::query($qry, true);
        }
        return array(
            'fileid' => $idfile,
            'filediskid' => $filediskid
        );
    }

    /**
     * хеширует файл hash;
     * если файл есть в БД, то получить id filediskid;
     * если файл проверялся на вирусы, то получить virid;
     * если в файле вирус, то получить vir - имя вируса
     *
     * @param string $filepath
     * @param string $fname
     * @param string $ftyp
     * @param string $apckey
     * @param string $content
     *            файла
     * @param string $postInd
     *            если файл содержится в $_POST[$postInd])
     * @return array (hash, id-filediskid, virid, vir - имя вируса) hash - обязательно
     */
    public function addfilehash($filepath, $fname, $ftyp, $apckey = "", &$content = "", $postInd = "")
    {
        $fullnam = $ftyp != "" ? $fname . "." . $ftyp : $fname;
        if ($apckey) {
            $keyprocess = self::apc_viruskey($apckey, 'process');
            $bl = apc_store($keyprocess, array(
                'filehash' => $fullnam
            ), 3600);
            // show('store hash', $keyprocess, $bl);
            // sleep(1);
        }
        $hash = '';
        $fsize = -1;
        if ($filepath) {
            $hash = md5_file($filepath);
            $fsize = filesize($filepath);
        } else if ($content) {
            $hash = md5($content);
            $fsize = strlen($content);
        } else if ($postInd != '' && isset($_POST[$postInd])) {
            $hash = md5($_POST[$postInd]);
            $fsize = strlen($_POST[$postInd]);
        }
        $fdinfo = array(
            'hash' => $hash
        );
        $arrs = \db::arr("SELECT fd.id, fd.dat, fd.virid, v.nam, fd.compress, fd.sizoryg FROM filedisk fd LEFT JOIN vir v ON v.id=fd.virid
WHERE fd.hash='$hash'");
        $siz = -1;
        foreach ($arrs as $row) {
            // $flsiz = $this->fsize($row['id'], $row['dat']);
            // if($flsiz < 0)
            // continue;
            $flsiz = $row['sizoryg'];

            $isadd = false;
            if ($row['virid'] == 2 && !isset($fdinfo['vir'])) {
                // проверялся - вирусов нет
                $isadd = true;
                $fdinfo['id'] = $row['id'];
                $fdinfo['virid'] = $row['virid'];
                $siz = $flsiz;
            } elseif ($row['virid'] > 2) {
                // проверялся - вирусы есть
                $isadd = true;
                $fdinfo['id'] = $row['id'];
                $fdinfo['virid'] = $row['virid'];
                $fdinfo['vir'] = $row['nam'];
                $siz = $flsiz;
            } else if (!isset($fdinfo['virid'])) {
                // не проверялся
                $isadd = true;
                $fdinfo['id'] = $row['id'];
                $siz = $flsiz;
            }
            // инфо о сжатии
            if ($isadd && $row['compress'] != '') {
                $fdinfo['compress'] = $row['compress'];
                if ($row['compress'] == 2)
                    $fdinfo['siz'] = $row['sizoryg'];
                $siz = $flsiz;
            }
        }
        if (isset($fdinfo['id'])) {
            if ($siz != -1 && $fsize != -1 && $siz != $fsize) {
                $fdinfo['sizeerror'] = array(
                    'sizecurrent' => $siz,
                    'size' => $siz
                );
            }
        }
        if ($apckey) {
            $bl = apc_delete($keyprocess);
            // show('delete hash', $keyprocess, $bl);
        }
        return $fdinfo;
    }

    /**
     * проверка на вирусы
     *
     * @param string $filepath
     * @param string $fname
     * @param string $ftyp
     * @param string $apckey
     * @return fname имя файла, vname имя вируса, type тип вируса
     */
    public function addfilevirus($filepath, $fname, $ftyp, $apckey = "")
    {
        $virusinfo = array();
        $fullnam = $ftyp != "" ? $fname . "." . $ftyp : $fname;

        if ($apckey) {
            $keyprocess = self::apc_viruskey($apckey, 'process');
            $bl = apc_store($keyprocess, array(
                'filecheckvirus' => $fullnam
            ), 3600);
            // show('store virus', $keyprocess, $bl);
            // sleep(1);
        }
        $vransw = $this->viruscheck($filepath);
        if ($vransw['code'] == 1 || $vransw['code'] == 2) {
            $virusinfo = array(
                'fname' => $fullnam,
                'vname' => $vransw['virus'],
                'type' => $vransw['type']
            );
        }
        if ($vransw['code'] == 2)
            $virusinfo["error"] = true;
        if ($apckey) {
            $bl = apc_delete($keyprocess);
            // show('delete vir', $keyprocess, $bl);
        }
        return $virusinfo;
    }

    /**
     * сжатие файла
     *
     * @param string $filepath
     * @param string $fname
     * @param string $ftyp
     * @param integer $sizeKb
     * @param integer $procent
     * @param array $exts
     *            которые не нужно сжимать
     * @return array compress-NULL не обработан,1-не сжат,2-сжат;
     *         path - путь к сжатому файлу, после перемещения его нужно удалить
     *         siz - размер сжатого файла
     */
    public function addfilecompress($filepath, $fname, $ftyp, $sizeKb = 0, $procent = 0, $apckey = "", array $exts = array("rar", "zip", "jpeg", "jpg", "7z", "avi", "3gp", "mkv", "mov", "mpg", "mpeg", "vob", "xvid"))
    {
        $fullnam = $ftyp != "" ? $fname . "." . $ftyp : $fname;
        if ($apckey) {
            $keyprocess = self::apc_viruskey($apckey, 'process');
            $bl = apc_store($keyprocess, array(
                'filecompress' => $fullnam
            ), 3600);
            // show('store compress', $keyprocess, $bl);
            // sleep(1);
        }
        $res = array(
            'compress' => NULL,
            'path' => '',
            'error' => '',
            'siz' => -1
        );
        $fnamelower = strtolower($fname);
        $ftyplower = strtolower($ftyp);

        // не сжимать mailtxt.txt и main.html
        if (($fnamelower == 'main' && $ftyplower == 'html') || ($fnamelower == 'mailtxt' && $ftyplower == 'txt')) {
            $res['compress'] = 1;
            if ($apckey)
                $bl = apc_delete($keyprocess);
            return $res;
        }

        // не сжимать файлы меньше определённого размера
        $sizfrom = filesize($filepath);
        if ($sizfrom <= $sizeKb * 1024) {
            $res['compress'] = 1;
            if ($apckey)
                $bl = apc_delete($keyprocess);
            return $res;
        }

        // не сжимать файлы с определёнными расширениями
        if (in_array(strtolower($ftyp), $exts)) {
            $res['compress'] = 1;
            if ($apckey)
                $bl = apc_delete($keyprocess);
            return $res;
        }

        // сжимаем файл
        $pinf = pathinfo($filepath);
        $fileTo = \db::escape_string("{$pinf['dirname']}/{$pinf['basename']}.zip");
        $mfile = new m_file();

        $fnam = $mfile->filenamget($fname, $ftyp, 1);
        $packlink = "{$pinf['dirname']}/$fnam";
        // $packlink = $pinf['dirname']."/".md5(m_auth::$id).md5(microtime().rand());

        $packed = $mfile->gzPack($filepath, $fileTo, true, $packlink);
        if ($packed != "") {
            $res['error'] = "Ошибка при сжатии: $packed";
            if ($apckey)
                $bl = apc_delete($keyprocess);
            return $res;
        }

        // процент сжатия
        $sizto = filesize($fileTo);
        $diff = $sizfrom - $sizto - ($procent / 100 * $sizfrom);
        if ($diff > 0)
            $diff = $sizfrom - $sizto - $sizeKb * 1024; // процент сжатия хороший, но файл маленький - не сжимать
        if ($diff > 0) {
            $res['siz'] = $sizto;
            $res['compress'] = 2;
            $res['path'] = $fileTo;
        } else {
            $res['compress'] = 1;
            unlink($fileTo);
        }
        if ($apckey) {
            $bl = apc_delete($keyprocess);
            // show('delete compress', $keyprocess, $bl);
        }
        return $res;
    }

    /**
     * окончание записи
     *
     * @param array $toadds
     * @param array $hashaccepts
     */
    public function addfiles2(array $toadds, array $hashaccepts)
    {
        // с вирусами не добавлять в базу
        $fileids = array();
        $hashs = array();
        foreach ($toadds as $row) {
            $fname = $row[0];
            $typ = $row[1];
            $date = $row[2];
            $siz = $row[3];
            $path = $row[4];
            $fdinfo = $row[5];

            if ($row[6]) {
                if (!in_array($row[6]['hash'], $hashaccepts)) {
                    // архивы и зашифрованные файлы не добавляем
                    if (in_array($row[6]['type'], array(
                        1,
                        2
                    ))) {
                        continue;
                    }
                    // выбор какие из файлов с подозрением (pua) отправлять
                    // if($row[6]['type'] == 3 && !in_array($row[6]['hash'], $hashaccepts)){
                    if ($row[6]['type'] == 3) {
                        continue;
                    }
                }
                if (!isset($fdinfo['vir'])) {
                    $fdinfo['virnew'] = $row[6]['vname'];
                }
            }

            // при отправке одинаковых файлов добавлять в filediskid 1 запись
            if (isset($hashs[$fdinfo['hash']]) && !isset($fdinfo['id'])) {
                $fdid = $hashs[$fdinfo['hash']];
                $arrs = \db::row("SELECT fd.virid, v.nam, siz, compress FROM filedisk fd LEFT JOIN vir v ON v.id=fd.virid WHERE fd.id='$fdid'");
                $fdinfo['id'] = $fdid;
                $fdinfo['virid'] = $arrs['virid'];
                $fdinfo['vir'] = $arrs['nam'];
                if ($arrs['compress'] != '') {
                    $fdinfo['compress'] = $arrs['compress'];
                    if ($arrs['compress'] == 2)
                        $fdinfo['siz'] = $arrs['siz'];
                }
            }

            // перемещаем файл на диск
            $adds = array();
            if (!(isset($fdinfo['id']) && $fdinfo['id'])) {
                $ispack = false;
                // если файл сжат, то берём его
                if (isset($fdinfo['path']) && is_file($fdinfo['path'])) {
                    $path = $fdinfo['path'];
                    $ispack = true;
                }

                if (is_file($path)) {
                    // добавляем файл в БД
                    $adds = $this->addfiletodb($fname, $typ, $date, $siz, $fdinfo);
                    $tofile = $this->adfiletodisk($adds['filediskid'], $date, $path);
                } else {
                    \db::diedlog("Ошибка добавления файла addfiles", false, true, false);
                }
            } else {
                // добавляем файл в БД
                $adds = $this->addfiletodb($fname, $typ, $date, $siz, $fdinfo);
                // если файл уже в базе, но его нету на диске - добавить его на диск
                $fddat = \db::val("SELECT dat FROM filedisk WHERE id = {$fdinfo['id']}");
                $isflexist = is_file($this->filepath($fdinfo['id'], $fddat));
                if (!$isflexist && !is_file($path)) {
                    \db::diedlog("Отсутствует файл addfiles", false, true, false);
                }
                if (is_file($path) && !$isflexist) {
                    $tofile = $this->adfiletodisk($fdinfo['id'], $fddat, $path);
                }
            }

            if ($adds) {
                $fileids[] = $adds['fileid'];
                $hashs[$fdinfo['hash']] = $adds['filediskid'];
            }
        }
        return $fileids;
    }

    /**
     * добавляем файлы в базу и в папки $arrFiles - массив файлов
     *
     * @param string $fileindex
     */
    function addfiles($fileindex = 'userfile', $apckey = "", array $params = array())
    {
        $arrFiles = $_FILES[$fileindex];
        $date = date("Y-m-d");
        $sdate = str_replace("-", "", date("Y-m-d"));
        // диалог показывать на 30 сек. меньше max_execution_time
        $timestart = time();
        $exectime = ini_get('max_execution_time') - 30;

        $upldir = ini_get('upload_tmp_dir');
        if (!is_dir($upldir)) {
            $this->apc_clean();
            return array(
                'error' => 'Отсутствует папка для сохранения файлов. Обратитесь к администратору'
            );
        }

        $funcparams = array();
        $isvirus = false;
        $viruses = array();
        $toadds = array();
        $iscances = false; // пользователь нажел отменить отправку
        for ($i = 0, $icnt = count($arrFiles['name']); $i < $icnt; $i++) {
            if (!isset($arrFiles['name'][$i]) || FALSE === file_exists($_FILES[$fileindex]['tmp_name'][$i]) || is_uploaded_file($_FILES[$fileindex]['tmp_name'][$i]) === FALSE)
                continue;

            $actionlog = array(
                'file: ' . $_FILES[$fileindex]['tmp_name'][$i]
            );

            $fname = \Encoder\Coder::coding($arrFiles['name'][$i], true, 1);
            $finf = pathinfo($arrFiles['name'][$i]);
            $typ = '';
            if (isset($finf['extension'])) {
                $typ = $finf['extension'];
                if ($typ != '') {
                    preg_match("/^[a-zA-Z\d]+/", $typ, $mtch);
                    if (!isset($mtch[0]))
                        $typ = '';
                    if ($typ != '')
                        $fname = substr($fname, 0, strlen($fname) - strlen($typ) - 1);
                }
            }
            $typ = strtolower($typ);
            $siz = $arrFiles['size'][$i];

            // хеширование файла
            $fdinfo = $this->addfilehash($arrFiles['tmp_name'][$i], $fname, $typ, $apckey);
            $hash = $fdinfo['hash'];
            $actionlog[] = ";hash= $hash";
            // если есть файл с таким hash, но другим размером - не вносить файл и выдавать ошибка хеширования
            if (isset($fdinfo['sizeerror'])) {
                \Encoder\Usrlog::logadd(35, "Ошибка хеширования файла: $fname", 'filedisk', $fdinfo['id']);
                return array(
                    'error' => 'Ошибка хеширования'
                );
            }

            // проверка на вирусы
            $virusinfo = array();
            if (isset($fdinfo['virid'], $fdinfo['vir'])) {
                // если уже файл проверялся на вирусы
                $fullnam = $typ != "" ? $fname . "." . $typ : $fname;
                $virtyp = $this->virustype($fdinfo['vir']);
                $virusinfo = array(
                    'fname' => $fullnam,
                    'vname' => $fdinfo['vir'],
                    'type' => $virtyp,
                    'hash' => $hash
                );
                $isvirus = true;
            } else if (!isset($fdinfo['virid'])) {
                $actionlog[] = ";virusbefore=" . file_exists($arrFiles['tmp_name'][$i]);
                $virusinfo = $this->addfilevirus($arrFiles['tmp_name'][$i], $fname, $typ, $apckey);
                if (!$virusinfo) {
                    $fdinfo['virid'] = 2; // вирусов нету (id=2 в таблице vir)
                } else if (isset($virusinfo['error'])) {
                    $fdinfo['virid'] = 1; // ошибка проверки на вирусы
                }
                $actionlog[] = ";virusafter=" . file_exists($arrFiles['tmp_name'][$i]);
            }
            if ($virusinfo) {
                $virusinfo['hash'] = $hash;
                $viruses[] = $virusinfo;
                $isvirus = true;
            }

            // сжатие (compress: NULL- не обработан; 1-не сжат; 2-сжат)
            if (!isset($fdinfo['compress'])) {
                if (isset($params['layout']) && $params['layout'] == 52) {
                    // tiff не сжимать в макетах
                    $fdinfo['compress'] = 1;
                } else {
                    $actionlog[] = ";compressbefore=" . file_exists($arrFiles['tmp_name'][$i]);

                    // был случай, что пришедшего файла $arrFiles['tmp_name'][$i] не оказалось ???
                    if (!file_exists($arrFiles['tmp_name'][$i])) {
                        \db::diedlog("Ошибка приёма файла " . implode("", $actionlog), true, false);
                        continue;
                    }

                    $a = optglobget(array(
                        "compressprocent",
                        "compresssize"
                    ));
                    $packs = $this->addfilecompress($arrFiles['tmp_name'][$i], $fname, $typ, $a['compresssize'], $a['compressprocent'], $apckey);
                    // $packs = $this->addfilecompress($arrFiles['tmp_name'][$i], $fname, $typ, 0, 0, $apckey);

                    $actionlog[] = ";compressafter=" . file_exists($arrFiles['tmp_name'][$i]);

                    // был случай, что пришедшего файла $arrFiles['tmp_name'][$i] не оказалось ???
                    if (!is_file($arrFiles['tmp_name'][$i])) {
                        \db::diedlog("Ошибка приёма файла " . implode("", $actionlog), true, false);
                        continue;
                    }

                    if ($packs['compress'] != NULL && !$packs['error']) {
                        $fdinfo['compress'] = $packs['compress'];
                        if ($packs['compress'] == 2) {
                            $fdinfo['siz'] = $packs['siz'];
                            $fdinfo['path'] = $packs['path'];
                        }
                    }
                }
            } else {
                // при добавление tiff в объявление если файл такой был и он был сжат - расжимать его
                if (isset($params['layout']) && $params['layout'] != 62 && in_array($typ, array(
                        'tif',
                        'tiff'
                    )) && $fdinfo['compress'] == 2) {
                    // удалить все линки на файл и расжать
                    $fileids = \db::col("SELECT id FROM file WHERE filediskid = {$fdinfo['id']}");
                    if ($fileids) {
                        $this->usr_filelinkcleaner("fileid IN (" . implode(",", $fileids) . ")");
                    }
                    $res = m_shellcron::unpackFiles($fdinfo['id']);
                    if ($res["arr"]) {
                        $qry = "UPDATE filedisk SET compress=1 WHERE id={$fdinfo['id']}";
                        \db::query($qry);
                        $fdinfo['compress'] = 1;
                        if (isset($fdinfo['siz']))
                            unset($fdinfo['siz']);
                    }
                }
            }

            $toadds[] = array(
                $fname,
                $typ,
                $date,
                $siz,
                $arrFiles['tmp_name'][$i],
                $fdinfo,
                $virusinfo
            );

            if (isset($params['layout'])) {
                // вирусы не пропускать
                if ($isvirus && in_array($virusinfo['type'], array(
                        1,
                        2
                    )))
                    break;
                $funcparams['ftyp'] = $typ;
                $funcparams['fname'] = $fname;
                $funcparams['fsize'] = $siz;
                $funcparams['uplfid'] = $i;
                $funcparams['fileindex'] = $fileindex;
                break;
            }
        }

        // очищаем данные: прогресс бар для файлов
        $this->apc_clean();

        // диалог проверки на вирусы
        $hashacceptslog = array();
        $hashaccepts = array();
        $chooses = array();
        $archivecorrectpwd = array();
        if ($isvirus) {
            $key = self::apc_viruskey($apckey, 'vir');
            $vrinf = array(
                'viruschk' => 1,
                'viruses' => $viruses
            );
            $bl = apc_store($key, $vrinf, 3600);

            // что выбрал пользователь
            $newkey = self::apc_viruskey($apckey, 'virchoose');

            // неправильный пароль
            $pwderrkey = self::apc_viruskey($apckey, 'pwderr');

            // переменная для обновления (нажал ли пользователь кнопку-подтверждение)
            $keyacc = self::apc_viruskey($apckey, 'viracc');
            $bl = apc_store($keyacc, 1, 60);
            while (apc_exists($keyacc) && (time() - $timestart < $exectime)) {

                // пароли к архивам
                if (apc_exists($newkey)) {
                    $chooses = apc_fetch($newkey);
                    if ($chooses['choose'] != 2) {
                        // правильные пароли к архивам
                        $archivecorrectpwd = array();
                        $isarchivpwd = false;

                        // проверка правильности паролей
                        $archiveserrpwd = array();
                        foreach ($chooses['fobj'] as $uk => $uv) {
                            if (strpos($uk, 'pwd_') === 0 && $uv) {
                                $archivehash = str_replace("pwd_", "", $uk);
                                $archivepwd = $uv;
                                // ищем файл
                                foreach ($toadds as $fkey => $frow) {
                                    if ($frow[5]['hash'] == $archivehash) {
                                        $isarchivpwd = true;
                                        $archivepath = $frow[4];

                                        if (!$this->virusencryptcheckpwd($archivepath, $archivepwd, $frow[6]['vname'])) {
                                            $archiveserrpwd[$archivehash] = 1;
                                        } else {
                                            $fname = $frow[0];
                                            if ($frow[1])
                                                $fname .= ".{$frow[1]}";
                                            $archivecorrectpwd[$archivehash] = array(
                                                'path' => $archivepath,
                                                'pwd' => $archivepwd,
                                                'fname' => $fname,
                                                'virnam' => $frow[6]['vname']
                                            );
                                        }
                                    }
                                }
                            }
                        }

                        // не более трёх проверок пароля
                        if ($archiveserrpwd) {
                            foreach ($vrinf['viruses'] as $vkey => $vval) {
                                if (isset($archiveserrpwd[$vval['hash']])) {
                                    if (!isset($vval['pwdcnt'])) {
                                        $vrinf['viruses'][$vkey]['pwdcnt'] = 1;
                                    } else if ($vval['pwdcnt'] == 3) {
                                        unset($archiveserrpwd[$vval['hash']]);
                                    } else {
                                        $vrinf['viruses'][$vkey]['pwdcnt'] += 1;
                                        if ($vval['pwdcnt'] == 2) {
                                            // log
                                            $fdiskid = \db::val("SELECT id FROM filedisk WHERE hash='{$vval['hash']}'");
                                            if (!$fdiskid)
                                                $fdiskid = false;
                                            \Encoder\Usrlog::flushLog();
                                            \Encoder\Usrlog::logadd(36, 'Ошибка загрузки', 'filedisk', $fdiskid);
                                        }
                                    }
                                }
                            }
                        }

                        if ($archiveserrpwd) {
                            // неверные пароли - сообщить пользователю
                            $bl = apc_store($key, $vrinf, 3600);
                            // удалить ключ $newkey
                            if (apc_exists($newkey))
                                $bl = apc_delete($newkey);
                        } else {
                            if ($isarchivpwd) {
                                break;
                            }
                        }
                    }
                }

                sleep(1);
            }

            if (apc_exists($keyacc)) {
                $bl = apc_delete($keyacc);
            }

            if (apc_exists($newkey)) {
                $chooses = apc_fetch($newkey);
                $choose = $chooses['choose'];

                // отмена отправки
                if ($choose == 2) {
                    $iscances = true;
                } else {
                    $hashaccepts = array_keys($chooses['fobj']);
                    foreach ($hashaccepts as $ha) {
                        if (strpos($ha, 'pwd_') === FALSE) {
                            $hashacceptslog[] = $ha;
                        }
                    }
                }
                $bl = apc_delete($newkey);
            } else {
                $iscances = true; // ожидание выбора пользователя окончилось
            }
            $bl = apc_delete($key);

            // отмена отправки
            if ($iscances) {
                return array(
                    'error' => ''
                );
            }

            // распаковать архивы с паролями
            foreach ($archivecorrectpwd as $vkey => $vval) {
                // распаковать архив, используя пароль
                $answ = $this->virusencryptunrar($vval['path'], $vval['pwd'], $vkey, $vval['virnam']);
                if ($answ['code']) {
                    $virs = $this->virusencryptcheckvirus($answ['dir']);
                    if (is_dir($answ['dir'])) {
                        self::directoryremove($answ['dir']);
                    }

                    // для каждого файла с вирусами свой диалог
                    if ($virs) {
                        // информация о найденных вирусах
                        $keyarch = self::apc_viruskey($apckey, 'virarch');

                        $vrs = array();
                        foreach ($virs as $vvkey => $vvval) {
                            $vrs[] = array(
                                'fname' => $vvval['fname'],
                                'vname' => $vvval['nam'],
                                'type' => $vvval['typ'],
                                'hash' => $vkey
                            );
                        }

                        $vrarchinf = array(
                            'viruses' => $vrs,
                            'virusarch' => 1
                        );
                        $bl = apc_store($keyarch, $vrarchinf, 3600);

                        // переменная для обновления (нажал ли пользователь кнопку-подтверждение)
                        $keyarchacc = self::apc_viruskey($apckey, 'virarchacc');
                        $bl = apc_store($keyarchacc, 1, 60);
                        while (apc_exists($keyarchacc) && (time() - $timestart < $exectime)) {
                            sleep(1);
                        }
                        if (apc_exists($keyarchacc)) {
                            $bl = apc_delete($keyarchacc);
                        }

                        // что выбрал пользователь
                        $newarchkey = self::apc_viruskey($apckey, 'virarchchoose');
                        if (apc_exists($newarchkey)) {
                            $chooses = apc_fetch($newarchkey);
                            $choose = $chooses['choose'];

                            // отмена отправки
                            if ($choose == 2) {
                                $iscances = true;
                            } else {
                                // TODO каке из зашифрованных архивов отправлять
                                $hasharchaccepts = $chooses['fobj'];
                                if ($hasharchaccepts) {
                                    $hashaccepts = array_merge($hashaccepts, array_keys($hasharchaccepts));
                                }
                            }
                            $bl = apc_delete($newarchkey);
                        } else {
                            // ожидание выбора пользователя окончилось
                            $iscances = true;
                        }
                        $bl = apc_delete($keyarch);
                        if ($iscances)
                            return array(
                                'error' => ''
                            );
                    } else {
                        // вирусов нету - файл можно отправить
                        $hashaccepts[] = $vkey;
                    }
                } else {
                    if (is_dir($answ['dir'])) {
                        self::directoryremove($answ['dir']);
                    }
                }
            }
        }

        // инфо о паролях для зашифрованных архивов (только для новых файлов)
        foreach ($toadds as $key => $row) {
            if (isset($archivecorrectpwd[$row[5]['hash']]) && !isset($row[5]['id'])) {
                $toadds[$key][5]['pwd'] = $archivecorrectpwd[$row[5]['hash']]['pwd'];
            }
        }

        if (isset($params['layout'])) {
            $funcparams['toadds'] = $toadds;
            $funcparams['hashaccepts'] = $hashaccepts;
            $funcparams['apckey'] = $apckey;
            return $funcparams;
        }

        // с вирусами не добавлять в базу
        $fileids = $this->addfiles2($toadds, $hashaccepts);

        // лог о загрузке pua
        if ($fileids && $hashacceptslog) {
            foreach ($hashacceptslog as $hash) {
                $fdiskid = \db::val("SELECT id FROM filedisk WHERE hash='{$hash}'");
                if ($fdiskid) {
                    \Encoder\Usrlog::flushLog();
                    \Encoder\Usrlog::logadd(37, 'Загрузка', 'filedisk', $fdiskid);
                }
            }
        }
        return array(
            'fileids' => $fileids
        );
    }

    /**
     * генерирует произвольное имя файла и возвращает его дескриптор
     *
     * @param string $path
     * @param string $key
     */
    public static function fileGenerate($path, $key = '')
    {
        $cnttry = 50;
        while ($cnttry > 0) {
            $fpath = $path . "/" . $key . md5(microtime() . rand());
            $f = @fopen($fpath, 'xb');
            if ($f !== FALSE) {
                return array(
                    'path' => $fpath,
                    'handle' => $f
                );
            }
            $cnttry--;
        }
        return FALSE;
    }

    /**
     * сохраняет файл в базе и на диске
     *
     * @param string $fname
     *            файла
     * @param
     *            содержимое для записи в файл $content
     * @param $_POST (если файл
     *            большой, лучше лишнюю переменную не заводить, использовать $_POST[$postInd]) $postInd
     */
    function saveFile($fname, &$content, $chkvirus = false, $compress = false, $postInd = '')
    {
        // дата и размер файла
        $dt = date("Y-m-d");
        $siz = strlen($content);
        if ($postInd != '' && isset($_POST[$postInd])) {
            $siz = strlen($_POST[$postInd]);
        }

        // получить имя файла и расширение
        $finf = pathinfo($fname);
        $typ = "";
        if (isset($finf['extension']) && $finf['extension']) {
            $typ = $finf['extension'];
            preg_match("/^[a-zA-Z\d]+/", $typ, $mtch);
            if (!isset($mtch[0]))
                $typ = '';
            if ($typ != '')
                $fname = substr($fname, 0, strlen($fname) - strlen($typ) - 1);
        }
        $typ = strtolower($typ);

        // хеширование файла
        $fdinfo = $this->addfilehash("", $fname, $typ, "", $content, $postInd);
        $hash = $fdinfo['hash'];

        // нужна ли проверка на вирусы
        $isneedcheckvirus = false;
        if (!isset($fdinfo['virid'], $fdinfo['vir'])) {
            if (!$chkvirus) {
                $fdinfo['virid'] = 2;
                if (isset($fdinfo['vir']))
                    unset($fdinfo['vir']);
            } else if (!isset($fdinfo['virid'])) {
                $isneedcheckvirus = true;
            }
        }

        // нужно ли сжатие
        $isneedcompress = false;
        if (!isset($fdinfo['compress'])) {
            if ($compress == false) {
                $fdinfo['compress'] = 1;
            } else {
                $isneedcompress = true;
            }
        }

        // создание временного файла
        if ($isneedcheckvirus || $isneedcompress) {
            $upldir = mydef::$uploadpath;

            $flinfs = self::fileGenerate($upldir, m_auth::$id);
            if ($flinfs === FALSE)
                return FALSE;
            $fpathrand = $flinfs['path'];
            $f = $flinfs['handle'];
            if ($content)
                fwrite($f, $content);
            else if ($postInd != '' && isset($_POST[$postInd])) {
                fwrite($f, $_POST[$postInd]);
            }
            fclose($f);
            @chmod($fpathrand, 0660);
        }

        // проверяем на вирусы
        if ($isneedcheckvirus) {
            $vransw = $this->viruscheck($fpathrand);
            if ($vransw['code'] == 1) {
                $fdinfo['virnew'] = $vransw['virus'];
            } else if ($vransw['code'] == 0) {
                $fdinfo['virid'] = 2; // вирусов нету (id=2 в таблице vir)
            } else
                $fdinfo['virid'] = 1; // ошибка - не проверено
        }

        // сжатие
        $iscompressed = false;
        if ($isneedcompress) {
            $a = optglobget(array(
                "compressprocent",
                "compresssize"
            ));
            $packs = $this->addfilecompress($fpathrand, $fname, $typ, $a['compresssize'], $a['compressprocent'], "");
            // $packs = $this->addfilecompress($fpathrand, $fname, $typ, 0, 0, "");
            if ($packs['compress'] != NULL && !$packs['error']) {
                $fdinfo['compress'] = $packs['compress'];
                if ($packs['compress'] == 2) {
                    $fdinfo['siz'] = $packs['siz'];
                    $fdinfo['path'] = $packs['path'];
                    $iscompressed = true;
                }
            }
        }

        // $fdinfo = array(); //id,hash,vir,virnew,virid,compress
        // добавляем файл в БД
        $inf = $this->addfiletodb($fname, $typ, $dt, $siz, $fdinfo);
        $filediskid = $inf['filediskid'];
        $idfile = $inf['fileid'];

        $inf = array();
        if (!(isset($fdinfo['id']) && $fdinfo['id'])) {
            // добавить на диск
            if ($iscompressed) {
                if (is_file($fdinfo['path']))
                    $uploadfile = $this->adfiletodisk($filediskid, $dt, $fdinfo['path']);
                else {
                    \db::diedlog("Ошибка добавления файла saveFile", false, true, false);
                }
            } else
                $uploadfile = $this->adfiletodisk($filediskid, $dt, "", $content, $postInd);
        } else {
            $fddat = \db::val("SELECT dat FROM filedisk WHERE id = {$fdinfo['id']}");
            $uploadfile = $this->filepath($fdinfo['id'], $fddat);

            // если файл уже в базе, но его нету на диске - добавить его на диск
            $isflexist = is_file($uploadfile);
            if (!$isflexist) {
                // добавить на диск
                if ($iscompressed) {
                    if (is_file($fdinfo['path']))
                        $uploadfile = $this->adfiletodisk($filediskid, $dt, $fdinfo['path']);
                    else
                        \db::diedlog("Ошибка добавления файла saveFile2", false, true, false);
                } else
                    $uploadfile = $this->adfiletodisk($filediskid, $dt, "", $content, $postInd);
            }
        }

        // удалить временный файл
        if (isset($fpathrand) && is_file($fpathrand)) {
            @unlink($fpathrand);
        }
        return array(
            'idfile' => $idfile,
            'idfiledisk' => $filediskid,
            'idfilepath' => $uploadfile
        );
    }

    // удаляет файл и возвращает его размер в случае удаления
    public static function removeFile($fid, $fdatt, $isunlink = true)
    {
        $size = 0;
        $fldat = str_replace('-', '', $fdatt);
        $flpath = mydef::$filepath . '/' . $fldat . '/' . $fid;
        if (is_file($flpath)) {
            $size = filesize($flpath);
            if ($isunlink)
                unlink($flpath);
        }
        return $size;
    }

    public static function returnfile(&$file, $name)
    {
        $name = preg_replace('/[\\/\<\>\*\?":]/', ' ', $name);
        $name = preg_replace('/\s+^/', '', preg_replace('/\s+$/', '', preg_replace('/\s+/', ' ', $name)));

        Header('Content-Type: application/octet-stream');
        Header('Accept-Ranges: bytes');
        Header('Content-Length: ' . strlen($file));
        Header('Content-disposition: attachment; filename="' . iconv("CP1251", "UTF-8", $name) . '"');

        echo $file;
        exit();
    }

    /**
     * преобразуем время в секундах в строку для отображения
     *
     * @param timestamp $val
     */
    public static function return_alltime($val)
    {
        $val = intval(trim($val));
        $res = "";
        $min = 0;
        $hour = 0;
        $day = 0;
        $year = 0;
        $sec = $val % 60;
        $res = $sec . " сек";
        $val = $val - $sec;
        if ($val > 59) {
            // get minute count
            $val = round($val / 60);
            $min = $val % 60;
            $res = $min . "мин " . $res;
            $val = $val - $min;
            if ($val > 59) {
                // get hour count
                $val = round($val / 60);
                $hour = $val % 24;
                $res = $hour . "час " . $res;
                $val = $val - $hour;
                if ($val > 23) {
                    // get day count
                    $val = round($val / 24);
                    $day = $val % 365;

                    $val = $val - $day;
                    $res = $day . "дней " . $res;
                    if ($val > 364) {
                        $year = round($val / 365);
                        $res = $year . "лет " . $res;
                    }
                }
            }
        }
        return $res;
    }

    // по filediskid и дате файла получает его размер
    public static function fsize($fid, $fdat)
    {
        $fldat = str_replace('-', '', $fdat);
        $flpath = mydef::$filepath . '/' . $fldat . '/' . $fid;
        if (is_file($flpath))
            return filesize($flpath);
        return -1;
    }

    /**
     * сверка целостности на жёстком диске файлов с файлами в БД
     */
    public static function fileDiskChecker2($withreturn = false)
    {
        $errs = array();
        $dirs = array();
        $pt = mydef::$filepath;
        $dels = array(
            '.',
            '..'
        );
        $a = scandir($pt);
        $a = array_diff($a, $dels);
        $icnt = count($a);
        $ar = array();

        $res = array();
        $noexist = array();

        foreach ($a as $i => $val) {
            if (strlen($val) == 8) {

                $fls = scandir($pt . '/' . $val);
                $fls = array_diff($fls, $dels);

                $ffids = array();
                $ids = "";
                foreach ($fls as $j => $flid) {
                    if (strpos($flid, '.') !== FALSE) {
                        $errs[$flid] = $val;
                    } else {
                        $flid = intval($flid);
                        $ids = ($ids == "") ? intval($flid) : $ids . ',' . $flid;
                        $ffids[] = $flid;
                    }
                }

                // ID файлов которые есть в БД
                if ($ids != '') {
                    $idsexist = \db::col("SELECT id FROM filedisk WHERE id IN ($ids) AND dat = '$val'");
                    $idsnoexist = array_diff($ffids, $idsexist);
                    foreach ($idsnoexist as $fdiskid) {
                        if (!isset($noexist[$fdiskid]))
                            $noexist[$fdiskid] = array();
                        $noexist[$fdiskid][] = $val;
                    }
                } else {
                    array_push($dirs, $pt . '/' . $val);
                }
            }
        }

        if ($withreturn) {
            return array(
                'noexist' => $noexist,
                'errs' => $errs,
                'emptydirs' => $dirs
            );
        }

        foreach ($noexist as $fdiskid => $datts) {
            foreach ($datts as $dat) {
                $path = $pt . '/' . $dat . '/' . $fdiskid;
                $res[] = "<p>В БД отсутствует запись о файле: $path
				<a target='wndfiledelete' href='?integrityfldel=$fdiskid&integrityfldeldat=$dat'>Удалить файл</a></p>";
            }
        }

        foreach ($errs as $filediskid => $dat) {
            $path = $pt . '/' . $dat . '/' . $filediskid;
            $res[] = "<p>В БД отсутствует запись о файле: $path
			<a target='wndfiledelete' href='?integrityfldel=$filediskid&integrityfldeldat=$dat'>Удалить файл</a></p>";
        }

        foreach ($dirs as $i => $val) {
            $res[] = "<p>Пустая папка: $val
			<a target='wndfiledelete' href='?integrityfldel=dir&integrityfldeldat=$val'>Удалить папку</a></p>";
        }

        if ($res) {
            $res[] = "<p>Удалить все:
			<a target='wndfiledelete' href='?integrityfldel=all&integrityfldeldat=all'>Удалить пустые папки и файлы, отсутствующие в БД</a></p>";
        }

        return $res;
    }
}