<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\exceptions\forms;

class SaveFileToDBFromNotValidException extends \Exception
{
    protected $message = 'Save file to db form not valid';
}
