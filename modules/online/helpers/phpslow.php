<?php
$PHPSLOW = 1;
//include_once 'phpslow.php';
if($PHPSLOW){

	function cpuUsageStart(){
		if(function_exists('getrusage')){
			$dat = getrusage();
			define('PHP_RUSAGE', $dat["ru_utime.tv_sec"] * 1e6 + $dat["ru_utime.tv_usec"]);
			define('PHP_RSUSAGE', $dat["ru_stime.tv_sec"] * 1e6 + $dat["ru_stime.tv_usec"]);
		}
		define('PHP_EXECTIME', microtime(true));
	}

	function cpuUsageEnd($sql_exectime){
		if(function_exists('getrusage')){
			$dat = getrusage();
			$tm = ($dat["ru_utime.tv_sec"] * 1e6 + $dat["ru_utime.tv_usec"] - PHP_RUSAGE) + ($dat["ru_stime.tv_sec"] * 1e6 + $dat["ru_stime.tv_usec"] - PHP_RSUSAGE);
		}else{
			$tm = 0;
		}

		$execTime = ceil((microtime(true) - PHP_EXECTIME) * 1000000);
		$sqlTime = ceil($sql_exectime * 1000000);

		$userIP = \sys\getIP();
		$post = "" . ($_GET ? \sys\json($_GET) : "") . ($_GET ? "   " : "") . ($_POST ? \sys\json($_POST) : "");
		$post = str_replace(array(
			"\": \"",
			"\", \"",
			"{ \"",
			"\" }"
		), array(
			"\":\"",
			"\",\"",
			"{\"",
			"\"}"
		), $post);
		if(preg_match('//u', $post))
			$post = iconv("UTF-8", "CP1251//IGNORE//TRANSLIT", $post);
		$str = date('H:i:s') . " " . $tm . " " . $execTime . " " . $sqlTime . " " . $userIP . " " . \Encoder\Opt::$row['loginview'] . " " . $post;
		return $str;
	}
	cpuUsageStart();
}

function cldestroy(){
	global $PHPSLOW;
	if($PHPSLOW){
		$str = cpuUsageEnd(\db::$SQL_EXECTIME);
		if($str){
			// $logpath = dirname(__FILE__);
			$logpath = \APP::getConfig()->getPathLog() . "/" . \Encoder\Log::fname("slow");
			\Encoder\Log::write($logpath, $str, "ab", true);
		}
	}
	\db::disconnect();
	exit();
}
register_shutdown_function('cldestroy');