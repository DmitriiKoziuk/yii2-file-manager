<?php

use yii\db\Migration;

/**
 * Handles the creation of table `dk_files`.
 */
class m181220_105233_create_dk_files_table extends Migration
{
    private $dkFilesTableName = '{{%dk_files}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable($this->dkFilesTableName, [
            'id'             => $this->primaryKey(),
            'entity_name'    => $this->string(45)->notNull(),
            'entity_id'      => $this->string(45)->notNull(),
            'location_alias' => $this->string(25)->notNull()
                ->comment('Yii alias: @frontend, @backend etc.'),
            'mime_type'      => $this->string(25)->notNull(),
            'name'           => $this->string(155)->notNull()
                ->comment('File name without extension.'),
            'extension'      => $this->string(10)->notNull(),
            'size'           => $this->integer()->unsigned()->notNull()
                ->comment('In bytes.'),
            'title'          => $this->string(255)->notNull(),
            'sort'           => $this->integer()->unsigned()->notNull(),
            'created_at'     => $this->integer()->unsigned()->notNull(),
            'updated_at'     => $this->integer()->unsigned()->notNull(),
        ], $tableOptions);

        $this->createIndex(
            'idx_dk_files_sort',
            $this->dkFilesTableName,
            [
                'entity_name',
                'entity_id',
                'sort',
            ],
            true
        );
        $this->createIndex(
            'idx_dk_files_name',
            $this->dkFilesTableName,
            [
                'name',
                'extension',
            ]
        );

        $this->createIndex(
            'idx_dk_files_entity_type',
            $this->dkFilesTableName,
            [
                'entity_name',
                'entity_id',
                'mime_type'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable($this->dkFilesTableName);
    }
}
