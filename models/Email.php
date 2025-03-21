<?php
namespace app\models;

use yii\db\ActiveRecord;
use yii;
use yii\db\Exception;

/**
 * @property int $id
 * @property string $name
 */
class Email extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%email}}';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 254],
            [['name'], 'unique', 'message' => Yii::t('app', 'This email is already taken.')],
            [['name'], 'email', 'message' => Yii::t('app', 'Invalid email format.')],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Email'),
        ];
    }

    /**
     * @throws Exception
     */
    public function addByName(string $name): string
    {
        $sql = 'INSERT INTO `email` (`name`) VALUES (:name) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)';
        yii::$app->db->createCommand($sql, [
            ':name' => $name,
        ])->execute();

        return yii::$app->db->getLastInsertID();
    }
}