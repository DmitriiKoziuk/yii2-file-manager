<?php
namespace DmitriiKoziuk\yii2FileManager\repositories;

use Yii;
use DmitriiKoziuk\yii2Base\repositories\AbstractActiveRecordRepository;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;

class FileRepository extends AbstractActiveRecordRepository
{
    /**
     * @param string $entityName
     * @param string $entityId
     * @return FileEntity[]
     */
    public function getEntityAllFiles(string $entityName, string $entityId): array
    {
        return FileEntity::find()
            ->with(['image'])
            ->where([
                FileEntity::tableName() . '.entity_name' => $entityName,
                FileEntity::tableName() . '.entity_id' => $entityId,
            ])
            ->orderBy('sort')
            ->all();
    }

    /**
     * @param string $entityName
     * @param string $entityId
     * @return FileEntity[]
     */
    public function getEntityImages(string $entityName, string $entityId): array
    {
        $files = FileEntity::find()
            ->from([FileEntity::tableName() . ' FORCE INDEX (idx_dk_files_entity_type)'])
            ->with(['image'])
            ->where([
                FileEntity::tableName() . '.entity_name' => $entityName,
                FileEntity::tableName() . '.entity_id' => $entityId,
            ])
            ->orderBy('sort')
            ->indexBy('sort');
        return $files->andWhere(['like', FileEntity::tableName() . '.mime_type', 'image%', false])->all();
    }

    public function getFileById(int $id): ?FileEntity
    {
        /** @var FileEntity|null $file */
        $file = FileEntity::find()
            ->where(['id' => $id])
            ->one();
        return $file;
    }

    public function increaseFileSortByOne(
        string $entityName,
        string $entityId,
        int $fromSort
    ): int {
        $tableName = FileEntity::getTableSchema()->name;
        $sql = <<<SQL
        UPDATE `{$tableName}`
        SET `{$tableName}`.`sort`=`{$tableName}`.`sort`+1
        WHERE (`{$tableName}`.`entity_name`='{$entityName}')
            AND (`{$tableName}`.`entity_id`='{$entityId}') AND (`{$tableName}`.`sort` >= {$fromSort})
        ORDER BY `{$tableName}`.`sort` DESC
        SQL;
        return Yii::$app->db->createCommand($sql)->execute();
    }

    public function decreaseFileSortByOne(
        string $entityName,
        string $entityId,
        int $fromSort
    ): int {
        $tableName = FileEntity::getTableSchema()->name;
        $sql = <<<SQL
        UPDATE `{$tableName}`
        SET `{$tableName}`.`sort`=`{$tableName}`.`sort`-1
        WHERE (`{$tableName}`.`entity_name`='{$entityName}')
            AND (`{$tableName}`.`entity_id`='{$entityId}') AND (`{$tableName}`.`sort` >= {$fromSort}) 
        ORDER BY `{$tableName}`.`sort` ASC
        SQL;
        return Yii::$app->db->createCommand($sql)->execute();
    }

    public function moveFileToEnd(FileEntity $file): int
    {
        /** @var FileEntity $lastFile */
        $lastFile = FileEntity::find()
            ->where(['entity_name' => $file->entity_name])
            ->orderBy(['sort' => SORT_DESC])
            ->one();
        if (! empty($lastFile) && $lastFile->id != $file->id) {
            $lastSort = $lastFile->sort++;
            /** @var FileEntity $movedFile */
            FileEntity::updateAllCounters(['sort' => $lastSort], ['id' => $file->id]);
            return $this->decreaseFileSortByOne($file->entity_name, $file->entity_id, $file->sort);
        }
        return 0;
    }

    public function defineNextSortNumber(string $entityName, int $entityID): int
    {
        $count = (int) FileEntity::find()->where([
            'entity_name' => $entityName,
            'entity_id'   => $entityID,
        ])->count();
        return ++$count;
    }
}
