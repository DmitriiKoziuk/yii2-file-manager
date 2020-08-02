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
        $this->createTable($this->dkFmFilesTableName, [
            'id' => $this->primaryKey(),
            'entity_group_id' => $this->integer()->notNull(),
            'mime_type_id' => $this->integer()->notNull(),
            'specific_entity_id' => $this->integer()->notNull(),
            'location_alias' => $this->string(25)->notNull(),
            'directory' => $this->string(255)->notNull(),
            'name' => $this->string(255)->notNull(),
            'real_name' => $this->string(255)->notNull(),
            'size' => $this->integer()->notNull(),
            'sort' => $this->integer()->notNull(),
            'created_at' => $this->timestamp()->notNull()
                ->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->notNull()
                ->defaultExpression('CURRENT_TIMESTAMP')
                ->append('ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex(
            'dk_fm_files__idx__sort',
            $this->dkFmFilesTableName,
            [
                'entity_group_id',
                'specific_entity_id',
                'sort',
            ]
        );
        $this->createIndex(
            'dk_fm_files__idx__name',
            $this->dkFmFilesTableName,
            'name'
        );
        $this->createIndex(
            'dk_fm_files__idx__entity_group_id',
            $this->dkFmFilesTableName,
            'entity_group_id'
        );
        $this->createIndex(
            'dk_fm_files__idx__mime_type_id',
            $this->dkFmFilesTableName,
            'mime_type_id'
        );
        $this->addForeignKey(
            'dk_fm_files__fk__entity_group_id',
            $this->dkFmFilesTableName,
            'entity_group_id',
            $this->dkFmEntityGroupsTableName,
            'id',
            'RESTRICT',
            'CASCADE'
        );
        $this->addForeignKey(
            'dk_fm_files__fk__mime_type_id',
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
        $this->dropForeignKey('dk_fm_files__fk__entity_group_id', $this->dkFmFilesTableName);
        $this->dropForeignKey('dk_fm_files__fk__mime_type_id', $this->dkFmFilesTableName);
        $this->dropTable($this->dkFmFilesTableName);
    }
}
