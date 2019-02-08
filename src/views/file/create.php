<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\file\FileInput;
use DmitriiKoziuk\yii2FileManager\assets\BackendFileUploadAsset;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;

/* @var $this yii\web\View */

$this->title = Yii::t(FileManagerModule::ID, 'Upload files');
$this->params['breadcrumbs'][] = ['label' => Yii::t(FileManagerModule::ID, 'Files'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

BackendFileUploadAsset::register($this);
?>
<div class="file-create">
  <h1><?= Html::encode($this->title) ?></h1>

  <div class="row">
    <div class="col-md-4">
      <div class="form-group">
        <label for="location"><?= Yii::t(FileManagerModule::ID, 'Save location alias') ?></label>
          <?= Html::dropDownList(
              'save-location-alias',
              null,
              ['@frontend' => '@frontend', '@backend' => '@backend'],
              ['id' => 'save-location-alias', 'class' => 'form-control']
          ) ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12">
        <?= FileInput::widget([
            'id' => 'upload-file-form',
            'name' => "UploadFileForm[upload][]",
            'options'=>[
                'multiple'=> true
            ],
            'pluginOptions' => [
                'uploadAsync' => false,
                'initialPreview' => [],
                'initialPreviewAsData'=> true,
                'initialCaption'=> "",
                'initialPreviewConfig' => [],
                'uploadUrl' => Url::to(['/dkFileManager/file/upload']),
                'uploadExtraData' => [
                    'UploadFileData[saveLocationAlias]' => '',
                    'UploadFileData[entityName]' => 'fileManager',
                    'UploadFileData[entityId]' => 1,
                ],
                'overwriteInitial'=> false,
                'maxFileCount' => 20
            ]
        ]) ?>
    </div>
  </div>
</div>
