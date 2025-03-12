<?php
namespace Mail;

class MailSender extends \Mail\PHPMailerMy{
	
	public function init(){
		//$this->CharSet='windows-1251';
		$this->CharSet='utf-8';
		// использовать SMTP
		$this->isSMTP();
		// не разрывать SMTP соединение после посылки каждого письма
		$this->SMTPKeepAlive = true;
	}
	
	public function sendMail($host){
		$assign = \Mail\def::$servers;
		
		try {
			if(!isset($assign[$host])){
				throw new \phpmailerException('Host '.$host.' not exist in define', self::STOP_CONTINUE);
			}
			
			$this->Host= $assign[$host]['outdomain'];	//"smtp.mail.ru";
			$this->Port= $assign[$host]['outport'];  	// set the SMTP port for the GMAIL server
			
			// enable SMTP authentication
			if($assign[$host]['smtpauth']==1){
				$this->SMTPAuth= true;
			}
			// ssl
			if($assign[$host]['smtptls'] == 1){
				$smtp_conn = @fsockopen("ssl://".$this->Host, $this->Port, $errno, $errstr, 5);
				if($smtp_conn){
					fclose($smtp_conn);
					$this->SMTPSecure	= "ssl";
				}else {
					$this->SMTPSecure	= "tls";
				}
			}
			
			$isSend= $this->send();
			if(!$isSend){
				throw new \phpmailerException('Send '.$this->ErrorInfo, self::STOP_CONTINUE);
			}
			return true;
		} catch (\Exception $exc) {
			$this->setError($exc->getMessage());
			return false;
		}
	}
	
	public function clear(){
		$this->clearAddresses();
		$this->clearAttachments();
	}
	
	/**
	 * simple send Email
	 		$mail = new MailSender();
			$isSend = $mail->sendSimple('mail.ru', 'pavelinside', 'nissan33', 'pavelinside@mail.ru', ['pavelinside@mail.ru', 'pborisovmail@gmail.com'], 'тестSubj', 'тестBody');
			if($isSend){
				echo 'Send';
			}
	 * @param string $host 				like mail.ru
	 * @param string $username 		SMTP account username
	 * @param string $password 		SMTP account password
	 * @param string $frommail 		Email from
	 * @param array $tomail 			Emails to
	 * @param string $subject
	 * @param string $body
	 */
	public function sendSimple($host, $username, $password, $frommail, array $tomail, $subject, $body){
		$this->init();
		
		$this->setFrom($frommail);
		$this->addReplyTo($frommail);
		
		foreach($tomail as $mail){
			$this->addAddress($mail);
		}
		$this->Subject = $subject;
		$this->AddBody($body);
		
		$this->Username= $username;
		$this->Password= $password;
		$isSend = $this->sendMail($host);
		
		return $isSend;
	}
	
	private function test(){
		$mail = new MailSender();
		$mail->init();
		
		$mail->Username= 'pavelinside'; // SMTP account username
		$mail->Password= 'nissan33';    // SMTP account password
		$mail->setFrom('pavelinside@mail.ru', 'Павел');     		// от
		$mail->addReplyTo('pavelinside@mail.ru', 'Павел');      // ответить кому
		
		$mail->addAddress('pavelinside@mail.ru', 'Павел');
		$mail->addBCC('pavelinside@rambler.ru', 'Иван');
		
		$mail->Subject = 'Тестовое письмо';
		// TODO images
		$htmlBody = "<!DOCTYPE HTML><html><body><p style='color:blue'>Текст</p></body></html>";
		$mail->AddHTML($htmlBody, 'Альтернативный текст');
		$mail->AddBody('Тестовое тело письма');
		//$mail->AddStringAttachment("tst", 'test.txt');
		
		$mail->addStringAttachment("Тест", 'test.txt');
		//$mail->AddFileAttachment($path, 'test1.txt');
		
		$mail->Username= 'pavelinside';   // SMTP account username
		$mail->Password= 'nissan33';    // SMTP account password
		$isSend = $mail->sendMail('mail.ru');
		if($isSend){
		
		}		
	}
	
}
?>