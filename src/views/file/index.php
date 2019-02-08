<?php

use yii\helpers\Html;
use yii\grid\GridView;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;

/**
 * @var $this           yii\web\View
 * @var $searchModel    DmitriiKoziuk\yii2FileManager\services\FileSearchService
 * @var $dataProvider   yii\data\ActiveDataProvider
 * @var $fileWebHelper \DmitriiKoziuk\yii2FileManager\helpers\FileWebHelper
 */

$this->title = Yii::t(FileManagerModule::ID, 'Files');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="file-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t(FileManagerModule::ID, 'Upload files'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'entity_name',
            'entity_id',
            'location_alias',
            'mime_type',
            'name',
            'extension',
            [
                'attribute' => 'preview',
                'content' => function ($model) use ($fileWebHelper) {
                    /** @var \DmitriiKoziuk\yii2FileManager\entities\File $model */
                    if (empty($model->image)) {
                        return '';
                    } else {
                        return Html::tag('img', '', [
                            'src' => $fileWebHelper->getFileFullWebPath($model),
                            'style' => 'max-width: 150px; max-height: 150px;',
                        ]);
                    }
                }
            ],
            'size',
            'title',
            'sort',
            'created_at:datetime',
            'updated_at:datetime',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
