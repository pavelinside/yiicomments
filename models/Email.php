<?php
namespace app\models;

use yii\db\ActiveRecord;
use yii;

class Email extends ActiveRecord {
  public static function tableName()  {
    return "email";
  }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string'],
        ];
    }

  public function addByName(string $name){
    $sql = 'INSERT INTO `email` (`name`) VALUES (:name) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)';
    yii::$app->db->createCommand($sql, [
      ':name' => $name,
    ])->execute();

    $id = yii::$app->db->getLastInsertID();
    return $id;
  }
}