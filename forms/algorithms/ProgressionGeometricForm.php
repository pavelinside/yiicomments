<?php
namespace app\forms\algorithms;

use yii\base\Model;

/**
 * Сумма геометрической прогрессии
 * @property float $a // Первый элемент
 * @property int $n // Количество элементов
 * @property float $q // Знаменатель, q <> 1
 */
class ProgressionGeometricForm extends Model {
    public ?float $a = null;
    public ?float $q = null;
    public ?int $n = null;

    public function rules() : array  {
        return [
            [['a', 'n', 'q'], 'required'],
            [['a', 'q'], 'number'],
            ['n', 'integer', 'min' => 1],
            ['q', 'compare', 'compareValue' => 1, 'operator' => '!=', 'type' => 'number']
        ];
    }
}