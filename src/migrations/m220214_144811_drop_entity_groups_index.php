<?php

declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\migrations;

use yii\db\Migration;

class m220214_144811_drop_entity_groups_index extends Migration
{
    private string $dkFmEntityGroupsTableName = '{{%dk_fm_entity_groups}}';

    public function safeUp()
    {
        $this->dropIndex('dk_fm_entity_group__uidx__module_entity', $this->dkFmEntityGroupsTableName);
    }

    public function safeDown()
    {
        echo "m220214_144811_drop_entity_groups_index cannot be reverted.\n";

        return false;
    }
}
