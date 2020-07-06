<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\migrations;

use yii\db\Migration;

class m200702_180649_create_dk_fm_files_table extends Migration
{
    private string $dkFmFilesTableName = '{{%dk_fm_files}}';
    private string $dkFmEntityGroupsTableName = '{{%dk_fm_entity_groups}}';
    private string $dkFmMimeTypesTableName = '{{%dk_fm_mime_types}}';

    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable($this->dkFmFilesTableName, [
            'id' => $this->primaryKey(),
            'entity_group_id' => $this->integer()->notNull(),
            'mime_type_id' => $this->integer()->notNull(),
            'specific_entity_id' => $this->integer()->notNull(),
            'location_alias' => $this->string(25)->notNull(),
            'name' => $this->string(155)->notNull(),
            'real_name' => $this->string(255)->notNull(),
            'size' => $this->integer()->notNull(),
            'sort' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);
        $this->createIndex(
            'dk_fm_files_idx_sort',
            $this->dkFmFilesTableName,
            [
                'entity_group_id',
                'specific_entity_id',
                'sort',
            ]
        );
        $this->createIndex(
            'dk_fm_files_idx_name',
            $this->dkFmFilesTableName,
            'name'
        );
        $this->createIndex(
            'dk_fm_files_idx_entity_group_id',
            $this->dkFmFilesTableName,
            'entity_group_id'
        );
        $this->createIndex(
            'dk_fm_files_idx_mime_type_id',
            $this->dkFmFilesTableName,
            'mime_type_id'
        );
        $this->addForeignKey(
            'dk_fm_files_fk_entity_group_id',
            $this->dkFmFilesTableName,
            'entity_group_id',
            $this->dkFmEntityGroupsTableName,
            'id',
            'RESTRICT',
            'CASCADE'
        );
        $this->addForeignKey(
            'dk_fm_files_fk_mime_type_id',
            $this->dkFmFilesTableName,
            'mime_type_id',
            $this->dkFmMimeTypesTableName,
            'id',
            'RESTRICT',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('dk_fm_files_fk_entity_group_id', $this->dkFmFilesTableName);
        $this->dropForeignKey('dk_fm_files_fk_mime_type_id', $this->dkFmFilesTableName);
        $this->dropTable($this->dkFmFilesTableName);
    }
}
