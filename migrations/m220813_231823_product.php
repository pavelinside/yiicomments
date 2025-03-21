<?php

use yii\db\Migration;

/**
 * Class m220813_231823_product
 */
class m220813_231823_product extends Migration {
    public function up() {
      $tableOptions = null;
      if ($this->db->driverName === 'mysql') {
        $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
      }

      $this->createTable('{{%product}}', [
        'id' => $this->primaryKey()->unsigned(),
        'title' => $this->string(200)->notNull(),
        'title_ru' => $this->string(200)->notNull(),
        'price' => $this->decimal(8, 2)->notNull(),
        'image' => $this->string(200)->notNull(),
        'description' => $this->string(1000)->notNull()

      ], $tableOptions);

      $this->insert('product', [
        'id' => '1',
        'title' => 'Fjallraven - Foldsack No. 1 Backpack, Fits 15 Laptops',
        'title_ru' => 'Рюкзак',
        'price' => '109.95',
        'image' => '81fPKd-2AYL._AC_SL1500_.jpg',
        'description' => 'Your perfect pack for everyday use and walks in the forest. Stash your laptop (up to 15 inches) in the padded sleeve, your everyday'
      ]);

      $this->insert('product', [
        'id' => '2',
        'title' => 'Mens Casual Premium Slim Fit T-Shirts',
        'title_ru' => 'Футболка',
        'price' => '22.30',
        'image' => '71-3HjGNDUL._AC_SY879._SX._UX._SY._UY_.jpg',
        'description' => 'Slim-fitting style, contrast raglan long sleeve, three-button henley placket, light weight & soft fabric for breathable and comfortable wearing. And Solid stitched shirts with round neck made for durability and a great fit for casual fashion wear and diehard baseball fans. The Henley style round neckline includes a three-button placket.'
      ]);

      $this->insert('product', [
        'id' => '3',
        'title' => 'Mens Cotton Jacket',
        'title_ru' => 'Куртка',
        'price' => '55.99',
        'image' => '71li-ujtlUL._AC_UX679_.jpg',
        'description' => 'great outerwear jackets for Spring/Autumn/Winter, suitable for many occasions, such as working, hiking, camping, mountain/rock climbing, cycling, traveling or other outdoors. Good gift choice for you or your family member. A warm hearted love to Father, husband or son in this thanksgiving or Christmas Day.'
      ]);
    }

    public function down() {
      $this->dropTable('product');
    }
}