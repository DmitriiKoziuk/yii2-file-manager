<?php
namespace DmitriiKoziuk\yii2FileManager\forms;

class UploadImageForm extends UploadFileForm
{
    public function rules()
    {
        return [
            [
                ['upload'],
                'file',
                'mimeTypes'   => ['image/jpeg', 'image/pjpeg', 'image/png', 'image/webp'],
                'skipOnEmpty' => false,
                'maxFiles'    => static::MAX_FILES,
            ],
        ];
    }
}