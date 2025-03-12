<?php
namespace app\modules\online\helpers;

class cssmin {
	private static $instance = NULL;

	public static function minify($string){
		if(self::$instance == NULL)
			self::$instance = new self();
			
		// 
		$string = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $string);
		
		$string = preg_replace('/[\s]+/', ' ', $string);
		
		// Now replace everythink else, that is useless
		$string = str_replace(
			array(' {', '{ ', ' }', '} ', ': ', '; ', ', ', ' ,', ';;'), 
			array('{', '{', '}', '}', ':', ';', ',', ',', ';'), 
			$string
		);
		return $string;
	}

	private function __construct(){
	}

	private function __clone(){
	}
}
?>