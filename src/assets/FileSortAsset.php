<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\assets;

use yii\web\AssetBundle;

class FileSortAsset extends AssetBundle
{
    public $sourcePath = '@DmitriiKoziuk/yii2FileManager/web/backend';
    public $js = [
        'js/file-sort.js'
    ];
    public $depends = [
        'kartik\file\FileInputAsset',
    ];
}
