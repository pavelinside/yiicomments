<?php
namespace app\modules\online\helpers;

class HtmlHelper {
    /**
     * из массива делает список html option-ов
     * @param array $arr сам список
     * @param int $strict добавление пустого варианта (<0 - не добавлять, >0 - добавлять, ==0 - добавлять, если более 1 элемента)
     * @param int|string $selected элемент, который надо выделить
     * @return string
     */
    function makeHtmlOptions(array $arr, int $strict=0, $selected=false): string
    {
        $answer=($strict>0 or (count($arr)>1 and $strict==0))?'<option value=""></option>':'';
        foreach($arr as $i=>$val){
            if($i==='total'){
                continue;
            }

            if( !is_array($val) )
                $val = [ 'id' => $i, 'nam' => $val ];
            $attrs = isset($val['attr']) ? $val['attr'] : [];
            if( isset($val['id']) )
                $attrs['value'] = $val['id'];
            elseif( isset($val[0]) )
                $attrs['value'] = $val[0];
            else
                $attrs['value'] = $i;

            if(is_array($val)){
                if( isset($val['nam']) )
                    $nam = $val['nam'];
                elseif( isset($val[1]) )
                    $nam = $val[1];
                else
                    return '';
            }else
                $nam = $val;

            $nam = htmlspecialchars($nam, 2, 'cp1251');

            if( is_array($val) )
                foreach( $val as $vnam => $vval )
                    // $vnam в кавычках, значения массива преобразуясь к числу будут = 0 -> $vnam==0 - true
                    if( in_array($vnam, ['selected', 'disabled', 'class', 'style']) )
                        $attrs[$vnam] = $vval;

            if(isset($attrs['disabled'])){
                if(isset($attrs['selected']))
                    unset($attrs['selected']);
            }elseif($selected!==false and ($attrs['value']==$selected or $selected==='all'))
                $attrs['selected'] = 'selected';

            $astr = '';
            foreach ($attrs as $anam=>$aval)
                $astr.= " $anam='$aval' ";

            $answer.= "<option$astr>$nam</option>";
        }
        return $answer;
    }

    /**
     * Generate HTML template into string
     * @param $viewPath
     * @param array $vars
     * @return string
     */
    public static function render($viewPath, array $vars = []): string
    {
        if(!file_exists($viewPath)){
            return '';
        }

        // set variables for template
        foreach ($vars as $k => $v){
            $$k = $v;
        }

        ob_start();
        include $viewPath;
        return ob_get_clean();
    }

    /**
     * to check if a string of English and Russian characters simultaneously
     * @param string $str
     */
    public static function isEnglishChars($str){
        preg_match('/([a-zA-Z])/i', $str, $mtch1);
        preg_match('/([а-яА-Я])/i', $str, $mtch2);
        return ($mtch1 && $mtch2) ? true : false;
    }
}