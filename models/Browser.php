<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii;
use yii\db\Exception;

class Browser extends ActiveRecord
{
    public static function tableName(): string
    {
        return "browser";
    }

    /**
     * @throws Exception
     */
    public function addByName(string $name): string
    {
        $sql = 'INSERT INTO `browser` (`name`) VALUES (:name) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)';
        yii::$app->db->createCommand($sql, [
            ':name' => $name,
        ])->execute();

        return yii::$app->db->getLastInsertID();
    }
}
