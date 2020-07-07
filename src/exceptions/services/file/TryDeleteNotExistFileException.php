<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\exceptions\services\file;

class TryDeleteNotExistFileException extends \Exception
{
    protected $message = 'Try delete not exist file';
}
