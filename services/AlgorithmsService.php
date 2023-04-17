<?php
namespace app\services;

use http\Exception\InvalidArgumentException;
use phpDocumentor\Reflection\Types\Null_;

class AlgorithmsService {
    /**
     * Алгори́тм Евкли́да для нахождения наибольшего общего делителя двух целых чисел m и n
     * 1. [Вычитание или нахождение остатка] Вычесть из большего меньшее, получим r (Второй вариант получить остаток от деления)
     * 2. [Сравнение с нулем] Если r=0, выполнение прекращается; n наибольший общий делитель
     * 3. [Замещение] Присвоить m <- n, n <- r и вернуться к шагу 1.
     * @param int $a
     * @param int $b
     * @return int
     */
    public function get_greatest_common_divisor(int $a, int $b ) : int {
        $small = min($a, $b);
        $remainder = max($a, $b) % $small;
        return 0 == $remainder ? $small : $this->get_greatest_common_divisor( $small, $remainder );
    }

    /**
     * Сумма геометрической прогрессии a + ax + ... + ax^n = a1*( q^n - 1 ) / (q - 1)
     *      q <> 1 - Знаменатель; a - Первый элемент; n - Количество элементов
     * @param float $a
     * @param int $n
     * @param float $q
     * @return float
     */
    public function get_progression_geometric_sum(float $a, int $n, float $q) :float{
        if($q == 1){
            throw new InvalidArgumentException("q не должно быть равно 1");
        }

        return $a*( pow($q, $n) - 1 ) / ($q - 1);
    }

    /**
     * Сумма арифметической прогрессии (a1 + an)/2*n = (2*a1 + d*(n-1))/2*n
     *      d Шаг прогрессии; a - Первый элемент; n - Количество элементов
     * @param float $a
     * @param int $n
     * @param float $d
     * @return float
     */
    public function get_progression_arithmetic_sum(float $a, int $n, float $d) :float{
        return (2*$a + $d*($n - 1 )) / 2 * $n;
    }
}