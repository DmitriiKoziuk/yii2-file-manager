<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\exceptions\forms;

use Exception;

class AddFileFormNotValidException extends Exception
{
    protected $message = 'Form not valid';
}