<?php

use yii\db\Migration;

/**
 * Class m220814_083407_token
 */
class m220814_083407_token extends Migration {
  public function up() {
    $tableOptions = null;
    if ($this->db->driverName === 'mysql') {
      $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
    }

    $this->createTable('{{%token}}', [
      'id' => $this->primaryKey(),
      'userid' => $this->integer()->notNull()->unsigned(),
      'token' => $this->string()->notNull()->unique(),
      'expired_at' => $this->integer()->notNull(),
    ], $tableOptions);

    $this->createIndex('idx-token-userid', '{{%token}}', 'userid');

    $this->addForeignKey('fk-token-userid', '{{%token}}', 'userid', '{{%user}}', 'id', 'CASCADE', 'RESTRICT');
  }

  public function down() {
    $this->dropTable('{{%token}}');
  }
}
