<?php
namespace app\models;

use yii\db\ActiveRecord;
use yii;

class Browser extends ActiveRecord {
  public static function tableName()  {
    return "browser";
  }

  public function addByName(string $name){
    $sql = 'INSERT INTO `browser` (`name`) VALUES (:name) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)';
    yii::$app->db->createCommand($sql, [
      ':name' => $name,
    ])->execute();

    $id = yii::$app->db->getLastInsertID();
    return $id;
  }
}
