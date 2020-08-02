<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\migrations;

use yii\db\Migration;

class m200702_180046_create_dk_fm_entity_groups_table extends Migration
{
    private string $dkFmEntityGroupsTableName = '{{%dk_fm_entity_groups}}';

    public function safeUp()
    {
        $this->createTable($this->dkFmEntityGroupsTableName, [
            'id' => $this->primaryKey(),
            'module_name' => $this->string(45)->notNull(),
            'entity_name' => $this->string(55)->notNull(),
        ]);
        $this->createIndex(
            'dk_fm_entity_group__uidx__module_entity',
            $this->dkFmEntityGroupsTableName,
            [
                'module_name',
                'entity_name',
            ],
            true
        );
    }

    public function safeDown()
    {
        $this->dropTable($this->dkFmEntityGroupsTableName);
    }
}
