<?php
namespace app\modules\online\helpers;

use yii\db\Exception;

class DbService {
    private static $_lnk;							// instance of the class mysqli
    public static $SQL_NO_CACHE = false;	        // use SQL_NO_CACHE for all query's
    public static $SQL_EXECTIME = 0;			    // total query execution time of the current script

    private static $_lockTimeout = 2;

    public static $SLOW_DURATION = 1;               // длительность для медленных запросов

    private static $_dbName = '';

    /**
     * получить таблицы, которые содержат определённое поле
     * @param string $fieldName
     * @param array $tablesignore
     * @param boolean $useLike
     * 	array('table'=>таблица, 'field'=>поле, 'where'=>условие join, 'deltype'=>тип удалять или обнулять)
     */
    public static function tablesWithField(string $fieldName, array $skipTables=array(), bool $useLike = false){
        $res= array();
        $tbls= \db::tables('online');
        if(!$tbls)
            return $res;
        $tbls= $tbls['tables'];
        if(!$tbls)
            return $res;
        foreach($tbls as $tablenam => $fields){
            if($useLike){
                foreach($fields as $field => $fieldinf){
                    if(strpos($field, $fieldName) !== FALSE){
                        $res[$tablenam]= array('field'=>$field, 'where'=>'', 'deltype'=>1);
                    }
                }
            } else if(isset($fields[$fieldName]) && !in_array($tablenam, $skipTables))
                $res[$tablenam]= array('field'=>$fieldName, 'where'=>'', 'deltype'=>1);
        }
        return $res;
    }

    /**
     * set lock $name
     * @param string $name
     * @return boolean|Ambigous <number, boolean, mixed, unknown>
     */
    public static function lock(string $name){
        $lockName = self::getLockName($name);
        $res = self::val("SELECT GET_LOCK('$lockName', ".self::$_lockTimeout.")");
        if(is_null($res)){
            throw new Exception("Error LOCK '$name'");
        }
        return $res;
    }

    /**
     * release lock
     * @return void
     */
    public static function releaseLock(string $name){
        $lockName = self::getLockName($name);
        return self::val("SELECT RELEASE_LOCK('".$lockName."')");
    }

    public static function getLockName($name): string
    {
        return 'online' . '.' . $name;
    }

    /**
     * get the possible values of the field enum
     * @param string $tableName
     * @param string $enumField
     * @return array|false|string[]
     */
    public static function enumValues(string $tableName, string $enumField){
        $values = self::row("DESCRIBE $tableName $enumField");
        if(!isset($values['Type']) || !$values['Type'])	// а-ля [Type] => enum('1280x1024x16','1024x768x16')
            return [];
        $str = substr($values['Type'], 6);
        $str = substr($str, 0, strlen($str)-2);
        return explode("','", $str);
    }

    /**
     * connection to database
     * @param string $host
     * @param string $usr
     * @param string $pwd
     * @param string $dbname
     * @param integer $port
     * @param string $socket
     * @return mysqli
     */
    static function connect($host, $usr, $pwd, $dbname, $port='', $socket='', $errorIgnore=false) {
        //	off errors to test the connection to the database
        //$lvl = error_reporting(0);
        if($port)
            $dbb= new mysqli($host, $usr, $pwd, $dbname, $port, $socket);
        else
            $dbb= new mysqli($host, $usr, $pwd, $dbname);
        //error_reporting($lvl);

        $old= self::$_lnk;
        self::$_lnk= $dbb;

        if($dbb->connect_error) {
            self::$_lnk = NULL;
//            self::diedlog("Can not connect: host($host) user($usr) pass($pwd) dbname($dbname)
//				errno({$dbb->connect_errno}) error({$dbb->connect_error})", true, false);
            if(!$errorIgnore){
                throw new \Exception("Error connect to database $dbname");
            }
        }
        // @self::$_lnk->query('SET NAMES cp1251' );
        @self::$_lnk->query('SET NAMES UTF8');
        self::$_dbName = $dbname;
        return $old;
    }

    /**
     * queries the database. if there is an error, writes the log and exits php, otherwise it returns the query
     * @param string $query
     * @return boolean|mixed
     * @throws Exception
     */
    public static function result($query) {
        if (!self::$_lnk)
            throw new Exception("Do not exist connection to database");

        // add SQL_NO_CACHE to queries
        if(self::$SQL_NO_CACHE && stripos($query, 'SQL_NO_CACHE') === FALSE && stripos($query, "SELECT") === 0){
            $query = "SELECT SQL_NO_CACHE " . substr($query, 6);
        }

        $start= microtime(true);
        $answer= @self::$_lnk->query($query);
        $end= microtime(true) - $start;

        self::$SQL_EXECTIME += $end;

        if(self::$SLOW_DURATION && $end >= self::$SLOW_DURATION){
            self::onSlow($query, $end);
        }

        if (!$answer){
            throw new Exception("Error db query $query");
        }
        return $answer;
    }

    // slow query log
    public static function onSlow(string $query, $duration){
        // log to slow log file
//        $a= \Encoder\Log::errFileLine(array(basename(__FILE__)));
//        $line= ($a) ? "{$a['file']} line {$a['line']} " : "";
//        \Encoder\Log::logg($duration." $line:\r".$query."\r", false, \Encoder\Log::fname("qslow"));
    }

    /**
     * Gets the number of rows affected by the previous MySQL operation
     * @return int
     */
    public static function affected_rows(){
        return self::$_lnk ? self::$_lnk->affected_rows : 0;
    }

    /**
     * returns the ID, the generated query to a table
     * @return int
     */
    public static function insert_id(){
        return self::$_lnk ? self::$_lnk->insert_id : 0;
    }

    /**
     * for insert and update querys
     * @param string $qry
     * @param boolean $returnInsertId
     * @return int
     * @throws Exception
     */
    static function query($qry, $returnInsertId= false) {
        $res= self::result($qry);
        if(!$res)
            return $res;
        $affected= self::affected_rows();
        return $returnInsertId ? self::insert_id() : $affected;
    }

    /**
     * get associative array key=value
     * @param string $qry
     * @return array
     */
    static function select($qry) {
        $res= self::result($qry);
        if(!$res)
            return $res;

        $r = Array();
        while($v= $res->fetch_row())
            $r[$v[0]]= $v[1];
        $res->close();
        return $r;
    }

    /**
     * a one-dimensional array of values on request (column). not indexed,
     * @param string $qry
     * @return array
     */
    static function col($qry) {
        $res= self::result($qry);
        if(!$res)
            return $res;

        $r= array();
        while($v = $res->fetch_row()){
            $r[] = $v[0];
        }
        $res->close();
        return $r;
    }

    /**
     * a one-dimensional array of values on request (first line)
     * @param string $qry
     * @param boolean $noindex - Indexed or not
     * @return array
     */
    static function row($qry, $noindex= false) {
        $res = self::result($qry);
        if(!$res)
            return $res;

        $method= $noindex ? 'fetch_row' : 'fetch_assoc';
        $r= $res->$method();
        if(!$r)
            $r= array();
        $res->close();
        return $r;
    }

    /**
     * dimensional array of on request (net value)
     * @param string $qry
     * @param boolean $noindex - Indexed or not
     * @return array
     */
    static function arr($qry, $noindex= false){
        $res = self::result($qry);
        if(!$res)
            return $res;

        $r= array();
        $method= $noindex ? 'fetch_row' : 'fetch_assoc';
        while($v = $res->$method())
            $r[]= $v;
        $res->close();
        return $r;
    }

    /**
     * hybrid method arr and select, use the value of the first column as the array index, as the rest of the values in the subarray
     * @param string $qry
     * @param boolean $noindex - Indexed or not
     * @return array
     */
    static function iarr($qry, $noindex= false) {
        $res = self::result($qry);
        if(!$res)
            return $res;
        $r= array();
        $method= $noindex ? 'fetch_row' : 'fetch_assoc';
        while($v= $res->$method())
            $r[array_shift($v)]= $v;
        $res->close();
        return $r;
    }

    /**
     * get one value from request
     * @param string $qry
     * @return mixed
     */
    static function val($qry) {
        $res = self::result($qry);
        if(!$res)
            return $res;
        $r = $res->fetch_row();
        $res->close();
        return ($r) ? $r [0] : $r;
    }

    /**
     * check table exist
     * @param string $tableName
     */
    static function tableExist($dbname, string $tableName){
        return self::val("SELECT count(TABLE_NAME) FROM information_schema.tables WHERE TABLE_SCHEMA = '$dbname' AND table_name = '$tableName' LIMIT 1");
    }

    public static function getDatabaseTables($dbname){
        $qry = "SHOW tables FROM $dbname";
        $tableNames= self::col($qry);
        $tables= [];
        for($i=0, $len= count($tableNames); $i<$len; $i++){
            $nam= $tableNames[$i];
            $tableParams= self::arr("DESCRIBE $dbname.$nam");
            $tableFields= [];
            for($j=0; $j<count($tableParams); $j++){
                $fieldName= $tableParams[$j]['Field'];
                unset($tableParams[$j]['Field']);
                $tableFields[$fieldName]= $tableParams[$j];
            }
            $tables[$nam]= $tableFields;
        }
        return ['tableNames'=>$tableNames, 'tables'=>$tables];
    }

    /**
     * set current database
     * @param mysqli $val
     */
    public static function setdb(mysqli $val){
        if($val)
            self::$_lnk= $val;
    }

    /**
     * get last database error (Error number: error)
     * @return string
     */
    public static function error(){
        $str= '';
        if(self::$_lnk){
            if(self::$_lnk->errno)
                $str.= self::$_lnk->errno.": ";
            if(self::$_lnk->error)
                $str.= self::$_lnk->error;
            return self::$_lnk->real_escape_string($str);
        }
        return "Connection to database not exist";
    }

    /**
     * escape string
     * @param string $str
     * @return string
     */
    public static function escape_string($str){
        return self::$_lnk->real_escape_string($str);
    }

    /**
     * close connection to database
     */
    public static function disconnect(){
        if(self::$_lnk)
            self::$_lnk->close();
    }

    private static $ocnt = 1;
    public static function obfuscateVariables($qry){
        if(!preg_match_all('/(\@[0-9a-zA-Z\_]+)/', $qry, $mtch))
            return $qry;

        $mtch = array_unique($mtch[0]);
        $repl = array();
        foreach ($mtch as $var)
            $repl[] = $var . (self::$ocnt++);
        return str_replace($mtch, $repl, $qry);
    }

    public static function getLnk(){
        return self::$_lnk;
    }
}
//$dbb= new mysqli(HOSTNAME, USERNAME, PASSWORD, DBNAME, PORT, SOCKET);
//\db::setlnk($dbb);