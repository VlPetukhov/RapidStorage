<?php
namespace common\components\RapidStorage\DataAdapters\Db\migrations;

use yii\db\Migration;

class m160813_162702_rapidStorage_mime_table_creation extends Migration
{
    protected $tableName = '{{%rs_mime}}';

    public function safeUp()
    {
        $tableOptions = null;

        if ('mysql' == $this->db->driverName) {
            $tableOptions = "CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB";
        }

        $this->createTable(
            $this->tableName,
            [
                'id'   => $this->primaryKey(),
                'mime' => $this->string()->unique()->notNull(),
            ],
            $tableOptions
        );

        return true;
    }

    public function safeDown()
    {
        if (YII_ENV_DEV || YII_ENV_TEST) {
            $this->dropTable($this->tableName);

            return true;
        }

        echo "m160813_162702_rapidStorage_mime_table_creation cannot be reverted.\n";

        return false;
    }
}
