<?php

use app\modules\online\helpers\FileTool as Tools;

class FileUpload
{

    /**
     * process downloaded files
     * @param string $fileindex for form input[type='file']
     * @param array $params
     * @return array
     */
    public function upload($fileindex = 'userfile', array $params = [])
    {
        $this->uploadCheckError($fileindex);

        $uploadDirectory = ini_get('upload_tmp_dir');

        // TODO rename files for users

        $date = date('Y-m-d');
        $timestart = time();
        $exectime = ini_get('max_execution_time') - 30;

        //UPLOAD_ERR_FORM_SIZE

        return ['fileids' => []];
    }

    /**
     * �������� ���� hash;
     * ���� ���� ���� � ��, �� �������� id filediskid;
     * ���� ���� ���������� �� ������, �� �������� virid;
     * ���� � ����� �����, �� �������� vir - ��� ������.
     *
     * @param string $filepath
     * @param string $fname
     * @param string $ftyp
     * @param string $apckey
     * @param string $content ���������� �����
     * @param string $postInd ���� ���� ���������� � $_POST[$postInd])
     * @return array (hash, id-filediskid, virid, vir - ��� ������) hash - �����������
     */
    public function addfilehash(
        $filepath,
        $fname,
        $ftyp,
        $apckey = '',
        &$content = '',
        $postInd = '') {
        $fullnam = $ftyp != '' ? $fname . '.' . $ftyp : $fname;
        if ($apckey) {
            $keyprocess = self::apc_viruskey($apckey, 'process');
            $bl = apcu_store($keyprocess, array('filehash' => $fullnam), 3600);
            //show('store hash', $keyprocess, $bl);
            //sleep(1);
        }
        $hash = '';
        $fsize = -1;
        if ($filepath) {
            $hash = md5_file($filepath);
            $fsize = filesize($filepath);
        } elseif ($content) {
            $hash = md5($content);
            $fsize = strlen($content);
        } elseif ($postInd != '' && isset($_POST[$postInd])) {
            $hash = md5($_POST[$postInd]);
            $fsize = strlen($_POST[$postInd]);
        }
        $fdinfo = array('hash' => $hash);
        $arrs = \File\Model\db::arr(
            "SELECT fd.id, fd.dat, fd.virid, v.nam, fd.compress, fd.sizoryg
                        FROM filedisk fd LEFT JOIN vir v ON v.id = fd.virid
                        WHERE fd.hash = '$hash'");
        $siz = -1;
        foreach ($arrs as $row) {
            //$flsiz = $this->fsize($row['id'], $row['dat']);
            //if($flsiz < 0)
            //continue;
            $flsiz = $row['sizoryg'];
            $isadd = false;
            if ($row['virid'] == 2 && ! isset($fdinfo['vir'])) {
                // ���������� - ������� ���
                $isadd = true;
                $fdinfo['id'] = $row['id'];
                $fdinfo['virid'] = $row['virid'];
                $siz = $flsiz;
            } elseif ($row['virid'] > 2) {
                // ���������� - ������ ����
                $isadd = true;
                $fdinfo['id'] = $row['id'];
                $fdinfo['virid'] = $row['virid'];
                $fdinfo['vir'] = $row['nam'];
                $siz = $flsiz;
            } elseif (!isset($fdinfo['virid'])) {
                // �� ����������
                $isadd = true;
                $fdinfo['id'] = $row['id'];
                $siz = $flsiz;
            }
            // ���� � ������
            if ($isadd && $row['compress'] != '') {
                $fdinfo['compress'] = $row['compress'];
                if ($row['compress'] == 2)
                    $fdinfo['siz'] = $row['sizoryg'];
                $siz = $flsiz;
            }
        }
        if (isset($fdinfo['id']))
            if ($siz != -1 && $fsize != -1 && $siz != $fsize) {
                $fdinfo['sizeerror'] = array(
                    'sizecurrent' => $siz,
                    'size' => $siz);
            }
        if ($apckey) {
            $bl = apcu_delete($keyprocess);
            //show('delete hash', $keyprocess, $bl);
        }

        return $fdinfo;
    }

    /**
     * ��������� ����� � ���� � � ����� $arrFiles - ������ ������.
     *
     * @param string $fileindex
     * @param string $apckey
     * @param array $params
     */
    function addfiles($fileindex = 'userfile', $apckey = '', array $params = [])
    {


        $arrFiles = $_FILES[$fileindex];
        $date = date('Y-m-d');
        $sdate = str_replace('-', '', date('Y-m-d'));
        #������ ���������� �� 30 ���. ������ max_execution_time
        $timestart = time();
        $exectime = ini_get('max_execution_time') - 30;
        $uploadDirectory = ini_get('upload_tmp_dir');

        // rename files
        $torenames = array_key_exists('torenames', $params) ? $params['torenames'] : [];
        if (isset($arrFiles['name']))
            foreach ($torenames as $torename)
                if (is_array($torename) && count($torename) === 3) {
                    $name = $torename[0];
                    $size = $torename[1];
                    $newname = $torename[2];
                    foreach ($arrFiles['name'] as $ind => $vval)
                        if ($vval == $name && $arrFiles['size'][$ind] == $size)
                            $arrFiles['name'][$ind] = $newname;
                }

        $funcparams = [];
        $isvirus = false;
        $viruses = [];
        $toadds = [];
        $iscances = false; // ������������ ����� �������� ��������
        for ($i = 0, $icnt = count($arrFiles['name']); $i < $icnt; $i++) {
            if (!isset($arrFiles['name'][$i])
                || file_exists($_FILES[$fileindex]['tmp_name'][$i]) === false
                || is_uploaded_file($_FILES[$fileindex]['tmp_name'][$i]) === false
            ) {
                continue;
            }

            $actionlog = array('file: ' . $_FILES[$fileindex]['tmp_name'][$i]);

            $fileName = \File\Model\coder::coding($arrFiles['name'][$i], true, 1);
            $typ = Tools::getFileExtension($fileName);
            if($typ != ''){
                $fileName = substr($fileName, 0, strlen($fileName) - strlen($typ) - 1);
            }
            $siz = $arrFiles['size'][$i];

            // ����������� �����
            $fdinfo = $this->addfilehash($arrFiles['tmp_name'][$i], $fileName, $typ, $apckey);
            $hash = $fdinfo['hash'];
            $actionlog [] = ";hash= $hash";
            // ���� ���� ���� � ����� hash,
            // �� ������ �������� -
            // �� ������� ���� � �������� ������ �����������
            if (isset($fdinfo['sizeerror'])) {
                \File\Model\m_usrlog::logadd(
                    35,
                    "������ ����������� �����: $fileName",
                    'filedisk',
                    $fdinfo['id']);
                return array('error' => '������ �����������');
            }

            // �������� �� ������
            $virusinfo = [];
            if (isset($fdinfo['virid'], $fdinfo['vir'])) {
                // ���� ��� ���� ���������� �� ������
                $fullnam = $typ != '' ? $fileName . '.' . $typ : $fileName;
                $virtyp = $this->virustype($fdinfo['vir']);
                $virusinfo = array(
                    'fname' => $fullnam,
                    'vname' => $fdinfo['vir'],
                    'type' => $virtyp,
                    'hash' => $hash);
                $isvirus = true;
            } elseif (!isset($fdinfo['virid'])) {
                $actionlog[] = ";virusbefore=" .
                    file_exists($arrFiles['tmp_name'][$i]);
                $virusinfo = $this->addfilevirus(
                    $arrFiles['tmp_name'][$i], $fileName, $typ, $apckey);
                if (!$virusinfo)
                    $fdinfo['virid'] = 2; // ������� ��� (id=2 � ������� vir)
                elseif (isset($virusinfo['error']))
                    $fdinfo['virid'] = 1; // ������ �������� �� ������
                $actionlog[] = ";virusafter=" .
                    file_exists($arrFiles['tmp_name'][$i]);
            }

            if ($virusinfo) {
                $virusinfo['hash'] = $hash;
                $viruses[] = $virusinfo;
                $isvirus = true;
            }

            // ������ (compress: NULL- �� ���������; 1-�� ����; 2-����)
            if (!isset($fdinfo['compress'])) {
                if ((isset($params['layout']) && $params['layout'] == 52)
                    || (isset($params['nocompress']) && $params['nocompress'])
                ) {
                    #tiff �� ������� � �������
                    $fdinfo['compress'] = 1;
                } else {
                    $actionlog[] = ";compressbefore=" .
                        file_exists($arrFiles['tmp_name'][$i]);
                    #��� ������, ��� ���������� ����� $arrFiles['tmp_name'][$i]
                    #�� ��������� ???
                    if (!file_exists($arrFiles['tmp_name'][$i])) {
                        \File\Model\m_lgr::onlog(
                            '������ ����� ����� ' . implode(
                                '', $actionlog),
                            ['log' => 1]);
                        continue;
                    }
                    $a = \File\Model\m_optglob::get(
                        array('compressprocent', 'compresssize'));
                    $packs = $this->addfilecompress(
                        $arrFiles['tmp_name'][$i],
                        $fileName,
                        $typ,
                        $a['compresssize'],
                        $a['compressprocent'],
                        $apckey);
                    //$packs = $this->addfilecompress(
                    //      $arrFiles['tmp_name'][$i],
                    //      $fname,
                    //      $typ,
                    //      0,
                    //      0,
                    //      $apckey);
                    $actionlog[] = ';compressafter=' .
                        file_exists($arrFiles['tmp_name'][$i]);
                    // ��� ������,
                    // ��� ���������� ����� $arrFiles['tmp_name'][$i]
                    // �� ��������� ???
                    if (!is_file($arrFiles['tmp_name'][$i])) {
                        \File\Model\m_lgr::onlog(
                            '������ ����� ����� ' . implode(
                                '', $actionlog),
                            ['log' => 1]);
                        continue;
                    }
                    if ($packs['compress'] !== NULL && !$packs['error']) {
                        $fdinfo['compress'] = $packs['compress'];
                        if ($packs['compress'] == 2) {
                            $fdinfo['siz'] = $packs['siz'];
                            $fdinfo['path'] = $packs['path'];
                        }
                    }
                }
            } else if (
                (isset($params['layout']) && $params['layout'] != 62 &&
                    in_array($typ, array('tif', 'tiff')) && $fdinfo['compress'] == 2) ||
                (isset($params['edn']) &&
                    in_array($typ, array('pdf')) && $fdinfo['compress'] == 2)
            ) {
                // ��� ���������� tiff � ���������� ��������� ������ tiff
                // ��� ���������� ������� pdf � ¸����� ��������� ���

                $fdinfo['compress'] = $this->unpack($fdinfo['id']);
                if ($fdinfo['compress'] == 1) {
                    if (isset($fdinfo['siz']))
                        unset($fdinfo['siz']);
                }
            }

            $toadds[] = array(
                $fileName,
                $typ,
                $date,
                $siz,
                $arrFiles['tmp_name'][$i],
                $fdinfo,
                $virusinfo);

            if (isset($params['layout'])) {
                #������ �� ����������
                if ($isvirus && in_array($virusinfo['type'], array(1, 2)))
                    break;
                $funcparams['ftyp'] = $typ;
                $funcparams['fname'] = $fileName;
                $funcparams['fsize'] = $siz;
                $funcparams['uplfid'] = $i;
                $funcparams['fileindex'] = $fileindex;
                break;
            }
        }

        // ������� ������: �������� ��� ��� ������
        $this->apc_clean();

        // ������ �������� �� ������
        $hashacceptslog = [];
        $hashaccepts = [];
        $chooses = [];
        $archivecorrectpwd = [];
        if ($isvirus) {
            $key = self::apc_viruskey($apckey, 'vir');
            $vrinf = array('viruschk' => 1, 'viruses' => $viruses);
            $bl = apcu_store($key, $vrinf, 3600);

            // ��� ������ ������������
            $newkey = self::apc_viruskey($apckey, 'virchoose');

            // ������������ ������
            $pwderrkey = self::apc_viruskey($apckey, 'pwderr');

            // ���������� ��� ����������
            // (����� �� ������������ ������-�������������)
            $keyacc = self::apc_viruskey($apckey, 'viracc');
            $bl = apcu_store($keyacc, 1, 60);
            while (apcu_exists($keyacc) && (time() - $timestart < $exectime)) {
                if (!apcu_exists($newkey)) {
                    sleep(1);
                    continue;
                }

                // ������ � �������
                $chooses = apcu_fetch($newkey);
                if ($chooses['choose'] == 2) {
                    sleep(1);
                    continue;
                }

                // ���������� ������ � �������
                $archivecorrectpwd = [];
                $isarchivpwd = false;

                // �������� ������������ �������
                $archiveserrpwd = [];
                foreach ($chooses['fobj'] as $uk => $uv)
                    if (strpos($uk, 'pwd_') === 0 && $uv) {
                        $archivehash = str_replace('pwd_', '', $uk);
                        $archivepwd = $uv;
                        // ���� ����
                        foreach ($toadds as $fkey => $frow)
                            if ($frow[5]['hash'] == $archivehash) {
                                $isarchivpwd = true;
                                $archivepath = $frow[4];
                                if (!$this->virusencryptcheckpwd(
                                    $archivepath,
                                    $archivepwd,
                                    $frow[6]['vname'])
                                )
                                    $archiveserrpwd[$archivehash] = 1;
                                else {
                                    $fileName = $frow[0];
                                    if ($frow[1])
                                        $fileName .= ".{$frow[1]}";
                                    $archivecorrectpwd[$archivehash] = array(
                                        'path' => $archivepath,
                                        'pwd' => $archivepwd,
                                        'fname' => $fileName,
                                        'virnam' => $frow[6]['vname']);
                                }
                            }
                    }

                // �� ����� ��� �������� ������
                if ($archiveserrpwd)
                    foreach ($vrinf['viruses'] as $vkey => $vval)
                        if (isset($archiveserrpwd[$vval['hash']]))
                            if (!isset($vval['pwdcnt']))
                                $vrinf['viruses'][$vkey]['pwdcnt'] = 1;
                            elseif ($vval['pwdcnt'] == 3)
                                unset($archiveserrpwd[$vval['hash']]);
                            else {
                                $vrinf['viruses'][$vkey]['pwdcnt'] += 1;
                                if ($vval['pwdcnt'] == 2) {
                                    // log
                                    $fdiskid = \File\Model\db::val(
                                        "SELECT id
                                            FROM filedisk
                                            WHERE hash = '{$vval['hash']}'");
                                    if (!$fdiskid)
                                        $fdiskid = false;
                                    \File\Model\m_usrlog::flushLog();
                                    \File\Model\m_usrlog::logadd(
                                        36,
                                        '������ ��������',
                                        'filedisk',
                                        $fdiskid);
                                }
                            }

                if ($archiveserrpwd) {
                    // �������� ������ - �������� ������������
                    $bl = apcu_store($key, $vrinf, 3600);
                    // ������� ���� $newkey
                    if (apcu_exists($newkey))
                        $bl = apcu_delete($newkey);
                } elseif ($isarchivpwd)
                    break;
                sleep(1);
            }
            if (apcu_exists($keyacc))
                $bl = apcu_delete($keyacc);
            if (apcu_exists($newkey)) {
                $chooses = apcu_fetch($newkey);
                $choose = $chooses['choose'];
                // ������ ��������
                if ($choose == 2)
                    $iscances = true;
                else {
                    $hashaccepts = array_keys($chooses['fobj']);
                    foreach ($hashaccepts as $ha)
                        if (strpos($ha, 'pwd_') === false)
                            $hashacceptslog[] = $ha;
                }
                $bl = apcu_delete($newkey);
            } else
                $iscances = true; // �������� ������ ������������ ����������
            $bl = apcu_delete($key);

            // ������ ��������
            if ($iscances)
                return array('error' => '');

            // ����������� ������ � ��������
            foreach ($archivecorrectpwd as $vkey => $vval) {
                // ����������� �����, ��������� ������
                $answ = $this->virusencryptunrar(
                    $vval['path'], $vval['pwd'], $vkey, $vval['virnam']);
                if ($answ['code']) {
                    $virs = $this->virusencryptcheckvirus($answ['dir']);
                    if (is_dir($answ['dir']))
                        self::directoryremove($answ['dir']);
                    // ��� ������� ����� � �������� ���� ������
                    if ($virs) {
                        // ���������� � ��������� �������
                        $keyarch = self::apc_viruskey($apckey, 'virarch');
                        $vrs = [];
                        foreach ($virs as $vvkey => $vvval)
                            $vrs[] = array(
                                'fname' => $vvval['fname'],
                                'vname' => $vvval['nam'],
                                'type' => $vvval['typ'],
                                'hash' => $vkey);
                        $vrarchinf = array(
                            'viruses' => $vrs,
                            'virusarch' => 1);
                        $bl = apcu_store($keyarch, $vrarchinf, 3600);
                        // ���������� ��� ����������
                        // (����� �� ������������ ������-�������������)
                        $keyarchacc = self::apc_viruskey(
                            $apckey, 'virarchacc');
                        $bl = apcu_store($keyarchacc, 1, 60);
                        while (
                            apcu_exists($keyarchacc) &&
                            (time() - $timestart < $exectime))
                            sleep(1);
                        if (apcu_exists($keyarchacc))
                            $bl = apcu_delete($keyarchacc);
                        // ��� ������ ������������
                        $newarchkey = self::apc_viruskey(
                            $apckey, 'virarchchoose');
                        if (apcu_exists($newarchkey)) {
                            $chooses = apcu_fetch($newarchkey);
                            $choose = $chooses['choose'];
                            // ������ ��������
                            if ($choose == 2)
                                $iscances = true;
                            else {
                                // @todo �����������,
                                // ����� �� ������������� ������� ����������
                                $hasharchaccepts = $chooses['fobj'];
                                if ($hasharchaccepts)
                                    $hashaccepts = array_merge(
                                        $hashaccepts,
                                        array_keys($hasharchaccepts));
                            }
                            $bl = apcu_delete($newarchkey);
                        } else
                            // �������� ������ ������������ ����������
                            $iscances = true;
                        $bl = apcu_delete($keyarch);
                        if ($iscances)
                            return array('error' => '');
                    } else
                        // ������� ��� - ���� ����� ���������
                        $hashaccepts[] = $vkey;
                } elseif (is_dir($answ['dir']))
                    self::directoryremove($answ['dir']);
            }
        }

        // ���� � ������� ��� ������������� ������� (������ ��� ����� ������)
        foreach ($toadds as $key => $row)
            if (
                isset($archivecorrectpwd[$row[5]['hash']]) &&
                !isset($row[5]['id'])
            )
                $toadds[$key][5]['pwd'] =
                    $archivecorrectpwd[$row[5]['hash']]['pwd'];
        if (isset($params['layout'])) {
            $funcparams['toadds'] = $toadds;
            $funcparams['hashaccepts'] = $hashaccepts;
            $funcparams['apckey'] = $apckey;

            return $funcparams;
        }

        // � �������� �� ��������� � ����
        $fileids = $this->addfiles2($toadds, $hashaccepts);

        // ��� � �������� pua
        if ($fileids && $hashacceptslog)
            foreach ($hashacceptslog as $hash) {
                $fdiskid = \File\Model\db::val(
                    "SELECT id FROM filedisk WHERE hash='{$hash}'");
                if ($fdiskid) {
                    \File\Model\m_usrlog::flushLog();
                    \File\Model\m_usrlog::logadd(37, '��������', 'filedisk', $fdiskid);
                }
            }

        return array('fileids' => $fileids);
    }

    private function uploadCheckError($fileindex = 'userfile')
    {
        if (!$fileindex) {
            throw new \File\Model\RuntimeException("Files with index $fileindex are not found");
        }
        if (!isset($_FILES[$fileindex])) {
            throw new \File\Model\RuntimeException("Files with index $fileindex are not found");
        }

        $uploadDirectory = ini_get('upload_tmp_dir');
        if (!is_dir($uploadDirectory)) {
            throw new \File\Model\RuntimeException("upload dir $uploadDirectory are not found");
        }

        // Undefined | Multiple Files | $_FILES Corruption Attack
        // TODO process errors array
        if (!isset($_FILES[$fileindex]['error']) || is_array($_FILES[$fileindex]['error'])) {
            throw new \File\Model\RuntimeException('Invalid parameters.');
        }
        //  php_value post_max_size, php_value upload_max_filesize
        switch ($_FILES[$fileindex]['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new \File\Model\RuntimeException('No file sent.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new \File\Model\RuntimeException('Exceeded filesize limit.');
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new \File\Model\RuntimeException('Upload directory not exist.');
            case UPLOAD_ERR_CANT_WRITE:
                throw new \File\Model\RuntimeException('Error write file to disk.');
            case UPLOAD_ERR_PARTIAL:
                throw new \File\Model\RuntimeException('Error partial write file to disk.');
            default:
                throw new \File\Model\RuntimeException('Unknown errors.');
        }
        // You should also check filesize here.
        // TODO limit max size
        if ($_FILES['upfile']['size'] > 1000000) {
            throw new \File\Model\RuntimeException('Exceeded filesize limit.');
        }
    }

    public function display_filesize($filesize)
    {
        if (!is_numeric($filesize)) {
            return 'NaN';
        }
        $decr = 1024;
        $step = 0;
        $prefix = array('Byte', 'KB', 'MB', 'GB', 'TB', 'PB');

        while (($filesize / $decr) > 0.9) {
            $filesize = $filesize / $decr;
            $step++;
        }
        return round($filesize, 2) . ' ' . $prefix[$step];
    }
}