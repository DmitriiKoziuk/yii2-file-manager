<?php
namespace DmitriiKoziuk\yii2FileManager\forms;

use yii\base\Model;

class UploadFileForm extends Model
{
    const MAX_FILES = 20;

    public $upload;

    public function rules()
    {
        return [
            [['saveLocationAlias', 'entityName', 'entityId'], 'required'],
            [
                ['upload'],
                'file',
                'skipOnEmpty' => false,
                'maxFiles'    => static::MAX_FILES,
            ],
        ];
    }
}