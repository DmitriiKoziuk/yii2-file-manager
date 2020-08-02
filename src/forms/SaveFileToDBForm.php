<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\forms;

use yii\base\Model;
use DmitriiKoziuk\yii2FileManager\interfaces\FileInterface;

class SaveFileToDBForm extends Model implements FileInterface
{
    public ?string $file = null;
    public ?string $locationAlias = null;
    public ?string $moduleName = null;
    public ?string $entityName = null;
    public ?string $directory = null;
    public ?string $name = null;
    public ?string $real_name = null;
    public ?int $specificEntityId = null;

    public function rules()
    {
        return [
            [
                [
                    'file', 'locationAlias', 'moduleName', 'entityName',
                    'directory', 'name', 'real_name', 'specificEntityId'
                ],
                'required'
            ],
            [['file'], 'string'],
            [['locationAlias'], 'string', 'max' => 25],
            [['moduleName'], 'string', 'max' => 45],
            [['entityName'], 'string', 'max' => 55],
            [['directory', 'name', 'real_name'], 'string', 'max' => 255],
            [['specificEntityId'], 'integer']
        ];
    }

    public function getLocationAlias(): string
    {
        return $this->locationAlias;
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getSpecificEntityID(): int
    {
        return $this->specificEntityId;
    }
}
