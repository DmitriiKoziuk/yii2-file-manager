<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\exceptions\services\file;

class FileNameAlreadyExistException extends \Exception
{
    protected $message = 'File name already exist';
}
