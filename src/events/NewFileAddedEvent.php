<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\events;

use yii\base\Event;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;

class NewFileAddedEvent extends Event
{
    public FileEntity $fileEntity;
}
