<?php

use yii\db\Migration;

/**
 * Class m220813_231155_email
 */
class m220813_231155_email extends Migration {
  public function up()   {
    $tableOptions = null;
    if ($this->db->driverName === 'mysql') {
      $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
    }

    $this->createTable('{{%email}}', [
      'id' => $this->primaryKey()->unsigned(),
      'name' => $this->string(254)->notNull()->unique()
    ], $tableOptions);
  }

    public function down()  {
      $this->dropTable('email');
    }
}
