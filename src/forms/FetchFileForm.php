<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\forms;

use yii\base\Model;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;
use DmitriiKoziuk\yii2FileManager\interfaces\SaveFileInterface;

class FetchFileForm extends Model implements SaveFileInterface
{
    public $source;
    public $saveLocationAlias = FileEntity::FRONTEND_LOCATION_ALIAS;
    public $entityName;
    public $entityId;
    public $newFileName;
    public $overwriteExistFile = false;
    public $optimizeFileName = true;

    public function rules()
    {
        return [
            [
                [
                    'source',
                    'saveLocationAlias',
                    'entityName',
                    'entityId',
                    'overwriteExistFile',
                    'optimizeFileName'
                ],
                'required'
            ],
            [['source'], 'string'],
            [['entityName', 'entityId'], 'string', 'max' => 45],
            [['saveLocationAlias'], 'string', 'max' => 25],
            [['newFileName'], 'string', 'max' => 155],
            [['overwriteExistFile', 'optimizeFileName'], 'boolean'],
        ];
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getSaveLocationAlias(): string
    {
        return $this->saveLocationAlias;
    }

    public function isRenameFile(): bool
    {
        return ! empty($this->newFileName);
    }

    public function getNewFileName(): string
    {
        return $this->newFileName;
    }

    public function isOverwriteExistFile(): bool
    {
        return $this->overwriteExistFile;
    }

    public function isOptimizeFileName(): bool
    {
        return $this->optimizeFileName;
    }
}
