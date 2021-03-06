<?php

use yii\helpers\Html;
use DmitriiKoziuk\yii2FileManager\assets\MainBackendFileUploadAsset;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;
use DmitriiKoziuk\yii2FileManager\widgets\FileInputWidget;
use DmitriiKoziuk\yii2FileManager\helpers\FileWebHelper;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;

/**
 * @var $this yii\web\View
 * @var FileWebHelper $fileWebHelper
 * @var FileEntity[] $files
 */

$this->title = Yii::t(FileManagerModule::ID, 'Upload files');
$this->params['breadcrumbs'][] = ['label' => Yii::t(FileManagerModule::ID, 'Files'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

MainBackendFileUploadAsset::register($this);
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
        <?= FileInputWidget::widget([
            'entityName' => FileManagerModule::getId(),
            'entityId' => '1',
            'initialPreview' => $fileWebHelper
                ->getFileInputInitialPreview($files),
            'initialPreviewConfig' => $fileWebHelper
                ->getFileInputInitialPreviewConfig($files),
        ]) ?>
    </div>
  </div>
</div>
