<?php
namespace DmitriiKoziuk\yii2FileManager\data;

use DmitriiKoziuk\yii2Base\forms\Form;

class UploadFileData extends Form
{
    public $saveLocationAlias;
    public $entityName;
    public $entityId;
    public $name;
    public $maxUploadFiles = 20;

    public function rules()
    {
        return [
            [['saveLocationAlias', 'entityName', 'entityId'], 'required'],
            [['entityName', 'entityId'], 'string', 'max' => 45],
            [['saveLocationAlias'], 'string', 'max' => 25],
            [['name'], 'string', 'max' => 165],
        ];
    }
}