<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\migrations;

use yii\db\Migration;

class m200702_180421_create_dk_fm_mime_types_table extends Migration
{
    private string $dkFmMimeTypesTableName = '{{%dk_fm_mime_types}}';

    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable($this->dkFmMimeTypesTableName, [
            'id' => $this->primaryKey(),
            'type' => $this->string(45)->notNull(),
            'subtype' => $this->string(55)->notNull(),
        ], $tableOptions);
        $this->createIndex(
            'dk_fm_mime_types_uidx_type',
            $this->dkFmMimeTypesTableName,
            [
                'type',
                'subtype',
            ],
            true
        );
    }

    public function safeDown()
    {
        $this->dropTable($this->dkFmMimeTypesTableName);
    }
}
