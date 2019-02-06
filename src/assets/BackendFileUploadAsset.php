<?php
namespace DmitriiKoziuk\yii2FileManager\assets;

use yii\web\AssetBundle;

class BackendFileUploadAsset extends AssetBundle
{
    public $sourcePath = '@DmitriiKoziuk/yii2FileManager/web/backend';
    public $js = [
        'js/file-upload.js'
    ];
    public $depends = [
        'kartik\file\FileInputAsset',
    ];
}