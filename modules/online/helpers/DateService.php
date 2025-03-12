<?php
namespace app\modules\online\helpers;

use app\modules\online\services\DateTime;

class DateService {
    static private int $_timer= 0;		// 	для измерения времени

    public static function timeStart(){
        $time_start = microtime(true);
        self::$_timer= $time_start;
        return $time_start;
    }

    /**
     * measurement time, the intermediate value
     */
    public static function timerCheckpoint(){
        $time = self::timerStop();
        self::timeStart();
        return $time;
    }

    // конец измерения времени, возвращает сколько секунд прошло
    public static function timerStop(){
        $time_end = microtime(true);
        return $time_end - self::$_timer;
    }

    /**
     * перевод секунд в удобочитаемую запись
     * @param integer $second
     */
    public static function secondToFull($second){
        $periods = array(60, 3600, 86400, 31536000);
        $res = array(0, 0, 0, 0, 0);
        for ($i = 3; $i >= 0; $i--)	{
            if($second >=  $periods[$i]){
                $res[$i+1] = floor($second/$periods[$i]);
                $second = $second % $periods[$i];
            }
        }
        $res[0] = $second;

        $txt = "";
        if($res[4] > 0){
            $txt .= $res[4]. "г. ";
        }
        if($res[3] > 0){
            $txt .= sprintf('%0'.strlen($res[3]).'d', $res[3]) . "д. ";
        } else if($res[4] > 0){
            $txt .= "00д. ";
        }
        if($res[2] > 0)
            $txt .= sprintf('%02d', $res[2]) . "ч. ";
        else if($res[3] > 0 || $res[4])
            $txt .= "00 ч. ";
        if($res[1] > 0)
            $txt .= sprintf('%02d', $res[1]) . "м. ";
        else if($res[2] > 0 || $res[3] > 0 || $res[4])
            $txt .= "00м. ";
        $txt .= sprintf('%02d', $res[0]) . "c. ";
        $txt = trim($txt);
        return array('text'=>$txt, 'year'=>$res[4], 'day'=>$res[3], 'hour'=>$res[2], 'minute'=>$res[1], 'second'=>$res[0]);
    }

    public static function likeDat($str, $returnDat = false){
        $datArr = explode('-', $str);
        $answ = (
            preg_match("/^([0-9]{4}-[0-9]{1,2}-[0-9]{1,2})$/",$str)
            and checkdate($datArr[1], $datArr[2], $datArr[0])
        );
        return ($returnDat and $answ) ? date('Y-m-d', strtotime($str)) : $answ;
    }

    public static function likeDatt($str, $returnDatt = false){
        $a = strtotime($str);
        return $returnDatt ? date('Y-m-d H:i:s', $a) : (boolean)$a;
    }

    public static function reverseDatt($datt){
        if(!$datt)
            return '';
        $answ = explode(' ', $datt);
        $time = isset($answ[1]) ? ' ' . $answ[1] : '';
        return implode('-', array_reverse(explode('.', $answ[0]))) . $time;
    }

    public static function userDat($datt){
        $answ = explode(' ', $datt);
        return implode('.', array_reverse(explode('-', $answ[0])));
    }

    public static function userDatt($datt){
        $answ = explode(' ', $datt);
        return static::userDat($answ[0]) . (isset($answ[1]) ? " $answ[1]" : '');
    }

    /**
     *	для даты текущий год не писать (год писать в формате двух чисел)
     * @param string $datt
     */
    public static function dattSmallView($datt){
        $viewdatt = substr(static::userDatt($datt), 0, 10);
        // текущий год не писать
        if(date("Y") == substr($viewdatt, 6)){
            $viewdatt = substr($viewdatt, 0, 5);
        } else {
            // год писать в формате двух чисел
            $viewdatt = substr(static::userDatt($viewdatt), 0, 6).substr(static::userDatt($viewdatt), 8);
        }
        return $viewdatt;
    }

    /**
     * добавить определённое количество месяцев к дате
     * @param string $dt
     * @param integer $addmonth				сколько месяцев добавляем
     * @param boolean $withtime
     * @return string
     */
    public static function dt_addmonth($dt, $addmonth, $withtime= true){
        $time= $withtime ? date(' H:i:s', strtotime($dt)) : "";
        $day= date("d", strtotime($dt));
        $month= date("m", strtotime($dt));
        $year= date("Y", strtotime($dt));

        // получаем нужный месяц
        if($addmonth > 0){
            $cntyears= floor($addmonth / 12);
            $needmonth= $month + ($addmonth % 12);
            if($needmonth > 12){
                $needmonth= $needmonth % 12;
                $year++;
            }
            $year+= $cntyears;
        } else
            return $dt;

        // если у месяца данного дня нету (напр. 30 февраля), будет следующий месяц
        $newdt= $year."-".$needmonth."-$day$time";
        $mn= date("m", strtotime($newdt));
        if($needmonth != $mn){
            // в текущем месяце какой это день
            $cntday= date("t", strtotime($dt));
            $deltaday= $cntday - $day;
            // количество дней в нужном месяце
            $dt2= $year."-".$needmonth."-01$time";
            $monthcntday= date("t", strtotime($dt2));
            $newdt= $year."-".$needmonth."-".($monthcntday - $deltaday)."$time";
        } else
            $newdt= date("Y-m-d", strtotime($newdt))."$time";
        return $newdt;
    }

    /**
     * проверка корректности ввода времени
     * @param string $time
     */
    public static function checktime($time, $withsecond = false){
        if(!$withsecond && !preg_match("/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/", $time))
            return false;
        if($withsecond && !preg_match("/^([0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/", $time))
            return false;
        return true;
    }

    /**
     * разница в днях
     * @param string $stdat
     * @param string $enddat
     * @param boolean $include
     * @return number
     */
    public static function calcDays($stdat, $enddat = null, $include = false){
        $datetime1 = new DateTime($stdat);
        $datetime2 = new DateTime($enddat);
        $interval = $datetime1->diff($datetime2);
        return $interval->format('%a') + ($include ? 1 : 0);
    }


    ////// пересмотреть файл
    /**
     * check date for database like 2018-06-06
     * @param $str
     * @return bool
     */
    public static function isDateDb($str){
        $datArr = explode('-', $str);
        $answer = (
            preg_match("/^([0-9]{4}-[0-9]{1,2}-[0-9]{1,2})$/",$str)
            and checkdate($datArr[1], $datArr[2], $datArr[0])
        );
        return $answer;
    }

    /**
     * check datetime, like 2018-06-06 22:12:55
     * @param $str
     * @return bool
     */
    public static function isDatetime($str){
        $a = strtotime($str);
        return (boolean)$a;
    }

    /**
     * check date for database like 2018-06-06 and return date or false
     * @param $str
     * @return bool|false|string
     */
    public static function likeDate($str){
        $isDat = self::isDateDb($str);
        if($isDat){
            return date('Y-m-d', strtotime($str));
        }
        return false;
    }

    /**
     * check datetime
     * @param $str
     * @return false|string
     */
    public static function likeDatetime($str){
        $a = strtotime($str);
        return date('Y-m-d H:i:s', $a);
    }

    /**
     * reverse datetime from 06.06.2018 22:12:55 to 2018-06-06 22:12:55
     * reverse date from 06.06.2018 to 2018-06-06
     * @param $str
     * @return string
     */
    public static function reverseDatetime($str){
        if(!$str)
            return '';
        $answer = explode(' ', $str);
        $time = isset($answer[1]) ? ' ' . $answer[1] : '';
        return implode('-', array_reverse(explode('.', $answer[0]))) . $time;
    }

    /**
     * reverse db date to user date like 2018-06-06 to 06.06.2018
     * @param $str
     * @return string
     */
    public static function userDate($str){
        $answer = explode(' ', $str);
        return implode('.', array_reverse(explode('-', $answer[0])));
    }

    /**
     * reverse db datetime to user datetime like 2018-06-06 22:12:55 to 06.06.2018 22:12:55
     * @param $str
     * @return string
     */
    public static function userDatetime($str){
        $answer = explode(' ', $str);
        return self::userDate($answer[0]) . (isset($answer[1]) ? " $answer[1]" : '');
    }
}