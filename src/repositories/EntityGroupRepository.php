<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\repositories;

use DmitriiKoziuk\yii2Base\repositories\AbstractActiveRecordRepository;
use DmitriiKoziuk\yii2FileManager\entities\GroupEntity;

class EntityGroupRepository extends AbstractActiveRecordRepository
{
    public function getEntityGroup(string $moduleName, string $entityName): ?GroupEntity
    {
        /** @var GroupEntity|null $e */
        $e = GroupEntity::find()->where([
            'module_name' => $moduleName,
            'entity_name' => $entityName,
        ])->one();
        return $e;
    }
}
