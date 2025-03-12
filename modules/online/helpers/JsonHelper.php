<?php
namespace app\modules\online\helpers;

class JsonHelper {
    /**
     * преобразует php переменную в json формат для ajax запросов
     * @param bool $a
     * @return string
     */
    public static function json( $a = false ){
        if ( is_null($a) )
            return 'null';
        if ( $a === false )
            return 'false';
        if ( $a === true )
            return 'true';
        if ( is_scalar($a) )	{
            if ( is_float($a) )
                $a = str_replace(",", ".", strval($a));
            static $jsonReplaces = [
                ["\\",	"/",	"\n",	"\t",	"\r",	"\b",	"\f",	'"' ],
                ['\\\\','\\/',	'\\n',	'\\t',	'\\r',	'\\b',	'\\f',	'\"' ]
            ];
            // баг в firefox 29 - добавлена замена неразрывного пробела на обычный
            // 		static $jsonReplaces = array(
            // 				array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"', "\xA0")
            // 				,array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"', ' ')
            // 		);
            return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
        }
        // not indexed
        $isList = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a))	{
            if (key($a) !== $i){
                $isList = false;
                break;
            }
        }
        $result = [];

        foreach ( $a as $k => $v ){
            $val = static::json($v);
            if (!$isList)
                $val = static::json($k).': '.$val;
            $result[] = $val;
        }
        $result = join(', ', $result);
        return $isList ? "[ $result ]" : "{ $result }";
    }

    /**
     * show dialog on json request
     * @param $text
     * @param string $title
     * @param string $btn
     * @param array $dialogSets
     * @param string $key
     * @return bool
     */
    public static function confirm($text, $title = 'Подтверждение', $btn = 'Подтвердить', $dialogSets = [], $key = '1'){
        if(isset($_POST['confirmvals'][$key])){
            $answ = $_POST['confirmvals'][$key] ? $_POST['confirmvals'][$key] : true;
            //		unset($_POST['confirmvals']);
            return $answ;
        }

        $arr = [
            'confirm'		=>	$text,
            'conftitle'		=>	$title,
            'confirmbtn'	=>	$btn,
            'confirmdlgsets'=>	$dialogSets,
            'confirmkey'	=>	$key,
        ];

        if(isset($_POST['confirmoverridekeys'])) {
            $arr['confirmoverridekeys'] = $_POST['confirmoverridekeys'];
        }

        echo self::json($arr);
        exit;
    }
}