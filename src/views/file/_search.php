<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use DmitriiKoziuk\yii2Base\BaseModule as BaseModule;

/* @var $this yii\web\View */
/* @var $model DmitriiKoziuk\yii2FileManager\services\FileSearchService */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="file-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'entity_name') ?>

    <?= $form->field($model, 'entity_id') ?>

    <?= $form->field($model, 'location') ?>

    <?= $form->field($model, 'web_path') ?>

    <?php // echo $form->field($model, 'name') ?>

    <?php // echo $form->field($model, 'extension') ?>

    <?php // echo $form->field($model, 'size') ?>

    <?php // echo $form->field($model, 'title') ?>

    <?php // echo $form->field($model, 'sort') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t(BaseModule::ID, 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t(BaseModule::ID, 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
