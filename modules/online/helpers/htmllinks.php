<?php
namespace app\modules\online\helpers;

// ����� � ����� ��������� html �����
class htmllinks {

	/**
	 * �� ������ �������� ��� ������
	 * 
	 * @param
	 *        	$str
	 */
	public function aFromTxt($str){
		preg_match_all('/(?:<a[^>]*)href=(?:[ \'\"]?)([^\s\"\'> ]+)(?:[ \'\"]?)(?:[^>]*>)/i', $str, $mtch);
		return $mtch;
	}

	/**
	 * ������� �� ������ ��� ��������� $match, ��������� �������� *FromTxt (����� aFromTxt, ��� ������������ ���� �� ������)
	 * 
	 * @param string $str        	
	 * @param array $mtch        	
	 */
	public function clearMatch($str, $match){
		foreach($match as $v){
			$str = str_replace($v, "", $str);
		}
		return $str;
	}

	/**
	 * �������� ��� ��������
	 * 
	 * @param string $str        	
	 */
	public function imgFromTxt($str){
		preg_match_all('/(?:<img[^>]*)src=(?:[ \'\"]?)([^\s\"\'> ]+)(?:[ \'\"]?)(?:[^>]*>)/i', $str, $mtch);
		return $mtch;
	}

	/**
	 * �������� ��� iframe
	 * 
	 * @param string $str        	
	 */
	public function iframeFromTxt($str){
		preg_match_all('/(?:<iframe[^>]*)src=(?:[ \'\"]?)([^\s\"\'> ]+)(?:[ \'\"]?)(?:[^<]*<\/iframe>)/i', $str, $mtch);
		return $mtch;
	}

	/**
	 * �������� ��� background-image
	 * 
	 * @param string $str        	
	 */
	public function backgroundImageFromTxt($str){
		preg_match_all('/(?:background-image)(?:[^)]*)(?:[\); ]+)/i', $str, $mtch);
		return $mtch;
	}

	/**
	 * �������� ��� background:url
	 * 
	 * @param string $str        	
	 */
	public function backgroundFromTxt($str){
		preg_match_all('/(?:background[ ]*=[\n \"\']*[\S\n]*)(?:[ >]+)/i', $str, $mtch);
		return $mtch;
	}

	/**
	 * �������� ��� background:url
	 * 
	 * @param string $str        	
	 */
	public function backgroundurlFromTxt($str){
		preg_match_all('/(?:background[ ]*:[\n ]*url[\S\s]*)(?:[;\n}]+)/iU', $str, $mtch);
		return $mtch;
	}

	/**
	 * �������� ��� <link>
	 * 
	 * @param string $str        	
	 */
	public function bgsoundFromTxt($str){
		preg_match_all('/(?:<bgsound)(?:[\S\s]*)(?:>)/iU', $str, $mtch);
		return $mtch;
	}

	/**
	 * �������� ��� <link>
	 * 
	 * @param string $str        	
	 */
	public function linkFromTxt($str){
		preg_match_all('/(?:<link)(?:[\S\s]*)(?:>)/iU', $str, $mtch);
		return $mtch;
	}

	/**
	 * �������� ��� <embed>*</embed> �� ������
	 * 
	 * @param string $str        	
	 */
	public function embedFromTxt($str){
		// U - ����������� ����������� �������� ���������������
		preg_match_all('/(?:<embed)(?:[\S\s]*)(?:\/embed>)/iU', $str, $mtch);
		return $mtch;
	}

	/**
	 * �������� ��� <embed>*</embed> �� ������
	 * 
	 * @param string $str        	
	 */
	public function videoFromTxt($str){
		// U - ����������� ����������� �������� ���������������
		preg_match_all('/(?:<video)(?:[\S\s]*)(?:\/video>)/iU', $str, $mtch);
		return $mtch;
	}

	/**
	 * �������� ��� <audio>*</audio> �� ������
	 * 
	 * @param string $str        	
	 */
	public function audioFromTxt($str){
		// U - ����������� ����������� �������� ���������������
		preg_match_all('/(?:<audio)(?:[\S\s]*)(?:\/audio>)/iU', $str, $mtch);
		return $mtch;
	}

	/**
	 * �������� ��� <audio>*</audio> �� ������
	 * 
	 * @param string $str        	
	 */
	public function objectFromTxt($str){
		// U - ����������� ����������� �������� ���������������
		preg_match_all('/(?:<object)(?:[\S\s]*)(?:\/object>)/iU', $str, $mtch);
		return $mtch;
	}

	/**
	 * �������� ��� <script>*</sript> �� ������
	 * 
	 * @param string $str        	
	 */
	public function scriptFromTxt($str){
		// U - ����������� ����������� �������� ���������������
		preg_match_all('/(?:<script)(?:[\S\s]*)(?:\/script>)/iU', $str, $mtch);
		return $mtch;
	}

	/**
	 * � ������ �� ������� �������� target �� _blank, ����� ������ ����������� � ����� ����
	 */
	public function aTargetSetBlank($link){
		$link = str_replace(array(
			"TARGET=",
			"<A",
			"A>" 
		), array(
			"target=",
			"<a",
			"a>" 
		), $link);
		// preg_match_all('/[ ]target=[\'\" ]*([^>\'\" ]+)[\'\" ]*/i', $link, $mtch);
		$repl = preg_replace('/[ ]target=[\'\" ]*([^>\'\" ]+)[\'\" ]*/i', ' target="_blank" ', $link, 1);
		if($repl == $link && strpos($link, 'target="_blank"') === FALSE){
			$cnt = 0;
			$repl = str_replace("<a", "<a target=\"_blank\"", $repl, $cnt);
		}
		return $repl;
	}

	public function aTargetSetBlankAll($str){
		$arr = $this->aFromTxt($str);
		if($arr){
			$cnt = count($arr[0]);
			$aFrom = array();
			$aTo = array();
			for($i = 0; $i < $cnt; $i ++){
				// ������ �� ����� � ��������� �� ������������
				if($arr[1][$i][0] != '#'){
					$aFrom[] = $arr[0][$i];
					$aTo[] = $this->aTargetSetBlank($arr[0][$i]);
				}
			}
			$str = str_replace($aFrom, $aTo, $str);
		}
		return $str;
	}
}