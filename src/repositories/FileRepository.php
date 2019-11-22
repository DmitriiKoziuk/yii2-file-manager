<?php
namespace DmitriiKoziuk\yii2FileManager\repositories;

use Yii;
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
            ->orderBy('sort')
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

    public function getFileById(int $id): ?File
    {
        /** @var File|null $file */
        $file = File::find()
            ->where(['id' => $id])
            ->one();
        return $file;
    }

    public function increaseFileSortByOne(
        string $entityName,
        int $fromSort
    ) {
        Yii::$app->db
            ->createCommand("UPDATE `dk_files` SET `sort`=`sort`+1 WHERE (`entity_name`='{$entityName}') AND (`sort` >= {$fromSort}) ORDER BY `sort` DESC ")
            ->execute();
    }

    public function moveFileToEnd(File $file)
    {
        /** @var File $lastFile */
        $lastFile = File::find()
            ->where(['entity_name' => $file->entity_name])
            ->orderBy(['sort' => SORT_DESC])
            ->one();
        if (! empty($lastFile)) {
            $lastSort = $lastFile->sort++;
            /** @var File $movedFile */
            File::updateAllCounters(['sort' => $lastSort], ['id' => $file->id]);
            Yii::$app->db
                ->createCommand("UPDATE `dk_files` SET `sort`=`sort`-1 WHERE (`entity_name`='{$file->entity_name}') AND (`sort` >= {$file->sort}) ORDER BY `sort` ASC ")
                ->execute();
        }
    }
}
