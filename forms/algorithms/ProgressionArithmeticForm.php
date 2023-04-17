<?php
namespace app\forms\algorithms;

use yii\base\Model;

/**
 * Сумма геометрической прогрессии
 * @property int $a // Первый элемент
 * @property int $n // Количество элементов
 * @property float $d // Шаг прогрессии
 */
class ProgressionArithmeticForm extends Model {
    public ?float $a = null;
    public ?float $d = null;
    public ?int $n = null;

    public function rules() : array  {
        return [
            [['a', 'n', 'd'], 'required'],
            [['a', 'd'], 'number'],
            ['n', 'integer', 'min' => 1]
        ];
    }
}