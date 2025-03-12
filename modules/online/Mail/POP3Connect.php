<?php
namespace Mail;

/**
 * класс для работы с pop3
 * TODO Exception
 */
class POP3Connect{

	private $pop_conn= NULL;

	/**
	 * получить i письмо с сервера
	 * @param integer $i
	 * @param string $sErr
	 */
	function pop_retr($i, &$sErr){
		if(!$this->pop_conn){
			$sErr= "connection lost";
			return false;
		}

		// получаем $i письмо
		if (!fputs($this->pop_conn,"RETR $i\r\n")){
			$this->disconnect();
			$sErr= "connection lost";
			return false;
		}
		$err= "";
		$text= $this->get_data($err);
		if($err != ""){
			$sErr= "connection lost";
			return false;
		}

		$text= trim($text);

		// служебную информацию +OK и +ERROR удалить
		if(substr($text, 0, 3) == "+OK"){
			$ipos= strpos($text, "\r\n");
			if($ipos !== FALSE)
				$text= substr($text, $ipos+2);
		} else if(substr($text, 0, 6) == "+ERROR"){
			$ipos= strpos($text, "\r\n");
			if($ipos !== FALSE)
				$text= substr($text, $ipos+2);
		}

		return $text;
	}

	/**
	 * получить количество сообщений в почтовом ящике (STAT +OK 2 320)
	 * @param string $sErr ''-всё хорошо; '-1' потеряно соединение; или ошибка серввера
	 */
	function pop_stat(&$sErr){
		if(!$this->pop_conn){
			$sErr= "connection lost";
			return false;
		}

		if (fwrite($this->pop_conn, "STAT\r\n") === FALSE) {
    	$this->disconnect();
    	// ERROR Потеряно соединение
    	$sErr= "connection lost";
    	return false;
    }

		$code= fgets($this->pop_conn,1024);

		// ERROR Неизвестный ответ сервера при получении количества писем
		if (substr($code,0,1)=='-')	{
			$this->disconnect();
			$sErr= $code;
			return false;
		}
		$mailcnt = explode(' ',$code);
		if (!isset($mailcnt[1])){
			if($code == "")
				$code= "unknown server answer";
			$this->disconnect();
			$sErr= $code;
			return false;
		}

		return $mailcnt[1];
	}

	/**
	 * удалить письмо с сервера
	 * @param integer $mailnum		номер письма для удаления
	 * @param string $sErr
	 */
	function deletemail($mailnum, &$sErr){
		if(!$this->pop_conn){
			$sErr= "connection lost";
			return false;
		}

		fputs($this->pop_conn,"DELE $mailnum\r\n");
		$code= fgets($this->pop_conn,1024);
		if (substr($code,0,1)=='-') {
			$sErr= "error attempt to delete non-existant mail: $code";
			return false;
		}
		return true;
	}

	/**
	 * получает размеры писем
	 */
	function maillist(&$sErr){
		$a= array();
		if(!$this->pop_conn){
			$sErr= "connection lost";
			return FALSE;
		}

		$len= fputs($this->pop_conn,"LIST\r\n");
		if($len == FALSE)
			return FALSE;

		$err= "";
		$list = $this->get_data($err);
		if($list == FALSE || $err != "")
			return FALSE;

		$a1= explode ( "\r\n" , $list);
		$ilen= count($a1);
		if($ilen < 2)
			return $list;

		for($i= 1; $i<$ilen; $i++){
			$b= explode(" " , $a1[$i]);
			$a[$i]= $b[1];
		}
		return $a;
	}

	/**
	 * получить данные (учесть точку, которую сервер ставит в конце вывода)
	 */
	function get_data(&$sErr){
		if(!$this->pop_conn){
			$sErr= "connection lost";
			return FALSE;
		}

		$data="";
		while (!feof($this->pop_conn)) {
			$buffer = fgets($this->pop_conn,1024);
			// final line consisting termination octet (decimal code  046, ".") and a CRLF pair
			if($buffer == ".\r\n")
				break;
			$buffer = chop($buffer);
			$data.= "$buffer\r\n";
		}
		if($data != "")
			return substr($data , 0 , -2);
		else
			return $data;
	}

	/**
	 * отключиться от почтового сервера
	 */
	function disconnect(&$sErr=""){
		if($this->pop_conn){
			fputs($this->pop_conn,"QUIT\r\n");
			fclose($this->pop_conn);
		} else
			$sErr= "connection lost";
	}

	/**
	 * подключение к почтовому серверу
	 * @param string $mailbox почтовый ящик
	 * @param string $str	ошибка
	 * @param string $login
	 * @param string $pass
	 * @param string $indomain
	 * @param integer $inport				// порт
	 * @param integer $poptls				// 0 или 1
	 * @param integer $popauth			// 0 или 1
	 */
	function pop_connect($mailbox, &$sErr, $login, $pass, $indomain, $inport, $poptls, $popauth){
		$code = false;
		if ($poptls==1){
			$usename = $login;
			$password = $pass;
			$ctx = stream_context_create([
				'ssl' => [
					'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
					'verify_peer'   => false,
					'header' => "Authorization: Basic " . base64_encode("$usename:$password")
				],
			]);
			$this->pop_conn = stream_socket_client(
					"tlsv1.2://$indomain:$inport", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $ctx);

			// * для gmail recent:имя_пользователя@gmail.com, чтобы c других POP принимать $login= "recent:".$login;
			//$this->pop_conn = @fsockopen("tls://".$indomain,$inport,$errno,$errstr, 5);
			if(!$this->pop_conn){
				$this->pop_conn = fsockopen($indomain,$inport,$errno,$errstr,5);
			}
			if($this->pop_conn){
				$code = @fgets($this->pop_conn,1024);
				if ($code)
					if (substr($code,0,1)=='-') {
						$sErr= "error bad server answer";
						fputs($this->pop_conn,"QUIT\r\n");
						fclose($this->pop_conn);
						return false;
					}

				/* // для Gmail перестало работать
				fputs($this->pop_conn,"STARTTLS\r\n");
				$code = @fgets($this->pop_conn,1024);
				if ($code)
					if (substr($code,0,1)=='-') {
						$sErr= "error server not accepting TLS";
						fputs($this->pop_conn,"QUIT\r\n");
						fclose($this->pop_conn);
						return false;
					}
				if (!stream_socket_enable_crypto($this->pop_conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
					$sErr= "error TLS not established";
					return false;
				}*/
			}
		}else {
			$this->pop_conn = @fsockopen($indomain,$inport,$errno,$errstr,5);
			$code = @fgets($this->pop_conn,1024);
		}
		if (!$this->pop_conn) {
			$sErr= "error no connection. right server? ".$indomain.":".$inport;
			return false;
		}
		if (@fputs($this->pop_conn,"USER ".$login."\r\n"))
			$code= @fgets($this->pop_conn,1024);
		else {
			$sErr= "error USER rejected";
			return false;
		}

		if (@fputs($this->pop_conn,"PASS ".$pass."\r\n"))
		$code= @fgets($this->pop_conn,1024);
		else {
			$sErr= "error PASS rejected";
			return false;
		}

		if (substr($code,0,1)=='-') {
			$sErr= "error ".substr($code,5)." $mailbox";
			@fputs($this->pop_conn,"QUIT\r\n");
			@fclose($this->pop_conn);
			return false;
		}
		return true;    // HELO pop.gmail.com
	}

}