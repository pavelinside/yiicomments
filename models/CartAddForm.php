<?php
namespace app\models;
use yii\base\Model;

class CartAddForm extends Model
{
    public $productId;
    public ?int $amount = null;

    public function rules(): array
    {
        return [
            [['productId', 'amount'], 'required'],
            [['amount'], 'integer', 'min' => 1],
        ];
    }
}
