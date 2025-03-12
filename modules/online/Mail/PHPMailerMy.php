<?php
namespace Mail;
// include_once('phpmailer/class.phpmailer.php');
// include_once('phpmailer/class.smtp.php');
// include_once('phpmailer/class.pop3.php');
include_once('phpmailer/PHPMailerAutoload.php');

class PHPMailerMy extends \PHPMailer{
	
	/**
	 * Длина письма, вычисляется при отправке
	 * @var int
	 */
	public $bodyLen= 0;
	
	/**
	 * Длина заголовков письма, вычисляется при отправке
	 * @var int
	 */
	public $headerLen= 0;
	
	public function __construct($exceptions = false) {
    parent::__construct($exceptions);
	}
	
	public function RuEncode($ru) {
		if (!preg_match('/[А-Яа-я]/',$ru)){
			return $ru;
		}	else {
			return "=?windows-1251?Q?".str_replace("+","_",str_replace("%","=",urlencode($ru)))."?=";
		}
	}
	
	public function createBody() {
		$str= parent::createBody();
		$this->bodyLen= strlen($str);
		return $str;
	}
	
	public function createHeader() {
		$str= parent::createHeader();
		$this->headerLen= strlen($str);
		return $str;
	}
	
	/**
	 * заголовки для подтверждения прочтения
	 * @param string $mail
	 */
	public function notifyRead($mail){
		$this->addCustomHeader("X-Confirm-Reading-To: $mail");
		$this->addCustomHeader("Disposition-Notification-To: $mail");
	}
	
	/**
	 * fix error "Message body empty"
	 * @param string $header
	 * @param string $body
	 * @return boolean
	 */
	protected function SmtpSend($header, $body) {
		$pos= strpos(trim($body), chr(7).chr(8));
		if($pos !== FALSE){
			$body= str_replace(chr(7).chr(8), '', $body);
		}
		return parent::smtpSend($header, $body);
	}
	
	// -------------------------------------------------------

	public function addAddress($address, $name = ''){
		return parent::addAddress($address, $this->RuEncode($name));
	}
	
	/**
	 * bcc альтернативное кому
	 */
	public function addBCC($address, $name = '') {
		parent::addBCC($address, $this->RuEncode($name));
	}
	
	public function setFrom($address, $name = '', $auto = true){
		parent::setFrom($address, $this->RuEncode($name), $auto);
	}
	
	/**
	 * body is html
	 * 		$mail->Body = "<!DOCTYPE HTML><html><body><p style='color:blue'>Текст</p></body></html>";
	 * @param string $htmlBody
	 * @param string $altBody
	 * @param array $images [cid=path]
	 */
	public function AddHTML($htmlBody, $altBody, array $images = []){
		$this->isHTML(true);
		
		$this->Body = $htmlBody;
		
		// баг если пустое body, не отправляет (см. SmtpSend)
		$this->AltBody = ($altBody== "") ? chr(7).chr(8) : $altBody;
		
		// прикрепить картинки для html варианта письма
		foreach($images as $imgcid => $imgpath){
			$this->addEmbeddedImage($imgpath, $imgcid, $imgcid, 'base64');
			//$mail->AddEmbeddedImage($imgpath, $imgcid, $imgcid, 'base64', 'image/jpg');
		}
	}
	
	/**
		body is text
	 */
	public function AddBody($body){
		$this->isHTML(false);
		
		// баг если пустое body, не отправляет (см. см. SmtpSend)
		$this->Body = ($body== "") ? chr(7).chr(8) : $body;
	}
	
	/**
	 * attch file
	 * @param string $path
	 * @param string $filenam
	 * @throws phpmailerException
	 * @return boolean
	 */
	public function AddFileAttachment($path, $filenam){
		try {
			if (!is_readable($path)) {
				throw new \phpmailerException($this->lang('file_open') . $path, self::STOP_CONTINUE);
			}
			if(!$filenam){
				throw new \phpmailerException('file_name', self::STOP_CONTINUE);
			}
			
			// attach file
			$filelen = filesize($path);
			$f = fopen($path, 'r');
			if($filelen > 0){
				$this->addStringAttachment(fread($f,$filelen), $filenam);
			} else {
				$this->addStringAttachment("", $filenam);
			}
			fclose($f);
		} catch (\Exception $exc) {
			$this->setError($exc->getMessage());
			return false;
		}
		return false;
	}
	
}
?>