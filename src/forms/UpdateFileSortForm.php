<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\forms;

use yii\base\Model;

class UpdateFileSortForm extends Model
{
    public $fileId;

    public $newSort;

    public function rules()
    {
        return [
            [['fileId', 'newSort'], 'integer']
        ];
    }
}
