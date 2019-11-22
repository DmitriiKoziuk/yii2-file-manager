<?php
namespace DmitriiKoziuk\yii2FileManager\assets;

use yii\web\AssetBundle;

class MainBackendFileUploadAsset extends AssetBundle
{
    public $sourcePath = '@DmitriiKoziuk/yii2FileManager/web/backend';
    public $js = [
        'js/main-file-upload.js'
    ];
    public $depends = [
        'kartik\file\FileInputAsset',
    ];
}
