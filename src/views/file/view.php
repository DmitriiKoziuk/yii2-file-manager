<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;
use DmitriiKoziuk\yii2Base\BaseModule as BaseModule;

/* @var $this yii\web\View */
/* @var $model \DmitriiKoziuk\yii2FileManager\entities\FileEntity */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t(FileManagerModule::ID, 'Files'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="file-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t(BaseModule::ID, 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t(BaseModule::ID, 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t(BaseModule::ID, 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'entity_name',
            'entity_id',
            'location_alias',
            'mime_type',
            'name',
            'extension',
            'size',
            'title',
            'sort',
            'created_at:datetime',
            'updated_at:datetime',
        ],
    ]) ?>

</div>
