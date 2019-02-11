<?php
namespace DmitriiKoziuk\yii2FileManager\repositories;

use DmitriiKoziuk\yii2Base\repositories\AbstractActiveRecordRepository;
use DmitriiKoziuk\yii2FileManager\entities\File;

class FileRepository extends AbstractActiveRecordRepository
{
    /**
     * @param string $entityName
     * @param string $entityId
     * @return File[]
     */
    public function getEntityAllFiles(string $entityName, string $entityId): array
    {
        return File::find()
            ->with(['image'])
            ->where([
                File::tableName() . '.entity_name' => $entityName,
                File::tableName() . '.entity_id' => $entityId,
            ])
            ->all();
    }

    /**
     * @param string $entityName
     * @param string $entityId
     * @return File[]
     */
    public function getEntityImages(string $entityName, string $entityId): array
    {
        $files = File::find()
            ->from([File::tableName() . ' FORCE INDEX (idx_dk_files_entity_type)'])
            ->with(['image'])
            ->where([
                File::tableName() . '.entity_name' => $entityName,
                File::tableName() . '.entity_id' => $entityId,
            ])
            ->indexBy('sort');
        return $files->andWhere(['like', File::tableName() . '.mime_type', 'image%', false])->all();
    }
}