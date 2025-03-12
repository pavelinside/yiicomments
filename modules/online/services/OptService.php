<?php

namespace app\modules\online\services;

use app\modules\online\helpers\ConvertService;
use app\modules\online\helpers\DbService;
use app\modules\online\services\UsrlogService;

class OptService
{
    public static int $id=0;						// id current user
    public static array $row = [];
    public static string $loginview='';
    private static array $_optCache = Array();

    /**
     * receiving user settings with caching
     * @param string|array $optnams
     * @param int $id
     * @return array|string
     */
    public static function getopts($optnams, $id= 0){
        $optnams = ConvertService::makeArray($optnams);
        $returnsingle= (count($optnams) == 1);
        $id = $id ?: self::$id;

        // If the option is not requested by itself, it does not cache
        if($id!=self::$id){
            $answ = self::_getopts($optnams, $id);
        }else{
            $cached = [];
            // select the cached option
            foreach ($optnams as $i=>$optNam){
                $optNam = $optnams[$i] = strtolower($optNam);
                if(isset(self::$_optCache[$optNam]))
                    $cached[$optNam] = self::$_optCache[$optNam];
            }

            // if not all there in the cache, gets
            $reqOpts = array_diff($optnams, array_keys($cached));
            if($reqOpts){
                $opts = self::_getopts($reqOpts, self::$id);
                foreach ($opts as $optNam=>$optVal)
                    self::$_optCache[$optNam] = $optVal;
            }

            $answ = [];
            foreach ($optnams as $optNam)
                if(isset(self::$_optCache[$optNam]))
                    $answ[$optNam] = self::$_optCache[$optNam];
        }

        return $returnsingle ? array_shift($answ) : $answ;
    }

    /**
     * receiving user settings
     * @param array $optnams
     * @param int $id
     */
    private static function _getopts($optnams, $id){
        $isroot = DbService::val("SELECT isroot FROM usr WHERE id=$id");
        $reqnams= "'" . implode("','", $optnams) . "'";

        $qry= "SELECT o.nam, COALESCE(uo.val, o.def) val, o.entityid, o.opttyp
		FROM opt o
		LEFT JOIN usr_opt uo ON o.opttyp='usr' AND uo.optid=o.id AND usrid='$id'
		WHERE o.nam IN($reqnams)";

        $arr= DbService::arr($qry);
        $answ= Array();

        foreach($arr as $i => $r){
            $nam= $r['nam'];
            if($r['opttyp'] == 'usr'){
                if($r['entityid'] > 2){
                    if(!isset($answ[$nam]))
                        $answ[$nam]= Array();
                    if(!is_null($r['val']) and !in_array($r['val'], $answ[$nam]))
                        $answ[$nam][]= $r['val'];
                }else
                    $answ[$nam]= ($isroot and self::$id == $id and $r['entityid'] == 2) ? 1 : $r['val'];
            }
        }
        return $answ;
    }

    // installation options to the user if the settings of type 1 or 2, then they replace / remove them prior
    public static function setopts($arrnam, $arrvals, $log = false, $id = 0){
        $id = $id ?: self::$id;

        // if the request is in the form of one value (not array) do single-cell array
        $arrnam = ConvertService::makeArray($arrnam);
        if( !is_array($arrvals) )
            $arrvals = Array( $arrvals );
        $returnsingle = (count($arrnam) == 1);
        if(count($arrnam) != count($arrvals)){
            throw new \Exception('setopts error in count parameters: usrID: ' . $id . ' snams: ' . print_r($arrnam, true) . ' svals:' . print_r($arrvals, true));
        }

        // select the current values and default values
        $qry= "SELECT * FROM opt WHERE nam IN ('" . implode("','", $arrnam) . "')";
        $arr= DbService::arr($qry);
        if(count($arr) != count($arrnam)){
            $arrDiff= Array();
            foreach($arrnam as $i => $sarr)
                $arrDiff[]= $sarr;
            $arrDiff= array_diff($arrnam, $arrDiff);
            throw new \Exception($qry . "\r\n" . 'Not all installed options is in the table. Not found: ' . implode(", ", $arrDiff));
        }

        // an associative array of values established
        $assocvals= Array();
        for($i= 0; $i < count($arrnam); $i++)
            $assocvals[strtolower($arrnam[$i])]= $arrvals[$i];

        if($log)
            UsrlogService::flushLog();

        $cnt= 0;

        for($i= 0; $i < count($arr); $i++){
            $rnam= strtolower($arr[$i]['nam']);
            $val= DbService::escape_string($assocvals[$rnam]);
            $optid= $arr[$i]['id'];

            // reset the cache on the key
            if($id==self::$id)
                unset(self::$_optCache[$rnam]);

            $oTyp= $arr[$i]['opttyp'];
            $valTable= $oTyp . '_opt';

            $deleted= $added= 0;
            // if the set value is not equal to the default
            if($arr[$i]['def'] == $val or $arr[$i]['entityid'] < 3){
                $delqry= "DELETE FROM $valTable WHERE " . $oTyp . "id=$id AND optid=$optid AND val <> '$val'";
                if(DbService::query($delqry)){
                    $deleted= 1;
                    $cnt++;
                }
            }

            if($arr[$i]['def'] != $val){
                $qry= "INSERT IGNORE INTO $valTable(" . $oTyp . "id, optid, val)
				VALUES($id, $optid, '$val')";
                if(DbService::query($qry)){
                    $added= 1;
                    $cnt++;
                }
            }

            if($log and ($deleted or $added)){
                $txt= '';
                if($deleted and $added){
                    $txt= ' (replacement opt)';
                    $typ= 22;
                }elseif($deleted){
                    $txt= ' (remove opt)';
                    $typ= 20;
                }elseif($added){
                    $typ= 21;
                    $txt= ' (add opt)';
                }
                //$optnam = DbService::val("SELECT shortdescr FROM opt WHERE id='$optid'");
                //\Encoder\Usrlog::logadd($typ, "$optnam = '$val'$txt", $oTyp, $id, true);
            }
        }
        return $cnt;
    }

    public static function flushCache($optNam = null){
        if(!is_null($optNam))
            unset(self::$_optCache[$optNam]);
        else
            self::$_optCache = Array();
    }

    /**
     * remove opt
     * @param $nam	- opt nam
     * @param $arrvals - values
     * @param $id - user/group user depending on opt
     */
    static function delopt($nam, $arrvals = 'all', $id = 0){
        $id = $id ?: self::$id;
        $h = 93;
        $b = array(5,7);

        $opt = DbService::row("SELECT * FROM opt WHERE nam='$nam'");
        if(!$opt)
            return false;

        if($id==self::$id)
            unset(self::$_optCache[$nam]);

        $qry = "DELETE FROM $opt[opttyp]_opt WHERE $opt[opttyp]id='$id' AND optid=$opt[id]";

        if($arrvals != 'all'){
            $arrvals = is_array($arrvals) ? ("'".implode("','", $arrvals)."'") : "'$arrvals'";
            $qry.= " AND val IN($arrvals)";
        }
        return DbService::query($qry);
    }
}