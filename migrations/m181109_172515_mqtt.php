<?php

use yii\db\Migration;

/**
 * Class m181109_172515_mqtt
 */
class m181109_172515_mqtt extends Migration
{
//    /**
//     * {@inheritdoc}
//     */
//    public function safeUp()
//    {
//
//    }
//
//    /**
//     * {@inheritdoc}
//     */
//    public function safeDown()
//    {
//        echo "m181109_172515_mqtt cannot be reverted.\n";
//
//        return false;
//    }


    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        $this->createTable('mqtt', [
            'id' => $this->primaryKey()->unique()->unsigned(),
            'topic' => $this->string(35)->notNull(),
            'payload' => $this->string(20)->notNull(),
            'datetime' => $this->dateTime()->notNull(),
        ]);
    }

    public function down()
    {
        echo "m181109_172515_mqtt cannot be reverted.\n";
        $this->dropTable('mqtt');
        return true;
    }

}
