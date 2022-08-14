<?php

use yii\db\Migration;

/**
 * Class m220813_234033_comment
 */
class m220813_234033_comment extends Migration{
  public function up() {
    $tableOptions = null;
    if ($this->db->driverName === 'mysql') {
      $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
    }

    $this->createTable('{{%comment}}', [
      'id' => $this->primaryKey()->unsigned(),
      'productid' => $this->integer()->notNull()->unsigned(),
      'name' => $this->string(100)->notNull(),
      'emailid' => $this->integer()->notNull()->unsigned(),
      'comment' => $this->string(1000)->notNull(),
      'rating' => $this->integer(4)->notNull()->unsigned(),
      'image' => $this->string(255),
      'advantage' => $this->string(1000),
      'flaws' => $this->string(1000),
      'ip' => $this->integer()->unsigned(),
      'browserid' => $this->integer()->unsigned(),
      'created' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
    ], $tableOptions);

    $this->createIndex('idx-comment-productid', '{{%comment}}', 'productid');
    $this->createIndex('idx-comment-emailid', '{{%comment}}', 'emailid');
    $this->createIndex('idx-comment-browserid', '{{%comment}}', 'browserid');

    $this->addForeignKey('fk-comment-productid', '{{%comment}}', 'productid', '{{%product}}', 'id', 'CASCADE', 'RESTRICT');
    $this->addForeignKey('fk-comment-emailid', '{{%comment}}', 'emailid', '{{%email}}', 'id', 'CASCADE', 'RESTRICT');
    $this->addForeignKey('fk-comment-browserid', '{{%comment}}', 'browserid', '{{%browser}}', 'id', 'CASCADE', 'RESTRICT');
  }

  public function down() {
    $this->dropTable('comment');
  }
}
