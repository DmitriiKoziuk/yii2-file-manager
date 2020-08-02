<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\migrations;

use yii\db\Migration;

class m200703_171302_create_dk_fm_images_table extends Migration
{
    private string $dkFmImagesTableName = '{{%dk_fm_images}}';
    private string $dkFmFilesTableName = '{{%dk_fm_files}}';

    public function safeUp()
    {
        $this->createTable($this->dkFmImagesTableName, [
            'file_id' => $this->integer()->notNull(),
            'width' => $this->smallInteger()->unsigned()->notNull(),
            'height' => $this->smallInteger()->unsigned()->notNull(),
            'orientation' => "ENUM('square', 'landscape', 'portrait') NOT NULL",
        ]);
        $this->addPrimaryKey(
            'primary_key',
            $this->dkFmImagesTableName,
            'file_id'
        );
        $this->addForeignKey(
            'dk_fm_images__fk__file_id',
            $this->dkFmImagesTableName,
            'file_id',
            $this->dkFmFilesTableName,
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('dk_fm_images__fk__file_id', $this->dkFmImagesTableName);
        $this->dropTable($this->dkFmImagesTableName);
    }
}
