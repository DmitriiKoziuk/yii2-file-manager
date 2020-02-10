<?php

namespace DmitriiKoziuk\yii2FileManager\widgets;

use Exception;
use InvalidArgumentException;
use yii\base\Widget;
use yii\helpers\Url;
use kartik\file\FileInput;
use DmitriiKoziuk\yii2FileManager\assets\FileSortAsset;

class FileInputWidget extends Widget
{
    public $entityName;
    public $entityId;
    public $fileName;
    public $saveLocationAlias = '@frontend';
    public $maxFileCount = 20;
    public $initialPreview = [];
    public $initialPreviewConfig = [];

    public function init()
    {
        parent::init();
        if (empty($this->entityName)) {
            throw new InvalidArgumentException("Property 'entityName' not set.");
        }
        if (empty($this->entityName)) {
            throw new InvalidArgumentException("Property 'entityId' not set.");
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public function run()
    {
        $this->view->registerAssetBundle(FileSortAsset::class);
        return FileInput::widget([
            'name' => "UploadFileForm[upload][]",
            'options'=>[
                'multiple'=> true,
                'class' => 'upload-file-form',
            ],
            'pluginOptions' => [
                'uploadAsync' => false,
                'initialPreview' => $this->initialPreview,
                'initialPreviewAsData'=> true,
                'initialCaption'=> "",
                'initialPreviewConfig' => $this->initialPreviewConfig,
                'uploadUrl' => urldecode(Url::to(['file/upload'])),
                'uploadExtraData' => [
                    'UploadFileData[saveLocationAlias]' => $this->saveLocationAlias,
                    'UploadFileData[entityName]' => $this->entityName,
                    'UploadFileData[entityId]' => $this->entityId,
                    'UploadFileData[name]' => $this->fileName,
                ],
                'overwriteInitial'=> false,
                'maxFileCount' => $this->maxFileCount,
            ]
        ]);
    }
}
