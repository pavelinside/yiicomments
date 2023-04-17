<?php
namespace app\forms\algorithms;

use yii\base\Model;

/**
 * Алгори́тм Евкли́да для нахождения наибольшего общего делителя двух целых чисел m и n
 *
 * @property int $number1
 * @property int $number2
 */
class EuclidForm extends Model {
    public ?int $number1 = null;
    public ?int $number2 = null;

    public function rules()  {
        return [
            [['number1', 'number2'], 'required'],
            [['number1', 'number2'], 'integer', 'min' => 1]
        ];
    }
}