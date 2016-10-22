<?php
namespace common\components\RapidStorage\DataAdapters\Db\migrations;

use yii\db\Migration;

class m160813_180252_rapidStorage_tables_indexes_creation extends Migration
{
    protected $fileRecordMimeIdFk = 'fk_file_record_mime_id';

    public function safeUp()
    {
        $this->addForeignKey(
            $this->fileRecordMimeIdFk,
            '{{%rs_file_record}}',
            '[[mime_id]]',
            '{{%rs_mime}}',
            '[[id]]',
            'SET NULL',
            'CASCADE'
        );

        return true;
    }

    public function safeDown()
    {
        if (YII_ENV_DEV || YII_ENV_TEST) {
            $this->dropForeignKey($this->fileRecordMimeIdFk, '{{%rs_file_record}}');

            return true;
        }

        echo "m160813_180252_rapidStorage_tables_indexes_creation cannot be reverted.\n";

        return false;
    }
}
