<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $title
 * @property string $title_ru
 * @property float $price
 * @property string $image
 * @property string $description
 */
class Product extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%product}}';
    }

    public function rules()
    {
        return [
            [['title', 'title_ru', 'price', 'image', 'description'], 'required'],
            [['title', 'title_ru', 'image'], 'string', 'max' => 200],
            [['description'], 'string', 'max' => 1000],
            [['price'], 'number', 'min' => 0],
            [['image'], 'file', 'extensions' => 'jpg, gif, jpeg, png', 'maxSize' => 2 * 1024 * 1024,
                'tooBig' => Yii::t('app', 'Maximum file size is 2MB'),
                'wrongExtension' => Yii::t('app', 'The file must be an image (JPG, PNG, GIF, JPEG)')
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'title' => Yii::t('app', 'Название'),
            'title_ru' => Yii::t('app', 'Название (рус)'),
            'price' => Yii::t('app', 'Цена'),
            'image' => Yii::t('app', 'Изображение'),
            'description' => Yii::t('app', 'Описание'),
        ];
    }
}
