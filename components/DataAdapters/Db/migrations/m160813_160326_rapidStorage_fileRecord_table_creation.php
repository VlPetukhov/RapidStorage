<?php
namespace common\components\RapidStorage\DataAdapters\Db\migrations;

use yii\db\Migration;

class m160813_160326_rapidStorage_fileRecord_table_creation extends Migration
{
    protected $tableName = '{{%rs_file_record}}';

    public function safeUp()
    {
        $tableOptions = null;

        if ('mysql' == $this->db->driverName) {
            $tableOptions = "CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB";
        }

        $this->createTable(
            $this->tableName,
            [
                'id'             => $this->primaryKey(),
                'name'           => $this->string()->notNull(),
                'mime_id'        => $this->integer(),
                'size'           => $this->integer()->notNull(),
                'is_private'     => $this->boolean()->notNull()->defaultValue(false),
                'created_at'     => $this->integer()->notNull(),
                'updated_at'     => $this->integer(),
                'delete_at_time' => $this->integer(),
                'status'         => $this->smallInteger()->notNull(),
                'status_time'    => $this->integer(),
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

        echo "m160813_160326_rapidStorage_fileRecord_table_creation cannot be reverted.\n";

        return false;
    }
}
