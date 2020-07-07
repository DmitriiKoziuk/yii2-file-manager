<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\repositories;

use DmitriiKoziuk\yii2Base\repositories\AbstractActiveRecordRepository;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;

class FileRepository extends AbstractActiveRecordRepository
{
    public function getFileById(int $fileId): ?FileEntity
    {
        /** @var FileEntity|null $entity */
        $entity = FileEntity::find()->where([
            'id' => $fileId,
        ])->one();
        return $entity;
    }

    public function getFileNextSortIndex(int $entityGroupId, int $specificEntityId): int
    {
        return (1 + FileEntity::find()->where([
            'entity_group_id' => $entityGroupId,
            'specific_entity_id' => $specificEntityId,
        ])->count());
    }
}
