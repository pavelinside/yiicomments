<?php
namespace app\modules\online\services;

use app\modules\online\helpers\DbService;

class UsrlogService
{
    private static
        $lastLogId=0,
        $lastLogIdEntityId=0;

    /**
     * 	$val - value,
     *  $entityid - table to make changes
     *  $entityidid - field to make changes
     *  $toLast - if you want to add multiple records in a journal under the same id
     */
    public static function logadd($typ=false, $val=false, $entityid=false, $entityidid=false, $toLast=true, $escape = true){
        switch($typ){
            case 'remove':
            case 'delete':
            case 'r': // removed
            case 'd': // deleted
                $typ = 12;
                break;

            case 'a': // added
            case 'add':
                $typ = 13;
                break;

            case 'change':
            case 'save':
            case 'c': // changed
            case 's': // saved
                $typ = 14;
                break;

            case 'pr':
            case 'pd':
                $typ = 20;
                break;

            case 'pa':
            case 'padd':
            case 'propadd':
                $typ = 21;
                break;

            case 'pc':
            case 'ps':
                $typ = 22;
                break;
        }
        if(is_array($val))
            $val = implode('; ', $val);

        $uid = \Encoder\Opt::$id ? \Encoder\Opt::$id : 'NULL';
        if($entityid and !is_int($entityid)){
            $qry = "SELECT id FROM entity WHERE tablenam='$entityid'";
            $entityid = DbService::val($qry);
        }

        if($toLast and self::$lastLogId and self::$lastLogIdEntityId==$entityidid)
            $id = self::$lastLogId;
        else {
            $eid = $entityid ? $entityid : 'NULL';
            $eidid = $entityidid ? $entityidid : 'NULL';
            $qry= "INSERT INTO usrlog(usrid, datt, entityid, entityidid)
				VALUES($uid, CURRENT_TIMESTAMP, $eid, $eidid)";
            $id = DbService::query($qry, 1);
        }
        self::$lastLogIdEntityId = $entityidid;
        self::$lastLogId = $id;

        if($id and ($typ or $val)){
            if(!$typ)
                $typ = 'NULL';

            if(!$val)
                $val_id = 'NULL';
            else {
                if($escape)
                    $val = DbService::escape_string($val);
                $qry = "INSERT INTO usrlogpropval (val) VALUES ('$val')
					ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
                $val_id = DbService::query($qry, 1);
            }
            $qry = "INSERT INTO usrlogprop(usrlogid, usrlogproptypid, usrlogpropvalid)
				VALUES($id, $typ, $val_id)";
            DbService::query($qry);
        }
    }

    public static function flushLog(){
        self::$lastLogIdEntityId = self::$lastLogId = false;
    }
}