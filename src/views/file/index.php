<?php

use yii\web\View;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;
use DmitriiKoziuk\yii2FileManager\services\SettingsService;
use DmitriiKoziuk\yii2FileManager\services\FileSearchService;

/**
 * @var $this         View
 * @var $searchModel  FileSearchService
 * @var $dataProvider ActiveDataProvider
 * @var $settings     SettingsService
 */

$this->title = Yii::t(FileManagerModule::ID, 'Files');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="file-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t(FileManagerModule::ID, 'Upload files'), ['upload'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            [
                'attribute' => 'module_name',
                'content' => function (FileEntity $model) {
                    return $model->entityGroup->module_name;
                },
            ],
            [
                'attribute' => 'entity_name',
                'content' => function (FileEntity $model) {
                    return $model->entityGroup->entity_name;
                },
            ],
            'specific_entity_id',
            'location_alias',
            [
                'attribute' => 'mime_type',
                'content' => function (FileEntity $model) {
                    return "{$model->mimeType->type} / {$model->mimeType->subtype}";
                },
            ],
            'name',
            'real_name',
            [
                'attribute' => 'preview',
                'content' => function (FileEntity $model) use ($settings) {
                    if ($model->isImage()) {
                        $url = FileEntity::FRONTEND_LOCATION_ALIAS == $model->location_alias ?
                            $settings->getFrontendDomain() . $model->getUrl() :
                            $model->getUrl();
                        return Html::tag('img', '', [
                            'src' => $url,
                            'style' => 'max-width: 150px; max-height: 150px;',
                        ]);

                    }
                    return '';
                }
            ],
            'size',
            'sort',
            'created_at:datetime',
            'updated_at:datetime',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
