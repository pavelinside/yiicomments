<?php

use yii\db\Migration;

/**
 * Class m220813_225353_browser
 */
class m220813_225353_browser extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%browser}}', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->text()->notNull()
        ], $tableOptions);

        $this->createIndex(
            'idx-browser-name',
            'browser',
            'name(255)',
            true
        );

        $this->insert('browser', [
            'id' => '1',
            'name' => ''
        ]);
    }

    public function down()
    {
        $this->dropTable('browser');
    }
}
