<?php
namespace app\modules\online\helpers;

use app\modules\online\helpers\JsonHelper;

class LogService
{
    private static string $logPath = '/';

    public static function setLogPath(string $logPath): void
    {
        // TODO check path exist
        self::$logPath = $logPath;
    }

    public static function write(string $filePath, &$str, string $mode, $newline=false, $permissions = 0660){
        $f= fopen($filePath, $mode);
        @flock($f, LOCK_EX);  // write lock
        if($newline)
            @fwrite($f,"\n");
        @fwrite($f,$str);
        @fflush($f); 					// cleansing file buffer and write to the file
        @flock($f, LOCK_UN); 	// unlock
        fclose($f);
        @chmod($filePath, intval($permissions, 8));
    }

    /**
     * returns the file name and the line from which the function is called
     * @param array $excludedFiles
     * @return array
     */
    public static function errorFileLine(array $excludedFiles = []): array
    {
        $a = [];
        $par = [];
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($backtrace as $v) {
            if (!isset($v['file']))
                continue;

            $baseName = basename($v['file']);
            if ($baseName != basename(__FILE__) && !in_array($baseName, $excludedFiles)) {
                if ($a) {
                    array_unshift($par, "$baseName: $v[line]");
                    $a['par'] = implode('=>', $par);
                    if ($baseName != $a['file'])
                        break;
                } else {
                    $a = ['file' => $baseName, 'line' => $v['line']];
                }
            }
        }
        return $a;
    }

    public static function getFileLogName(string $prefix="log"): string
    {
        return $prefix.date("Y-m-d").".txt";
    }

    /**
     * removes tabs and converts the data to a string if it is an array
     * @param array $msg
     */
    public static function processStr(array &$msg){
        $msg= print_r($msg, true);

        $msg = str_replace ( "\t\t\t\t\t\t", "\t" . chr ( 2 ) . "\t", $msg );
        $msg = str_replace ( "\t\t\t", "", $msg );
        $msg = str_replace ( chr ( 2 ), "", $msg );
    }

    // log code, error, error database
    public static function log($data, $code="", $dberr="", $fileName=''){
        if(is_array($data))
            self::processStr($data);
        $filePath = self::$logPath.'/'.$fileName;

        $login= 'root';
        $usr_id= 1;
        $dt= date("[Y-m-d H:i:s]");
        $str= "DATE:$dt\tuserid:$usr_id\tlogin:$login\tIP:".\sys\getIP()."\nCODE:$code\tERROR:\n$data\n";
        if($dberr != "")
            $str.= "DBERROR\n:$dberr\n";
        self::write($filePath, $str, "ab", true);
    }

    // write string into file
    public static function logSimple($data, $rewrite= false, $fileName=''){
        if(is_array($data))
            self::processStr($data);
        $filePath = self::$logPath.'/'.$fileName;
        $mode= ($rewrite) ? "wb" : "ab";
        self::write($filePath, $data, $mode, true);
    }

    public static function logGetContent($fileName=''){
        $filePath = self::$logPath.'/'.$fileName;
        return (is_file($filePath)) ? file_get_contents($filePath) : '';
    }

    // the formation of an array of string arguments
    public static function argsMake(&$args, $cnt){
        $s = '';
        for($i= 0; $i < $cnt; $i++) {
            $str= is_array($args[$i]) ? "\n".self::ProcessStr($args[$i]) : $args[$i].chr(9);
            $s= ($s == '') ? $str : $s.' '.$str;
        }
        return $s;
    }

    public static function logCustom($rewrite= false){
        $len = func_num_args();
        $lst = func_get_args();
        $str= self::argsMake($lst, $len);
        self::logSimple($str, $rewrite, 'custom.log');
    }

    public static function died($errMsg){
        if ($_GET)
            die ( htmlspecialchars_decode ( "error " . $errMsg . " Обратитесь к администратору" ) );
        die(JsonHelper::json(["error"=>$errMsg]));
    }
}