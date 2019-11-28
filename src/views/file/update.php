<?php

use yii\helpers\Html;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;
use DmitriiKoziuk\yii2Base\BaseModule as BaseModule;

/* @var $this yii\web\View */
/* @var $model \DmitriiKoziuk\yii2FileManager\entities\FileEntity */

$this->title = Yii::t(FileManagerModule::ID, 'Update File: {name}', [
    'name' => $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t(FileManagerModule::ID, 'Files'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t(BaseModule::ID, 'Update');
?>
<div class="file-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
