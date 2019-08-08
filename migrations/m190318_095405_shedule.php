<?php

use yii\db\Migration;

/**
 * Class m190318_095405_shedule
 */
class m190318_095405_shedule extends Migration
{
    /**
     * {@inheritdoc}
     */
    /*
    public function safeUp()
    {

    }
*/
    /**
     * {@inheritdoc}
     */
    /*
    public function safeDown()
    {
        echo "m190318_095405_shedule cannot be reverted.\n";

        return false;
    }
*/
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        $this->createTable('shedule', [
            'id'       => $this->primaryKey()->unique()->unsigned(),
            'command'  => $this->string(255)->notNull(),
            'interval' => $this->string(255),
            'last_run' => $this->dateTime(),
            'next_run' => $this->dateTime(),
            'created'  => $this->dateTime(),
            'updated'  => $this->dateTime(),
        ]);
    }

    public function down()
    {
        echo "m190318_095405_shedule cannot be reverted.\n";
        $this->dropTable('shedule');
        return true;
    }
}
