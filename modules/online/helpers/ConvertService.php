<?php
namespace app\modules\online\helpers;

use app\modules\online\helpers\DbService;

class ConvertService {
    /**
     * транслитерация
     * @param string $st
     * @return string
     */
    public static function translit($st){
        // Сначала заменяем "односимвольные" фонемы.
        $st = strtr($st, "абвгдеёзийклмнопрстуфыэ",
            "abvgdeeziiklmnoprstufye");
        $st = strtr($st, "АБВГДЕЁЗИЙКЛМНОПРСТУФЫЭ",
            "ABVGDEEZIIKLMNOPRSTUFYE");
        // Затем - "многосимвольные".
        $st = strtr($st, array(
            "ж" => "zh", "ц" => "ts", "ч" => "ch", "х"=>"kh", "ш" => "sh", "щ" => "shch", "ь" => "", "ъ"=>"", "ю" => "yu", "я" => "ya",
            "Ж" => "Zh", "Ц" => "Ts", "Ч" => "Ch", "Х"=>"Kh", "Ш" => "Sh", "Щ" => "Shch", "Ь" => "", "ъ"=>"", "Ю" => "Yu", "Я" => "Ya",
            "ї" => "i", "Ї" => "Yi", "є" => "ie", "Є" => "Ye"));
        // Возвращаем результат.
        return $st;
    }

    public static function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
                break;
        }
        return $val;
    }

    /**
     * перевод числа в читаемый вид (скорость передачи или размер)
     * @param integer $size
     * @param boolean $isspeed
     * @return string
     */
    public static function Size2Str($size, $isspeed= 0)
    {
        $kb = 1024;
        $mb = 1024 * $kb;
        $gb = 1024 * $mb;
        $tb = 1024 * $gb;
        if ($size < $kb) {
            $prefBt= ($isspeed == 1) ? ' бт/с' : ' Бт';
            return number_format($size, 2, '.', '').$prefBt;
        } else if ($size < $mb) {
            $prefKb= ($isspeed == 1) ? ' кб/с' : ' Кб';
            return number_format($size / $kb, 2, '.', '').$prefKb;
        } else if ($size < $gb) {
            $prefMb= ($isspeed == 1) ? ' мб/с' : ' Мб';
            return number_format($size / $mb, 2, '.', '').$prefMb;
        } else if ($size < $tb) {
            $prefGb= ($isspeed == 1) ? ' гб/с' : ' Гб';
            return number_format($size / $gb, 2, '.', '').$prefGb;
        } else {
            $prefTb= ($isspeed == 1) ? ' тб/с' : ' Тб';
            return number_format($size / $tb, 2, '.', '').$prefTb;
        }
    }

    /**
     * преобразование переменной в массив из строки. если переменная является массивом,
     * возвращается в исходном виде, иначе разбивается $delimetr
     * @param $val
     * @param $def
     * @param $delimetr
     */
    public function makeArray($val, $def = [], $delimetr = ','){
        $answ = is_array($val) ? $val : ( $val ? explode($delimetr, $val) : $def );
        return count($answ) ? $answ : $def;
    }

    public static function toWin($str){
        return @iconv("UTF-8","CP1251//IGNORE//TRANSLIT", $str);
    }
    public static function toUtf($str){
        return @iconv("CP1251", "UTF-8", $str);
    }

    /**
     * cleaning of the input parameters of special characters
     * @param array $arr
     */
    public static function cleanData(&$arr, $trim = false){
        if(!$arr)
            return;
        if(is_array($arr))
            foreach($arr as $i=>&$val)
                static::cleanData($val, $trim);
        if(is_string($arr)){
            $arr = preg_replace("~[^\s\x20-\xFF]~", '', $arr);
            if($trim)
                $arr = trim($arr);
        }
    }

    /**
     * transcoding to UTF or back
     * @param $var
     * @param bool $mysqlEscape
     * @param int $code 0-not need;	1-from UTF8 to WINDOWS-1251;	2-from WINDOWS-1251 to UTF8
     */
    public static function coding($var, bool $mysqlEscape=true, int $code=0){
        switch(gettype($var)){
            case 'string':
                switch($code){
                    case 1:
                        $var= self::toWin($var);
                        break;
                    case 2:
                        $var= self::toUtf($var);
                        break;
                }
                if($mysqlEscape)
                    $var = DbService::escape_string($var);
                return $var;
            case 'array' :
                foreach($var as $nam=>$val){
                    $var[$nam] = self::coding($val, $mysqlEscape, $code);
                }
                break;
        }
        return $var;
    }

    /**
     * @param $str
     * @return bool
     */
    public function hasEnglishChars($str){
        preg_match('/([a-zA-Z])/i', $str, $mtch1);
        return $mtch1 ? true : false;
    }

    /**
     * @param $str
     * @return bool
     */
    public function hasRussianChars($str){
        preg_match('/([а-яА-Я])/i', $str, $mtch2);
        return $mtch2 ? true : false;
    }

    /**
     * to check if a string of English and Russian characters simultaneously
     * @param $str
     * @return bool
     */
    public function isEnglishChars($str){
        preg_match('/([a-zA-Z])/i', $str, $mtch1);
        preg_match('/([а-яА-Я])/i', $str, $mtch2);
        return ($this->hasEnglishChars($str) && $this->hasRussianChars($str)) ? true : false;
    }
}