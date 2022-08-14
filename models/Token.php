<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $userid
 * @property integer $expired_at
 * @property string $token
 */
class Token extends ActiveRecord {
  public static function tableName() {
    return '{{%token}}';
  }

  public function generateToken($expire) {
    $this->expired_at = $expire;
    $this->token = \Yii::$app->security->generateRandomString();
  }

  public function fields() {
    return [
      'token' => 'token',
      'expired' => function () {
        return date(DATE_RFC3339, $this->expired_at);
      },
    ];
  }
}