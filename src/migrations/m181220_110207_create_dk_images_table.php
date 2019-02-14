<?php
namespace DmitriiKoziuk\yii2FileManager\migrations;

use yii\db\Migration;

/**
 * Handles the creation of table `dk_images`.
 */
class m181220_110207_create_dk_images_table extends Migration
{
    private $dkImagesTableName = '{{%dk_images}}';
    private $dkFilesTableName  = '{{%dk_files}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable($this->dkImagesTableName, [
            'file_id' => $this->primaryKey(),
            'width'   => $this->integer()->unsigned()->notNull(),
            'height'  => $this->integer()->unsigned()->notNull(),
            'alt'     => $this->string(255)->defaultValue(NULL)
        ], $tableOptions);

        $this->createIndex(
            'idx-dk_images-wh',
            $this->dkImagesTableName,
            [
                'width',
                'height',
            ]
        );

        $this->addForeignKey(
            'fk-dk_images-files_id',
            $this->dkImagesTableName,
            'file_id',
            $this->dkFilesTableName,
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-dk_images-files_id', $this->dkImagesTableName);
        $this->dropTable($this->dkImagesTableName);
    }
}
