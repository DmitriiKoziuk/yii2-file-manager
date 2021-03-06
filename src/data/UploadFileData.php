<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\data;

use DmitriiKoziuk\yii2Base\forms\Form;
use DmitriiKoziuk\yii2FileManager\interfaces\SaveFileInterface;

class UploadFileData extends Form implements SaveFileInterface
{
    public $saveLocationAlias;
    public $entityName;
    public $entityId;
    public $name;
    public $maxUploadFiles = 20;
    public $overwriteExistFile = false;
    public $optimizeFileName = true;

    public function rules()
    {
        return [
            [
                [
                    'saveLocationAlias',
                    'entityName',
                    'entityId',
                    'maxUploadFiles',
                    'overwriteExistFile',
                    'optimizeFileName'
                ],
                'required'
            ],
            [['entityName', 'entityId'], 'string', 'max' => 45],
            [['saveLocationAlias'], 'string', 'max' => 25],
            [['name'], 'string', 'max' => 165],
            [['maxUploadFiles'], 'integer'],
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
        return ! empty($this->name) && 'null' != $this->name;
    }

    public function getNewFileName(): string
    {
        return $this->name;
    }

    public function getMaxUploadFiles(): int
    {
        return $this->maxUploadFiles;
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
