<?php
namespace app\modules\online\helpers;

class ArrayHelper {

	/**
	 * преобразование переменной в массив из строки разделителем $delimiter
	 * если переменная является массивом, возвращается в исходном виде,
	 * @param $val
	 * @param array $def
	 * @param string $delimiter
	 * @return array
	 */
	public static function makeArray($val, $def = [], $delimiter = ','){
		$answer = is_array($val) ? $val : ( $val ? explode($delimiter, $val) : $def );
		return count($answer) ? $answer : $def;
	}
}