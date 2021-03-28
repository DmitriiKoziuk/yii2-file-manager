<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\migrations;

use yii\db\Migration;

class m210328_143140_add_files_directory_column_to_dk_fm_entity_groups_table extends Migration
{
    private string $dkFmEntityGroupsTableName = '{{%dk_fm_entity_groups}}';

    public function safeUp()
    {
        $this->addColumn(
            $this->dkFmEntityGroupsTableName,
            'files_directory',
            $this->string(55)->null()->defaultValue(NULL)
        );
    }

    public function safeDown()
    {
        $this->dropColumn(
            $this->dkFmEntityGroupsTableName,
            'files_directory'
        );
    }
}
