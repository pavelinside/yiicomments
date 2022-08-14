<?php
use yii\db\Migration;

/**
 * Class m220814_082849_user
 */
class m220814_082849_user extends Migration {
  public function up()  {
    $tableOptions = null;
    if ($this->db->driverName === 'mysql') {
      // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
      $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
    }

    $this->createTable('{{%user}}', [
      'id' => $this->primaryKey()->unsigned(),
      'username' => $this->string()->notNull()->unique(),
      'auth_key' => $this->string(32)->notNull(),
      'password_hash' => $this->string()->notNull(),
      'password_reset_token' => $this->string()->unique(),
      'email' => $this->string()->notNull()->unique(),
      'status' => $this->smallInteger()->notNull()->defaultValue(10)
    ], $tableOptions);

    $this->insert('user', [
      'id' => '1',
      'username' => 'admin',
      'auth_key' => 'test100key',
      'password_hash' => '$2y$13$MN0EH8eXyvnLtYZA5T0pDugflzcaYYWpD7YRQsGIz4iNm8kiiq4B6', // admin
      'password_reset_token' => 'admin',
      'email' => 'superadmin@gmail.com'
    ]);

    $this->insert('user', [
      'id' => '2',
      'username' => 'demo',
      'auth_key' => 'test101key',
      'password_hash' => '$2y$13$rCejqslQ8c7olfs4TOz7hudK7aPbvYQP9hjIgT5y/Lqk.Cb8kSBDy', // demo
      'password_reset_token' => 'demo',
      'email' => 'demo@gmail.com'
    ]);
  }

  public function down()  {
    $this->dropTable('{{%user}}');
  }
}