<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\widgets;

use Exception;
use InvalidArgumentException;
use yii\base\Widget;
use yii\helpers\Url;
use kartik\file\FileInput;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;
use DmitriiKoziuk\yii2FileManager\assets\FileSortAsset;

class FileInputWidget extends Widget
{
    public string $fileUploadArrayName;
    public string $moduleName;
    public string $entityName;
    public string $entityId;
    public string $saveLocationAlias = '@frontend';
    public int    $maxFileCount = 20;
    public array  $initialPreview = [];
    public array  $initialPreviewConfig = [];

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
            'name' => "{$this->fileUploadArrayName}[]",
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
                'uploadUrl' => urldecode(Url::to(['/' . FileManagerModule::getId() . '/file/upload'])),
                'uploadExtraData' => [
                    'FileUploadForm[locationAlias]' => $this->saveLocationAlias,
                    'FileUploadForm[moduleName]' => $this->moduleName,
                    'FileUploadForm[entityName]' => $this->entityName,
                    'FileUploadForm[specificEntityId]' => $this->entityId,
                ],
                'overwriteInitial'=> false,
                'maxFileCount' => $this->maxFileCount,
            ]
        ]);
    }
}
