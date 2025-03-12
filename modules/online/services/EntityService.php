<?php
namespace app\modules\online\services;

class EntityService {
    /**
     * @var array
     */
    public static $indirectLinks = [
        'opt'       => ['table' => 'usr_opt', 'val' => 'val'],
        'usrevttyp' => ['table' => 'usrevt', 'val' => 'entityidid']
    ];

    /**
     * add table to table control
     * @param string $tablenam
     * @param string $nam
     * @param string $descr
     * @return int
     */
    public static function add($tablenam, $nam, $descr = ''){
        $qry = "INSERT IGNORE INTO entity(tablenam, nam, descr) VALUES('$tablenam', '$nam', '$descr')";
        return \db::query($qry, true);
    }

    /**
     * check tables in database with tables in table entity
     * @return array of errors
     */
    public static function checkExistTables(){
        $answ = [];

        // tables in db
        $ar = \db::tables(\dbdef::DBNAME);
        $tablenams = $ar['tablenams'];

        // tables in entity
        $qry = 'SELECT tablenam FROM entity WHERE id>2';
        $entTables = \db::col($qry);

        $absence = array_diff($entTables, $tablenams);
        if($absence){
            $answ[] = 'In database do not exist tables: '.implode(', ', $absence);
        }

        $absence = array_diff($tablenams, $entTables);
        if($absence){
            $answ[] = 'In table entity do not exist tables: '.implode(', ', $absence);
        }

        return $answ;
    }

    /**
     * get entityid from table tablenam
     * @param string $tablenam
     * @return int
     */
    public static function getentityid($tablenam){
        $qry = "SELECT id FROM entity WHERE tablenam = '$tablenam'";
        return \db::query($qry, true);
    }

    /**
     * check id exist in $tablenam
     * @param string $tablekeyval value of index field
     * @param string $tablenam
     * @param string $tablekey table index field
     * @param string $tablefields fields that need get
     * @return string|boolean
     */
    public static function entityVal2($tablekeyval, $tablenam, $tablekey = 'id', $tablefields = ''){
        // if tablenam is entityid - get tablenam from table entity
        $tid = (int)$tablenam;
        if($tid){
            $tablenam = \db::val("SELECT tablenam FROM entity WHERE id=$tablenam");
        }
        if(!$tablenam){
            return '';
        }
        if(!$tablefields){
            $tablefields = $tablekey;
        }
        $qry = "SELECT $tablefields FROM $tablenam WHERE $tablekey='$tablekeyval' LIMIT 1";
        return \db::row($qry);
    }

    /**
     * получение значения поля для entity
     * @param integer $externalid
     * @param string $tbl
     * @param string $fldNam
     */
    public static function entityVal($externalid, $tbl, $fldNam='id'){
        $tid = intval($tbl);
        if($tid)
            $tbl = \db::val("SELECT tablenam FROM entity WHERE id=$tbl");

        switch ($tbl){
            case 'tel':
                return m_tel::cuteView($externalid, true, true);
            case 'contact':
                $ar = \db::select(m_contact::selectQry("WHERE c.id=$externalid"));
                return $ar ? current($ar) : "";
        }
        $qry = "SELECT $fldNam FROM $tbl WHERE $fldNam='$externalid' LIMIT 1";
        return \db::val($qry);
    }

    /**
     * check id exist if entytyid in $typtable
     * @param integer $typid
     * @param string $typtable
     * @param string $tablekeyval value of index field
     * @param string $tablekey table index field
     * @param string $tablefields fields that need get
     * @return Array
     */
    public static function externalEntityVal($typid, $typtable, $tablekeyval, $tablekey = 'id', $tablefields = ''){
        $entityid = $typtable ? \db::val("SELECT entityid FROM $typtable WHERE id = $typid") : $typid;
        return self::entityVal($tablekeyval, $entityid, $tablekey, $tablefields);
    }

    /**
     * query to create table
     * @return string
     */
    private static $sql = 'CREATE TABLE IF NOT EXISTS `entity2` (
		`id` int(10) unsigned NOT NULL PRIMARY KEY,
		`tablenam` char(30) CHARACTER SET cp1251 UNIQUE KEY NOT NULL DEFAULT "",
		`nam` char(30) CHARACTER SET cp1251 UNIQUE KEY NOT NULL DEFAULT "",
		`descr` char(255) CHARACTER SET cp1251 NOT NULL DEFAULT ""
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT="List with all tables"';

    /**
     * check id exist if entytyid in $typtable
     * @param integer $externalid
     * @param integer $typid
     * @param string $typtable
     * @param string $fldNam
     */
    public static function checkExistExternalId($externalid, $typid, $typtable, $fldNam = "id"){
        $entityid = $typtable ? \db::val("SELECT entityid FROM $typtable WHERE id = $typid") : $typid;
        return self::checkExistId($externalid, $entityid, $fldNam);
    }

    /**
     *sk Метод проверяет существования записи в таблице по id
     * @param integer $id
     * @param string $tbl
     * @param string $fldNam
     */
    public static function checkExistId($externalid, $tbl, $fldNam='id'){
        $tid = intval($tbl);
        if($tid)
            $tbl = \db::val("SELECT tablenam FROM entity WHERE id=$tbl");
        if(!$tbl)
            return '';
        $qry = "SELECT $fldNam FROM $tbl WHERE $fldNam='$externalid' LIMIT 1";
        return !!\db::val($qry);
    }
}